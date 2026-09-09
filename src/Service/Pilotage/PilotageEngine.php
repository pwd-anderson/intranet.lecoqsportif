<?php

namespace App\Service\Pilotage;

/**
 * Portage PHP du moteur de calcul JS V2 ("Moulinette V10") de
 * templates/pilotage/pilotage.html.twig (fonction computeV9()).
 *
 * Doit rester strictement synchronisé avec la version JS : toute évolution
 * des règles de calcul (transit, ETA, statuts, substitutions…) doit être
 * répercutée dans les deux fichiers.
 *
 * Contrairement au moteur JS (qui lit des classeurs Excel avec correspondance
 * de colonnes approximative via pick()/findKey()), ce portage lit directement
 * les propriétés nommées exactement comme les alias SQL de Pilotage.php
 * (backlog_client_pilotage.sql, backlog_fournisseur_pilotage.sql, stock_pilotage.sql).
 */
class PilotageEngine
{
    private const DAY = 86400;

    // ── Paramètres métier (objet R du JS) ───────────────────────────────
    private const GRACE = 10;
    private const FR_STOCK_DELAY = 5;
    private const FR_PO_DELAY = 5;
    private const FR_ASIA_DELAY = 90;
    private const SS27_PO_DELAY = 80;
    private const INT_PREP_DELAY = 10;
    private const INT_PO_ASIA_DELAY = 10;
    private const INT_FR_DELAY = 30;
    private const TOP_CLIENTS = 10;

    private const SITES_LOG = ['WLOGM'];
    private const SITES_FRO = ['WHSLM', 'WFIFR'];
    private const SITE_SF = 'WSFCN';
    private const SITE_DT = 'WDTTH';

    private const NETS_FR = ['WHOLESALE FRANCE'];
    private const NETS_FR_DOM = ['E-COMMERCE', 'RETAIL', 'MARKETING CONTRACTS'];
    private const FR_USES_ASIA_STOCK = true;
    private const FR_ASIA_GROUPS = ['SF', 'DT'];

    private const SUBST_MAX_CAND = 5;
    private const SUBST_R2_MAX = 99;

    private const STOCK_LABEL = [
        'LOG' => 'Stock Logtex',
        'FRO' => 'Stock France (WHSLM/WFIFR)',
        'SF' => 'Stock SF Chine',
        'DT' => 'Stock DT Thaïlande',
        'ASO' => 'Stock Asie (autre)',
    ];
    private const GRP_SRC = [
        'LOG' => 'Logtex (WLOGM)',
        'FRO' => 'Autres FR (WHSLM/WFIFR)',
        'SF' => 'SF Chine (WSFCN)',
        'DT' => 'DT Thaïlande (WDTTH)',
        'ASO' => 'Autres Asie',
    ];

    // ── En-têtes de sortie (identiques au JS) ───────────────────────────
    public const HEAD_FR = [
        'COLLECTION', 'CATÉGORIE', 'CLIENT FACTURÉ', 'N° COMMANDE', 'QTÉ RESTANTE À LIVRER',
        'PCS DISPO LOGTEX (allouées)', '% COUV. LOGTEX', 'STATUT ACTION (pousser Logtex)', 'SYNTHÈSE — À DIRE AU CLIENT',
        'ETA PROCHAINE ARRIVÉE', 'ETA COMMANDE COMPLÈTE', 'DATE LIVRAISON DEMANDÉE', "RETARD ÉCHU (j) — vs AUJOURD'HUI",
        'MONTANT EUR', 'QTÉ À TEMPS', 'QTÉ EN RETARD', 'QTÉ À ANNULER', '% À ANNULER', 'QTÉ ALLOUÉE SUR STOCK',
        'QTÉ ALLOUÉE SUR PROD (PO)', 'TYPE DE SOURCE', 'PCS SUBSTITUABLES', '1ʳᵉ ARRIVÉE À TEMPS', 'DERN. ARRIVÉE À TEMPS',
        '1ʳᵉ ARRIVÉE RETARD', 'DERN. ARRIVÉE RETARD', 'MAGASIN / CLIENT CMD', 'RÉF. CMD CLIENT', 'CODE CLIENT', 'GROUP CODE',
        'NOM GROUPEMENT', 'CODE MAGASIN (client cmd)', 'VILLE LIVRAISON', 'REPRÉSENTANT', 'DATE COMMANDE',
        'PO RATTACHÉS (allocation)', '% COUVERTURE À TERME', 'RETARD PRÉVISIONNEL (j) — ETA vs DATE DEMANDÉE Client',
    ];
    public const HEAD_INT = [
        'COLLECTION', 'CATÉGORIE', 'CLIENT FACTURÉ', 'N° COMMANDE', 'QTÉ RESTANTE À LIVRER',
        'PCS DISPO SF/DT (allouées, +10 j)', '% COUV. SF/DT', 'STATUT ACTION (expédition SF/DT)', 'SYNTHÈSE — À DIRE AU CLIENT',
        'ETA PROCHAINE ARRIVÉE', 'ETA COMMANDE COMPLÈTE', 'DATE DEMANDÉE', "RETARD ÉCHU (j) — vs AUJOURD'HUI",
        'MONTANT EUR', 'QTÉ À TEMPS', 'QTÉ EN RETARD', 'QTÉ À ANNULER', '% À ANNULER', 'QTÉ ALLOUÉE SUR STOCK',
        'QTÉ ALLOUÉE SUR PROD (PO)', 'TYPE DE SOURCE', 'PCS SUBSTITUABLES', '1ʳᵉ ARRIVÉE À TEMPS', 'DERN. ARRIVÉE À TEMPS',
        '1ʳᵉ ARRIVÉE RETARD', 'DERN. ARRIVÉE RETARD', 'MAGASIN / CLIENT CMD', 'RÉF. CMD CLIENT', 'CODE CLIENT', 'GROUP CODE',
        'NOM GROUPEMENT', 'CODE MAGASIN (client cmd)', 'VILLE LIVRAISON', 'REPRÉSENTANT', 'DATE COMMANDE',
        'PO RATTACHÉS (allocation)', 'RETARD PRÉVISIONNEL (j) — ETA vs DATE DEMANDÉE Client',
    ];
    public const HEAD_MACRO = [
        'SEGMENT', 'COLLECTION', '🟢 PRÊT À ENVOYER (pcs)', '🟢 CMD PRÊTES', '🟢 VALEUR PRÊT (€)',
        '🟡 PARTIEL (pcs)', '🟡 CMD PARTIEL', 'RETARD PRÉV. MOYEN (j)', 'NB CMD EN RETARD (PRÉV.)', 'VALEUR EUR', 'TOTAL CMD',
    ];
    public const HEAD_REST = ['SKU', 'COLLECTION', 'DESCRIPTION ARTICLE', 'SOURCE', 'PCS', 'DISPO', 'ETA'];
    public const HEAD_SUBST = [
        'COMMANDE', 'CLIENT', 'STATUT CMD', 'SKU MANQUANT', 'DÉSIGNATION', 'QTÉ MANQUANTE', 'RANG', 'TYPE DE SUBSTITUTION',
        'SKU SUBSTITUT', 'DÉSIGNATION SUBSTITUT', 'PCS DISPO MAINTENANT', 'PCS À TERME', 'ETA À TERME', 'QTÉ PROPOSÉE',
        'QTÉ RETENUE (SAISIE)',
    ];
    public const HEAD_DET = ['N° COMMANDE', 'SKU', 'DÉSIGNATION', 'QTÉ', 'ALLOUÉE', 'SOURCES → ARRIVÉES', 'À TEMPS', 'RETARD', 'ANNULÉ'];

