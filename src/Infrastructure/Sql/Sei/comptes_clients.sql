-- Stat Comptes Clients.
--
-- Les trois colonnes FACTURE_*_MOIS ne viennent plus de la vue : elles sont lues dans
-- MASTER_TABLES.INTRANET_FACTURATION_CLIENT, alimentee depuis le cube SEI par la commande
-- app:import-facturation-client. La vue les calculait sur les tables X3 brutes, qui ne
-- contiennent des factures qu'a partir d'avril 2026 : les plages 12-18 et 18-24 mois y
-- etaient systematiquement a "Non".
--
-- {{TABLE_FACTURATION}} est remplace par Sales::getComptesClients() (suffixe _DEV en dev).
-- Un client absent de la table de facturation (hors perimetre BOH, hors familles
-- FTW/HDW/APL, hors societes LCSI, ou sans reseau principal) ressort a "Non".

SELECT
    V.CODE_CLIENT,
    V.RAISON_SOCIALE,
    ISNULL(F.facture_0_12_mois,  'Non') AS FACTURE_0_12_MOIS,
    ISNULL(F.facture_12_18_mois, 'Non') AS FACTURE_12_18_MOIS,
    ISNULL(F.facture_18_24_mois, 'Non') AS FACTURE_18_24_MOIS,
    V.SOMME_MONTANT_TTC_EUR AS SOLDE,
    V.GROUPEMENT_INDEPENDANT,
    V.STATUT_CLIENT,
    V.CATEGORIE_CLIENT,
    V.CODE_PAYS_CLIENT,
    V.CLIENT_INTERSITE,
    V.CODE_CLIENT_GROUPE,
    V.SOUS_GROUPE,
    V.CODE_GROUPE,
    V.LIBELLE_GROUPE,
    V.CODE_REPRESENTANT_1,
    V.REPRESENTANT_1,
    V.CODE_REPRESENTANT_2,
    V.REPRESENTANT_2,
    V.REMISE_2026_02_FW,
    V.REMISE_2027_01_SS,
    V.BUSINESS_MODEL,
    V.CANAL_DISTRIBUTION,
    V.SEGMENT_OFFRE,
    V.REPORTING_DIMENSION,
    V.GROUPE_TARIF,
    V.GROUPE_REMISE,
    V.LIBELLE_CONDITION_PAIEMENT,
    V.CODE_CONDITION_PAIEMENT,
    V.DEVISE,
    V.ENCOURS_AUTORISE,
    V.CONTROLE_ENCOURS,
    V.CODE_CLIENT_FACTURE,
    V.CODE_CLIENT_PAYEUR,
    V.CODE_FACTOR,
    V.EMAIL_CONFIRMATION_COMMANDE,
    V.EMAIL_FACTURE_COMPTABILITE_RELANCE,
    V.EMAIL_DIRECTEUR_ACHATS,
    V.EMAIL_ACHETEUR_FTW,
    V.EMAIL_ACHETEUR_APP,
    V.NUMERO_SIRET,
    V.TVA_INTRACOMMUNAUTAIRE,
    V.REGIME_TAXE,
    V.CODE_COMPTABLE,
    V.CODE_ADRESSE_DEFAUT,
    V.LIBELLE_ADRESSE,
    V.ADRESSE_LIGNE_1,
    V.ADRESSE_LIGNE_2,
    V.ADRESSE_LIGNE_3,
    V.CODE_POSTAL,
    V.VILLE,
    V.ETAT_REGION,
    V.PAYS_ADRESSE,
    V.CODE_PAYS_ADRESSE,
    V.LANGUE
FROM MASTER_TABLES.PRODXXX_CLIENT_INTRANE V
LEFT JOIN {{TABLE_FACTURATION}} F
       ON F.code_client = V.CODE_CLIENT;
