<?php

namespace App\Service\SuiviSellin;

use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Divers;

/**
 * Jeux « accdata » (avoirs) et « anndata » (annulations de commandes) de l'onglet Accompagnement commercial et des sections
 * correspondantes des fiches magasin. Requêtes : modules/suivi_sellin/06_avoirs.sql, 12_annulations.sql et
 * 13_annulations_articles.sql. Les clients sont ceux du jeu principal (indices dans son dictionnaire) : un avoir ou une
 * annulation d'un client absent du carnet depuis 2024 est ignoré, comme dans le template.
 *
 * Format du template : tableaux simples (pas de binaire) ; montants des avoirs en euros HT, positifs = rendus au client.
 */
final class AccompagnementBuilder
{
    private const CATEGORIES = ['Décote (markdown)', 'Retours', 'Défauts / SAV / litiges', 'Régularisations de facturation', 'Autres avoirs', 'Interco'];
    private const CLASSES = ['PS', 'RE', 'OP', 'CO', 'PR', 'Autres'];
    /** Avoirs sans pièce, plus petits que cela : exclus de la liste « Derniers avoirs » (des milliers d'avoirs de transport). */
    private const DOC_MIN_EUR = 100.0;

    public function __construct(
        private readonly SuiviSellinDb $db,
        private readonly SqlFileLoader $sqlFileLoader,
        private readonly Divers $divers,
    ) {
    }

    /**
     * @param array<string,mixed> $main jeu principal décodé
     * @return array<string,mixed>
     */
    public function buildAcc(array $main): array
    {
        $custIndex = array_flip($main['dicts']['cust']);
        $dict = ['ym' => [], 'se' => ['' => 0], 'rr' => ['' => 0]];
        $agg = [];   // clé = cust|cat|ym|se|rr|dot => [montant, quantité]
        $docs = [];  // clé = document|cust|cat|dot => [date, montant, quantité, motif, saison, objet]
        $items = []; // clé = cust|cat|article => [montant, quantité]
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/06_avoirs.sql')) as $r) {
            $code = (string) $r['CODE_CLIENT'];
            if (!isset($custIndex[$code])) {
                continue;
            }
            $cust = $custIndex[$code];
            $cat = $this->category((string) $r['MOTIF'], (int) $r['EST_DECOTE']);
            $date = (string) $r['DATE_AVOIR'];
            $ym = substr($date, 0, 7);
            $se = (string) $r['SAISON'];
            $rr = (string) $r['MOTIF_RETOUR'];
            $dot = (int) $r['EST_DOTATION'] === 1 ? 1 : 0;
            $a = (float) $r['MONTANT_EUR'];
            $q = (float) $r['QUANTITE'];
            $item = trim((string) $r['ARTICLE']);

            ColumnarEncoder::index($dict['ym'], $ym);
            ColumnarEncoder::index($dict['se'], $se);
            ColumnarEncoder::index($dict['rr'], $rr);
            $k = $cust . '|' . $cat . '|' . $ym . '|' . $se . '|' . $rr . '|' . $dot;
            $agg[$k] = [($agg[$k][0] ?? 0.0) + $a, ($agg[$k][1] ?? 0.0) + $q];

            // Liste « Derniers avoirs » : hors régularisations et interco (le JS les écarte aussi).
            if ($cat !== 3 && $cat !== 5) {
                $dk = $r['DOCUMENT'] . '|' . $cust . '|' . $cat . '|' . $dot;
                $objet = $this->objet($item);
                $d = $docs[$dk] ?? [$date, 0.0, 0.0, $rr, $se, $objet];
                $d[1] += $a;
                $d[2] += $q;
                $docs[$dk] = $d;
            }
            // Références les plus concernées : décote, retours, défauts, sur de vrais articles.
            if ($cat <= 2 && $this->isProduct($item)) {
                $ik = $cust . '|' . $cat . '|' . $item;
                $items[$ik] = [($items[$ik][0] ?? 0.0) + $a, ($items[$ik][1] ?? 0.0) + $q];
            }
        }

        [$ymList, $ymMap] = ColumnarEncoder::plainOrder($dict['ym']);
        [$seList, $seMap] = ColumnarEncoder::plainOrder($dict['se']);
        [$rrList, $rrMap] = ColumnarEncoder::plainOrder($dict['rr']);
        $C = ['cust' => [], 'cat' => [], 'ym' => [], 'se' => [], 'rr' => [], 'dot' => [], 'a' => [], 'q' => []];
        foreach ($agg as $k => [$a, $q]) {
            [$cust, $cat, $ym, $se, $rr, $dot] = explode('|', $k);
            $C['cust'][] = (int) $cust;
            $C['cat'][] = (int) $cat;
            $C['ym'][] = $ymMap[$dict['ym'][$ym]];
            $C['se'][] = $seMap[$dict['se'][$se]];
            $C['rr'][] = $rrMap[$dict['rr'][$rr]];
            $C['dot'][] = (int) $dot;
            $C['a'][] = round($a, 2);
            $C['q'][] = (int) round($q);
        }

