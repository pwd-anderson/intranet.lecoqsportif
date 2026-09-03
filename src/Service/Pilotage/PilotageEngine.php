<?php

namespace App\Service\Pilotage;

/**
 * Portage PHP du moteur de calcul JS de templates/pilotage/pilotage.html.twig
 * (fonction compute()). Doit rester strictement identique à la version JS :
 * toute évolution des règles de calcul doit être répercutée des deux côtés.
 */
class PilotageEngine
{
    private const TRANSIT = 80;
    private const TRANSIT_IKS = 30;
    private const XF_DEF = 90;
    private const COLLECTIONS = ['2026-02-FW', '2027-01-SS', '2027-02-FW'];
    private const COLL_PROD_LANCEE = ['2026-02-FW'];
    private const HUBS = ['WSFCN', 'WDTTH'];
    private const DIRECT = 'WFIFR';
    private const FFOB_SITES = ['WSFCN', 'WDTTH', 'WFIFR'];

    /**
     * @return array{orders: array[], detail: array[]}
     */
    public function compute(array $clientRows, array $fournRows, array $stockRows): array
    {
        $today = new \DateTimeImmutable('today');

        // ── Backlog clients ──────────────────────────────────────────────
        $cl = [];
        foreach ($clientRows as $r) {
            $coll = (string) ($r->{'COLLECTION'} ?? '');
            if (!in_array($coll, self::COLLECTIONS, true)) {
                continue;
            }
            $sku = (string) ($r->{'SKU'} ?? '');
            if ($sku === '' || preg_match('/_(SMS|SPL)/i', $sku)) {
                continue;
            }
            $q = $this->toNum($r->{'QUANTITE'} ?? null);
            if ($q <= 0) {
                continue;
            }
            $net = strtoupper(trim((string) ($r->{'MAINNETWORK'} ?? '')));
            $cat = match (true) {
                $net === 'WHOLESALE FRANCE' => 'WHOLESALE FRANCE',
                $net === 'WHOLESALE INTERNATIONAL' || $net === 'WHOLESALE EUROPE' => 'INTERNATIONAL',
                default => null,
            };
            if ($cat === null) {
                continue;
            }
            $site = trim((string) ($r->{'SITE'} ?? ''));

            $cl[] = [
                'cat' => $cat, 'site' => $site, 'sku' => $sku, 'qte' => $q,
                'ord' => trim((string) ($r->{'NO COMMANDE'} ?? '')),
                'ref' => $r->{'REF. COMMANDE'} ?? '',
                'cf' => trim((string) ($r->{'NOM CLIENT'} ?? '')),
                'mag' => trim(trim((string) ($r->{'CODE CLIENT CMD.'} ?? '')) . ' - ' . trim((string) ($r->{'NOM CLIENT CMD.'} ?? ''))),
                'des' => $r->{'DESIGNATION'} ?? '',
                'coll' => $coll,
                'drop' => strtoupper(trim((string) ($r->{'DROPPE'} ?? 'NON'))),
                'dliv' => $this->toDate($r->{'DATE LIVRAISON'} ?? null),
                'dcmd' => $this->toDate($r->{'DATE COMMANDE'} ?? null),
                'eur' => $this->toNum($r->{'PRIX EUR'} ?? null),
                'direct' => $site === self::DIRECT,
                'ffob' => in_array($site, self::FFOB_SITES, true),
            ];
        }

        // ── Backlog fournisseur ──────────────────────────────────────────
        $supply = [];
        foreach ($fournRows as $r) {
            $coll = (string) ($r->{'COLLECTION'} ?? '');
            if (!in_array($coll, self::COLLECTIONS, true)) {
                continue;
            }
            $art = (string) ($r->{'ARTICLE'} ?? '');
            if ($art === '' || preg_match('/_(SMS|SPL)/i', $art)) {
                continue;
            }
            $flux = trim((string) ($r->{'TYPE FLUX'} ?? ''));
            $exp = trim((string) ($r->{'SITE EXPEDITION'} ?? ''));
            $isBL = $flux === 'BL Fournisseur';
            if (!$isBL && !in_array($exp, self::HUBS, true)) {
                continue;
            }
            $q = $this->toNum($r->{'QTE A LIVRER'} ?? null);
            if ($q <= 0) {
                continue;
            }
            $fourn = trim((string) ($r->{'NOM FOURN.'} ?? ''));
            $podate = $this->toDate($r->{'DATE DE COMMANDE'} ?? null);
            $xf = $this->toDate($r->{'DATE EXPEDITION'} ?? null);
            $est = false;
            if ($xf === null && $podate !== null) {
                $xf = $this->addDays($podate, self::XF_DEF);
                $est = true;
            }
            if ($xf === null) {
                continue;
            }
            $iks = (bool) preg_match('/I\.?K\.?S/i', $fourn);
            $transit = ($isBL && $iks) ? self::TRANSIT_IKS : self::TRANSIT;
            $eta = $this->addDays($xf, $transit);
            $orig = $isBL
                ? ('En production' . ($est ? ' (date IC estimée : PO + 3 mois)' : ''))
                : ('Parti de ' . $exp . ' (transfert interne)');

            $supply[$art] ??= [];
            $supply[$art][] = [
                'po' => trim((string) ($r->{'NO COMMANDE'} ?? '')),
                'fourn' => $fourn,
                'ref' => trim((string) ($r->{'REF. INTERNE'} ?? '')),
                'xf' => $xf, 'eta' => $eta, 'rem' => $q, 'orig' => $orig,
                'type' => $isBL ? 'PO usine' : 'Stock Asia',
            ];
        }
        foreach ($supply as &$arr) {
            usort($arr, fn($x, $y) => $x['eta'] <=> $y['eta']);
        }
        unset($arr);

        // ── Stock ─────────────────────────────────────────────────────────
        $stock = [];
        foreach ($stockRows as $r) {
            if (trim((string) ($r->{'STATUS STOCK'} ?? '')) !== 'A1') {
                continue;
            }
            $site = trim((string) ($r->{'SITE'} ?? ''));
            $art = trim((string) ($r->{'ARTICLE'} ?? ''));
            if ($site === '' || $art === '') {
                continue;
            }
            $q = $this->toNum($r->{'STOCK INTERNE'} ?? null);
            if ($q <= 0) {
                continue;
            }
            $k = $site . '|' . $art;
            $stock[$k] = ($stock[$k] ?? 0) + $q;
        }

        // ── Priorisation clients (plus gros total qté d'abord) ─────────────
        $tot = [];
        foreach ($cl as $r) {
            $tot[$r['cf']] = ($tot[$r['cf']] ?? 0) + $r['qte'];
        }
        arsort($tot);
        $rank = array_flip(array_keys($tot));

        usort($cl, function ($a, $b) use ($rank) {
            $ra = $rank[$a['cf']] ?? PHP_INT_MAX;
            $rb = $rank[$b['cf']] ?? PHP_INT_MAX;
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            $da = $a['dliv']?->getTimestamp() ?? PHP_INT_MAX;
            $db = $b['dliv']?->getTimestamp() ?? PHP_INT_MAX;
            if ($da !== $db) {
                return $da <=> $db;
            }
            $ca = $a['dcmd']?->getTimestamp() ?? PHP_INT_MAX;
            $cb = $b['dcmd']?->getTimestamp() ?? PHP_INT_MAX;
            if ($ca !== $cb) {
                return $ca <=> $cb;
            }
            if ($a['ord'] !== $b['ord']) {
                return $a['ord'] <=> $b['ord'];
            }
            return $a['sku'] <=> $b['sku'];
        });

        // ── Affectation du stock (site du client) ──────────────────────────
        $take = function (string $site, string $sku, float $need) use (&$stock): float {
            if ($need <= 0) {
                return 0.0;
            }
            $k = $site . '|' . $sku;
            $avail = $stock[$k] ?? 0;
            if ($avail <= 0) {
                return 0.0;
            }
            $t = min($need, $avail);
            $stock[$k] = $avail - $t;
            return $t;
        };
        foreach ($cl as &$r) {
            $r['qs'] = ($r['direct'] || in_array($r['site'], self::HUBS, true)) ? 0.0 : $take($r['site'], $r['sku'], $r['qte']);
        }
        unset($r);

        // ── Affectation stock Asie + production ─────────────────────────────
        $etaHub = $this->addDays($today, self::TRANSIT);
        $srcByOrder = [];

        foreach ($cl as &$r) {
            $ffob = $r['ffob'];
            $need = $r['qte'] - $r['qs'];
            $qp = 0.0;
            $onT = $r['qs'];
            $late = 0.0;
            $lateW = 0.0;
            $etasOn = [];
            $etasLate = [];
            $srcs = [];
            $types = [];
            $pos = [];

            if ($r['qs'] > 0) {
                $srcs[] = ['t' => 'Stock local', 'lab' => 'STOCK ' . $r['site'], 'q' => $r['qs'], 'eta' => $today, 'xf' => null,
                    'st' => 'Déjà en stock, livrable immédiatement', 'on' => true];
                $types['Stock local'] = true;
            }

            if ($need > 0 && !$r['direct']) {
                $hubs = in_array($r['site'], self::HUBS, true)
                    ? array_values(array_unique(array_merge([$r['site']], array_diff(self::HUBS, [$r['site']]))))
                    : self::HUBS;
                foreach ($hubs as $h) {
                    if ($need <= 0) {
                        continue;
                    }
                    $t = $take($h, $r['sku'], $need);
                    if ($t > 0) {
                        $need -= $t;
                        $qp += $t;
                        $e = $ffob ? $today : $etaHub;
                        $on = $ffob ? true : ($r['dliv'] !== null && $e <= $r['dliv']);
                        if ($on) {
                            $etasOn[] = $e;
                            $onT += $t;
                        } else {
                            $etasLate[] = $e;
                            $late += $t;
                            $lateW += max(0, $this->diffDays($e, $r['dliv'])) * $t;
                        }
                        $srcs[] = ['t' => 'Stock Asia', 'lab' => 'STOCK ' . $h, 'q' => $t, 'eta' => $e, 'xf' => null,
                            'st' => $ffob
                                ? ('Disponible à ' . $h . ' — FFOB, à enlever par le client')
                                : ('Déjà produit, en stock à ' . $h . ' (Asie) — transit ' . self::TRANSIT . ' j'),
                            'on' => $on];
                        $types['Stock Asia'] = true;
                    }
                }
            }

            $arr2 = $supply[$r['sku']] ?? null;
            if ($arr2 !== null && $ffob) {
                $arr2 = $arr2;
                usort($arr2, fn($x, $y) => ($x['xf'] ?? $x['eta']) <=> ($y['xf'] ?? $y['eta']));
            }
            if ($arr2 !== null && $need > 0) {
                foreach ($arr2 as &$sp) {
                    if ($need <= 0) {
                        break;
                    }
                    if ($sp['rem'] <= 0) {
                        continue;
                    }
                    $t = min($need, $sp['rem']);
                    $sp['rem'] -= $t;
                    $need -= $t;
                    $qp += $t;
                    $e = $ffob ? $sp['xf'] : $sp['eta'];
                    $on = $r['dliv'] !== null && $e <= $r['dliv'];
                    if ($on) {
                        $etasOn[] = $e;
                        $onT += $t;
                    } else {
                        $etasLate[] = $e;
                        $late += $t;
                        $lateW += max(0, $this->diffDays($e, $r['dliv'])) * $t;
                    }
                    $srcs[] = ['t' => $sp['type'], 'lab' => $sp['po'], 'q' => $t, 'eta' => $e, 'xf' => $sp['xf'],
                        'st' => ($ffob ? ('FFOB — mise à disposition XF ' . $this->fmtD($sp['xf']) . ' · ') : '') . $sp['orig'] . ($sp['fourn'] ? (' — ' . $sp['fourn']) : ''),
                        'on' => $on];
                    $types[$sp['type']] = true;
                    if ($sp['type'] === 'PO usine') {
                        $pos[$sp['po']] = true;
                    }
                }
                unset($sp);
                // Reporter la consommation dans la source d'origine (rem)
                if (isset($supply[$r['sku']])) {
                    $supply[$r['sku']] = $arr2;
                }
            }

            $r['qp'] = $qp;
            $r['qa'] = $need;
            $r['on'] = $onT;
            $r['late'] = $late;
            $r['lateW'] = $lateW;
            $r['etaOnArr'] = $etasOn;
            $r['etaLateArr'] = $etasLate;

            $allE = array_values(array_filter(array_map(fn($s) => $s['eta'], $srcs)));
            $r['eta'] = $allE === [] ? null : array_reduce($allE, fn($carry, $d) => ($carry === null || $d > $carry) ? $d : $carry);
            $r['ret'] = ($r['eta'] !== null && $r['dliv'] !== null) ? max(0, $this->diffDays($r['eta'], $r['dliv'])) : null;
            $r['cov'] = $r['qte'] ? ($r['qs'] + $r['qp']) / $r['qte'] : 0;
            $r['srcTxt'] = implode(' | ', array_map(
                fn($s) => $s['lab'] . ' · ' . $this->fmtN($s['q']) . ' pcs' . ($s['xf'] ? (' · IC ' . $this->fmtD($s['xf'])) : '') . ' · ETA ' . $this->fmtD($s['eta']) . ' · ' . $s['st'],
                $srcs
            ));
            $r['types'] = array_keys($types);
            $r['pos'] = array_keys($pos);
            $r['st'] = $r['drop'] === 'OUI'
                ? 'ANNULER (droppé)'
                : ($r['qa'] >= $r['qte'] ? 'ANNULER' : ($r['qa'] > 0 ? 'PARTIEL' : ($qp === 0.0 ? 'LIVRABLE' : ($late > 0 ? 'RETARD' : 'OK DELAI'))));

            $srcByOrder[$r['ord']] ??= [];
            foreach ($srcs as $s) {
                $found = null;
                foreach ($srcByOrder[$r['ord']] as $idx => $acc) {
                    if ($acc['lab'] === $s['lab'] && $acc['eta'] !== null && $s['eta'] !== null && $acc['eta'] == $s['eta']) {
                        $found = $idx;
                        break;
                    }
                }
                if ($found !== null) {
                    $srcByOrder[$r['ord']][$found]['q'] += $s['q'];
                    $srcByOrder[$r['ord']][$found]['nsku']++;
                } else {
                    $s['nsku'] = 1;
                    $srcByOrder[$r['ord']][] = $s;
                }
            }
        }
        unset($r);

        // ── Agrégation par commande ─────────────────────────────────────────
        $om = [];
        foreach ($cl as $r) {
            if (!isset($om[$r['ord']])) {
                $om[$r['ord']] = [
                    'cat' => $r['cat'], 'cf' => $r['cf'], 'mag' => $r['mag'], 'ord' => $r['ord'], 'ref' => $r['ref'],
                    'coll' => $r['coll'], 'dliv' => $r['dliv'],
                    'qte' => 0.0, 'on' => 0.0, 'late' => 0.0, 'qs' => 0.0, 'qp' => 0.0, 'qa' => 0.0, 'eur' => 0.0, 'nl' => 0,
                    'ffob' => false, 'etaOn' => [], 'etaLate' => [], 'retW' => 0.0, 'retQ' => 0.0, 'types' => [], 'pos' => [],
                ];
            }
            $o = &$om[$r['ord']];
            $o['qte'] += $r['qte'];
            $o['on'] += $r['on'];
            $o['late'] += $r['late'];
            $o['qs'] += $r['qs'];
            $o['qp'] += $r['qp'];
            $o['qa'] += $r['qa'];
            $o['eur'] += $r['eur'];
            $o['nl']++;
            if ($r['ffob']) {
                $o['ffob'] = true;
            }
            if ($o['dliv'] === null || ($r['dliv'] !== null && $r['dliv'] < $o['dliv'])) {
                $o['dliv'] = $r['dliv'];
            }
            $o['retW'] += $r['lateW'];
            $o['retQ'] += $r['late'];
            foreach ($r['types'] as $t) {
                $o['types'][$t] = true;
            }
            foreach ($r['pos'] as $p) {
                $o['pos'][$p] = true;
            }
            unset($o);
        }
        foreach ($cl as $r) {
            $om[$r['ord']]['etaOn'] = array_merge($om[$r['ord']]['etaOn'], $r['etaOnArr']);
            $om[$r['ord']]['etaLate'] = array_merge($om[$r['ord']]['etaLate'], $r['etaLateArr']);
        }

        $order = ['Stock local', 'Stock Asia', 'PO usine'];
        $orders = [];
        foreach ($om as $o) {
            $covq = $o['on'] + $o['late'];
            $o['pctLate'] = $covq ? $o['late'] / $covq : 0;
            $o['pctOn'] = $covq ? $o['on'] / $covq : 0;
            $o['stat'] = ($covq === 0.0 && in_array($o['coll'], self::COLL_PROD_LANCEE, true))
                ? '⚫ À annuler'
                : ($o['pctLate'] == 0 ? '🟢 On time' : ($o['pctLate'] >= 0.5 ? '🔴 Critique +50% retard' : '🟠 Partiellement en retard'));
            $o['cov'] = $o['qte'] ? ($o['qs'] + $o['qp']) / $o['qte'] : 0;
            $o['retMoy'] = $o['retQ'] ? $o['retW'] / $o['retQ'] : null;
            $o['etaOn1'] = $o['etaOn'] === [] ? null : min($o['etaOn']);
            $o['etaOn2'] = $o['etaOn'] === [] ? null : max($o['etaOn']);
            $o['etaL1'] = $o['etaLate'] === [] ? null : min($o['etaLate']);
            $o['etaL2'] = $o['etaLate'] === [] ? null : max($o['etaLate']);
            $o['nsrc'] = count($srcByOrder[$o['ord']] ?? []);
            $typeKeys = array_keys($o['types']);
            usort($typeKeys, fn($a, $b) => array_search($a, $order) <=> array_search($b, $order));
            $o['tsrc'] = $typeKeys === [] ? '—' : implode(' + ', $typeKeys);
            $o['posTxt'] = implode(' ; ', array_keys($o['pos']));
            unset($o['types'], $o['pos'], $o['etaOn'], $o['etaLate']);
            $orders[] = $o;
        }

        return ['orders' => $orders, 'detail' => $cl];
    }

