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
        #[Autowire('%db.lcs_sei%')]
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
     * Valide les lignes OD : Axe analytique, Compte (existence + collectif), Tiers.
     * Retourne un tableau d'erreurs indexé par numéro de ligne (1-based).
     */
    public function validateLignes(array $lignes): array
    {
        // ── 1. Requête Axe analytique ────────────────────────────────────────
        // On extrait uniquement les 3 premiers segments : "04_1_1_LIBELLE" → "04_1_1"
        $axeRaw    = array_column($lignes, 'Axe');
        $axeCodes  = array_map(fn($v) => $this->extractAxeCode($v), $axeRaw);
        $axeValues = $this->uniqueNonEmpty($axeCodes);

        $validAxe = [];
        if (!empty($axeValues)) {
            $in       = $this->inClause($axeValues);
            $rows     = $this->mssqlLcs->executeQuery("SELECT CAE.CCE_0 FROM X3_LCS.CACCE CAE WHERE CAE.CCE_0 IN ($in)");
            $validAxe = array_column(array_map(fn($r) => (array) $r, $rows), 'CCE_0');
        }

        // ── 2. Requête Compte : existence + flag collectif (SAC_0) ──────────
        $compteValues = $this->uniqueNonEmpty(array_column($lignes, 'Compte'));
        $compteMap    = [];
        if (!empty($compteValues)) {
            $in   = $this->inClause($compteValues);
            $rows = $this->mssqlLcs->executeQuery(
                "SELECT GAC.ACC_0, GAC.SAC_0 FROM X3_LCS.GACCOUNT GAC WHERE GAC.ACC_0 IN ($in)"
            );
            foreach ($rows as $row) {
                $r = (array) $row;
                $compteMap[$r['ACC_0']] = (int) $r['SAC_0'];
            }
        }

        // ── 3. Requête Tiers ────────────────────────────────────────────────
        $tiersValues = $this->uniqueNonEmpty(array_column($lignes, 'Tiers'));
        $validTiers  = [];
        if (!empty($tiersValues)) {
            $in         = $this->inClause($tiersValues);
            $rows       = $this->mssqlLcs->executeQuery("SELECT BPR.BPRNUM_0 FROM X3_LCS.BPARTNER BPR WHERE BPR.BPRNUM_0 IN ($in)");
            $validTiers = array_column(array_map(fn($r) => (array) $r, $rows), 'BPRNUM_0');
        }

        // ── 4. Contrôle ligne par ligne ─────────────────────────────────────
        $errors = [];
        foreach ($lignes as $i => $ligne) {
            $lineErrors = [];
            $lineNum    = $i + 1;

            $compte  = trim($ligne['Compte'] ?? '');
            $axeCode = $this->extractAxeCode($ligne['Axe'] ?? '');

            // Axe analytique obligatoire si compte commence par 2, 6 ou 7
            if ($compte !== '' && in_array($compte[0], ['2', '6', '7'], true) && $axeCode === '') {
                $lineErrors[] = "Axe analytique obligatoire pour le compte « $compte »";
            }

            // Axe analytique — validation si rempli
            if ($axeCode !== '' && !in_array($axeCode, $validAxe, true)) {
                $lineErrors[] = "Axe analytique « $axeCode » inconnu";
            }

            // Compte
            if ($compte === '') {
                $lineErrors[] = 'Compte manquant';
            } elseif (!array_key_exists($compte, $compteMap)) {
                $lineErrors[] = "Compte « $compte » inconnu";
            } else {
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

    /**
     * Extrait le code axe analytique : segments numériques de tête uniquement.
     * "04_1_1_PRODUCT FEES_SPORT MARKETING" → "04_1_1"
     * "04_1_12"                             → "04_1_12"
     * "03_2_1_12"                           → "03_2_1_12"
     */
    private function extractAxeCode(string $value): string
    {
        $value = trim($value);
        if ($value === '') return '';

        $numericParts = [];
        foreach (explode('_', $value) as $part) {
            if (!ctype_digit($part)) break;
            $numericParts[] = $part;
        }

        return implode('_', $numericParts);
    }

    private function uniqueNonEmpty(array $values): array
    {
        return array_values(array_unique(array_filter($values, fn($v) => $v !== '' && $v !== null)));
    }

    private function inClause(array $values): string
    {
        return implode(',', array_map(
            fn($v) => "'" . str_replace("'", "''", $v) . "'",
            $values
        ));
    }
}