        $docRows = [];
        foreach ($docs as $dk => [$date, $a, $q, $rr, $se, $objet]) {
            if (abs($a) < self::DOC_MIN_EUR) {
                continue;
            }
            [$number, $cust, $cat, $dot] = explode('|', $dk);
            $docRows[] = [(int) $cust, $number, (int) $cat, $date, round($a, 2), (int) round($q), $rr, (int) $dot, $se, $objet];
        }
        $itemRows = [];
        foreach ($items as $ik => [$a, $q]) {
            [$cust, $cat, $item] = explode('|', $ik);
            $itemRows[] = [(int) $cust, (int) $cat, $item, round($a, 2), (int) round($q)];
        }

        return [
            'asof'  => date('Y-m-d'),
            'src'   => 'SEI CONSO_INVOICES · avoirs (CREDITMEMO) LCSI depuis 2024',
            'cats'  => self::CATEGORIES,
            'dicts' => ['ym' => $ymList, 'se' => $seList, 'rr' => $rrList],
            'n'     => count($C['a']),
            'C'     => $C,
            'docs'  => $docRows,
            'items' => $itemRows,
        ];
    }

    /**
     * @param array<string,mixed> $main jeu principal décodé
     * @return array<string,mixed>
     */
    public function buildAnn(array $main): array
    {
        $custIndex = array_flip($main['dicts']['cust']);
        $rates = $this->divers->getExchangeRatesValues();
        $seasons = $main['dicts']['season'];
        $seIndex = array_flip($seasons);
        $clsIndex = array_flip(self::CLASSES);

        // Commandé de comparaison : le carnet SEI (jeu principal), par client, saison et classe.
        $ordered = $this->orderedFromMain($main);

        // Annulé et livré : X3.
        $x3 = [];        // cust|se|cls => [qa, aa, ql, al]
        $x3Ordered = []; // se => quantité PS commandée dans X3 (pour reconnaître les saisons partiellement archivées)
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/12_annulations.sql')) as $r) {
            $code = (string) $r['CODE_CLIENT'];
            $se = (string) $r['COLLECTION'];
            $rate = (float) ($rates[trim((string) $r['DEVISE'])] ?? 0);
            if (!isset($custIndex[$code], $seIndex[$se]) || $rate <= 0) {
                continue;
            }
            $cls = $this->cls((string) $r['CLASSE']);
            $k = $custIndex[$code] . '|' . $seIndex[$se] . '|' . $clsIndex[$cls];
            $v = $x3[$k] ?? [0.0, 0.0, 0.0, 0.0];
            $v[0] += (float) $r['QUANTITE_ANNULEE'];
            $v[1] += (float) $r['MONTANT_ANNULE_DEVISE'] / $rate;
            $v[2] += (float) $r['QUANTITE_LIVREE'];
            $v[3] += (float) $r['MONTANT_LIVRE_DEVISE'] / $rate;
            $x3[$k] = $v;
        }

        $keys = array_unique(array_merge(array_keys($x3), array_keys($ordered)));
        sort($keys, SORT_STRING);
        $C = array_fill_keys(['cust', 'se', 'cls', 'qc', 'ac', 'qa', 'aa', 'ql', 'al'], []);
        $psOrderedSeason = [];
        $psX3Season = [];
        foreach ($keys as $k) {
            [$cust, $se, $cls] = array_map('intval', explode('|', $k));
            [$qc, $ac] = $ordered[$k] ?? [0.0, 0.0];
            [$qa, $aa, $ql, $al] = $x3[$k] ?? [0.0, 0.0, 0.0, 0.0];
            if ($qc == 0 && $ac == 0 && $qa == 0 && $ql == 0) {
                continue;
            }
            $C['cust'][] = $cust;
            $C['se'][] = $se;
            $C['cls'][] = $cls;
            $C['qc'][] = (int) round($qc);
            $C['ac'][] = round($ac, 2);
            $C['qa'][] = (int) round($qa);
            $C['aa'][] = round($aa, 2);
            $C['ql'][] = (int) round($ql);
            $C['al'][] = round($al, 2);
            if (self::CLASSES[$cls] === 'PS') {
                $psOrderedSeason[$se] = ($psOrderedSeason[$se] ?? 0) + $qc;
                $psX3Season[$se] = ($psX3Season[$se] ?? 0) + $ql + $qa;
            }
        }

        // Saison « partiellement archivée » : X3 ne contient qu'une partie des pièces du carnet (les commandes d'avant
        // avril 2026 sont dans l'historique Navision, pas dans X3).
        $partial = [];
        foreach ($seasons as $i => $name) {
            $o = $psOrderedSeason[$i] ?? 0;
            if ($o > 0 && ($psX3Season[$i] ?? 0) < 0.9 * $o && $name < '2026-02-FW') {
                $partial[] = $name;
            }
        }

        // Références les plus annulées.
        $itemRows = [];
        $perItem = [];
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/13_annulations_articles.sql')) as $r) {
            $code = (string) $r['CODE_CLIENT'];
            $se = (string) $r['COLLECTION'];
            $rate = (float) ($rates[trim((string) $r['DEVISE'])] ?? 0);
            if (!isset($custIndex[$code], $seIndex[$se]) || $rate <= 0) {
                continue;
            }
            $k = $custIndex[$code] . '|' . $seIndex[$se] . '|' . trim((string) $r['ARTICLE']);
            $v = $perItem[$k] ?? [0.0, 0.0, 0.0];
            $v[0] += (float) $r['QUANTITE_ANNULEE'];
            $v[1] += (float) $r['MONTANT_ANNULE_DEVISE'] / $rate;
            $v[2] += (float) $r['QUANTITE_COMMANDEE'];
            $perItem[$k] = $v;
        }
        foreach ($perItem as $k => [$qa, $aa, $qc]) {
            [$cust, $se, $item] = explode('|', $k);
            $itemRows[] = [(int) $cust, (int) $se, $item, (int) round($qa), round($aa, 2), (int) round($qc)];
        }

        return [
            'asof'    => date('Y-m-d'),
            'partial' => $partial,
            'dicts'   => ['se' => $seasons, 'cls' => self::CLASSES],
            'n'       => count($C['cust']),
            'C'       => $C,
            'items'   => $itemRows,
        ];
    }

    /**
     * Commandé du carnet par client, saison et classe : « cust|se|cls » => [pièces, montant €].
     *
     * @param array<string,mixed> $main
     * @return array<string,array{0:float,1:float}>
     */
    private function orderedFromMain(array $main): array
    {
        $n = (int) $main['n'];
        $raw = gzdecode(base64_decode($main['bin']));
        $amt = array_values(unpack('g*', substr($raw, 0, 4 * $n)));
        $qty = array_values(unpack('l*', substr($raw, 4 * $n, 4 * $n)));
        $off = 8 * $n;
        $cols = [];
        foreach ($main['u16'] as $k) {
            $cols[$k] = array_values(unpack('v*', substr($raw, $off, 2 * $n)));
            $off += 2 * $n;
        }
        foreach ($main['u8'] as $k) {
            $cols[$k] = array_values(unpack('C*', substr($raw, $off, $n)));
            $off += $n;
        }
        unset($raw);

        $sotMap = array_map(fn (string $v): int => array_search($this->cls($v), self::CLASSES, true), $main['dicts']['sot']);
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $k = $cols['cust'][$i] . '|' . $cols['season'][$i] . '|' . $sotMap[$cols['sot'][$i]];
            $v = $out[$k] ?? [0.0, 0.0];
            $v[0] += $qty[$i];
            $v[1] += $amt[$i];
            $out[$k] = $v;
        }

        return $out;
    }

    private function cls(string $code): string
    {
        $code = trim($code);

        return in_array($code, ['PS', 'RE', 'OP', 'CO', 'PR'], true) ? $code : 'Autres';
    }

    /** 0 décote, 1 retours, 2 défauts / SAV / litiges, 3 régularisation, 4 autres, 5 interco. */
    private function category(string $reason, int $isDecote): int
    {
        return match (true) {
            $reason === 'INTERCO'                                  => 5,
            $reason === 'IN-REGUL'                                 => 3,
            $isDecote === 1 || $reason === 'MARK-DOWN'             => 0,
            str_starts_with($reason, 'RETURN')                     => 1,
            in_array($reason, ['DEFECTIVE', 'AS', 'CLAIM'], true)  => 2,
            default                                                => 4,
        };
    }

    private function isProduct(string $item): bool
    {
        return preg_match('/^\d{7}$/', $item) === 1;
    }

    /** Objet de l'avoir d'après le code article : un vrai article, ou une ligne de service (affiliation, transport…). */
    private function objet(string $item): string
    {
        if ($this->isProduct($item)) {
            return 'Articles';
        }

        return match (true) {
            str_starts_with($item, 'V-AFFILIATION'), $item === 'FACT_AFFILIES' => 'Affiliation',
            $item === 'V-MARKDOWN', $item === 'DECOTE'                         => 'Décote',
            $item === 'V-OTHERFINANCIAL'                                       => 'Financier',
            $item === 'V-ROYALTIES', $item === 'ROYALTIES'                     => 'Royalties',
            $item === 'V-TRANSPORT'                                            => 'Transport',
            $item === 'V-SERVICES', $item === 'VENTESERVICE'                   => 'Services',
            $item === 'V-MARKETING'                                            => 'Marketing',
            default                                                            => 'Divers',
        };
    }
}
