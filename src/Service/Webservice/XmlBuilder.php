<?php

namespace App\Service\Webservice;

final class XmlBuilder
{
    public static function buildOD(array $entete, array $lignes): string
    {
        $root = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8' standalone='no'?><PARAM></PARAM>");

        $grp = $root->addChild('GRP');
        $grp->addAttribute('ID', 'INH');

        self::fld($grp, 'HAE_TYP',    $entete['type']    ?? '');
        self::fld($grp, 'HAE_FCY',    $entete['site']    ?? '');
        self::fld($grp, 'HAE_JOU',    $entete['journal'] ?? '');
        self::fld($grp, 'HAE_ACCDAT', self::formatDate($entete['date'] ?? ''));
        self::fld($grp, 'HAE_BPRVCR', $entete['bprvcr'] ?? '');
        self::fld($grp, 'HAE_CUR',    $entete['devise'] ?? '');

        $isExtourne   = ($entete['extourne'] ?? 'non') === 'oui';
        $dateExtourne = self::formatDate($entete['date_extourne'] ?? '');
        self::fld($grp, 'HAE_RVS',    $isExtourne ? '2' : '1');
        self::fld($grp, 'HAE_RVSDAT', $isExtourne ? $dateExtourne : '');

        $nbLignes = count($lignes);

        $tab = $root->addChild('TAB');
        $tab->addAttribute('ID', 'IND');
        $tab->addAttribute('DIM', '998');
        $tab->addAttribute('SIZE', (string) $nbLignes);

        foreach ($lignes as $i => $ligne) {
            $debit  = (float) str_replace(',', '.', $ligne['Debit']  ?? '0');
            $credit = (float) str_replace(',', '.', $ligne['Credit'] ?? '0');
            $amtcur = round($credit - $debit, 2);

            $lin = $tab->addChild('LIN');
            $lin->addAttribute('NUM', (string) ($i + 1));

            self::fld($lin, 'DAE_ACC',    $ligne['Compte']  ?? '');
            self::fld($lin, 'DAE_BPR',    $ligne['Tiers']   ?? '');
            self::fld($lin, 'DAE_DES',    $ligne['Libelle'] ?? '');
            self::fld($lin, 'DAE_AMTCUR', (string) $amtcur);
            self::fld($lin, 'DAE_TAX',    $ligne['Taxe']    ?? '');
            self::fld($lin, 'DAE_FREREF', $ligne['RefPiece'] ?? '');
            self::fld($lin, 'DAE_CCE1',   self::extractAxeCode($ligne['Axe'] ?? ''));
            self::fld($lin, 'DAE_CCE2',   '');
        }

        return self::forcePairedTags($root->asXML());
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

    private static function forcePairedTags(string $xml): string
    {
        return preg_replace('/<FLD([^>]*)\/>/', '<FLD$1></FLD>', $xml);
    }

    private static function extractAxeCode(string $value): string
    {
        $value = trim($value);
        if ($value === '') return '';

        $numericParts = [];
        foreach (explode('_', $value) as $part) {
            if (!ctype_digit($part)) break;
            $numericParts[] = $part;
        }

        return implode('_', $numericParts);
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
