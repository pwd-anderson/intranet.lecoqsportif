<?php

namespace App\Service;

use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;

/**
 * Module "Arrivées Logtex" — voir docs/superpowers/plans/2026-09-29-arrivees-logtex-progress.md
 * pour l'historique de conception.
 *
 * Réutilise Achat::getBacklogFournisseur() (PO) et Achat::getBacklogFournisseurIntersites()
 * (transferts intersites), déjà normalisées dans la même forme (SITE_RECEPTION, TYPE_FLUX,
 * PRIX_EUR, VALIDE, MDL_0). Filtre sur le site de réception WLOGM (Logtex Moussey), puis
 * regroupe les lignes par modèle + commande + date de livraison en une grille de tailles —
 * c'est le grain attendu par le template de référence (public/template/arrivees-logtex_2.html) :
 * une carte = un modèle sur une commande avec sa grille de tailles, pas une ligne par taille.
 *
 * Les intersites sont volontairement inclus, distingués par le champ INTERSITE (OUI/NON) —
 * le template de référence les traite déjà comme un flux à part entière (badge "Intersite"),
 * pas comme une option à activer.
 */
class ArrivalsLogtex
{
    private const string SITE_RECEPTION = 'WLOGM';

    public function __construct(
        private Achat $achat,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
    ) {}

    public function getArrivals(): array
    {
        try {
            $rows = array_merge(
                $this->achat->getBacklogFournisseur(),
                $this->achat->getBacklogFournisseurIntersites()
            );

            $rows = array_filter($rows, fn($r) => ($r->SITE_RECEPTION ?? null) === self::SITE_RECEPTION);

            return $this->groupByModel($rows);

        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ Arrivées Logtex : getArrivals', $e);
            $this->logger->error('Arrivées Logtex : getArrivals', ['exception' => $e]);
            return [];
        }
    }

    /**
     * Éclate ITMREF_0 en article de base + taille (même découpe que le Backlog Client :
     * tout avant le premier '_' est l'article, le reste est la variante).
     *
     * @return array{0: string, 1: string} [article_base, taille]
     */
    private function splitArticle(string $itmref): array
    {
        $pos = strpos($itmref, '_');
        if ($pos === false) {
            return [$itmref, ''];
        }
        return [substr($itmref, 0, $pos), substr($itmref, $pos + 1)];
    }

    /**
     * Regroupe les lignes par modèle + commande + date de livraison. Chaque groupe
     * devient une carte avec sa grille de tailles, sa quantité et son montant EUR
     * totalisés.
     */
    private function groupByModel(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            [$article, $taille] = $this->splitArticle((string) ($row->ITMREF_0 ?? ''));
            if ($article === '') {
                continue;
            }

            $key = implode('|', [
                $article,
                $row->POHNUM_0 ?? '',
                $row->EXTRCPDAT_0 ?? '',
            ]);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'article'          => $article,
                    'designation'      => $row->ITMDES1_0 ?? '',
                    'collection'       => $row->COLLECTION ?? '',
                    'famille'          => $row->FAMILLE ?? '',
                    'fournisseur'      => $row->BPRNAM_0 ?? '',
                    'code_fournisseur' => $row->BPSNUM_0 ?? '',
                    'commande'         => $row->POHNUM_0 ?? '',
                    'reference'        => $row->ORDREF_0 ?? '',
                    'date_commande'    => $row->ORDDAT_0 ?? '',
                    'date_expedition'  => $row->XSHIPDAT_0 ?? '',
                    'date_livraison'   => $row->EXTRCPDAT_0 ?? '',
                    'statut'           => $row->STATUS ?? '',
                    'transport'        => $row->MDL_0 ?? '',
                    'flux'             => ($row->INTERSITE ?? 'NON') === 'OUI' ? 'Intersite' : 'BL Fournisseur',
                    'valide'           => $row->VALIDE ?? 'NON',
                    'noos'             => $row->NOOS ?? 'Non',
                    'droppe'           => $row->DROPPE ?? 'NON',
                    'tailles'          => [],
                    'quantite'         => 0,
                    'montant_eur'      => 0.0,
                ];
            }

            $groups[$key]['tailles'][]    = [$taille !== '' ? $taille : '—', (int) ($row->QUANTITE ?? 0)];
            $groups[$key]['quantite']    += (int) ($row->QUANTITE ?? 0);
            $groups[$key]['montant_eur'] += (float) ($row->PRIX_EUR ?? 0);
        }

        return array_values($groups);
    }
}
