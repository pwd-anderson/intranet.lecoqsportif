<?php

namespace App\Service\SuiviSellin;

use App\Infrastructure\Sql\SqlFileLoader;

/**
 * Jeu de données principal du module (le « data » du template) : le carnet de commandes sell-in au grain
 * saison × type de commande × client × article, avec la hiérarchie commerciale de chaque client.
 *
 * Requêtes : modules/suivi_sellin/01_carnet_commandes.sql et 02_referentiel_clients.sql (affichées telles quelles
 * sous le bouton « Requêtes » de chaque onglet). Écart connu avec le template : les attributs du client (commercial,
 * manager, groupement…) viennent du référentiel client actuel, alors que le template les lisait ligne par ligne dans
 * l'extraction ; ils diffèrent sur ~0,3 % des lignes (client dont le commercial a changé).
 */
final class MainDataBuilder
{
    private const NR = ColumnarEncoder::NR;

    private const FAMILIES = ['APL' => 'Textile', 'FTW' => 'Footwear', 'HDW' => 'Accessoires'];

    public function __construct(
        private readonly SuiviSellinDb $db,
        private readonly SqlFileLoader $sqlFileLoader,
    ) {
    }

    /** @return array<string,mixed> */
    public function build(): array
    {
        // 1. Référentiel clients : code => attributs (une seule ligne par client).
        $customers = [];
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/02_referentiel_clients.sql')) as $r) {
            $customers[(string) $r['CODE_CLIENT']] = $r;
        }

