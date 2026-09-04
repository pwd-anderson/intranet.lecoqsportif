<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class Achat
{

    private ?MssqlManager $mssqlLcsInstance = null;
    private ?MssqlManager $mssqlSeiInstance = null;
    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        private Divers $divers,
        #[Autowire('%db.lcs%')]
        private string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        private string $dbLcsSei,
    )
    {
    }

    /**
     * Connexions MSSQL établies à la demande seulement (pas dans le constructeur) :
     * ouvrir une connexion PDO vers un serveur distant est coûteux (mssqlLcs peut
     * prendre ~15s), donc on évite de payer ce coût pour les actions qui n'ont besoin
     * que d'une seule des deux connexions.
     */
    private function mssqlLcs(): MssqlManager
    {
        return $this->mssqlLcsInstance ??= $this->mssqlManagerFactory->create($this->dbLcs);
    }

    private function mssqlSei(): MssqlManager
    {
        return $this->mssqlSeiInstance ??= $this->mssqlManagerFactory->create($this->dbLcsSei);
    }

    public function getBAcklogFournisseurNavision(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/backlog_fournisseur_tmp.sql');
            $data = $this->mssqlLcs()->executeMultiStatement($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Backlog fournisseur : Récupération de données achat', $e);
            $this->logger->error('LCS Erreur Backlog fournisseur : Récupération de données achat', ['exception' => $e]);
        }
    }

    /**
     * Backlog fournisseur classique (sans intersites).
     */
    public function getBacklogFournisseur(): array
    {
        try {
            $queryBacklog = $this->sqlFileLoader->load('Sei/backlog_fournisseur.sql');
            $backlogRows  = $this->mssqlSei()->executeQuery($queryBacklog);

            $taux               = $this->divers->getExchangeRatesValues();
            $supplierReferences = $this->divers->getSupplierReferences();

            $today       = new \DateTimeImmutable('today');
            $inThreeDays = $today->modify('+3 days');

            $normalized = [];
            foreach ($backlogRows as $row) {

                $quantite = (int) round((float) $row->QTYUOM_0 - (float) $row->RCPQTYSTU_0);
                $prixUni  = (float) $row->NETPRI_0;
                $devise   = $row->CUR_0;

                $prixEur = (isset($taux[$devise]) && (float) $taux[$devise] > 0)
                    ? round(($prixUni / (float) $taux[$devise]) * $quantite, 2)
                    : 0.0;

                $status = $this->computeBacklogFournisseurStatus(
                    $row->EXTRCPDAT_0 ?? null,
                    $today,
                    $inThreeDays
                );

                $normalized[] = (object) [
                    'SITE_RECEPTION'    => $row->SITE_RECEPTION    ?? null,
                    'SITE_EXPEDITION' => null,                       // 🆕 vide
                    'TYPE_FLUX'       => 'BL Fournisseur',           // 🆕 valeur en dur
                    'COLLECTION'  => $row->COLLECTION  ?? null,
                    'FAMILLE'     => $row->FAMILLE     ?? null,
                    'ITMREF_0'    => $row->ITMREF_0    ?? null,
                    'ITMDES1_0'   => $row->ITMDES1_0   ?? null,
                    'DROPPE'      => $row->DROPPE      ?? null,
                    'BPSNUM_0'    => $row->BPSNUM_0    ?? null,
                    'BPRNAM_0'    => $row->BPRNAM_0    ?? null,
                    'POHNUM_0'    => $row->POHNUM_0    ?? null,
                    'ORDDAT_0'    => $row->ORDDAT_0    ?? null,
                    'XSHIPDAT_0'  => $row->XSHIPDAT_0  ?? null,
                    'EXTRCPDAT_0' => $row->EXTRCPDAT_0 ?? null,
                    'STATUS'      => $status,
                    'ORDREF_0'    => $row->ORDREF_0    ?? null,
                    'QUANTITE'    => $quantite,
                    'PRIX'        => round($prixUni * $quantite, 2),
                    'CUR_0'       => $devise,
                    'PRIX_EUR'    => $prixEur,
                    'MDL_0'       => $row->MDL_0       ?? null,
                    'VALIDE'      => $row->VALIDE           ?? null,
                    'REF_FOURN'   => $supplierReferences[$row->TSICOD_0 ?? ''][$row->ITMREF_0 ?? ''] ?? null,
                    'INTERSITE'   => 'NON',
                ];
            }

            return $normalized;

        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Backlog fournisseur X3', $e);
            $this->logger->error('LCS Erreur Backlog fournisseur X3', ['exception' => $e]);
            return [];
        }
    }

    /**
     * Intersites uniquement (chargés à la demande via la case à cocher).
     */
    public function getBacklogFournisseurIntersites(): array
    {
        try {
            $queryIntersites = $this->sqlFileLoader->load('Sei/backlog_fournisseur_intersites.sql');
            $intersitesRows  = $this->mssqlSei()->executeQuery($queryIntersites);

            $taux = $this->divers->getExchangeRatesValues();

            $today       = new \DateTimeImmutable('today');
            $inThreeDays = $today->modify('+3 days');

            $normalized = [];
            foreach ($intersitesRows as $row) {

                $quantite = (int) round((float) ($row->QTE_RESTANTE ?? 0));
                $prixUni  = (float) ($row->PRIX ?? 0);
                $devise   = $row->DEVISE ?? null;

                $prixEur = ($devise !== null && isset($taux[$devise]) && (float) $taux[$devise] > 0)
                    ? round(($prixUni / (float) $taux[$devise]) * $quantite, 2)
                    : 0.0;

                $status = $this->computeBacklogFournisseurStatus(
                    $row->DATE_LIVRAISON ?? null,
                    $today,
                    $inThreeDays
                );

                $normalized[] = (object) [
                    'SITE_RECEPTION'    => $row->SITE_RECEPTION   ?? null,
                    'SITE_EXPEDITION' => $row->SITE_EXPEDITION ?? null,   // 🆕 nouveau champ
                    'TYPE_FLUX'       => $row->TYPE_FLUX       ?? null,   // 🆕 vient de la donnée
                    'COLLECTION'  => $row->COLLECTION       ?? null,
                    'FAMILLE'     => $row->FAMILLE          ?? null,
                    'ITMREF_0'    => $row->ARTICLE          ?? null,
                    'ITMDES1_0'   => $row->DESIGNATION      ?? null,
                    'DROPPE'      => $row->DROPPE           ?? null,
                    'BPSNUM_0'    => $row->CODE_FOURNISSEUR ?? null,
                    'BPRNAM_0'    => $row->BPRNAM_0    ?? null,
                    'POHNUM_0'    => $row->NO_DOCUMENT      ?? null,
                    'ORDDAT_0'    => $row->DATE_COMMANDE    ?? null,
                    'XSHIPDAT_0'  => $row->DATE_EXPEDITION  ?? null,
                    'EXTRCPDAT_0' => $row->DATE_LIVRAISON   ?? null,
                    'STATUS'      => $status,
                    'ORDREF_0'    => $row->REF_INTERNE      ?? null,
                    'QUANTITE'    => $quantite,
                    'PRIX'        => round($prixUni * $quantite, 2),
                    'CUR_0'       => $devise,
                    'PRIX_EUR'    => $prixEur,
                    'MDL_0'       => $row->MDL_0            ?? null,
                    'VALIDE'      => $row->VALIDE           ?? null,
                    'REF_FOURN'   => null,
                    'INTERSITE'   => 'OUI',
                ];
            }

            return $normalized;

        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Backlog fournisseur intersites', $e);
            $this->logger->error('LCS Erreur Backlog fournisseur intersites', ['exception' => $e]);
            return [];
        }
    }

    /**
     * Calcule le STATUS (EN RETARD / BIENTOT EN RETARD / null) à partir
     * d'une date de livraison prévue. Méthode partagée backlog fournisseur + intersites.
     *
     * @param string|null $dateLivraison Format YYYY-MM-DD ou similaire parseable
     */
    private function computeBacklogFournisseurStatus(
        ?string $dateLivraison,
        \DateTimeImmutable $today,
        \DateTimeImmutable $inThreeDays
    ): ?string {
        if (empty($dateLivraison)) {
            return null;
        }

        try {
            $expectedDate = new \DateTimeImmutable($dateLivraison);
        } catch (\Throwable) {
            return null;
        }

        if ($expectedDate < $today) {
            return 'EN RETARD';
        }
        if ($expectedDate <= $inThreeDays) {
            return 'BIENTOT EN RETARD';
        }
        return null;
    }

    public function getReceptionFournisseur(): array
    {
        try {
            // 1. Requête principale : réceptions
            $query = $this->sqlFileLoader->load('Sei/reception_fournisseur.sql');
            $data = $this->mssqlSei()->executeQuery($query);

            if (empty($data)) {
                return [];
            }

            // 2. Données annexes
            /*$supplierReferences = $this->divers->getSupplierReferences();
            $brands = $this->divers->getBrandTranslations();*/
            $supplierReferences = [];
            $brands = [];

            // 3. Prix unitaires depuis la réception
            $queryPrixReception = $this->sqlFileLoader->load('Sei/reception_fournisseur_prix_reception.sql');
            $resultsPrixReception = $this->mssqlSei()->executeQuery($queryPrixReception);

            $prixDataReception = [];
            foreach ($resultsPrixReception as $prixReception) {
                $prixDataReception[$prixReception->POHNUM_0][$prixReception->ITMREF_0] = [
                    'PRIX'   => $prixReception->PRIX_UNI,
                    'DEVISE' => $prixReception->NETCUR_0,
                ];
            }

            // 4. Prix unitaires depuis la facture (prioritaires)
            $queryPrixFacture = $this->sqlFileLoader->load('Sei/reception_fournisseur_prix_facture.sql');
            $resultsPrixFacture = $this->mssqlSei()->executeQuery($queryPrixFacture);

            $prixDataFacture = [];
            foreach ($resultsPrixFacture as $prixFacture) {
                $prixDataFacture[$prixFacture->POHNUM_0][$prixFacture->ITMREF_0] = [
                    'PRIX'   => $prixFacture->GROPRI_0,
                    'DEVISE' => $prixFacture->NETCUR_0,
                ];
            }

            // 5. Enrichissement des lignes
            foreach ($data as $row) {
                $row->QUANTITE = (int) round((float) $row->QUANTITE);

                // Prix unitaire : on cherche d'abord dans la facture, sinon dans la réception
                if (isset($prixDataFacture[$row->POHNUM_0][$row->ARTICLE])) {
                    $row->PRIX_UNITAIRE = round((float) $prixDataFacture[$row->POHNUM_0][$row->ARTICLE]['PRIX'], 2);
                    $row->DEVISE = $prixDataFacture[$row->POHNUM_0][$row->ARTICLE]['DEVISE'];
                } elseif (isset($prixDataReception[$row->POHNUM_0][$row->ARTICLE])) {
                    $row->PRIX_UNITAIRE = round((float) $prixDataReception[$row->POHNUM_0][$row->ARTICLE]['PRIX'], 2);
                    $row->DEVISE = $prixDataReception[$row->POHNUM_0][$row->ARTICLE]['DEVISE'];
                } else {
                    $row->PRIX_UNITAIRE = 0.0;
                    $row->DEVISE = '';
                }

                // Montant total ligne
                $row->MONTANT_TOT_LIGNE = round($row->PRIX_UNITAIRE * $row->QUANTITE, 2);
            }

            return $data;

        } catch (\Throwable $e) {
            $this->graphMailer->notifyError(
                '❌ LCS Erreur Réception Fournisseur : Récupération de données achat',
                $e
            );

            $this->logger->error(
                'LCS Erreur Réception Fournisseur : Récupération de données achat',
                ['exception' => $e]
            );

            return [];
        }
    }

}
