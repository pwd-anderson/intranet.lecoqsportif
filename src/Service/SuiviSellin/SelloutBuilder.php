<?php

namespace App\Service\SuiviSellin;

use App\Infrastructure\Sql\SqlFileLoader;

/**
 * Jeu « sodata » de l'onglet Sell-out (Intersport et Sport 2000) : ventes et stocks des magasins semaine par semaine.
 *
 * Sources (voir CLAUDE.md, section Suivi Sellin) :
 * - cube LCS_SELLOUT_SALES pour tout l'historique hebdomadaire (requêtes 07 et 08), restreint aux articles du reporting
 *   « Push Réassort » de chaque saison (SelloutArticleLists) ;
 * - fichiers Push Réassort de la dernière semaine (SharePoint) pour les saisons en cours : liste des articles, RAL,
 *   préparation, informations des magasins et réassort conseillé (formule du fichier). Les saisons closes n'ont ni RAL
 *   ni réassort conseillé : les fichiers 2025 ne sont pas disponibles en entier.
 *
 * Format du template : JSON de tableaux simples (colonnes), compressé en gzip puis base64, rangé dans {"gz": "…"}.
 */
final class SelloutBuilder
{
    private const SOURCES = ['INTERSPORT', 'SPORT2000'];
    private const FAMILIES = ['Textile', 'Footwear', 'Accessoires'];

    /**
     * Saisons closes : première semaine affichée, dernière semaine, nombre de semaines de la fenêtre de rythme.
     * Les saisons en cours (présentes dans les fichiers de la dernière semaine) vont jusqu'à la dernière semaine du cube.
     */
    private const CLOSED = [
        '2025-01-SS' => ['from' => 202501, 'last' => 202522, 'irq' => 4],
        '2025-02-FW' => ['from' => 202521, 'last' => 202544, 'irq' => 4],
        '2026-01-SS' => ['from' => 202546, 'last' => 202628, 'irq' => 2],
    ];
    /** Première semaine affichée d'une saison en cours (celle du template pour FW26, sinon la première du cube). */
    private const CURRENT_FROM = ['2026-02-FW' => 202629];

    public function __construct(
        private readonly SuiviSellinDb $db,
        private readonly SqlFileLoader $sqlFileLoader,
        private readonly SelloutArticleLists $lists,
    ) {
    }

