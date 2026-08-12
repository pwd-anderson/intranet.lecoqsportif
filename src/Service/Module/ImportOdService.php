<?php

namespace App\Service\Module;

use App\Entity\SalesWebService;
use App\Factory\MssqlManagerFactory;
use App\Service\Tools\MssqlManager;
use App\Service\Webservice\SageX3Client;
use App\Service\Webservice\WebServiceDispatcher;
use App\Service\Webservice\XmlBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ImportOdService
{
    private MssqlManager $mssqlLcs;

    public function __construct(
        private WebServiceDispatcher $dispatcher,
        private SageX3Client $sageClient,
        private EntityManagerInterface $em,
        MssqlManagerFactory $mssqlManagerFactory,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcs,
    ) {
        $this->mssqlLcs = $mssqlManagerFactory->create($dbLcs);
    }

    /**
     * Génère le XML, appelle X3 immédiatement et retourne le résultat.
     * Retourne ['erpDocumentId' => string|null, 'message' => string]
     */
    public function generate(array $entete, array $lignes): array
    {
        $xml = XmlBuilder::buildOD($entete, $lignes);

        $ws = new SalesWebService();
        $ws->setName('ZWSIMPOD');
        $ws->setParameter($xml);
        $this->em->persist($ws);

        $erpDocumentId = null;
        $message       = '';

        try {
            $result = $this->sageClient->run('ZWSIMPOD', $xml);

            if (isset($result->resultXml)) {
                $ws->setResult($result->resultXml);
                $xml = simplexml_load_string($result->resultXml);
                if ($xml instanceof \SimpleXMLElement) {
                    $docId = (string) ($xml->GRP[1]?->FLD ?? '');
                    if ($docId !== '') {
                        $erpDocumentId = $docId;
                        $ws->setErpDocumentId($docId);
                    }
                }
            }

            if (isset($result->messages)) {
                $messages = array_map(fn($m) => $m->message, (array) $result->messages);
                $message  = implode("\n", $messages);
                $ws->setMessage($message);
            }

            $ws->setExecuted(true);

        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $ws->setMessage($message);
        }

        $ws->setUpdatedAt(new \DateTime());
        $this->em->flush();

        return ['erpDocumentId' => $erpDocumentId, 'message' => $message];
    }

    /**
     * Valide les lignes OD : Axe analytique, Compte (existence + collectif), Tiers.
     * Retourne un tableau d'erreurs indexé par numéro de ligne (1-based).
     */
    public function validateLignes(array $lignes): array
    {
        // ── 1. Validation Axe 1 (DIE_0 = 'AX1') et Axe 2 (DIE_0 = 'AX2') ──
        $axe1Values = $this->uniqueNonEmpty(array_map(fn($v) => trim($v), array_column($lignes, 'Axe')));
        $axe2Values = $this->uniqueNonEmpty(array_map(fn($v) => trim($v), array_column($lignes, 'Axe2')));

        $validAxe1 = [];
        if (!empty($axe1Values)) {
            $in        = $this->inClause($axe1Values);
            $rows      = $this->mssqlLcs->executeQuery("SELECT CAE.CCE_0 FROM X3_LCS.CACCE CAE WHERE CAE.CCE_0 IN ($in) AND CAE.DIE_0 = 'AX1'");
            $validAxe1 = array_column(array_map(fn($r) => (array) $r, $rows), 'CCE_0');
        }

        $validAxe2 = [];
        if (!empty($axe2Values)) {
            $in        = $this->inClause($axe2Values);
            $rows      = $this->mssqlLcs->executeQuery("SELECT CAE.CCE_0 FROM X3_LCS.CACCE CAE WHERE CAE.CCE_0 IN ($in) AND CAE.DIE_0 = 'AX2'");
            $validAxe2 = array_column(array_map(fn($r) => (array) $r, $rows), 'CCE_0');
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

            $compte   = trim($ligne['Compte'] ?? '');
            $axeCode  = trim($ligne['Axe']  ?? '');
            $axe2Code = trim($ligne['Axe2'] ?? '');

            // Axe 1 obligatoire si compte commence par 2, 6 ou 7
            if ($compte !== '' && in_array($compte[0], ['2', '6', '7'], true) && $axeCode === '') {
                $lineErrors[] = "Axe 1 obligatoire pour le compte « $compte »";
            }

            // Axe 1 — validation si rempli
            if ($axeCode !== '' && !in_array($axeCode, $validAxe1, true)) {
                $lineErrors[] = "Axe 1 « $axeCode » inconnu";
            }

            // Axe 2 — validation si rempli (facultatif)
            if ($axe2Code !== '' && !in_array($axe2Code, $validAxe2, true)) {
                $lineErrors[] = "Axe 2 « $axe2Code » inconnu";
            }

            // Compte
            if ($compte === '') {
                $lineErrors[] = 'Compte manquant';
            } elseif (!array_key_exists($compte, $compteMap)) {
                $lineErrors[] = "Compte « $compte » inconnu";
            } else {
                $tiers = trim($ligne['Tiers'] ?? '');
                if ($compteMap[$compte] === 2) {
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

    private function inClause(array $values): string
    {
        return implode(',', array_map(
            fn($v) => "'" . str_replace("'", "''", $v) . "'",
            $values
        ));
    }
}
