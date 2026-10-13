<?php

namespace App\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferFlow;
use App\Service\Webservice\WebServiceDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Flux XML vers X3 d'une OC validée : un WSCRESPL (création du tarif spécial) par ligne.
 *
 * DÉSACTIVÉ PAR DÉFAUT (variable d'env OC_X3_FLOW_ENABLED, « 0 » si absente) : le XML est généré et conservé dans
 * oc_offer_flow (statut « prepared ») pour être relu par l'équipe X3, mais RIEN n'est placé dans sales_web_service.
 * Ce point est essentiel : la commande app:sales:webservices:execute exécute TOUTES les lignes non exécutées de
 * sales_web_service, y compris celles des autres modules. Activé, les XML sont placés dans sales_web_service
 * (statut « queued ») et partent avec cette commande.
 */
class OfferFlowService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OfferXmlBuilder $builder,
        private readonly WebServiceDispatcher $dispatcher,
        #[Autowire('%oc.x3_flow_enabled%')]
        private readonly bool $enabled,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Génère les flux de l'OC (une fois : sans effet si l'OC en a déjà). Atomique : si une ligne ne peut pas être
     * construite, aucun flux n'est enregistré et l'exception remonte.
     *
     * @return list<OfferFlow> les flux de l'OC
     */
    public function prepare(Offer $offer): array
    {
        if (!$offer->getFlows()->isEmpty()) {
            return $offer->getFlows()->toArray();
        }

        $flows = [];
        foreach ($offer->getLines() as $line) {
            $flows[] = new OfferFlow($offer, $line->getArticleCode(), OfferXmlBuilder::WEBSERVICE, $this->builder->creation($offer, $line));
        }

        $this->em->wrapInTransaction(function () use ($offer, $flows): void {
            foreach ($flows as $flow) {
                $offer->addFlow($flow);
            }
            $this->em->persist($offer);
            $this->em->flush();
        });

        return $flows;
    }

    /**
     * Place les flux « prepared » dans sales_web_service (envoi effectif à X3 par la commande d'exécution).
     * Ne fait rien, et renvoie 0, si l'envoi est désactivé.
     *
     * @return int nombre de flux placés en file
     */
    public function queue(Offer $offer): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $queued = 0;
        $this->em->wrapInTransaction(function () use ($offer, &$queued): void {
            foreach ($offer->getFlows() as $flow) {
                if ($flow->getStatus() !== OfferFlow::PREPARED) {
                    continue;
                }
                $ws = $this->dispatcher->dispatch($flow->getWebservice(), $flow->getXml(), $offer->getId());
                $flow->markQueued((int) $ws->getId());
                ++$queued;
            }
            $this->em->flush();
        });

        return $queued;
    }
}