    /** Ordre des champs canoniques constituant chaque ligne de pilotage. */
    private const FIELDS_FR = [
        'coll', 'cat', 'cli', 'ord', 'q', 'dispo', 'pct', 'stat', 'syn', 'eta1', 'etac', 'ddem', 'rechu', 'mt',
        'qot', 'qlate', 'qcanc', 'pcanc', 'astk', 'aprod', 'src', 'subq', 'a1t', 'a2t', 'a1r', 'a2r', 'mag', 'ref',
        'codc', 'grpc', 'grp', 'codm', 'ville', 'rep', 'dcmd', 'po', 'covterme', 'rprev',
    ];
    private const FIELDS_INT = [
        'coll', 'cat', 'cli', 'ord', 'q', 'dispo', 'pct', 'stat', 'syn', 'eta1', 'etac', 'ddem', 'rechu', 'mt',
        'qot', 'qlate', 'qcanc', 'pcanc', 'astk', 'aprod', 'src', 'subq', 'a1t', 'a2t', 'a1r', 'a2r', 'mag', 'ref',
        'codc', 'grpc', 'grp', 'codm', 'ville', 'rep', 'dcmd', 'po', 'rprev',
    ];

    /** Texte de l'onglet « Méthode de calcul » (METHODE_V10 côté JS). */
    public const METHODE_V10 = [
        ['Périmètre', 'Une ligne de pilotage = un groupe commande × collection. Ligne retenue si DROPPÉ ≠ OUI, quantité > 0 et date de livraison renseignée. Le montant EUR additionne toutes les lignes du groupe, lignes droppées comprises.'],
        ['Réseaux', 'France affichée = WHOLESALE FRANCE. E-commerce, retail et marketing contracts sont alloués avec la France mais ne sont pas affichés. Tout le reste part en international.'],
        ['Sources', 'Stock : colonne « Stock Interne », sites commençant par W — Logtex (WLOGM), autres FR (WHSLM, WFIFR), SF Chine (WSFCN), DT Thaïlande (WDTTH). Production : backlog fournisseur filtré sur TYPE FLUX = BL Fournisseur, INTERSITE = NON, DROPPÉ = NON, site de réception en W.'],
        ['Dates d’arrivée', "T0 = date du jour de calcul. France : stock Logtex et FR à T0 + 5 j · PO vers sites FR à ETA + 5 j (ETA passée ramenée à T0) · stock et PO Asie à XF + 90 j. International : stock SF/DT + 10 j · PO Asie ETA + 10 j · stock Logtex + 30 j · PO France ETA + 30 j."],
        ['Règle SS27', 'Collection 2027-01-SS : arrivée = XF usine + 80 j, quel que soit le site de réception, sans écrêtage à T0.'],
        ['Allocation', "Exclusive : une pièce ne sert qu'une commande. Le module France consomme en premier, l'international ensuite. Priorité : top 10 clients par € du module, puis date demandée, puis n° de commande. Deux passes par commande — passe 1 « à temps » (arrivée ≤ date demandée + 10 j), puis passe 2 au plus tôt. Candidats triés par arrivée croissante, stock avant PO."],
        ['Annulation', 'Vrai zéro seulement : plus aucune pièce disponible pour ce SKU, tous sites confondus, après les deux passes.'],
        ['Retard échu', "Date du jour de calcul moins date demandée. Masqué pour les commandes SS27 dont la date demandée est antérieure au mois précédant le début de la saison (anomalie ERP), pour les commandes France annulées et pour les commandes internationales à 100 % disponibles."],
        ['Substitutions', 'Propositions exclusives puisées dans le reste à allouer, même collection obligatoire, 5 candidats au maximum par SKU manquant. Rang 1 = même article, autre taille (taille la plus proche d’abord). Rang 2 = même modèle (premier mot de la désignation), autre coloris, accord client requis.'],
        ['Reste à allouer', 'Stock et PO non consommés après les deux passes, par SKU × source, limité aux collections présentes dans le backlog client. L’ETA affichée est l’ETA brute du PO, sans délai de transport.'],
        ['Écarts connus', "Les colonnes de périmètre, d'identité client et de montant sont reproduites à l'identique. Les quantités allouées et les dates d'arrivée restent à quelques pour cent du classeur de référence : les départages entre commandes à égalité de priorité sur un SKU en pénurie ne sont pas tous reproductibles."],
    ];

