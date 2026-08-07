<?php

namespace App\Service\Module;

use App\Entity\SalesWebService;
use App\Factory\MssqlManagerFactory;
use App\Service\Tools\MssqlManager;
use App\Service\Webservice\WebServiceDispatcher;
use App\Service\Webservice\XmlBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ImportOdService
{
    private MssqlManager $mssqlLcs;

    public function __construct(
        private WebServiceDispatcher $dispatcher,
        MssqlManagerFactory $mssqlManagerFactory,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
    ) {
        $this->mssqlLcs = $mssqlManagerFactory->create($dbLcs);
    }

    public function generate(array $entete, array $lignes): SalesWebService
    {
        $xml = XmlBuilder::buildOD($entete, $lignes);
        return $this->dispatcher->dispatch('ZWSIMPOD', $xml);
    }

    /**
     * Valide les lignes OD : Axe1, Compte (existence + collectif), Tiers.
     * Retourne un tableau d'erreurs par numéro de ligne (index 0-based).
     */
    public function validateLignes(array $lignes): array
    {
        // ── 1. Requête Axe1 ─────────────────────────────────────────────────
        $axe1Values = $this->uniqueNonEmpty(array_column($lignes, 'Axe'));
        $validAxe1  = [];
        if (!empty($axe1Values)) {
            [$sql, $params] = $this->inClause('CAE.CCE_0', $axe1Values, 'axe1');
            $validAxe1 = array_column(
                $this->mssqlLcs->executeQueryWithParams("SELECT CAE.CCE_0 FROM X3_LCS.CACCE CAE WHERE $sql", $params),
                'CCE_0'
            );
        }

        // ── 2. Requête Compte : existence + flag collectif (SAC_0) ──────────
        $compteValues = $this->uniqueNonEmpty(array_column($lignes, 'Compte'));
        $compteMap    = []; // ACC_0 => SAC_0
        if (!empty($compteValues)) {
            [$sql, $params] = $this->inClause('GAC.ACC_0', $compteValues, 'cpt');
            $rows = $this->mssqlLcs->executeQueryWithParams(
                "SELECT GAC.ACC_0, GAC.SAC_0 FROM X3_LCS.GACCOUNT GAC WHERE GAC.COA_0 = 'FRA' AND $sql",
                $params
            );
            foreach ($rows as $row) {
                $compteMap[$row['ACC_0']] = (int) $row['SAC_0'];
            }
        }

        // ── 3. Requête Tiers ────────────────────────────────────────────────
        $tiersValues = $this->uniqueNonEmpty(array_column($lignes, 'Tiers'));
        $validTiers  = [];
        if (!empty($tiersValues)) {
            [$sql, $params] = $this->inClause('BPR.BPRNUM_0', $tiersValues, 'tiers');
            $validTiers = array_column(
                $this->mssqlLcs->executeQueryWithParams(
                    "SELECT BPR.BPRNUM_0 FROM X3_LCS.BPARTNER BPR WHERE $sql",
                    $params
                ),
                'BPRNUM_0'
            );
        }

        // ── 4. Contrôle ligne par ligne ─────────────────────────────────────
        $errors = [];
        foreach ($lignes as $i => $ligne) {
            $lineErrors = [];
            $lineNum    = $i + 1;

            // Axe analytique
            $axe1 = trim($ligne['Axe'] ?? '');
            if ($axe1 !== '' && !in_array($axe1, $validAxe1, true)) {
                $lineErrors[] = "Axe analytique « $axe1 » inconnu";
            }

            // Compte
            $compte = trim($ligne['Compte'] ?? '');
            if ($compte === '') {
                $lineErrors[] = 'Compte manquant';
            } elseif (!array_key_exists($compte, $compteMap)) {
                $lineErrors[] = "Compte « $compte » inconnu";
            } else {
                // Compte collectif → Tiers obligatoire
                $tiers = trim($ligne['Tiers'] ?? '');
                if ($compteMap[$compte] === 1) {
                    if ($tiers === '') {
                        $lineErrors[] = "Compte « $compte » est collectif : Tiers obligatoire";
                    } elseif (!in_array($tiers, $validTiers, true)) {
                        $lineErrors[] = "Tiers « $tiers » inconnu";
                    }
                } elseif ($tiers !== '' && !in_array($tiers, $validTiers, true)) {
                    $lineErrors[] = "Tiers « $tiers » inconnu";
                }
            }

            if (!empty($lineErrors)) {
                $errors[$lineNum] = $lineErrors;
            }
        }

        return $errors;
    }

    private function uniqueNonEmpty(array $values): array
    {
        return array_values(array_unique(array_filter($values, fn($v) => $v !== '' && $v !== null)));
    }

    private function inClause(string $column, array $values, string $prefix): array
    {
        $params = [];
        $placeholders = [];
        foreach ($values as $i => $v) {
            $key = $prefix . '_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key]   = $v;
        }
        return ["{$column} IN (" . implode(',', $placeholders) . ")", $params];
    }
}
