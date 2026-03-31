WITH Base AS (
    SELECT
        SH.CompanyCode,
        SH.[Sell-to Customer No_],
    C.Name,
    C.[Customer Blocking Code],
    C.[Customer Posting Group],
    SH.[Currency Code],
    DATEDIFF(DAY, GETDATE(), [Requested Delivery Date]) AS NbDays,
    (ISNULL(SL.Quantity,0) - ISNULL(SL.[Quantity Invoiced],0)) * (ISNULL(SL.[Line Amount],0)/ NULLIF(ISNULL(SL.Quantity,0),0)) AS LineAmount
FROM DB_Datalake.nav.[Sales Line] SL
    LEFT JOIN DB_Datalake.nav.[Sales Header] SH
ON SH.No_ = SL.[Document No_]
    AND SH.CompanyCode = SL.CompanyCode
    LEFT JOIN DB_Datalake.nav.Customer C
    ON C.No_ = SH.[Sell-to Customer No_]
    AND SH.CompanyCode = C.CompanyCode
WHERE
    SL.Quantity <> SL.[Quantity Invoiced]
  AND SH.[Document Type] = 1
  AND SH.[Created Date Time] >= DATEADD(MONTH,-18,GETDATE())
  AND C.[Gen_ Bus_ Posting Group] <> 'DOTFACT'
    AND SH.[N° Contrat Dotation] = ''
)

SELECT
    CompanyCode,
    [Sell-to Customer No_],
    Name,
    [Customer Blocking Code],
    [Customer Posting Group],
    [Currency Code],

                          -- Past
    CAST(SUM(CASE WHEN NbDays <= 0 THEN LineAmount ELSE 0 END) AS FLOAT) AS PastRDD,

    CAST(SUM(CASE WHEN NbDays BETWEEN 1 AND 7 THEN LineAmount ELSE 0 END) AS FLOAT) AS [1_7],
    CAST(SUM(CASE WHEN NbDays BETWEEN 8 AND 14 THEN LineAmount ELSE 0 END) AS FLOAT) AS [8_14],
    CAST(SUM(CASE WHEN NbDays BETWEEN 15 AND 21 THEN LineAmount ELSE 0 END) AS FLOAT) AS [15_21],
    CAST(SUM(CASE WHEN NbDays BETWEEN 22 AND 28 THEN LineAmount ELSE 0 END) AS FLOAT) AS [22_28],
    CAST(SUM(CASE WHEN NbDays BETWEEN 29 AND 35 THEN LineAmount ELSE 0 END) AS FLOAT) AS [29_35],
    CAST(SUM(CASE WHEN NbDays BETWEEN 36 AND 42 THEN LineAmount ELSE 0 END) AS FLOAT) AS [36_42],
    CAST(SUM(CASE WHEN NbDays BETWEEN 43 AND 49 THEN LineAmount ELSE 0 END) AS FLOAT) AS [43_49],
    CAST(SUM(CASE WHEN NbDays BETWEEN 50 AND 56 THEN LineAmount ELSE 0 END) AS FLOAT) AS [50_56],
    CAST(SUM(CASE WHEN NbDays BETWEEN 57 AND 63 THEN LineAmount ELSE 0 END) AS FLOAT) AS [57_63],
    CAST(SUM(CASE WHEN NbDays BETWEEN 64 AND 70 THEN LineAmount ELSE 0 END) AS FLOAT) AS [64_70],
    CAST(SUM(CASE WHEN NbDays BETWEEN 71 AND 77 THEN LineAmount ELSE 0 END) AS FLOAT) AS [71_77],
    CAST(SUM(CASE WHEN NbDays BETWEEN 78 AND 84 THEN LineAmount ELSE 0 END) AS FLOAT) AS [78_84],
    CAST(SUM(CASE WHEN NbDays BETWEEN 85 AND 91 THEN LineAmount ELSE 0 END) AS FLOAT) AS [85_91],

    CAST(SUM(CASE WHEN NbDays >= 92 THEN LineAmount ELSE 0 END) AS FLOAT) AS [92_plus]

FROM Base

GROUP BY
    CompanyCode,
    [Sell-to Customer No_],
    Name,
    [Customer Blocking Code],
    [Customer Posting Group],
    [Currency Code]