    /**
     * @return array{
     *   pfrHead: string[], pfr: array[], pintHead: string[], pint: array[],
     *   detFR: array<string,array[]>, detIN: array<string,array[]>,
     *   subFR: array{head: string[], rows: array[]}, subIN: array{head: string[], rows: array[]},
     *   restHead: string[], rest: array[],
     *   macroHead: string[], macro: array[],
     *   methode: array[],
     *   ctrl: array{demande: float, onT: float, late: float, canc: float},
     * }
     */
    public function compute(array $clientRows, array $fournRows, array $stockRows): array
    {
        $now = new \DateTimeImmutable('today');
        $tgen = $this->utcDay((int) $now->format('Y'), (int) $now->format('n'), (int) $now->format('j'));
        $tarr = $tgen; // mode CLI : pas de saisie utilisateur, T0 = date du jour de calcul

        $collSet = [];
        $seasonStart = function (?string $coll): ?int {
            if (!preg_match('/^(\d{4})-(\d{2})/', (string) $coll, $m)) {
                return null;
            }
            return $this->utcDay((int) $m[1], $m[2] === '01' ? 1 : 7, 1);
        };
        $isFut = fn (?string $coll) => ($t = $seasonStart($coll)) !== null && $t > $tgen;
        $preSeasonCutoff = function (?string $coll) use ($seasonStart) {
            $t = $seasonStart($coll);
            if ($t === null) {
                return null;
            }
            $d = (new \DateTimeImmutable('@' . $t))->setTimezone(new \DateTimeZone('UTC'))->modify('-1 month');
            return $this->utcDay((int) $d->format('Y'), (int) $d->format('n'), (int) $d->format('j'));
        };

        $flow = [];

        // ── STOCK : Stock Interne, sites W ──────────────────────────────
        $stock = [];
        $collBySku = [];
        foreach ($stockRows as $r) {
            $site = $this->nrm($r->{'SITE'} ?? null);
            if (!str_starts_with($site, 'W')) {
                continue;
            }
            if (trim((string) ($r->{'STATUS STOCK'} ?? '')) !== 'A1') {
                continue;
            }
            $sku = trim((string) ($r->{'ARTICLE'} ?? ''));
            if ($sku === '') {
                continue;
            }
            $coll = trim((string) ($r->{'COLLECTION'} ?? ''));
            if ($coll !== '' && !isset($collBySku[$sku]) && preg_match('/\d{4}-\d{2}/', $coll)) {
                $collBySku[$sku] = $coll;
            }
            $grp = match (true) {
                in_array($site, self::SITES_LOG, true) => 'LOG',
                in_array($site, self::SITES_FRO, true) => 'FRO',
                $site === self::SITE_SF => 'SF',
                $site === self::SITE_DT => 'DT',
                default => 'ASO',
            };
            $q = $this->num($r->{'STOCK INTERNE'} ?? null);
            if ($q > 0) {
                $stock[$sku] ??= ['LOG' => 0.0, 'FRO' => 0.0, 'SF' => 0.0, 'DT' => 0.0, 'ASO' => 0.0];
                $stock[$sku][$grp] += $q;
            }
        }

        // ── BACKLOG FOURNISSEUR ──────────────────────────────────────────
        $pos = [];
        foreach ($fournRows as $r) {
            if ($this->nrm($r->{'TYPE FLUX'} ?? null) !== 'BL FOURNISSEUR') {
                continue;
            }
            if ($this->nrm($r->{'INTERSITE'} ?? null) === 'OUI') {
                continue;
            }
            if ($this->nrm($r->{'DROPPE'} ?? null) === 'OUI') {
                continue;
            }
            $rec = $this->nrm($r->{'SITE RECEPTION'} ?? null);
            if (!str_starts_with($rec, 'W')) {
                continue;
            }
            $sku = trim((string) ($r->{'ARTICLE'} ?? ''));
            $q = $this->num($r->{'QTE A LIVRER'} ?? null);
            if ($sku === '' || $q <= 0) {
                continue;
            }
            $eta = $this->toT($r->{'DATE LIVRAISON'} ?? null);
            $xf = $this->toT($r->{'DATE EXPEDITION'} ?? null);
            $rawEta = $eta;
            if ($eta !== null && $eta < $tarr) {
                $eta = $tarr;
            }
            $dest = (in_array($rec, self::SITES_LOG, true) || in_array($rec, self::SITES_FRO, true)) ? 'FR' : 'ASIE';
            $coll = trim((string) ($r->{'COLLECTION'} ?? ''));
            if ($coll !== '' && !isset($collBySku[$sku]) && preg_match('/\d{4}-\d{2}/', $coll)) {
                $collBySku[$sku] = $coll;
            }
            $po = new \stdClass();
            $po->sku = $sku;
            $po->q = $q;
            $po->left = $q;
            $po->eta = $eta ?? $tarr;
            $po->rawEta = $rawEta;
            $po->xf = $xf;
            $po->dest = $dest;
            $po->po = trim((string) ($r->{'NO COMMANDE'} ?? ''));
            $pos[] = $po;
        }

        // ── BACKLOG CLIENT : groupes commande × collection ──────────────
        $orders = [];
        $demTot = 0.0;
        foreach ($clientRows as $r) {
            $ord = trim((string) ($r->{'NO COMMANDE'} ?? ''));
            if ($ord === '') {
                continue;
            }
            $net = $this->nrm($r->{'MAINNETWORK'} ?? null);
            if (in_array($net, self::NETS_FR, true)) {
                $mod = 'FR';
                $show = true;
            } elseif (in_array($net, self::NETS_FR_DOM, true)) {
                $mod = 'FR';
                $show = false;
            } else {
                $mod = 'INT';
                $show = true;
            }
            $coll = trim((string) ($r->{'COLLECTION'} ?? ''));
            if ($coll !== '') {
                $collSet[$coll] = true;
            }
            $key = $ord . '|' . $coll . '|' . $mod;
            if (!isset($orders[$key])) {
                $o = new \stdClass();
                $o->ord = $ord;
                $o->coll = $coll;
                $o->mod = $mod;
                $o->show = $show;
                $o->nets = [];
                $o->dl = null;
                $o->cli = '';
                $o->mag = '';
                $o->ref = '';
                $o->codc = null;
                $o->grpc = null;
                $o->grp = '';
                $o->codm = null;
                $o->ville = '';
                $o->rep = '';
                $o->dcmd = null;
                $o->mt = 0.0;
                $o->q = 0.0;
                $o->lines = [];
                $o->row = null;
                $o->subNow = 0.0;
                $orders[$key] = $o;
            }
            $o = $orders[$key];
            if ($net !== '') {
                $o->nets[$net] = ($o->nets[$net] ?? 0) + 1;
            }
            $o->mt += $this->num($r->{'PRIX EUR'} ?? null);
            $fill = function (string $k, $v) use ($o): void {
                if (($o->$k === '' || $o->$k === null) && $v !== null && $v !== '') {
                    $o->$k = $v;
                }
            };
            $fill('cli', trim((string) ($r->{'NOM CLIENT'} ?? '')));
            $fill('mag', trim((string) ($r->{'NOM CLIENT CMD.'} ?? '')));
            $fill('ref', trim((string) ($r->{'REF. COMMANDE'} ?? '')));
            $fill('codc', $r->{'CLIENT'} ?? null);
            $fill('grpc', $r->{'GROUP CODE'} ?? null);
            $fill('grp', trim((string) ($r->{'NOM GROUPEMENT'} ?? '')));
            $fill('codm', $r->{'CODE CLIENT CMD.'} ?? null);
            $fill('ville', trim((string) ($r->{'VILLE'} ?? '')));
            $fill('rep', trim((string) ($r->{'REPRESENTANT 1'} ?? '')));
            if ($o->dcmd === null) {
                $o->dcmd = $this->toT($r->{'DATE COMMANDE'} ?? null);
            }
            if ($this->nrm($r->{'DROPPE'} ?? null) === 'OUI') {
                continue;
            }
            $q = $this->num($r->{'QUANTITE'} ?? null);
            if ($q <= 0) {
                continue;
            }
            $dl = $this->toT($r->{'DATE LIVRAISON'} ?? null);
            if ($dl === null) {
                continue;
            }
            $sku = trim((string) ($r->{'SKU'} ?? ''));
            if ($sku === '') {
                continue;
            }
            if ($o->dl === null || $dl < $o->dl) {
                $o->dl = $dl;
            }
            $o->q += $q;
            $demTot += $q;
            if ($coll !== '' && !isset($collBySku[$sku]) && preg_match('/\d{4}-\d{2}/', $coll)) {
                $collBySku[$sku] = $coll;
            }
            $l = new \stdClass();
            $l->sku = $sku;
            $l->q = $q;
            $l->left = $q;
            $l->desig = trim((string) ($r->{'DESIGNATION'} ?? ''));
            $l->alloc = [];
            $o->lines[] = $l;
        }

        // ── ALLOCATION SÉQUENTIELLE : FRANCE puis INTERNATIONAL ─────────
        $stockLeft = $stock; // copie (tableaux PHP = copy-on-write)
        $posBySku = [];
        foreach ($pos as $p) {
            $posBySku[$p->sku] ??= [];
            $posBySku[$p->sku][] = $p;
        }

        $candidates = function (string $mod, string $sku, bool $isSS27) use (&$stockLeft, $posBySku): array {
            $stq = $stockLeft[$sku] ?? ['LOG' => 0.0, 'FRO' => 0.0, 'SF' => 0.0, 'DT' => 0.0, 'ASO' => 0.0];
            $out = [];
            $push = function (string $kind, string $grp, int $arr, ?\stdClass $poRef = null) use (&$out): void {
                $out[] = ['kind' => $kind, 'grp' => $grp, 'arr' => $arr, 'poRef' => $poRef];
            };
            if ($mod === 'FR') {
                if (($stq['LOG'] ?? 0) > 0) {
                    $push('S', 'LOG', $this->tarrRef + self::FR_STOCK_DELAY * self::DAY);
                }
                if (($stq['FRO'] ?? 0) > 0) {
                    $push('S', 'FRO', $this->tarrRef + self::FR_STOCK_DELAY * self::DAY);
                }
                foreach ($posBySku[$sku] ?? [] as $p) {
                    if ($p->left <= 0) {
                        continue;
                    }
                    if ($isSS27) {
                        $base = $p->xf ?? $p->rawEta ?? $p->eta;
                        $push('P', $p->dest === 'FR' ? 'FR' : 'ASIE', $base + self::SS27_PO_DELAY * self::DAY, $p);
                    } elseif ($p->dest === 'FR') {
                        $push('P', 'FR', $p->eta + self::FR_PO_DELAY * self::DAY, $p);
                    } else {
                        $base = max($p->xf ?? $p->eta, $this->tarrRef);
                        $push('P', 'ASIE', $base + self::FR_ASIA_DELAY * self::DAY, $p);
                    }
                }
                if (self::FR_USES_ASIA_STOCK) {
                    foreach (self::FR_ASIA_GROUPS as $g) {
                        if (($stq[$g] ?? 0) > 0) {
                            $push('S', $g, $this->tarrRef + self::FR_ASIA_DELAY * self::DAY);
                        }
                    }
                }
            } else {
                foreach (['SF', 'DT', 'ASO'] as $g) {
                    if (($stq[$g] ?? 0) > 0) {
                        $push('S', $g, $this->tarrRef + self::INT_PREP_DELAY * self::DAY);
                    }
                }
                foreach ($posBySku[$sku] ?? [] as $p) {
                    if ($p->left <= 0) {
                        continue;
                    }
                    $delay = $p->dest === 'ASIE' ? self::INT_PO_ASIA_DELAY : self::INT_FR_DELAY;
                    $push('P', $p->dest === 'ASIE' ? 'ASIE' : 'FR', $p->eta + $delay * self::DAY, $p);
                }
                if (($stq['LOG'] ?? 0) > 0) {
                    $push('S', 'LOG', $this->tarrRef + self::INT_FR_DELAY * self::DAY);
                }
                if (($stq['FRO'] ?? 0) > 0) {
                    $push('S', 'FRO', $this->tarrRef + self::INT_FR_DELAY * self::DAY);
                }
            }
            usort($out, function ($a, $b) {
                if ($a['arr'] !== $b['arr']) {
                    return $a['arr'] <=> $b['arr'];
                }
                $ka = $a['kind'] === 'S' ? -1 : 1;
                $kb = $b['kind'] === 'S' ? -1 : 1;
                return $ka <=> $kb;
            });
            return $out;
        };
        $this->tarrRef = $tarr;

        $take = function (array $c, string $sku, float $need) use (&$stockLeft): float {
            if ($c['kind'] === 'S') {
                $avail = $stockLeft[$sku][$c['grp']] ?? 0.0;
                $t = min($need, $avail);
                $stockLeft[$sku][$c['grp']] = $avail - $t;
                return $t;
            }
            $poRef = $c['poRef'];
            $t = min($need, $poRef->left);
            $poRef->left -= $t;
            return $t;
        };

        $runModule = function (string $mod) use ($orders, $candidates, $take, $isFut, &$flow): void {
            $os = array_values(array_filter($orders, fn ($o) => $o->mod === $mod && count($o->lines) > 0));
            $byCli = [];
            foreach ($os as $o) {
                $byCli[$o->cli] = ($byCli[$o->cli] ?? 0) + $o->mt;
            }
            arsort($byCli);
            $rank = array_flip(array_keys($byCli));
            $top = array_filter($rank, fn ($i) => $i < self::TOP_CLIENTS);

            $domKey = fn ($o) => $o->show ? 1 : 0;
            $pri = function ($o) use ($domKey, $top, $rank) {
                return [
                    $domKey($o),
                    isset($top[$o->cli]) ? 0 : 1,
                    $o->dl,
                    $o->coll,
                    $o->dcmd ?? PHP_INT_MAX,
                ];
            };
            usort($os, function ($a, $b) use ($pri) {
                $pa = $pri($a);
                $pb = $pri($b);
                foreach ($pa as $i => $va) {
                    $vb = $pb[$i];
                    if ($va === $vb) {
                        continue;
                    }
                    return $va <=> $vb;
                }
                return $a->ord <=> $b->ord;
            });

            $allocTier = function (\stdClass $o, int $pass) use ($mod, $candidates, $take, $isFut, &$flow): void {
                $isSS27 = $isFut($o->coll) && $mod === 'FR';
                $limit = $o->dl + self::GRACE * self::DAY;
                $bySku = [];
                foreach ($o->lines as $l) {
                    if ($l->left > 0) {
                        $bySku[$l->sku] ??= [];
                        $bySku[$l->sku][] = $l;
                    }
                }
                foreach (array_keys($bySku) as $sku) {
                    foreach ($candidates($mod, $sku, $isSS27) as $c) {
                        $lines = array_values(array_filter($bySku[$sku], fn ($l) => $l->left > 0));
                        if ($lines === []) {
                            break;
                        }
                        if ($pass === 1 && $c['arr'] > $limit) {
                            continue;
                        }
                        $need = array_sum(array_map(fn ($l) => $l->left, $lines));
                        $avail = $c['kind'] === 'S'
                            ? ($this->stockLeftRef[$sku][$c['grp']] ?? 0.0)
                            : $c['poRef']->left;
                        if ($avail <= 0) {
                            continue;
                        }
                        $give = min($need, $avail);
                        if ($give >= $need) {
                            $shares = array_map(fn ($l) => $l->left, $lines);
                        } else {
                            $raw = array_map(fn ($l) => $give * $l->left / $need, $lines);
                            $shares = array_map('floor', $raw);
                            $rem = $give - array_sum($shares);
                            $order = [];
                            foreach ($raw as $i => $v) {
                                $order[] = [$v - floor($v), $i];
                            }
                            usort($order, fn ($x, $y) => $y[0] <=> $x[0]);
                            for ($j = 0; $j < count($order) && $rem > 0; $j++) {
                                $shares[$order[$j][1]]++;
                                $rem--;
                            }
                        }
                        foreach ($lines as $i => $l) {
                            $t = min($shares[$i], $l->left);
                            if ($t > 0) {
                                $take($c, $sku, $t);
                                $l->left -= $t;
                                $arrGrp = in_array($c['grp'], ['LOG', 'FRO', 'FR'], true) ? 0 : 1;
                                $flow[] = [$mod === 'FR' ? 0 : 1, $c['arr'], $arrGrp, $t, $c['kind'] === 'S' ? 0 : 1];
                                $a = new \stdClass();
                                $a->kind = $c['kind'];
                                $a->grp = $c['grp'];
                                $a->arr = $c['arr'];
                                $a->q = $t;
                                $a->po = $c['poRef']->po ?? null;
                                $l->alloc[] = $a;
                            }
                        }
                    }
                }
            };
            foreach ($os as $o) {
                $allocTier($o, 1);
                $allocTier($o, 2);
            }
        };
        // NB : $candidates/$take capturent $stockLeft par référence via des closures liées
        // à cette méthode ; on les expose aussi via des propriétés pour les fermetures imbriquées.
        $this->stockLeftRef = &$stockLeft;
        $runModule('FR');
        $runModule('INT');

        // ── AGRÉGATION ───────────────────────────────────────────────────
        $pfr = [];
        $pint = [];
        $detFR = [];
        $detIN = [];
        $sumOT = 0.0;
        $sumLate = 0.0;
        $sumCanc = 0.0;

        foreach ($orders as $o) {
            $mod = $o->mod;
            $limit = $o->dl !== null ? $o->dl + self::GRACE * self::DAY : null;
            $aStk = 0.0;
            $aProd = 0.0;
            $prio = 0.0;
            $ot = 0.0;
            $late = 0.0;
            $canc = 0.0;
            $arrs = [];
            $arrsOT = [];
            $arrsLate = [];
            $posSet = [];
            $det = [];

            foreach ($o->lines as $l) {
                $lot = 0.0;
                $llate = 0.0;
                $srcs = [];
                foreach ($l->alloc as $a) {
                    if ($a->kind === 'S') {
                        $aStk += $a->q;
                    } else {
                        $aProd += $a->q;
                        if ($a->po) {
                            $posSet[$a->po] = true;
                        }
                    }
                    $prioGrp = $mod === 'FR' ? ($a->grp === 'LOG') : ($a->grp === 'SF' || $a->grp === 'DT');
                    if ($a->kind === 'S' && $prioGrp) {
                        $prio += $a->q;
                    }
                    if ($limit !== null && $a->arr <= $limit) {
                        $lot += $a->q;
                        $arrsOT[] = $a->arr;
                    } else {
                        $llate += $a->q;
                        $arrsLate[] = $a->arr;
                    }
                    $arrs[] = $a->arr;
                    $lbl = $a->kind === 'S'
                        ? self::STOCK_LABEL[$a->grp]
                        : ('PO ' . ($a->po ?: '?') . ' → ' . ($a->grp === 'ASIE' ? 'Asie' : 'Logtex'));
                    $srcs[] = $lbl . ' · ' . $a->q . ' pcs · ' . $this->dfr($a->arr);
                }
                $ot += $lot;
                $late += $llate;
                if ($l->left > 0) {
                    $canc += $l->left;
                }
                $det[] = [$l->sku, $l->desig, $l->q, $l->q - $l->left, implode('  ;  ', $srcs), $lot, $llate, $l->left];
            }

            if ($mod === 'FR' || $o->show) {
                $sumOT += $ot;
                $sumLate += $late;
                $sumCanc += $canc;
            }
            if (!$o->show) {
                continue;
            }

            $coll = $o->coll ?? '';
            $eta1 = $arrs === [] ? null : min($arrs);
            $etaC = $arrs === [] ? null : max($arrs);
            $a1t = $arrsOT === [] ? null : min($arrsOT);
            $a2t = $arrsOT === [] ? null : max($arrsOT);
            $a1r = $arrsLate === [] ? null : min($arrsLate);
            $a2r = $arrsLate === [] ? null : max($arrsLate);

            $stat = ($o->q <= 0 || $canc >= $o->q) ? '⚫ Annulé'
                : ($prio >= $o->q ? '🟢 Pousser tout' : ($prio > 0 ? '🟡 Pousser partiel' : '⚪ Rien à pousser'));
            $src = ($o->q <= 0 || $canc >= $o->q) ? 'Aucune source'
                : (($aStk > 0 && $aProd > 0) ? 'Stock + Prod' : ($aStk > 0 ? 'Stock' : ($aProd > 0 ? 'Prod' : 'Aucune source')));
            $arriv = ($aStk + $aProd) - $prio;

            if ($stat === '⚫ Annulé') {
                $syn = '❌ Annulée — plus rien à livrer';
            } elseif ($mod === 'FR') {
                if ($stat === '🟢 Pousser tout') {
                    $syn = '✅ 100 % dispo à Logtex — ' . $this->nUS($prio) . ' pcs à pousser maintenant';
                } elseif ($stat === '🟡 Pousser partiel') {
                    $syn = '🟡 ' . $this->nUS($prio) . ' pcs dispo à Logtex · ' . $this->nUS($arriv) . " pcs en arrivage d'ici le " . ($etaC ? $this->dfr($etaC) : '?');
                } else {
                    $syn = '⏳ Rien à Logtex — ' . $this->nUS($arriv) . ' pcs attendues entre le ' . ($eta1 ? $this->dfr($eta1) : '?') . ' et le ' . ($etaC ? $this->dfr($etaC) : '?');
                }
            } else {
                $prep = $this->dfr($tgen + self::INT_PREP_DELAY * self::DAY);
                if ($stat === '🟢 Pousser tout') {
                    $syn = '✅ 100 % dispo usine SF/DT — ' . $this->nUS($prio) . ' pcs livrables ~ ' . $prep . ' (+' . self::INT_PREP_DELAY . ' j préparation)';
                } elseif ($stat === '🟡 Pousser partiel') {
                    $syn = '🟡 ' . $this->nUS($prio) . ' pcs dispo SF/DT (livrables ~ ' . $prep . ') · ' . $this->nUS($arriv) . " pcs en arrivage d'ici le " . ($etaC ? $this->dfr($etaC) : '?');
                } else {
                    $syn = '⏳ Rien de dispo SF/DT — ' . $this->nUS($arriv) . ' pcs attendues entre le ' . ($eta1 ? $this->dfr($eta1) : '?') . ' et le ' . ($etaC ? $this->dfr($etaC) : '?');
                }
            }
            if ($stat !== '⚫ Annulé' && $canc > 0) {
                $syn .= ' · ' . $this->nUS($canc) . ' pcs annulées';
            }

            $isAnomaly = $isFut($coll) && $o->dl !== null && ($cutoff = $preSeasonCutoff($coll)) !== null && $o->dl < $cutoff;
            $rechuHidden = $isAnomaly || ($mod === 'FR' && $stat === '⚫ Annulé') || ($mod === 'INT' && $stat === '🟢 Pousser tout');
            $rechu = (!$rechuHidden && $o->dl !== null && $o->dl < $tgen) ? (int) round(($tgen - $o->dl) / self::DAY) : null;
            $rprev = ($etaC !== null && $o->dl !== null) ? (int) round(($etaC - $o->dl) / self::DAY) : null;

            arsort($o->nets);
            $catKeys = array_keys($o->nets);
            $cat = $catKeys[0] ?? ($mod === 'FR' ? 'WHOLESALE FRANCE' : 'WHOLESALE INTERNATIONAL');

            $row = [
                'coll' => $coll, 'cat' => $cat, 'cli' => $o->cli, 'ord' => $o->ord, 'q' => $o->q,
                'dispo' => $prio, 'pct' => $o->q ? round($prio / $o->q, 4) : 0.0, 'stat' => $stat, 'syn' => $syn,
                'eta1' => $eta1 !== null ? $this->dstr($eta1) : null, 'etac' => $etaC !== null ? $this->dstr($etaC) : null,
                'ddem' => $o->dl !== null ? $this->dstr($o->dl) : null, 'rechu' => $rechu, 'mt' => round($o->mt, 2),
                'qot' => $ot, 'qlate' => $late, 'qcanc' => $canc, 'pcanc' => $o->q ? round($canc / $o->q, 4) : 0.0,
                'astk' => $aStk, 'aprod' => $aProd, 'src' => $src, 'subq' => 0.0,
                'a1t' => $a1t !== null ? $this->dstr($a1t) : null, 'a2t' => $a2t !== null ? $this->dstr($a2t) : null,
                'a1r' => $a1r !== null ? $this->dstr($a1r) : null, 'a2r' => $a2r !== null ? $this->dstr($a2r) : null,
                'mag' => $o->mag ?: $o->cli, 'ref' => $o->ref, 'codc' => $o->codc, 'grpc' => $o->grpc, 'grp' => $o->grp,
                'codm' => $o->codm, 'ville' => $o->ville, 'rep' => $o->rep, 'dcmd' => $o->dcmd !== null ? $this->dstr($o->dcmd) : null,
                'po' => implode(' · ', $this->sortedKeys($posSet)),
            ];

            if ($mod === 'FR') {
                $row['covterme'] = $o->q ? round(($o->q - $canc) / $o->q, 4) : 0.0;
                $row['rprev'] = $rprev;
                $o->row = &$pfr[];
                $o->row = $row;
                $detFR[$o->ord] = array_merge($detFR[$o->ord] ?? [], $det);
            } else {
                $row['rprev'] = $rprev;
                $o->row = &$pint[];
                $o->row = $row;
                $detIN[$o->ord] = array_merge($detIN[$o->ord] ?? [], $det);
            }
        }

        $statOrder = fn ($s) => match (true) {
            str_starts_with($s, '🟢') => 0,
            str_starts_with($s, '🟡') => 1,
            str_starts_with($s, '⚪') => 2,
            default => 3,
        };
        $srt = function ($a, $b) use ($statOrder) {
            $so = $statOrder($a['stat']) <=> $statOrder($b['stat']);
            if ($so !== 0) {
                return $so;
            }
            $pc = $b['pct'] <=> $a['pct'];
            if ($pc !== 0) {
                return $pc;
            }
            return $b['mt'] <=> $a['mt'];
        };
        usort($pfr, $srt);
        usort($pint, $srt);

        // ── RESTE À ALLOUER ──────────────────────────────────────────────
        $rest = [];
        $restPool = [];
        foreach ($stockLeft as $sku => $grps) {
            $coll = $collBySku[$sku] ?? '';
            foreach (['LOG', 'FRO', 'SF', 'DT', 'ASO'] as $g) {
                $q = $grps[$g] ?? 0.0;
                if ($q <= 0) {
                    continue;
                }
                $restPool[] = ['sku' => $sku, 'coll' => $coll, 'stock' => true, 'q' => $q, 'left' => $q, 'eta' => null, 'grp' => $g];
                if (isset($collSet[$coll])) {
                    $rest[] = [$sku, $coll, '', self::GRP_SRC[$g], $q, 'Maintenant', null];
                }
            }
        }
        foreach ($pos as $p) {
            if ($p->left <= 0) {
                continue;
            }
            $coll = $collBySku[$p->sku] ?? '';
            $eta = $p->rawEta ?? $p->eta;
            $restPool[] = ['sku' => $p->sku, 'coll' => $coll, 'stock' => false, 'q' => $p->left, 'left' => $p->left, 'eta' => $eta, 'grp' => $p->dest];
            if (isset($collSet[$coll])) {
                $rest[] = [$p->sku, $coll, '', $p->dest === 'FR' ? 'PO → France' : 'PO → Asie', $p->left, 'À terme', $eta !== null ? $this->dstr($eta) : null];
            }
        }
        usort($rest, fn ($a, $b) => strcmp((string) $a[0], (string) $b[0]));

        // ── SUBSTITUTIONS ────────────────────────────────────────────────
        $desigBySku = [];
        foreach ($orders as $o) {
            foreach ($o->lines as $l) {
                if ($l->desig && !isset($desigBySku[$l->sku])) {
                    $desigBySku[$l->sku] = $l->desig;
                }
            }
        }
        $base = fn (string $sku) => ($i = strrpos($sku, '_')) !== false && $i > 0 ? substr($sku, 0, $i) : $sku;
        $size = fn (string $sku) => ($i = strrpos($sku, '_')) !== false && $i > 0 ? substr($sku, $i + 1) : '';
        $model = function (string $sku) use ($desigBySku) {
            $d = $desigBySku[$sku] ?? '';
            $parts = preg_split('/\s+/', trim($d));
            return $parts[0] ?? null;
        };
        $poolBySku = [];
        $poolByModel = [];
        foreach ($restPool as $idx => $e) {
            $poolBySku[$base($e['sku'])][] = $idx;
            $m = $model($e['sku']);
            if ($m) {
                $poolByModel[$m . '|' . $e['coll']][] = $idx;
            }
        }
        $okGrp = fn (array $e, string $mod) => $mod === 'FR'
            ? ($e['stock'] ? ($e['grp'] === 'LOG' || $e['grp'] === 'FRO') : $e['grp'] === 'FR')
            : ($e['stock'] ? in_array($e['grp'], ['SF', 'DT', 'ASO'], true) : $e['grp'] === 'ASIE');

        $buildSubst = function (string $mod) use ($orders, &$restPool, $poolBySku, $poolByModel, $base, $size, $model, $okGrp, $desigBySku): array {
            $rows = [];
            foreach ($orders as $o) {
                if ($o->mod !== $mod || !$o->show || $o->row === null) {
                    continue;
                }
                foreach ($o->lines as $l) {
                    if ($l->left <= 0) {
                        continue;
                    }
                    $coll = $o->coll ?? '';
                    $need = $l->left;
                    $got = 0.0;
                    $gotNow = 0.0;
                    $nCand = 0;
                    $propose = function (int $idx, int $rang, string $type) use (&$restPool, &$need, &$got, &$gotNow, &$nCand, $coll, $mod, $okGrp, &$rows, $o, $l, $desigBySku): void {
                        $e = $restPool[$idx];
                        if ($need <= 0 || $e['left'] <= 0 || $nCand >= PilotageEngine::SUBST_MAX_CAND) {
                            return;
                        }
                        $nCand++;
                        if ($e['coll'] !== $coll || $coll === '') {
                            return;
                        }
                        if (!$okGrp($e, $mod)) {
                            return;
                        }
                        $t = min($need, $e['left']);
                        $restPool[$idx]['left'] -= $t;
                        $need -= $t;
                        $got += $t;
                        if ($e['stock']) {
                            $gotNow += $t;
                        }
                        $rows[] = [
                            $o->ord, $o->cli, $o->row['stat'], $l->sku, $l->desig, $l->left, $rang, $type,
                            $e['sku'], $desigBySku[$e['sku']] ?? '', $e['stock'] ? $t : 0, $e['stock'] ? 0 : $t,
                            $e['eta'] !== null ? $this->dstrPublic($e['eta']) : null, $t, null,
                        ];
                    };
                    $sib = array_filter($poolBySku[$base($l->sku)] ?? [], fn ($idx) => $restPool[$idx]['sku'] !== $l->sku && $restPool[$idx]['left'] > 0 && $restPool[$idx]['coll'] === $coll);
                    $mySz = (float) preg_replace('/[^0-9.]/', '', $size($l->sku)) ?: 0.0;
                    $sib = array_values($sib);
                    usort($sib, function ($ia, $ib) use ($restPool, $size, $mySz) {
                        $da = abs(((float) preg_replace('/[^0-9.]/', '', $size($restPool[$ia]['sku'])) ?: 0.0) - $mySz);
                        $db = abs(((float) preg_replace('/[^0-9.]/', '', $size($restPool[$ib]['sku'])) ?: 0.0) - $mySz);
                        if ($da !== $db) {
                            return $da <=> $db;
                        }
                        return ($restPool[$ib]['stock'] ? 1 : 0) <=> ($restPool[$ia]['stock'] ? 1 : 0);
                    });
                    foreach ($sib as $idx) {
                        $propose($idx, 1, 'Même article, autre taille');
                    }
                    $r1got = $got;
                    if ($need > 0) {
                        $m = $model($l->sku);
                        if ($m) {
                            $cand = array_filter($poolByModel[$m . '|' . $coll] ?? [], fn ($idx) => $base($restPool[$idx]['sku']) !== $base($l->sku) && $restPool[$idx]['left'] > 0);
                            $cand = array_values($cand);
                            usort($cand, fn ($ia, $ib) => ($restPool[$ib]['stock'] ? 1 : 0) <=> ($restPool[$ia]['stock'] ? 1 : 0));
                            $n2 = 0;
                            foreach ($cand as $idx) {
                                if ($n2 >= PilotageEngine::SUBST_R2_MAX) {
                                    break;
                                }
                                $before = $got;
                                $propose($idx, 2, 'Même modèle, autre coloris (accord client)');
                                if ($got > $before) {
                                    $n2++;
                                }
                            }
                        }
                    }
                    $o->row['subq'] = ($o->row['subq'] ?? 0) + $got;
                    $o->subNow += $gotNow;
                }
                if ($o->subNow > 0 && !str_contains((string) $o->row['syn'], '🔄')) {
                    $o->row['syn'] .= ' · 🔄 ' . $this->nUS($o->subNow) . ' pcs substituables dispo';
                }
            }
            return ['head' => self::HEAD_SUBST, 'rows' => $rows];
        };
        $subFR = $buildSubst('FR');
        $subIN = $buildSubst('INT');

        // ── MACRO (par segment × collection) ─────────────────────────────
        $macro = $this->buildMacro($pfr, $pint);

        // ── Mise en forme finale des lignes de pilotage (ordre des colonnes) ─
        $pfrOut = array_map(fn ($row) => $this->orderRow($row, self::FIELDS_FR), $pfr);
        $pintOut = array_map(fn ($row) => $this->orderRow($row, self::FIELDS_INT), $pint);

        return [
            'pfrHead' => self::HEAD_FR, 'pfr' => $pfrOut,
            'pintHead' => self::HEAD_INT, 'pint' => $pintOut,
            'detFR' => $detFR, 'detIN' => $detIN,
            'subFR' => $subFR, 'subIN' => $subIN,
            'restHead' => self::HEAD_REST, 'rest' => $rest,
            'macroHead' => self::HEAD_MACRO, 'macro' => $macro,
            'methode' => self::METHODE_V10,
            'ctrl' => ['demande' => $demTot, 'onT' => $sumOT, 'late' => $sumLate, 'canc' => $sumCanc],
        ];
    }

