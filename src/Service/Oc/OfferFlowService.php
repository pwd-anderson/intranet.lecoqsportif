<?php

namespace App\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferFlow;
use App\Entity\Oc\OfferLine;
use App\Service\Webservice\WebServiceDispatcher;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Flux XML vers X3 d'une OC validée : un WSCRESPL (création du tarif spécial) par ARTICLE ENFANT (variant) de chaque
 * ligne d'OC (saisie par article parent), précédé d'un WSUPD7TSPE (clôture la veille) pour chaque tarif spécial déjà en place
 * du client sur ces variants (le prix de l'OC la plus récente remplace l'ancien).
 *
 * Les XML sont générés au moment où la Direction valide l'OC et déposés directement dans sales_web_service ; l'envoi
 * effectif à X3 est fait par la commande app:sales:webservices:execute (lancée sur les serveurs, jamais en local).
 */
class OfferFlowService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OfferXmlBuilder $builder,
        private readonly WebServiceDispatcher $dispatcher,
        private readonly OcX3Lookup $x3,
    ) {
    }

    /**
     * Ce qui partira à X3 pour cette OC, en relisant X3 : un flux par variant à créer, et les tarifs spéciaux déjà en place du
     * client à clôturer avant (ou en conflit, voir OfferTariffClosures). Une erreur X3 remonte.
     *
     * @return array{creations: list<array{variant:string,line:OfferLine,own:bool}>, closures: list<array{fiche:string,article:string,endDate:string,row:array<string,mixed>}>, conflicts: list<array<string,mixed>>}
     *
     * @throws \Throwable X3 injoignable ; \RuntimeException ligne sans variant
     */
    public function inspect(Offer $offer): array
    {
        // Règle parent / taille : voir OfferSizing (une ligne de taille l'emporte sur celle de son parent ; un parent envoie ses variants
        // sans ceux qui ont leur propre ligne ; une taille seule ne tarife que cette taille ; jamais le parent lui-même).
        $lines = $offer->getLines()->toArray();
        $variants = $this->x3->getVariants(array_map(static fn (OfferLine $l) => $l->getParentCode(), $lines));
        $creations = OfferSizing::assign($lines, $variants);

        $existing = $this->x3->getExistingSpecialTariffs(
            $offer->getCustomerCode(),
            array_map(static fn (array $f): string => $f['variant'], $creations),
            $offer->getStartDate(),
        );

        return ['creations' => $creations] + OfferTariffClosures::plan($existing, $offer->getStartDate());
    }

    /**
     * Tarifs spéciaux déjà en place du client sur les variants que ces articles enverraient à X3, pour une OC pas encore
     * enregistrée (avertissement à la saisie, jamais bloquant). Les articles qui ne peuvent pas partir (taille inconnue, parent
     * sans variant) sont ignorés ici : l'enregistrement les refuse. Une erreur X3 remonte.
     *
     * @param list<string> $articleCodes parents ou tailles, comme les lignes de l'OC
     *
     * @return list<array<string,mixed>> lignes X3 (FICHE, ARTICLE, DATE_DEBUT, DATE_FIN, PRIX, DEVISE), triées par article puis fiche
     *
     * @throws \Throwable X3 injoignable
     */
    public function existingTariffsFor(string $customerCode, \DateTimeInterface $startDate, array $articleCodes): array
    {
        $lines = array_map(static fn (string $code): OfferLine => (new OfferLine())->setArticleCode($code), array_values(array_unique($articleCodes)));
        $variants = $this->x3->getVariants(array_values(array_unique(array_map(static fn (OfferLine $l) => $l->getParentCode(), $lines))));
        $flows = OfferSizing::plan($lines, $variants)['flows'];

        return $this->x3->getExistingSpecialTariffs($customerCode, array_map(static fn (array $f): string => $f['variant'], $flows), $startDate);
    }

    /**
     * Textes des conflits de tarifs existants (vide = rien ne s'oppose à l'envoi). Sert à bloquer la validation.
     *
     * @return list<string>
     *
     * @throws \Throwable X3 injoignable
     */
    public function conflictMessages(Offer $offer): array
    {
        return array_map(
            static fn (array $row): string => OfferTariffClosures::conflictMessage($row, $offer->getStartDate()),
            $this->inspect($offer)['conflicts'],
        );
    }

    /**
     * Génère les flux de l'OC et les dépose dans sales_web_service (une fois : sans effet si l'OC en a déjà), clôtures
     * (WSUPD7TSPE) d'abord, créations (WSCRESPL) ensuite : la commande d'exécution les envoie dans l'ordre de dépôt.
     * Atomique : si un parent n'a aucun variant, si un tarif existant est en conflit ou si une ligne ne peut pas être construite,
     * rien n'est enregistré ni déposé et l'exception remonte.
     *
     * @return list<OfferFlow> les flux de l'OC (statut « queued »), clôtures puis créations
     */
    public function send(Offer $offer): array
    {
        if (!$offer->getFlows()->isEmpty()) {
            return $offer->getFlows()->toArray();
        }

        $plan = $this->inspect($offer);
        if ($plan['conflicts'] !== []) {
            throw new \RuntimeException('Flux X3 non généré : ' . OfferTariffClosures::conflictMessage($plan['conflicts'][0], $offer->getStartDate()));
        }

        $flows = [];
        foreach ($plan['closures'] as $c) {
            $flows[] = new OfferFlow($offer, $c['article'], OfferXmlBuilder::WEBSERVICE_CLOSURE, $this->builder->closure($c['fiche'], $c['article'], $c['endDate']));
        }
        foreach ($plan['creations'] as $f) {
            $flows[] = new OfferFlow($offer, $f['variant'], OfferXmlBuilder::WEBSERVICE, $this->builder->creation($offer, $f['line'], $f['variant']));
        }

        $this->em->wrapInTransaction(function () use ($offer, $flows): void {
            foreach ($flows as $flow) {
                $offer->addFlow($flow);
                $ws = $this->dispatcher->dispatch($flow->getWebservice(), $flow->getXml(), $offer->getId(), $offer->getX3Env()->value);
                $flow->markQueued((int) $ws->getId());
            }
            $this->em->persist($offer);
            $this->em->flush();
        });

        return $flows;
    }
}