    /** @return array{gz:string} */
    public function build(): array
    {
        $seed = json_decode((string) file_get_contents(__DIR__ . '/../../Infrastructure/SelloutReference/articles_seed.json'), true, 512, JSON_THROW_ON_ERROR);
        $files = $this->lists->fromLatestWeek();

        // Saisons : graine pour les closes, fichiers de la dernière semaine pour les autres ; ordre chronologique.
        $articles = [];
        foreach (array_keys(self::CLOSED) as $season) {
            $articles[$season] = $seed['seasons'][$season];
        }
        foreach ($files['articles'] as $season => $bySource) {
            $articles[$season] = $bySource;
        }
        uksort($articles, fn (string $a, string $b): int => strcmp($a, $b));
        $seasons = array_keys($articles);

        $dict = ['cust' => [], 'item' => [], 'idesc' => [], 'iline' => [], 'ifam' => []];
        $out = [
            'W' => ['season' => [], 'fam' => [], 'src' => [], 'cust' => [], 'yw' => [], 'q' => [], 'e' => []],
            'S' => ['season' => [], 'fam' => [], 'src' => [], 'cust' => [], 'yw' => [], 'cq' => [], 'ce' => [], 'st' => []],
            'L' => ['season' => [], 'fam' => [], 'src' => [], 'cust' => [], 'ral' => [], 'reco' => [], 'prep' => []],
            'I' => ['season' => [], 'src' => [], 'cust' => [], 'item' => [], 'q' => [], 'e' => [], 'st' => [], 'sv' => [], 'ral' => [], 'reco' => [], 'rq' => []],
        ];
        $last = [];
        $weeks = [];
        $iref = [];
        $irq = [];
        $lastCubeWeek = 0;
        $lastWeekOfSeason = [];

        foreach ($seasons as $se => $season) {
            $closed = self::CLOSED[$season] ?? null;
            $current = $closed === null;
            $weekSet = [];
            foreach (self::SOURCES as $si => $source) {
                $list = $articles[$season][$source] ?? [];
                if ($list === []) {
                    continue;
                }
                $values = [];
                foreach ($list as $a) {
                    $values[] = "('" . str_replace("'", "''", $a['code']) . "','" . $a['fam'] . "')";
                    $c = $a['code'];
                    if (!isset($dict['item'][$c])) {
                        $dict['item'][$c] = count($dict['item']);
                        $dict['idesc'][] = $a['desc'];
                        $dict['iline'][] = $a['line'];
                        $dict['ifam'][] = array_search($a['fam'], self::FAMILIES, true);
                    }
                }
                $articlesSql = implode(',', $values);
                $to = $closed['last'] ?? 999999;

                // 1) Historique hebdomadaire par magasin et famille (cumuls de saison calculés ici).
                $rows = $this->rows('07_sellout_hebdo', [
                    '{{ARTICLES}}' => $articlesSql, '{{ENSEIGNE}}' => $source, '{{SAISON}}' => $season,
                    '{{SEMAINE_DEBUT}}' => '0', '{{SEMAINE_FIN}}' => (string) $to,
                ]);
                $from = $closed['from'] ?? self::CURRENT_FROM[$season] ?? 0;
                $series = [];
                foreach ($rows as $r) {
                    $f = (int) array_search($r['FAMILLE'], self::FAMILIES, true);
                    $series[$r['CODE_CLIENT'] . '|' . $f][(int) $r['SEMAINE']] = $r;
                    $lastCubeWeek = $current ? max($lastCubeWeek, (int) $r['SEMAINE']) : $lastCubeWeek;
                    $lastWeekOfSeason[$season] = max($lastWeekOfSeason[$season] ?? 0, (int) $r['SEMAINE']);
                }
                $allWeeks = [];
                foreach ($series as $byWeek) {
                    foreach ($byWeek as $w => $_) {
                        $allWeeks[$w] = true;
                    }
                }
                ksort($allWeeks);
                if ($from === 0) {
                    $from = (int) (array_key_first($allWeeks) ?? 0);
                }
                foreach ($series as $key => $byWeek) {
                    [$code, $f] = explode('|', $key);
                    $ci = $this->index($dict['cust'], $code);
                    ksort($byWeek);
                    $cq = 0.0;
                    $ce = 0.0;
                    $prevStock = 0.0;
                    foreach (array_keys($allWeeks) as $w) {
                        $r = $byWeek[$w] ?? null;
                        $q = $r ? (float) $r['VENTES_QTE'] : 0.0;
                        $e = $r ? (float) $r['VENTES_MONTANT'] : 0.0;
                        $st = $r ? (float) $r['STOCK_QTE'] : 0.0;
                        $cq += $q;
                        $ce += $e;
                        // Une semaine sans ligne dans le cube : stock tombé à zéro (une ligne est écrite pour que le
                        // dernier relevé du magasin ne reste pas sur l'ancien stock).
                        if ($r !== null || $prevStock != 0.0) {
                            if ($w >= $from) {
                                $weekSet[$w] = true;
                                $out['S']['season'][] = $se;
                                $out['S']['fam'][] = (int) $f;
                                $out['S']['src'][] = $si;
                                $out['S']['cust'][] = $ci;
                                $out['S']['yw'][] = $w;
                                $out['S']['cq'][] = (int) round($cq);
                                $out['S']['ce'][] = (int) round($ce);
                                $out['S']['st'][] = (int) round($st);
                            }
                        }
                        if ($r !== null && $w >= $from && ($q != 0.0 || $e != 0.0)) {
                            $out['W']['season'][] = $se;
                            $out['W']['fam'][] = (int) $f;
                            $out['W']['src'][] = $si;
                            $out['W']['cust'][] = $ci;
                            $out['W']['yw'][] = $w;
                            $out['W']['q'][] = (int) round($q);
                            $out['W']['e'][] = (int) round($e);
                        }
                        $prevStock = $st;
                    }
                }
            }
            ksort($weekSet);
            $weeks[$season] = array_keys($weekSet);
            $last[$season] = $closed['last'] ?? ($lastWeekOfSeason[$season] ?? 0);
            $iref[$season] = $last[$season];
            $irq[$season] = $closed['irq'] ?? 4;
        }

        // 2) Relevé par référence à la semaine de référence de chaque saison (+ RAL / réassort conseillé des saisons en cours).
        foreach ($seasons as $se => $season) {
            $ref = $iref[$season];
            $recent = $this->recentWeeks($weeks[$season], $ref, $irq[$season]);
            $fileStock = $files['stock'][$season] ?? [];
            foreach (self::SOURCES as $si => $source) {
                $list = $articles[$season][$source] ?? [];
                if ($list === []) {
                    continue;
                }
                $values = array_map(fn (array $a): string => "('" . str_replace("'", "''", $a['code']) . "','" . $a['fam'] . "')", $list);
                $rows = $this->rows('08_sellout_reference', [
                    '{{ARTICLES}}' => implode(',', $values), '{{ENSEIGNE}}' => $source, '{{SAISON}}' => $season,
                    '{{SEMAINE_REF}}' => (string) $ref, '{{SEMAINES_RECENTES}}' => implode(',', $recent),
                ]);
                $ifam = [];
                foreach ($list as $a) {
                    $ifam[$a['code']] = array_search($a['fam'], self::FAMILIES, true);
                }
                $files4 = $fileStock[$source] ?? [];
                $seen = [];
                $famTot = []; // magasin|famille => [ral, reco, prep]
                $emit = function (string $cust, string $code, float $q, float $e, float $st, float $sv, float $rq) use (&$out, &$dict, &$seen, &$famTot, $se, $si, $files4, $ifam, $ref): void {
                    $k = $cust . '|' . $code;
                    $seen[$k] = true;
                    $ral = $files4[$k]['ral'] ?? 0.0;
                    $prep = $files4[$k]['prep'] ?? 0.0;
                    $reco = isset($files4[$k]) ? $this->recommended($files4[$k], $ref) : 0;
                    // Valeur du stock : le cube n'a plus de montant de stock en 2026, les fichiers l'ont (saisons en cours).
                    if (isset($files4[$k])) {
                        $sv = $files4[$k]['sv'];
                    }
                    $ci = $this->index($dict['cust'], $cust);
                    $out['I']['season'][] = $se;
                    $out['I']['src'][] = $si;
                    $out['I']['cust'][] = $ci;
                    $out['I']['item'][] = $dict['item'][$code];
                    $out['I']['q'][] = (int) round($q);
                    $out['I']['e'][] = (int) round($e);
                    $out['I']['st'][] = (int) round($st);
                    $out['I']['sv'][] = (int) round($sv);
                    $out['I']['ral'][] = (int) round($ral);
                    $out['I']['reco'][] = $reco;
                    $out['I']['rq'][] = (int) round($rq);
                    $fk = $cust . '|' . ($ifam[$code] ?? 0);
                    $t = $famTot[$fk] ?? [0.0, 0, 0.0];
                    $famTot[$fk] = [$t[0] + $ral, $t[1] + $reco, $t[2] + $prep];
                };
                foreach ($rows as $r) {
                    $emit((string) $r['CODE_CLIENT'], (string) $r['ARTICLE'], (float) $r['VENTES_QTE'], (float) $r['VENTES_MONTANT'],
                        (float) $r['STOCK_QTE'], (float) $r['STOCK_MONTANT'], (float) $r['VENTES_RECENTES_QTE']);
                }
                // Articles présents dans le fichier (RAL, préparation) mais sans vente ni stock dans le cube.
                foreach ($files4 as $k => $v) {
                    if (!isset($seen[$k]) && ($v['ral'] != 0.0 || $v['prep'] != 0.0)) {
                        [$cust, $code] = explode('|', $k, 2);
                        if (isset($dict['item'][$code])) {
                            $emit($cust, $code, 0.0, 0.0, 0.0, 0.0, 0.0);
                        }
                    }
                }
                foreach ($famTot as $fk => [$ral, $reco, $prep]) {
                    [$cust, $f] = explode('|', $fk);
                    if ($ral == 0.0 && $reco == 0 && $prep == 0.0) {
                        continue;
                    }
                    $out['L']['season'][] = $se;
                    $out['L']['fam'][] = (int) $f;
                    $out['L']['src'][] = $si;
                    $out['L']['cust'][] = $this->index($dict['cust'], $cust);
                    $out['L']['ral'][] = (int) round($ral);
                    $out['L']['reco'][] = (int) $reco;
                    $out['L']['prep'][] = (int) round($prep);
                }
            }
        }

        // Magasins : informations des fichiers de la dernière semaine, à défaut celles de la graine (template).
        $cinfo = [];
        foreach (array_keys($dict['cust']) as $code) {
            $code = (string) $code;
            $cinfo[] = $files['stores'][$code] ?? $seed['customers'][$code] ?? ['', '', '', $code];
        }

        $lastWeek = max($last ?: [0]);
        $json = [
            'asof' => $this->mondayAfter($lastWeek),
            'seasons' => $seasons,
            'srcs' => self::SOURCES,
            'fams' => self::FAMILIES,
            'pair' => array_map(fn (string $s): int => $this->pairIndex($seasons, $s), $seasons),
            'last' => $last,
            'weeks' => $weeks,
            'iref' => $iref,
            'irq' => $irq,
            'cust' => array_map('strval', array_keys($dict['cust'])),
            'cinfo' => $cinfo,
            'item' => array_map('strval', array_keys($dict['item'])),
            'idesc' => $dict['idesc'],
            'iline' => $dict['iline'],
            'ifam' => $dict['ifam'],
            'W' => $out['W'], 'S' => $out['S'], 'L' => $out['L'], 'I' => $out['I'],
        ];

        return ['gz' => base64_encode(gzencode(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), 6))];
    }

    /**
     * Réassort conseillé d'un magasin et d'un article : formule du fichier Push Réassort (onglet Item Detail, colonne Reco).
     * K = nombre de semaines depuis la 1re semaine de vente ou de stock ; R = taux d'écoulement ; Reco = (L + N) × R ÷ (0,03 × K) × 0,33.
     *
     * @param array{ral:float,prep:float,sold:float,stock:float,sv:float,min:int} $f
     */
    private function recommended(array $f, int $week): int
    {
        $k = $week + 1 - $f['min'];
        if ($f['min'] <= 0) {
            return 0;
        }
        if ($k > 52) {
            $k %= 48;
        }
        $den = $f['sold'] + $f['stock'] + $f['prep'];
        if ($k == 0 || $den == 0.0) {
            return 0;
        }
        $rate = $f['sold'] / $den;

        return (int) round(($f['sold'] + $f['stock']) * ($rate / (0.03 * $k)) * 0.33);
    }

    /** @return list<int> les $n dernières semaines (jusqu'à la semaine de référence incluse) */
    private function recentWeeks(array $weeks, int $ref, int $n): array
    {
        $upTo = array_values(array_filter($weeks, fn (int $w): bool => $w <= $ref));

        return array_slice($upTo, -$n) ?: [$ref];
    }

    /** @param array<string,int> $dict */
    private function index(array &$dict, string $key): int
    {
        return $dict[$key] ??= count($dict);
    }

    /** Index de la même saison de l'année précédente (SS26 → SS25), -1 si absente. */
    private function pairIndex(array $seasons, string $season): int
    {
        if (!preg_match('/^(\d{4})-(\d{2}-\w+)$/', $season, $m)) {
            return -1;
        }
        $i = array_search(($m[1] - 1) . '-' . $m[2], $seasons, true);

        return $i === false ? -1 : $i;
    }

    /** Lundi suivant la dernière semaine connue (date de dépôt du fichier suivant, utilisée comme « données au »). */
    private function mondayAfter(int $yw): string
    {
        if ($yw <= 0) {
            return '';
        }
        $d = new \DateTimeImmutable();
        $d = $d->setISODate((int) floor($yw / 100), $yw % 100, 1)->modify('+7 days');

        return $d->format('Y-m-d');
    }

    /** @param array<string,string> $params @return list<array<string,mixed>> */
    private function rows(string $file, array $params): array
    {
        $sql = strtr($this->sqlFileLoader->load('Sei/modules/suivi_sellin/' . $file . '.sql'), $params);
        $rows = [];
        foreach ($this->db->iterate($sql) as $r) {
            $rows[] = $r;
        }

        return $rows;
    }
}