        // 2. Carnet : une passe, valeurs encodées en indices provisoires.
        $dict = array_fill_keys(['season', 'sot', 'perim', 'cust', 'item', 'fam', 'age', 'genus', 'line'], []);
        $amt = $qty = $season = $sot = $perim = $cust = $item = $fam = $age = $genus = $line = [];
        $itemDesc = [];
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/01_carnet_commandes.sql')) as $r) {
            $code = trim((string) $r['ARTICLE']);
            $amt[] = (float) $r['MONTANT_EUR'];
            $qty[] = (int) round((float) $r['QUANTITE']);
            $season[] = ColumnarEncoder::index($dict['season'], (string) $r['SAISON']);
            $sot[] = ColumnarEncoder::index($dict['sot'], $this->clean($r['TYPE_COMMANDE']));
            $perim[] = ColumnarEncoder::index($dict['perim'], (int) $r['PERIMETRE_BOH'] === 1 ? 'Périmètre BOH' : 'Hors périmètre');
            $cust[] = ColumnarEncoder::index($dict['cust'], (string) $r['CODE_CLIENT']);
            $iIdx = ColumnarEncoder::index($dict['item'], $code);
            $item[] = $iIdx;
            $itemDesc[$iIdx] ??= trim((string) $r['DESIGNATION']);
            $fam[] = ColumnarEncoder::index($dict['fam'], self::FAMILIES[trim((string) $r['FAMILLE'])] ?? self::NR);
            $age[] = ColumnarEncoder::index($dict['age'], $this->clean($r['AGE']));
            $genus[] = ColumnarEncoder::index($dict['genus'], $this->clean($r['GENRE']));
            $line[] = ColumnarEncoder::index($dict['line'], $this->clean($r['LIGNE_PRODUIT']));
        }
        $n = count($amt);

        // 3. Ordres définitifs des dictionnaires propres aux lignes.
        $final = [];
        $maps = [];
        $fixed = [
            'perim' => ['Hors périmètre', 'Périmètre BOH'],
            'fam'   => ['Textile', 'Footwear', 'Accessoires', self::NR],
        ];
        foreach ($dict as $name => $values) {
            [$final[$name], $maps[$name]] = ColumnarEncoder::finalOrder($values, $fixed[$name] ?? null);
        }
        $remap = static function (array $col, array $map): array {
            foreach ($col as $i => $v) {
                $col[$i] = $map[$v];
            }

            return $col;
        };

        // 4. Attributs par client (dans l'ordre définitif des clients) + dictionnaires de hiérarchie.
        $hDict = array_fill_keys(['bm1', 'cg', 'grp', 'n1', 'sales'], [self::NR => 0]);
        $custRows = [];
        foreach ($final['cust'] as $code) {
            $c = $customers[$code] ?? null;
            $custRows[] = [
                'city'  => $c ? trim((string) $c['VILLE']) : '',
                'grp'   => $c ? trim((string) $c['GROUPEMENT']) : '',
                'bm1'   => ColumnarEncoder::index($hDict['bm1'], $this->clean($c['BUSINESS_MODEL'] ?? null)),
                'cg'    => ColumnarEncoder::index($hDict['cg'], $this->clean($c['GROUPE_CLIENT'] ?? null)),
                'gi'    => ColumnarEncoder::index($hDict['grp'], $this->clean($c['GROUPEMENT'] ?? null)),
                'n1'    => ColumnarEncoder::index($hDict['n1'], $this->clean($c['MANAGER_N1'] ?? null)),
                'sales' => ColumnarEncoder::index($hDict['sales'], $this->clean($c['COMMERCIAL'] ?? null)),
            ];
        }
        $hMaps = [];
        foreach ($hDict as $name => $values) {
            [$final[$name], $hMaps[$name]] = ColumnarEncoder::finalOrder($values);
        }
        foreach (['bm1', 'cg', 'n1', 'sales'] as $name) {
            if (count($final[$name]) > 255) {
                throw new \RuntimeException("Dictionnaire {$name} trop grand pour un octet (" . count($final[$name]) . ').');
            }
        }
        if (count($final['grp']) > 255) {
            throw new \RuntimeException('Dictionnaire grp trop grand pour un octet (' . count($final['grp']) . ').');
        }

        $custBm1I = $custCgI = $custGrpI = $custN1I = $custSalesI = $custCity = $custGrp = [];
        foreach ($custRows as $c) {
            $custBm1I[] = $hMaps['bm1'][$c['bm1']];
            $custCgI[] = $hMaps['cg'][$c['cg']];
            $custGrpI[] = $hMaps['grp'][$c['gi']];
            $custN1I[] = $hMaps['n1'][$c['n1']];
            $custSalesI[] = $hMaps['sales'][$c['sales']];
            $custCity[] = $c['city'];
            $custGrp[] = $c['grp'];
        }

        // Colonnes par ligne issues de la hiérarchie du client.
        $custFinal = $remap($cust, $maps['cust']);
        $rowBm1 = $rowCg = $rowGrp = $rowN1 = $rowSales = [];
        foreach ($custFinal as $c) {
            $rowBm1[] = $custBm1I[$c];
            $rowCg[] = $custCgI[$c];
            $rowGrp[] = $custGrpI[$c];
            $rowN1[] = $custN1I[$c];
            $rowSales[] = $custSalesI[$c];
        }

        // Désignations dans l'ordre définitif des articles.
        $descFinal = [];
        foreach ($final['item'] as $k => $code) {
            $descFinal[$k] = '';
        }
        foreach ($itemDesc as $provisional => $desc) {
            $descFinal[$maps['item'][$provisional]] = $desc;
        }

        // 5. Binaire : amt, qty, puis les uint16 (cust, item, line) et les uint8 dans l'ordre déclaré à u16 / u8.
        $u8Order = ['season', 'bm1', 'cg', 'perim', 'fam', 'n1', 'sales', 'grp', 'age', 'genus', 'sot'];
        $cols8 = [
            'season' => $remap($season, $maps['season']),
            'bm1'    => $rowBm1,
            'cg'     => $rowCg,
            'perim'  => $remap($perim, $maps['perim']),
            'fam'    => $remap($fam, $maps['fam']),
            'n1'     => $rowN1,
            'sales'  => $rowSales,
            'grp'    => $rowGrp,
            'age'    => $remap($age, $maps['age']),
            'genus'  => $remap($genus, $maps['genus']),
            'sot'    => $remap($sot, $maps['sot']),
        ];
        $u16Order = ['cust', 'item', 'line'];
        $cols16 = [
            'cust' => $custFinal,
            'item' => $remap($item, $maps['item']),
            'line' => $remap($line, $maps['line']),
        ];
        $bin = ColumnarEncoder::float32($amt) . ColumnarEncoder::int32($qty);
        foreach ($u16Order as $k) {
            $bin .= ColumnarEncoder::uint16($cols16[$k]);
        }
        foreach ($u8Order as $k) {
            $bin .= ColumnarEncoder::uint8($cols8[$k]);
        }

        return [
            'dicts' => [
                'season' => $final['season'],
                'bm1'    => $final['bm1'],
                'cg'     => $final['cg'],
                'perim'  => $final['perim'],
                'fam'    => $final['fam'],
                'n1'     => $final['n1'],
                'sales'  => $final['sales'],
                'grp'    => $final['grp'],
                'age'    => $final['age'],
                'genus'  => $final['genus'],
                'line'   => $final['line'],
                'cust'   => $final['cust'],
                'item'   => $final['item'],
                'sot'    => $final['sot'],
            ],
            'asof'       => date('Y-m-d'),
            'n'          => $n,
            'custCity'   => $custCity,
            'custGrp'    => $custGrp,
            'custCgI'    => $custCgI,
            'custGrpI'   => $custGrpI,
            'custSalesI' => $custSalesI,
            'custN1I'    => $custN1I,
            'custBm1I'   => $custBm1I,
            'itemDesc'   => $descFinal,
            'u16'        => $u16Order,
            'u8'         => $u8Order,
            'bin'        => ColumnarEncoder::gzipBase64($bin),
        ];
    }

    private function clean(mixed $value): string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? self::NR : $v;
    }
}