    /** Référence partagée utilisée par les closures d'allocation (candidates/take/allocTier). */
    private int $tarrRef = 0;
    private array $stockLeftRef = [];

    private function buildMacro(array $pfr, array $pint): array
    {
        $agg = function (array $rows): array {
            $a = ['pretP' => 0.0, 'pretC' => 0, 'pretE' => 0.0, 'partP' => 0.0, 'partC' => 0, 'lateN' => 0, 'lateSum' => 0.0, 'mt' => 0.0, 'cmd' => count($rows)];
            foreach ($rows as $r) {
                $a['mt'] += $r['mt'] ?? 0;
                $st = (string) ($r['stat'] ?? '');
                if (str_contains($st, '🟢')) {
                    $a['pretP'] += $r['dispo'] ?? 0;
                    $a['pretC']++;
                    $a['pretE'] += $r['mt'] ?? 0;
                } elseif (str_contains($st, '🟡')) {
                    $a['partP'] += $r['dispo'] ?? 0;
                    $a['partC']++;
                }
                $rp = $r['rprev'] ?? null;
                if (is_numeric($rp) && $rp > 0) {
                    $a['lateN']++;
                    $a['lateSum'] += $rp;
                }
            }
            $a['lateAvg'] = $a['lateN'] ? $a['lateSum'] / $a['lateN'] : 0.0;
            return $a;
        };

        $groups = [];
        $mk = function (string $seg, array $rows) use (&$groups, $agg): void {
            $by = [];
            foreach ($rows as $r) {
                $by[$r['coll']][] = $r;
            }
            ksort($by);
            foreach ($by as $coll => $rrows) {
                $a = $agg($rrows);
                $groups[] = [$seg, $coll, $a['pretP'], $a['pretC'], round($a['pretE'], 2), $a['partP'], $a['partC'], round($a['lateAvg'], 2), $a['lateN'], round($a['mt'], 2), $a['cmd']];
            }
        };
        $mk('France — Wholesale France', $pfr);
        $mk('International — Wholesale International', $pint);

        $gFR = $agg($pfr);
        $gIN = $agg($pint);
        $totLateN = $gFR['lateN'] + $gIN['lateN'];
        $totLateSum = $gFR['lateSum'] + $gIN['lateSum'];
        $groups[] = [
            'TOTAL', '',
            $gFR['pretP'] + $gIN['pretP'], $gFR['pretC'] + $gIN['pretC'], round($gFR['pretE'] + $gIN['pretE'], 2),
            $gFR['partP'] + $gIN['partP'], $gFR['partC'] + $gIN['partC'],
            round($totLateN ? $totLateSum / $totLateN : 0, 2), $totLateN,
            round($gFR['mt'] + $gIN['mt'], 2), $gFR['cmd'] + $gIN['cmd'],
        ];

        return $groups;
    }

