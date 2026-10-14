<?php

namespace App\Tests\Service\Webservice;

use App\Service\Webservice\XmlBuilder;
use PHPUnit\Framework\TestCase;

/** Flux ZWSIMPOD (Import OD) : le champ DAE_OFFACC (contre-compte) de chaque ligne vient de la colonne « Contre-compte » du fichier importé. */
final class XmlBuilderOdTest extends TestCase
{
    private const ENTETE = ['type' => 'ODDIV', 'site' => 'LCSI1', 'journal' => 'ODDIV', 'bprvcr' => 'ANDERSON-123456', 'devise' => 'EUR', 'extourne' => 'non'];

    /** @return array<string,string> */
    private static function lineFields(string $xml, int $num): array
    {
        $out = [];
        foreach (simplexml_load_string($xml)->TAB->LIN[$num - 1]->FLD as $fld) {
            $out[(string) $fld['NAM']] = (string) $fld;
        }

        return $out;
    }

    private static function ligne(string $compte, string $tiers, string $debit, string $credit, string $contre): array
    {
        return ['Date' => '2026-08-13', 'Compte' => $compte, 'Tiers' => $tiers, 'Libelle' => 'OD Prov Bonus_UAR 06/26', 'Debit' => $debit, 'Credit' => $credit,
            'Taxe' => '', 'RefPiece' => 'OD BONUS UAR 06/26', 'Axe' => '05_1_4', 'Axe2' => 'CBL', 'ContreCompte' => $contre];
    }

    public function testNewFlowMatchesTheReferenceExampleFromTheBusiness(): void
    {
        $xml = XmlBuilder::buildOD(self::ENTETE, [
            self::ligne('4511110', '9822', '26086.96', '0', '6226000'),
            self::ligne('6226000', '', '0', '26086.96', '4511110'),
        ]);

        self::assertSame(['DAE_ACC', 'DAE_BPR', 'DAE_DES', 'DAE_AMTCUR', 'DAE_TAX', 'DAE_FREREF', 'DAE_OFFACC', 'DAE_CCE1', 'DAE_CCE2'], array_keys(self::lineFields($xml, 1)), 'ordre des champs de ligne');
        self::assertSame('6226000', self::lineFields($xml, 1)['DAE_OFFACC']);
        self::assertSame('4511110', self::lineFields($xml, 2)['DAE_OFFACC']);
        self::assertSame('-26086.96', self::lineFields($xml, 1)['DAE_AMTCUR']);
        self::assertSame('26086.96', self::lineFields($xml, 2)['DAE_AMTCUR']);

        // Identique, balise par balise, au flux d'exemple (hors mise en page).
        $expected = preg_replace('/>\s+</', '><', trim(<<<'XML'
<PARAM>
<GRP ID="INH"><FLD NAM="HAE_TYP">ODDIV</FLD><FLD NAM="HAE_FCY">LCSI1</FLD><FLD NAM="HAE_JOU">ODDIV</FLD><FLD NAM="HAE_ACCDAT">20260813</FLD><FLD NAM="HAE_BPRVCR">ANDERSON-123456</FLD><FLD NAM="HAE_CUR">EUR</FLD><FLD NAM="HAE_RVS">1</FLD><FLD NAM="HAE_RVSDAT"></FLD></GRP>
<TAB ID="IND" DIM="998" SIZE="2">
<LIN NUM="1"><FLD NAM="DAE_ACC">4511110</FLD><FLD NAM="DAE_BPR">9822</FLD><FLD NAM="DAE_DES">OD Prov Bonus_UAR 06/26</FLD><FLD NAM="DAE_AMTCUR">-26086.96</FLD><FLD NAM="DAE_TAX"></FLD><FLD NAM="DAE_FREREF">OD BONUS UAR 06/26</FLD><FLD NAM="DAE_OFFACC">6226000</FLD><FLD NAM="DAE_CCE1">05_1_4</FLD><FLD NAM="DAE_CCE2">CBL</FLD></LIN>
<LIN NUM="2"><FLD NAM="DAE_ACC">6226000</FLD><FLD NAM="DAE_BPR"></FLD><FLD NAM="DAE_DES">OD Prov Bonus_UAR 06/26</FLD><FLD NAM="DAE_AMTCUR">26086.96</FLD><FLD NAM="DAE_TAX"></FLD><FLD NAM="DAE_FREREF">OD BONUS UAR 06/26</FLD><FLD NAM="DAE_OFFACC">4511110</FLD><FLD NAM="DAE_CCE1">05_1_4</FLD><FLD NAM="DAE_CCE2">CBL</FLD></LIN>
</TAB>
</PARAM>
XML));
        $actual = preg_replace(['/^<\?xml[^>]*\?>\s*/', '/>\s+</'], ['', '><'], trim($xml));
        self::assertSame($expected, $actual);
    }

    public function testAnImportedFileWithoutTheNewColumnStillWorksWithAnEmptyOffsetAccount(): void
    {
        $ligne = self::ligne('4511110', '9822', '10', '0', '');
        unset($ligne['ContreCompte']);

        $fields = self::lineFields(XmlBuilder::buildOD(self::ENTETE, [$ligne]), 1);

        self::assertArrayHasKey('DAE_OFFACC', $fields);
        self::assertSame('', $fields['DAE_OFFACC']);
    }
}
