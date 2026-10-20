<?php

namespace App\Service\SuiviSellin;

use App\Infrastructure\Sql\SqlFileLoader;

/**
 * Jeux « factdata » et « robdata » du template, pour l'onglet Facturation :
 *  - factdata : commandé (b), facturé (i) et reste à facturer (r) par saison, type de commande, famille, périmètre,
 *    hiérarchie du client, mois de facture et mois de livraison demandée (RDD) ;
 *  - robdata : reste à livrer (montant r, quantité q) par client, article et date RDD.
 * Requêtes : modules/suivi_sellin/03_facturation_rdd.sql et 11_reste_a_livrer_rdd.sql.
 *
 * Comme pour le backlog, la hiérarchie du client (manager, commercial, business model, groupe, groupement) est reprise de
 * celle du jeu principal : les filtres du haut de page comparent des libellés et doivent s'appliquer de la même façon
 * au carnet et à la facturation. Les dictionnaires sont triés tels quels (« (non renseigné) » en tête, comme le template).
 */
final class FacturationBuilder
{
    private const NR = ColumnarEncoder::NR;
    private const FAMILIES = ['APL' => 'Textile', 'FTW' => 'Footwear', 'HDW' => 'Accessoires'];
    private const HIERARCHY = ['bm1', 'cg', 'n1', 'sales', 'grp'];

    public function __construct(
        private readonly SuiviSellinDb $db,
        private readonly SqlFileLoader $sqlFileLoader,
    ) {
    }