    private function orderRow(array $row, array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $out[] = $row[$f] ?? null;
        }
        return $out;
    }

    // ── Utilitaires date / nombre ────────────────────────────────────────

    private function utcDay(int $y, int $m, int $d): int
    {
        return gmmktime(0, 0, 0, $m, $d, $y);
    }

    /** Équivalent de toT() côté JS : renvoie un timestamp UTC à minuit, ou null. */
    private function toT(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof \DateTimeInterface) {
            return $this->utcDay((int) $v->format('Y'), (int) $v->format('n'), (int) $v->format('j'));
        }
        $s = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return $this->utcDay((int) $m[1], (int) $m[2], (int) $m[3]);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $s, $m)) {
            return $this->utcDay((int) $m[3], (int) $m[2], (int) $m[1]);
        }
        $ts = strtotime($s);
        if ($ts === false) {
            return null;
        }
        return $this->utcDay((int) gmdate('Y', $ts), (int) gmdate('n', $ts), (int) gmdate('j', $ts));
    }

    private function dstr(int $t): string
    {
        return gmdate('Y-m-d', $t);
    }

    public function dstrPublic(int $t): string
    {
        return $this->dstr($t);
    }

    private function dfr(int $t): string
    {
        return gmdate('d/m/Y', $t);
    }

    private function nUS(float $n): string
    {
        return number_format(round($n), 0, '.', ',');
    }

    private function num(mixed $v): float
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

    private function nrm(mixed $v): string
    {
        return preg_replace('/\s+/', ' ', strtoupper(trim((string) ($v ?? ''))));
    }

    private function sortedKeys(array $set): array
    {
        $keys = array_keys($set);
        sort($keys);
        return $keys;
    }
}
