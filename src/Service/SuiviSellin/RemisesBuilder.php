<?php

namespace App\Service\SuiviSellin;

use App\Infrastructure\Sql\SqlFileLoader;

/**
 * Jeu « remises » du template : remise client de la saison précédente et de la saison en cours, et condition de
 * paiement, par client (affichés sur les fiches magasin). Requête : modules/suivi_sellin/05_remises_clients.sql, qui lit
 * la vue de la stat Comptes Clients (le template lisait un Excel saisi à la main).
 *
 * Format du template : r = { indice du client dans le jeu principal : [remise précédente, remise courante, paiement] }.
 */
final class RemisesBuilder
{
    public function __construct(
        private readonly SuiviSellinDb $db,
        private readonly SqlFileLoader $sqlFileLoader,
    ) {
    }

    /**
     * @param array<string,mixed> $main jeu principal décodé
     * @return array<string,mixed>
     */
    public function build(array $main): array
    {
        $custIndex = array_flip($main['dicts']['cust']);
        $remises = [];
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/05_remises_clients.sql')) as $r) {
            $code = (string) $r['CODE_CLIENT'];
            if (!isset($custIndex[$code])) {
                continue;
            }
            $terms = trim((string) ($r['CONDITION_PAIEMENT'] ?? ''));
            $remises[$custIndex[$code]] = [
                $r['REMISE_SAISON_PRECEDENTE'] !== null ? (float) $r['REMISE_SAISON_PRECEDENTE'] : null,
                $r['REMISE_SAISON_COURANTE'] !== null ? (float) $r['REMISE_SAISON_COURANTE'] : null,
                $terms !== '' ? $terms : null,
            ];
        }

        return ['r' => (object) $remises, 'src' => 'Vue Comptes Clients (MASTER_TABLES.PRODXXX_CLIENT_INTRANE)'];
    }
}