    /**
     * @param array<string,mixed> $main jeu principal décodé
     * @return array<string,mixed>
     */
    public function buildFact(array $main): array
    {
        $names = ['season', 'sot', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp', 'cust', 'inv', 'rdd'];
        $dict = array_fill_keys($names, []);
        $b = $i = $r = [];
        $col = array_fill_keys($names, []);
        $h = $this->hierarchy($main);
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/03_facturation_rdd.sql')) as $row) {
            $code = (string) $row['CODE_CLIENT'];
            $attr = $h[$code] ?? null;
            $b[] = (float) $row['COMMANDE_EUR'];
            $i[] = (float) $row['FACTURE_EUR'];
            $r[] = (float) $row['RESTE_A_FACTURER_EUR'];
            $col['season'][] = ColumnarEncoder::index($dict['season'], (string) $row['SAISON']);
            $col['sot'][] = ColumnarEncoder::index($dict['sot'], $this->clean($row['TYPE_COMMANDE']));
            $col['fam'][] = ColumnarEncoder::index($dict['fam'], self::FAMILIES[trim((string) $row['FAMILLE'])] ?? self::NR);
            $col['perim'][] = ColumnarEncoder::index($dict['perim'], (int) $row['PERIMETRE_BOH'] === 1 ? 'Périmètre BOH' : 'Hors périmètre');
            foreach (self::HIERARCHY as $k) {
                $col[$k][] = ColumnarEncoder::index($dict[$k], $attr[$k] ?? self::NR);
            }
            $col['cust'][] = ColumnarEncoder::index($dict['cust'], $code);
            $col['inv'][] = ColumnarEncoder::index($dict['inv'], trim((string) ($row['MOIS_FACTURE'] ?? '')));
            $col['rdd'][] = ColumnarEncoder::index($dict['rdd'], trim((string) ($row['MOIS_RDD'] ?? '')));
        }

        [$final, $final8] = $this->finalize($dict, $col, ['season', 'sot', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp', 'inv', 'rdd']);

        $bin = ColumnarEncoder::float32($b) . ColumnarEncoder::float32($i) . ColumnarEncoder::float32($r)
            . ColumnarEncoder::uint16($final8['cust']);
        $u8 = ['season', 'sot', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp', 'inv', 'rdd'];
        foreach ($u8 as $k) {
            $bin .= ColumnarEncoder::uint8($final8[$k]);
        }

        return [
            'dicts'    => $final,
            'asof'     => date('Y-m-d'),
            'custCity' => array_map(static fn (string $c): string => $h[$c]['city'] ?? '', $final['cust']),
            'custGrp'  => array_map(static fn (string $c): string => $h[$c]['grpText'] ?? '', $final['cust']),
            'n'        => count($b),
            'u16'      => ['cust'],
            'u8'       => $u8,
            'bin'      => ColumnarEncoder::gzipBase64($bin),
        ];
    }

    /**
     * Jeu « readata » (onglet Pré-saison / réassort) : commandes de réassort (type RE) par client, mois de facture et
     * semaine de livraison demandée, avec montants et pièces commandés (b, q) et facturés (i, qi).
     * Requête : modules/suivi_sellin/04_reassort.sql.
     *
     * @param array<string,mixed> $main jeu principal décodé
     * @return array<string,mixed>
     */
    public function buildRea(array $main): array
    {
        $names = ['season', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp', 'cust', 'inv', 'wk'];
        $dict = array_fill_keys($names, []);
        $b = $i = $q = $qi = [];
        $col = array_fill_keys($names, []);
        $h = $this->hierarchy($main);
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/04_reassort.sql')) as $row) {
            $code = (string) $row['CODE_CLIENT'];
            $attr = $h[$code] ?? null;
            $b[] = (float) $row['COMMANDE_EUR'];
            $i[] = (float) $row['FACTURE_EUR'];
            $q[] = (float) $row['COMMANDE_QTE'];
            $qi[] = (float) $row['FACTURE_QTE'];
            $col['season'][] = ColumnarEncoder::index($dict['season'], (string) $row['SAISON']);
            $col['fam'][] = ColumnarEncoder::index($dict['fam'], self::FAMILIES[trim((string) $row['FAMILLE'])] ?? self::NR);
            $col['perim'][] = ColumnarEncoder::index($dict['perim'], (int) $row['PERIMETRE_BOH'] === 1 ? 'Périmètre BOH' : 'Hors périmètre');
            foreach (self::HIERARCHY as $k) {
                $col[$k][] = ColumnarEncoder::index($dict[$k], $attr[$k] ?? self::NR);
            }
            $col['cust'][] = ColumnarEncoder::index($dict['cust'], $code);
            $col['inv'][] = ColumnarEncoder::index($dict['inv'], trim((string) ($row['MOIS_FACTURE'] ?? '')));
            $col['wk'][] = ColumnarEncoder::index($dict['wk'], trim((string) ($row['SEMAINE_RDD'] ?? '')));
        }

        [$final, $f] = $this->finalize($dict, $col, ['season', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp', 'inv']);
        $bin = ColumnarEncoder::float32($b) . ColumnarEncoder::float32($i) . ColumnarEncoder::float32($q) . ColumnarEncoder::float32($qi)
            . ColumnarEncoder::uint16($f['cust']) . ColumnarEncoder::uint16($f['wk']);
        $u8 = ['season', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp', 'inv'];
        foreach ($u8 as $k) {
            $bin .= ColumnarEncoder::uint8($f[$k]);
        }

        return [
            'dicts' => $final,
            'asof'  => date('Y-m-d'),
            'n'     => count($b),
            'u16'   => ['cust', 'wk'],
            'u8'    => $u8,
            'bin'   => ColumnarEncoder::gzipBase64($bin),
        ];
    }

    /**
     * @param array<string,mixed> $main jeu principal décodé
     * @return array<string,mixed>
     */
    public function buildRob(array $main): array
    {
        $names = ['season', 'sot', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp', 'cust', 'item', 'rdd'];
        $dict = array_fill_keys($names, []);
        $rob = $qty = [];
        $col = array_fill_keys($names, []);
        $desc = [];
        $h = $this->hierarchy($main);
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/11_reste_a_livrer_rdd.sql')) as $row) {
            $code = (string) $row['CODE_CLIENT'];
            $attr = $h[$code] ?? null;
            $rob[] = (float) $row['RESTE_EUR'];
            $qty[] = (float) $row['RESTE_QTE'];
            $col['season'][] = ColumnarEncoder::index($dict['season'], (string) $row['SAISON']);
            $col['sot'][] = ColumnarEncoder::index($dict['sot'], $this->clean($row['TYPE_COMMANDE']));
            $col['fam'][] = ColumnarEncoder::index($dict['fam'], self::FAMILIES[trim((string) $row['FAMILLE'])] ?? self::NR);
            $col['perim'][] = ColumnarEncoder::index($dict['perim'], (int) $row['PERIMETRE_BOH'] === 1 ? 'Périmètre BOH' : 'Hors périmètre');
            foreach (self::HIERARCHY as $k) {
                $col[$k][] = ColumnarEncoder::index($dict[$k], $attr[$k] ?? self::NR);
            }
            $col['cust'][] = ColumnarEncoder::index($dict['cust'], $code);
            $item = trim((string) $row['ARTICLE']);
            $iIdx = ColumnarEncoder::index($dict['item'], $item);
            $col['item'][] = $iIdx;
            $desc[$iIdx] ??= trim((string) $row['DESIGNATION']);
            $col['rdd'][] = ColumnarEncoder::index($dict['rdd'], trim((string) ($row['DATE_RDD'] ?? '')));
        }

        [$final, $f, $maps] = $this->finalize($dict, $col, ['season', 'sot', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp']);
        $itemDesc = array_fill(0, count($final['item']), '');
        foreach ($desc as $provisional => $text) {
            $itemDesc[$maps['item'][$provisional]] = $text;
        }

        $bin = ColumnarEncoder::float32($rob) . ColumnarEncoder::float32($qty)
            . ColumnarEncoder::uint16($f['cust']) . ColumnarEncoder::uint16($f['item']) . ColumnarEncoder::uint16($f['rdd']);
        $u8 = ['season', 'sot', 'fam', 'perim', 'bm1', 'cg', 'n1', 'sales', 'grp'];
        foreach ($u8 as $k) {
            $bin .= ColumnarEncoder::uint8($f[$k]);
        }

        return [
            'dicts'    => $final,
            'asof'     => date('Y-m-d'),
            'itemDesc' => $itemDesc,
            'n'        => count($rob),
            'u16'      => ['cust', 'item', 'rdd'],
            'u8'       => $u8,
            'bin'      => ColumnarEncoder::gzipBase64($bin),
        ];
    }

    /**
     * Ordre définitif de chaque dictionnaire (tri binaire, « (non renseigné) » et valeur vide en tête) et conversion des
     * colonnes. Renvoie [dictionnaires, colonnes converties] ; les colonnes non listées dans $u8 (cust, item, rdd si 16 bits)
     * sont converties aussi (tous les dictionnaires).
     *
     * @param array<string,array<string,int>> $dict
     * @param array<string,list<int>> $col
     * @param list<string> $u8
     * @return array{0:array<string,list<string>>, 1:array<string,list<int>>, 2:array<string,array<int,int>>} dictionnaires, colonnes, tables de conversion
     */
    private function finalize(array $dict, array $col, array $u8): array
    {
        $final = [];
        $maps = [];
        $out = [];
        foreach ($dict as $name => $values) {
            [$final[$name], $maps[$name]] = ColumnarEncoder::plainOrder($values);
            $m = $maps[$name];
            $out[$name] = array_map(static fn (int $v): int => $m[$v], $col[$name]);
        }
        foreach ($u8 as $name) {
            if (count($final[$name]) > 255) {
                throw new \RuntimeException("Dictionnaire {$name} trop grand pour un octet (" . count($final[$name]) . ').');
            }
        }
        return [$final, $out, $maps];
    }

    /**
     * Hiérarchie de chaque client du jeu principal : code => [bm1, cg, n1, sales, grp, city, grpText].
     *
     * @param array<string,mixed> $main
     * @return array<string,array<string,string>>
     */
    private function hierarchy(array $main): array
    {
        $d = $main['dicts'];
        $out = [];
        foreach ($d['cust'] as $idx => $code) {
            $out[(string) $code] = [
                'bm1'     => $d['bm1'][$main['custBm1I'][$idx]],
                'cg'      => $d['cg'][$main['custCgI'][$idx]],
                'n1'      => $d['n1'][$main['custN1I'][$idx]],
                'sales'   => $d['sales'][$main['custSalesI'][$idx]],
                'grp'     => $d['grp'][$main['custGrpI'][$idx]],
                'city'    => (string) $main['custCity'][$idx],
                'grpText' => (string) $main['custGrp'][$idx],
            ];
        }

        return $out;
    }

    private function clean(mixed $value): string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? self::NR : $v;
    }
}
