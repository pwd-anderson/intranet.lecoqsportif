-- Référentiel clients : hiérarchie commerciale de chaque client (une ligne par client).
-- Source : vue SEI LCS_CUSTOMER. Sert de dimension à toutes les analyses (filtres Business model, Groupe client,
-- Groupement, Manager N+1, Commercial) et aux fiches client / groupement.
-- LCS_CUSTOMER ne contient que la société LCSI : les clients des filiales (Espagne, Italie) n'ont pas de hiérarchie
-- et ressortent « (non renseigné) », comme dans le template.
SELECT
    CUST.CUSTOMER_ID            AS CODE_CLIENT,
    CUST.CUSTOMER_NAME          AS NOM_CLIENT,
    CUST.CITY                   AS VILLE,
    CUST.COUNTRYCODE            AS PAYS,
    CUST.BUSINESS_MODEL_1       AS BUSINESS_MODEL,
    CUST.CUSTOMERGROUPCODE      AS GROUPE_CLIENT,
    CUST.INDEPENDANTGROUPMENT   AS GROUPEMENT,
    CUST.SALESMANNAMENPLUS1     AS MANAGER_N1,
    CUST.SALESMANNAME           AS COMMERCIAL,
    CUST.ISACTIVE               AS ACTIF
FROM SEI_X3_LCS.LCS_CUSTOMER CUST
WHERE CUST.COMPANY_ID = 'LCSI';