    private function toDate(mixed $v): ?\DateTimeImmutable
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $s, $m)) {
            return (new \DateTimeImmutable())->setDate((int) $m[1], (int) $m[2], (int) $m[3])->setTime(0, 0);
        }
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})/', $s, $m)) {
            $y = (int) $m[3];
            if ($y < 100) {
                $y += 2000;
            }
            return (new \DateTimeImmutable())->setDate($y, (int) $m[2], (int) $m[1])->setTime(0, 0);
        }
        try {
            $d = new \DateTimeImmutable($s);
            return $d->setTime(0, 0);
        } catch (\Exception) {
            return null;
        }
    }

    private function toNum(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        $s = str_replace([' ', ','], ['', '.'], (string) $v);
        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function diffDays(\DateTimeImmutable $a, \DateTimeImmutable $b): int
    {
        $diff = (int) round(($a->getTimestamp() - $b->getTimestamp()) / 86400);
        return $diff;
    }

    private function addDays(\DateTimeImmutable $d, int $n): \DateTimeImmutable
    {
        return $d->modify(($n >= 0 ? '+' : '') . $n . ' days');
    }

    public function fmtD(?\DateTimeImmutable $d): string
    {
        return $d ? $d->format('d/m/Y') : '';
    }

    public function fmtN(float $v): string
    {
        return number_format(round($v), 0, ',', ' ');
    }
}
