-- Export CA / Marge X3 (commande app:export:ca-marge-x3, service CaMargeX3).
--
-- Source : le cube SEI (SEI_X3_LCS.CONSO_INVOICES), et non plus les tables X3 brutes.
-- Les montants y sont déjà convertis : AMOUNTEURTM est en EUR, AMOUNTCURRENCY dans la
-- devise de la facture. Il n'y a donc plus de taux de change à appliquer.
--
-- Cette requête ne renvoie que des champs bruts : les libellés, les types de document, le
-- signe des avoirs, les prix unitaires et l'article complet sont tous dérivés côté PHP dans
-- CaMargeX3::buildSalesLine(). Chaque colonne est annotée avec l'intitulé qu'elle porte dans
-- le CSV final (voir CaMargeX3::HEADERS).
--
-- Les colonnes achat/marge/lot/TAR du CSV ne sont pas alimentées — elles sortent vides.

SELECT
    I.COMPANYCODE               AS SOCIETE,                 -- CSV : SOCIETE
    CUST.COUNTRYCODE            AS PAYS,                    -- CSV : PAYS
    CONVERT(varchar(10), I.DOCUMENTPOSTINGDATE, 23) AS DATE_FACTURE, -- CSV : DATE FACTURE
    I.DOCUMENTNO                AS NUM_FACTURE,             -- CSV : No FACTURE — son préfixe (3 car.) détermine aussi le type master
    I.DELIVERY_NUMBER           AS SDP_CLIENT,              -- CSV : SDP CLIENT
    I.TYPE_MASTER               AS TYPE_MASTER,             -- CSV : TYPE MASTER — repli quand le préfixe de NUM_FACTURE n'est ni AVB/AVY ni FVB/FVY
    I.SIVTYP                    AS SIVTYP,                  -- CSV : TYPE DOCUMENT (code) — si NULL, PHP reprend le type master ; détermine aussi les types pass-thru
    I.MOTIF_AVOIR_CODE          AS MOTIF_AVOIR_CODE,        -- CSV : MOTIF AVOIR (code)
    MOT.TEXTE_0                 AS MOTIF_AVOIR_LIBELLE,     -- CSV : MOTIF AVOIR (libellé)
    I.INVOICE_REFERENCE         AS REFERENCE_FACTURE,       -- CSV : No DOSSIER INTERNE
    I.USERID                    AS UTILISATEUR_CREATION,    -- CSV : UTILISATEUR CREATION
    -- Même convention que le Backlog Client et le Sell-In / Sell-Out : le rep 1 est le
    -- responsable de zone (SALESMANNAMENPLUS1), le rep 2 le commercial terrain.
    CUST.SALESMANNAMENPLUS1     AS REPRESENTANT_1,          -- CSV : REPRESENTANT 1
    CUST.SALESMANNAME           AS REPRESENTANT_2,          -- CSV : REPRESENTANT 2
    CUST.CUSTOMER_ID            AS CLIENT_COMMANDE,         -- CSV : CLIENT COMMANDE
    I.PAYBY_CUSTOMER            AS TIERS_PAYEUR,            -- CSV : TIERS PAYEUR
    CUST.CUSTOMER_NAME          AS RAISON_SOCIALE,          -- CSV : RAISON SOCIALE
    CUST.INDEPENDANTGROUPMENT   AS INDEPENDANT_GROUPMENT,   -- CSV : GROUPEMENT INDEPENDANT
    C.GENUSCODE                 AS GENRE,                   -- CSV : GENRE
    C.ITEMFAMILYCODE            AS FAMILLE,                 -- CSV : FAMILLE
    I.SERIESNO                  AS COLLECTION,              -- CSV : COLLECTION
    C.SEGMENT_PRODUIT           AS SEGMENT_OFFRE,           -- CSV : SEGMENT OFFRE
    I.ITEMNO                    AS ARTICLE,                 -- CSV : ARTICLE (première moitié — PHP recompose ARTICLE_VARIANT)
    I.VARIANTCODE               AS CODE_VARIANT,            -- CSV : ARTICLE (seconde moitié)
    I.NOOS                      AS NOOS,                    -- CSV : NOOS — PHP traduit 2 en Oui, tout le reste en Non
    C.ITEMDESC                  AS DESIGNATION,             -- CSV : DESIGNATION
    I.QUANTITY                  AS QTE,                     -- CSV : QTE FACTUREE X3 et QTE PHYSIQUE (cette dernière forcée à 0 en pass-thru) ; sert aussi de diviseur aux prix unitaires
    I.CURRENCYCODE              AS DEVISE_FACTURE,          -- CSV : DEVISE FACTURE — si = EUR, les trois colonnes "DEVISE" sortent vides
    I.AMOUNTEURTM               AS MONTANT_EUR,             -- CSV : MONTANT HT EUR, MONTANT HT EUR HORS PASS THRU, et PRIX UNITAIRE HT EUR (÷ QTE)
    I.AMOUNTCURRENCY            AS MONTANT_DEVISE           -- CSV : MONTANT HT DEVISE, MONTANT HT DEVISE HORS PASS THRU, et PRIX UNITAIRE HT DEVISE (÷ QTE)
                                                            -- CSV : ETAT FACTURE — constante 'VALIDE' côté PHP, le cube ne remonte que des documents validés

FROM SEI_X3_LCS.CONSO_INVOICES I

    LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
        ON  I.ITEMNO   = C.ITEM_ID
        AND I.SERIESNO = C.SERIESCODE

    LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
        ON  I.COMPANYCODE = CUST.COMPANY_ID
        AND I.CUSTOMERNO  = CUST.CUSTOMER_ID

    -- Libellé du motif d'avoir (ATABDIV n°8) : toujours lu dans X3, le cube ne porte que le code
    OUTER APPLY (
        SELECT TOP 1 ATX.TEXTE_0
        FROM X3_LCS.ATEXTRA ATX
        WHERE ATX.CODFIC_0 = 'ATABDIV' AND ATX.ZONE_0 = 'LNGDES' AND ATX.LANGUE_0 = 'FRA'
          AND ATX.IDENT1_0 = '8' AND ATX.IDENT2_0 = I.MOTIF_AVOIR_CODE
    ) MOT

WHERE
    I.ISBOHPERIMETERPRODUCT = 1
    AND (
        I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO')
        OR (I.DOCUMENTTYPE = 'ORDER' AND I.ORDERSTATUS = 3 AND I.DLVQTY > 0)
    )
    AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
    AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
    AND I.DOCUMENTPOSTINGDATE >= DATEADD(YEAR, -2, GETDATE())
    AND CUST.MAINNETWORK IS NOT NULL
    AND CUST.REPORTINGDIMENSION NOT IN ('RETAIL', 'E-COMMERCE')  -- exclure le B2C
