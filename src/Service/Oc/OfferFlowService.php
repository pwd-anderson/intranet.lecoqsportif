<?php

namespace App\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferFlow;
use App\Service\Webservice\WebServiceDispatcher;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Flux XML vers X3 d'une OC validée : un WSCRESPL (création du tarif spécial) par ARTICLE ENFANT (variant) de chaque
 * ligne d'OC (saisie par article parent).
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
     * Génère les flux de l'OC et les dépose dans sales_web_service (une fois : sans effet si l'OC en a déjà).
     * Atomique : si un parent n'a aucun variant ou si une ligne ne peut pas être construite, rien n'est enregistré ni
     * déposé et l'exception remonte.
     *
     * @return list<OfferFlow> les flux de l'OC (statut « queued »)
     */
    public function send(Offer $offer): array
    {
        if (!$offer->getFlows()->isEmpty()) {
            return $offer->getFlows()->toArray();
        }

        // L'OC est saisie par article PARENT, mais X3 reçoit un tarif par article ENFANT (variant) : tous les variants du
        // parent, sans tenir compte de la collection, chacun une fois ; le parent n'est jamais envoyé.
        $variants = $this->x3->getVariants(array_map(static fn ($l) => $l->getArticleCode(), $offer->getLines()->toArray()));

        $flows = [];
        $seen = [];
        foreach ($offer->getLines() as $line) {
            $children = $variants[$line->getArticleCode()] ?? [];
            if ($children === []) {
                throw new \RuntimeException(sprintf('Aucun variant trouvé pour l\'article parent %s : flux X3 non généré.', $line->getArticleCode()));
            }
            foreach ($children as $child) {
                if (isset($seen[$child])) {
                    continue; // jamais deux fois le même variant dans un flux
                }
                $seen[$child] = true;
                $flows[] = new OfferFlow($offer, $child, OfferXmlBuilder::WEBSERVICE, $this->builder->creation($offer, $line, $child));
            }
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
