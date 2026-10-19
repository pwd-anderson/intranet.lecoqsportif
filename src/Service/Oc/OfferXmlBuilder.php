<?php

namespace App\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferLine;

/**
 * XML du webservice X3 WSCRESPL (création d'une ligne de tarif) pour une ligne d'OC « Tarif Spé ».
 *
 * Même structure que l'ancien module Screeneo (WebservicesToX3::getSplXml) : un groupe INH de champs FLD.
 * ⚠ FORMAT À CONFIRMER avec l'équipe X3 de LCS (type de tarif 7TSPEC pas encore créé chez LCS, clé article parent ou
 * variant, collection) : tant que ce n'est pas fait, l'envoi reste désactivé (voir OfferFlowService).
 */
final class OfferXmlBuilder
{
    public const WEBSERVICE = 'WSCRESPL';
    public const PRICE_TYPE = '7TSPEC';

    /** Limite du champ « index » (référence) dans X3 : l'OC est référencée par son numéro, tronqué au besoin. */
    private const MAX_INDEX = 30;

    /**
     * @param string|null $articleCode article ENFANT (variant) du flux ; null = le code de la ligne (article parent)
     */
    public function creation(Offer $offer, OfferLine $line, ?string $articleCode = null): string
    {
        $injected = $line->getInjectedPrice();
        if ($injected === null || (float) $injected <= 0) {
            throw new \LogicException(sprintf('Ligne %s de l\'OC %s : prix à envoyer à X3 absent.', $line->getArticleCode(), $offer->getNumber()));
        }

        $root = new \SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' ?><PARAM></PARAM>");
        $group = $root->addChild('GRP');
        $group->addAttribute('ID', 'INH');

        $fields = [
            'WPLI' => self::PRICE_TYPE,
            'WPLISTRDAT' => $offer->getStartDate()->format('Ymd'),
            'WPLIENDDAT' => $offer->getEndDate()->format('Ymd'),
            'WPLICRDIDX' => substr($offer->getNumber(), 0, self::MAX_INDEX),
            'WITMREF' => $articleCode ?? $line->getArticleCode(),
            'WBPCNUM' => $offer->getCustomerCode(),
            'WMINQTY' => (string) $line->getMinQty(),
            'WMAXQTY' => (string) $line->getMaxQty(),
            'WCUR' => (string) $offer->getCustomerCurrency(),
            'WDISPRI' => rtrim(rtrim(number_format((float) $injected, 5, '.', ''), '0'), '.'),
        ];
        foreach ($fields as $name => $value) {
            $group->addChild('FLD', htmlspecialchars($value, ENT_XML1))->addAttribute('NAME', $name);
        }

        return $root->asXML();
    }
}
