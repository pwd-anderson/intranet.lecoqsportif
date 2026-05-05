/*
   TCD : nombre de commandes non soldées par date de création
   Colonnes : ESHOP / GLOBALE / MARK
   Lignes   : date de création
*/
WITH base AS (
    SELECT DISTINCT
        CASE
            WHEN CAST(SOH.ORDDAT_0 AS DATE) = '1753-01-01' THEN NULL
            ELSE CONVERT(varchar(10), SOH.ORDDAT_0, 23)
        END                     AS ORDDATE,
        SOH.SOHTYP_0,
        SOH.SOHNUM_0
    FROM X3_LCS.SORDER SOH
    LEFT JOIN X3_LCS.SORDERQ SOQ
        ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
    WHERE
        SOH.SOHTYP_0 IN ('ESHOP', 'GLOBA', 'MARK')
        AND SOQ.SOQSTA_0 <> 3
),
src AS (
    SELECT
        ORDDATE,
        SOHTYP_0,
        1 AS cnt
    FROM base
)
SELECT
    ORDDATE                             AS DATE_CREATION,
    ISNULL([ESHOP],   0)                AS ESHOP,
    ISNULL([GLOBALE], 0)                AS GLOBALE,
    ISNULL([MARK],    0)                AS MARK,
    ISNULL([ESHOP], 0)
    + ISNULL([GLOBALE], 0)
    + ISNULL([MARK], 0)                 AS TOTAL
FROM src
PIVOT (
    SUM(cnt) FOR SOHTYP_0 IN ([ESHOP], [GLOBALE], [MARK])
) AS pvt
ORDER BY ORDDATE DESC;
