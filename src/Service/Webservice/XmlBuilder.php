<?php

namespace App\Service\Webservice;

final class XmlBuilder
{
    public static function buildOD(array $entete, array $lignes): string
    {
        $root = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8' standalone='no'?><PARAM></PARAM>");

        $grp = $root->addChild('GRP');
        $grp->addAttribute('ID', 'INH');

        self::fld($grp, 'WCPY',    $entete['societe']       ?? '');
        self::fld($grp, 'WDAT',    self::formatDate($entete['date'] ?? ''));
        self::fld($grp, 'WTYP',    $entete['type']          ?? '');
        self::fld($grp, 'WJOU',    $entete['journal']       ?? '');
        self::fld($grp, 'WREF',    $entete['ref']           ?? '');
        self::fld($grp, 'WBPRVCR', $entete['bprvcr']        ?? '');
        self::fld($grp, 'WCUR',    $entete['devise']        ?? '');
        self::fld($grp, 'WDATDUE', self::formatDate($entete['date_echeance'] ?? ''));
        self::fld($grp, 'WEXT',    $entete['extourne']      ?? '');
        self::fld($grp, 'WEXTDAT', self::formatDate($entete['date_extourne'] ?? ''));
        self::fld($grp, 'WDES',    $entete['libelle']       ?? '');

        $tab = $root->addChild('TAB');
        $tab->addAttribute('ID', 'LIN');

        foreach ($lignes as $i => $ligne) {
            $lin = $tab->addChild('LIN');
            $lin->addAttribute('NUM', (string) ($i + 1));

            self::fld($lin, 'WACCCOD', $ligne['Compte']  ?? '');
            self::fld($lin, 'WBPR',    $ligne['Tiers']   ?? '');
            self::fld($lin, 'WDEB',    $ligne['Debit']   ?? '');
            self::fld($lin, 'WCRE',    $ligne['Credit']  ?? '');
            self::fld($lin, 'WDES',    $ligne['Libelle'] ?? '');
            self::fld($lin, 'WAXE1',   $ligne['Axe1']    ?? '');
            self::fld($lin, 'WAXE2',   $ligne['Axe2']    ?? '');
            self::fld($lin, 'WTAX',    $ligne['Taxe']    ?? '');
        }

        return $root->asXML();
    }

    public static function buildRetourClient(array $entete, array $lignes): string
    {
        $root = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8' standalone='no'?><PARAM></PARAM>");

        $grp = $root->addChild('GRP');
        $grp->addAttribute('ID', 'INH');

        self::fld($grp, 'WSTOFCY',       $entete['WSTOFCY']       ?? '');
        self::fld($grp, 'WBPCORD',       $entete['WBPCORD']       ?? '');
        self::fld($grp, 'WRTNDAT',       $entete['WRTNDAT']       ?? '');
        self::fld($grp, 'WZREFEXT',      $entete['WZREFEXT']      ?? '');
        self::fld($grp, 'WZNFACT',       $entete['WZNFACT']       ?? '');
        self::fld($grp, 'WZNORIGIN',     $entete['WZNORIGIN']     ?? '');
        self::fld($grp, 'WZDATECLO',     $entete['WZDATECLO']     ?? '');
        self::fld($grp, 'WZYCOLLECTION', $entete['WZYCOLLECTION'] ?? '');
        self::fld($grp, 'WSZSTATUT',     $entete['WSZSTATUT']     ?? '');

        $tab = $root->addChild('TAB');
        $tab->addAttribute('ID', 'IND');

        foreach ($lignes as $i => $ligne) {
            $lin = $tab->addChild('LIN');
            $lin->addAttribute('NUM', (string) ($i + 1));

            self::fld($lin, 'WITMREF', $ligne['WITMREF'] ?? '');
            self::fld($lin, 'WEXTQTY', $ligne['WEXTQTY'] ?? '');
            self::fld($lin, 'WQTY',    $ligne['WQTY']    ?? '');
            self::fld($lin, 'WPRI',    $ligne['WPRI']    ?? '');
            self::fld($lin, 'WSTA',    $ligne['WSTA']    ?? '');
        }

        return $root->asXML();
    }

    public static function buildSolderCommande(array $orderNumbers): string
    {
        $root = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8' standalone='no'?><PARAM></PARAM>");

        $tab = $root->addChild('TAB');
        $tab->addAttribute('ID', 'SOH');

        foreach ($orderNumbers as $i => $num) {
            $lin = $tab->addChild('LIN');
            $lin->addAttribute('NUM', (string) ($i + 1));
            self::fld($lin, 'SOHNUM', (string) $num);
            self::fld($lin, 'SOQSTA', '3');
        }

        return $root->asXML();
    }

    private static function fld(\SimpleXMLElement $parent, string $name, string $value): void
    {
        $fld = $parent->addChild('FLD', htmlspecialchars($value, ENT_XML1));
        $fld->addAttribute('NAM', $name);
    }

    private static function formatDate(string $date): string
    {
        if (!$date) return '';
        // Convertit YYYY-MM-DD → YYYYMMDD pour X3
        return str_replace('-', '', $date);
    }
}
