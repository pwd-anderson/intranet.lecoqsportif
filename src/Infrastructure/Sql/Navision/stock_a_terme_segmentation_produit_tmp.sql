IF OBJECT_ID('tempdb..#stock_aggregated') IS NOT NULL DROP TABLE #stock_aggregated;
IF OBJECT_ID('tempdb..#achats') IS NOT NULL DROP TABLE #achats;
IF OBJECT_ID('tempdb..#ventes') IS NOT NULL DROP TABLE #ventes;
IF OBJECT_ID('tempdb..#production') IS NOT NULL DROP TABLE #production;
IF OBJECT_ID('tempdb..#produits') IS NOT NULL DROP TABLE #produits;
IF OBJECT_ID('tempdb..#segmentation') IS NOT NULL DROP TABLE #segmentation;

/* =======================
   1. Stock_aggregated
   ======================= */
select sto.[ItemNo], sto.[VariantCode], sum(sto.Stock_actuel) as Stock_actuel
into #stock_aggregated
from (
         SELECT stock.[ItemNo], stock.[VariantCode], stock.Quantity as Stock_actuel
         FROM [NAV_LCS].ItemLedgerEntry stock
         WHERE stock.[ItemNo] <> ''
           and stock.[LocationCode] = 'LOGTXM-1'
           and stock.Quantity <> 0

         union all

         -- transfert depuis IKS-PF vers LOGTXM-1
         select pro.[ItemNo], pro.[VariantCode], (pro.[FinishedQuantity] - tl.[QuantityShippedEDI]) as Stock_actuel
         FROM [NAV_LCS].[ProdOrderLine] pro
             left join [NAV_LCS].Item I on pro.[ItemNo] = I.ItemNo AND pro.CompanyCode = I.CompanyCode
             left join [NAV_LCS].[TransferLine] tl on pro.CompanyCode = tl.CompanyCode and pro.[IKSProdOrderNo] = tl.[IKSProdOrderNo] and pro.[ItemNo] = tl.[ItemNo] and pro.[VariantCode] = tl.[VariantCode]
         where tl.[TransferfromCode] = 'IKS-PF'
           and tl.[TransfertoCode] = 'LOGTXM-1'
           and tl.[IKSProdOrderNo] <> ''

     )sto
GROUP BY sto.[ItemNo], sto.[VariantCode];
/* =======================
   2. Achats
   ======================= */

select
    achat.ItemNo,
    achat.[VariantCode],

    sum(Quantites_commandees_a_recevoir_mois_m) Quantites_commandees_a_recevoir_mois_m,

    sum(Quantites_commandees_a_recevoir_mois_m_1) Quantites_commandees_a_recevoir_mois_m_1,

    sum(Quantites_commandees_a_recevoir_mois_m_2) Quantites_commandees_a_recevoir_mois_m_2,

    sum(Quantites_commandees_a_recevoir_mois_m_3) Quantites_commandees_a_recevoir_mois_m_3,

    sum(Quantites_commandees_a_recevoir_mois_m_4) Quantites_commandees_a_recevoir_mois_m_4,

    sum(Quantites_commandees_a_recevoir_mois_m_5) Quantites_commandees_a_recevoir_mois_m_5,

    sum(Quantites_commandees_a_recevoir_mois_m_6) Quantites_commandees_a_recevoir_mois_m_6

INTO #achats
from(

        SELECT
            p.ItemNo,
            p.[VariantCode],

            (CASE
                 WHEN YEAR(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = year(getdate()) AND MONTH(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = month(getdate())
    THEN p.[OutstandingQuantity]
    ELSE 0
END) AS Quantites_commandees_a_recevoir_mois_m,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = YEAR(DATEADD(MONTH, 1, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = MONTH(DATEADD(MONTH, 1, GETDATE()))
    THEN p.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_1,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = YEAR(DATEADD(MONTH, 2, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = MONTH(DATEADD(MONTH, 2, GETDATE()))
    THEN p.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_2,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = YEAR(DATEADD(MONTH, 3, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = MONTH(DATEADD(MONTH, 3, GETDATE()))
    THEN p.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_3,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = YEAR(DATEADD(MONTH, 4, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = MONTH(DATEADD(MONTH, 4, GETDATE()))
    THEN p.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_4,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = YEAR(DATEADD(MONTH, 5, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = MONTH(DATEADD(MONTH, 5, GETDATE()))
    THEN p.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_5,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = YEAR(DATEADD(MONTH, 6, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[ResquetedExfactorydate])) = MONTH(DATEADD(MONTH, 6, GETDATE()))
    THEN p.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_6

    FROM
    [NAV_LCS].[PurchaseLine] p
    WHERE
    p.ItemNo <> ''
    AND p.[Type] = 2
    AND p.[DocumentType] = 1
    AND DATEADD(DAY, 45, p.[ResquetedExfactorydate]) < DATEADD(DAY, 300, GETDATE())
    and p.[OutstandingQuantity] <> 0
    )achat
group by achat.ItemNo, achat.[VariantCode];

/* =======================
   3. Ventes
   ======================= */

select

    vente.ItemNo,
    vente.[VariantCode],

    sum(Quantites_vendues_a_livrer_mois_m) Quantites_vendues_a_livrer_mois_m,

    sum(Quantites_vendues_a_livrer_mois_m_1) Quantites_vendues_a_livrer_mois_m_1,

    sum(Quantites_vendues_a_livrer_mois_m_2) Quantites_vendues_a_livrer_mois_m_2,

    sum(Quantites_vendues_a_livrer_mois_m_3) Quantites_vendues_a_livrer_mois_m_3,

    sum(Quantites_vendues_a_livrer_mois_m_4) Quantites_vendues_a_livrer_mois_m_4,

    sum(Quantites_vendues_a_livrer_mois_m_5) Quantites_vendues_a_livrer_mois_m_5,

    sum(Quantites_vendues_a_livrer_mois_m_6) Quantites_vendues_a_livrer_mois_m_6

into #ventes
from(


        SELECT
            S.ItemNo,
            S.[VariantCode],

            (CASE
                 WHEN YEAR(S.[RequestedDeliveryDate]) = year(getdate()) AND MONTH(S.[RequestedDeliveryDate]) = month(getdate())
    THEN S.[OutstandingQuantity]
    ELSE 0
END) AS Quantites_vendues_a_livrer_mois_m,

    (CASE
    WHEN YEAR(S.[RequestedDeliveryDate]) = YEAR(DATEADD(MONTH, 1, GETDATE())) AND MONTH(S.[RequestedDeliveryDate]) = MONTH(DATEADD(MONTH, 1, GETDATE()))
    THEN S.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_1,

    (CASE
    WHEN YEAR(S.[RequestedDeliveryDate]) = YEAR(DATEADD(MONTH, 2, GETDATE())) AND MONTH(S.[RequestedDeliveryDate]) = MONTH(DATEADD(MONTH, 2, GETDATE()))
    THEN S.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_2,

    (CASE
    WHEN YEAR(S.[RequestedDeliveryDate]) = YEAR(DATEADD(MONTH, 3, GETDATE())) AND MONTH(S.[RequestedDeliveryDate]) = MONTH(DATEADD(MONTH, 3, GETDATE()))
    THEN S.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_3,

    (CASE
    WHEN YEAR(S.[RequestedDeliveryDate]) = YEAR(DATEADD(MONTH, 4, GETDATE())) AND MONTH(S.[RequestedDeliveryDate]) = MONTH(DATEADD(MONTH, 4, GETDATE()))
    THEN S.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_4,

    (CASE
    WHEN YEAR(S.[RequestedDeliveryDate]) = YEAR(DATEADD(MONTH, 5, GETDATE())) AND MONTH(S.[RequestedDeliveryDate]) = MONTH(DATEADD(MONTH, 5, GETDATE()))
    THEN S.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_5,

    (CASE
    WHEN YEAR(S.[RequestedDeliveryDate]) = YEAR(DATEADD(MONTH, 6, GETDATE())) AND MONTH(S.[RequestedDeliveryDate]) = MONTH(DATEADD(MONTH, 6, GETDATE()))
    THEN S.[OutstandingQuantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_6

    FROM
    [NAV_LCS].[SalesLine] S
    LEFT JOIN [NAV_LCS].[SalesHeader] SH
    ON SH.CompanyCode = S.CompanyCode
    AND SH.DocumentNo = S.[DocumentNo]
    AND SH.[DocumentType] = S.[DocumentType]
    left join [NAV_LCS].[Customer] as c on S.CompanyCode = c.CompanyCode and S.[BilltoCustomerNo] = c.CustomerNo
    WHERE
    S.ItemNo <> ''
    AND S.[Type] = 2
    AND S.[DocumentType] = 1
    AND (SH.[Salesordertyp] <> 'IR' OR SH.[OrderDate] <= '20200101') -- exclusion des forecast JO 2024
    AND S.[RequestedDeliveryDate] < DATEADD(DAY, 300, GETDATE())
    and c.[BusinessModel] in ('1_WHOLESALE', '2_DISTRIBUTORS', '3_BTOC')
    and S.[OutstandingQuantity] <> 0
    )vente
group by vente.ItemNo, vente.[VariantCode];

/* =======================
   4. Produits
   ======================= */
SELECT
    p.*,
    I.[ItemFamilyCode],
    I.Description [Item Description],
		I.[LastSeriesNo]
INTO #produits
FROM
    (
    SELECT DISTINCT [ItemNo],[VariantCode] Variant_Code FROM (
    SELECT DISTINCT [ItemNo],[VariantCode] FROM #stock_aggregated
    UNION ALL SELECT DISTINCT ItemNo,[VariantCode] FROM #ventes
    UNION ALL SELECT DISTINCT ItemNo,[VariantCode] FROM #achats
    --UNION ALL SELECT DISTINCT [Item No_],[Variant Code] FROM production
    )T
    ) p
    LEFT JOIN NAV_LCS.Item I ON I.ItemNo = p.[ItemNo];

/* =======================
   4. production
   ======================= */

select
    production.[ItemNo],
    production.[VariantCode],

    sum(Quantites_produites_a_recevoir_mois_m) Quantites_produites_a_recevoir_mois_m,

    sum(Quantites_produites_a_recevoir_mois_m_1) Quantites_produites_a_recevoir_mois_m_1,

    sum(Quantites_produites_a_recevoir_mois_m_2) Quantites_produites_a_recevoir_mois_m_2,

    sum(Quantites_produites_a_recevoir_mois_m_3) Quantites_produites_a_recevoir_mois_m_3,

    sum(Quantites_produites_a_recevoir_mois_m_4) Quantites_produites_a_recevoir_mois_m_4,

    sum(Quantites_produites_a_recevoir_mois_m_5) Quantites_produites_a_recevoir_mois_m_5,

    sum(Quantites_produites_a_recevoir_mois_m_6) Quantites_produites_a_recevoir_mois_m_6

into #production
from (

         SELECT
             pro.[ItemNo],
             pro.[VariantCode],

             (CASE
                  WHEN YEAR(DATEADD(DAY, 4, tl.[ShipmentDate])) = year(getdate()) AND MONTH(DATEADD(DAY, 4, tl.[ShipmentDate])) = month(getdate())
    THEN pro.[RemainingQuantity]
    ELSE 0
END) AS Quantites_produites_a_recevoir_mois_m,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[ShipmentDate])) = YEAR(DATEADD(MONTH, 1, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[ShipmentDate])) = MONTH(DATEADD(MONTH, 1, GETDATE()))
    THEN pro.[RemainingQuantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_1,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[ShipmentDate])) = YEAR(DATEADD(MONTH, 2, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[ShipmentDate])) = MONTH(DATEADD(MONTH, 2, GETDATE()))
    THEN pro.[RemainingQuantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_2,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[ShipmentDate])) = YEAR(DATEADD(MONTH, 3, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[ShipmentDate])) = MONTH(DATEADD(MONTH, 3, GETDATE()))
    THEN pro.[RemainingQuantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_3,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[ShipmentDate])) = YEAR(DATEADD(MONTH, 4, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[ShipmentDate])) = MONTH(DATEADD(MONTH, 4, GETDATE()))
    THEN pro.[RemainingQuantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_4,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[ShipmentDate])) = YEAR(DATEADD(MONTH, 5, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[ShipmentDate])) = MONTH(DATEADD(MONTH, 5, GETDATE()))
    THEN pro.[RemainingQuantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_5,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[ShipmentDate])) = YEAR(DATEADD(MONTH, 6, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[ShipmentDate])) = MONTH(DATEADD(MONTH, 6, GETDATE()))
    THEN pro.[RemainingQuantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_6

    FROM
    [NAV_LCS].[ProdOrderLine] pro
    left join [NAV_LCS].Item I on pro.[ItemNo] = I.ItemNo AND pro.CompanyCode = I.CompanyCode
    left join [NAV_LCS].[TransferLine] tl on pro.CompanyCode = tl.CompanyCode and pro.[IKSProdOrderNo] = tl.[IKSProdOrderNo] and pro.[ItemNo] = tl.[ItemNo]
    and pro.[VariantCode] = tl.[VariantCode] and pro.[SourceNo] = tl.[DocumentNo]
    WHERE
    pro.[RemainingQuantity] <> 0
    AND pro.[ItemNo] <> ''
    AND DATEADD(DAY, 4, tl.[ShipmentDate]) < DATEADD(DAY, 300, GETDATE())

    )production
group by production.[ItemNo], production.[VariantCode];

/* =======================
   5. segmentation
   ======================= */

SELECT
    A.[SeriesNo]        AS Series_No,
    A.[ItemNo]          AS Item_No,
    IV.VariantCode               AS Variant_Code,
    IV.BarCode,
    A.[MarketPrice],
    A.[MarketPriceCurrency],
    IV.Width,
    IV.Height,
    IV.Length,
    IV.[ReferenceGrossWeightinkg] AS Gross_Weight,
    A.[CountryCodeOfOrigin],
    A.[PDMMaterialCode],
    A.CompanyCode,
    A.[ItemFamilyCode] as ItemFamilyCode
INTO #segmentation
FROM [NAV_LCS].[SeriesLine] A
    LEFT JOIN [NAV_LCS].[ItemVariant] IV
ON IV.[ItemNo] = A.[ItemNo] -- and A.CompanyCode = IV.CompanyCode
WHERE
    A.[DroppedItem] = 0
  AND A.CompanyCode = 'LCSI BV'
  and A.[ItemFamilyCode] IN ('1 FOOTWEAR', '2 TEXTILE', '3 HARDWARE')
  and IV.VariantCode <> 'SPL';

SELECT
    p.[LastSeriesNo],
    p.[ItemFamilyCode],
    p.[ItemNo],
    p.[Item Description],
    p.Variant_Code
    ,seg.BarCode as EAN_CODE
    ,seg.[MarketPrice]
    ,seg.[MarketPriceCurrency]
    ,seg.Width
    ,seg.Height
    ,seg.Length
    ,seg.Gross_Weight
    ,seg.[Country Code Of Origin]
    ,seg.[PDM Material Code]

    ,CAST(ISNULL(sa.Stock_actuel, 0) as float) as Stock_actuel,

    CAST(ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0) as float) as BUY_MOIS_M,
    CAST(ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0) as float) as SALES_MOIS_M,
    0 as RESA_ETAIL_MOIS_M,
    0 as RESA_RETAIL_MOIS_M,

    CAST(ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m_1, 0) as float) as BUY_MOIS_M_1,
    CAST(ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0) as float) as SALES_MOIS_M_1,
    0 as RESA_ETAIL_MOIS_M_1,
    0 as RESA_RETAIL_MOIS_M_1,

    CAST(ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m_2, 0) as float) as BUY_MOIS_M_2,
    CAST(ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0) as float) as SALES_MOIS_M_2,
    0 as RESA_ETAIL_MOIS_M_2,
    0 as RESA_RETAIL_MOIS_M_2,

    CAST(ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m_3, 0) as float) as BUY_MOIS_M_3,
    CAST(ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0) as float) as SALES_MOIS_M_3,
    0 as RESA_ETAIL_MOIS_M_3,
    0 as RESA_RETAIL_MOIS_M_3,

    CAST(ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_4, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m_4, 0) as float) as BUY_MOIS_M_4,
    CAST(ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_4, 0) as float) as SALES_MOIS_M_4,
    0 as RESA_ETAIL_MOIS_M_4,
    0 as RESA_RETAIL_MOIS_M_4,

    CAST(ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_5, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m_5, 0) as float) as BUY_MOIS_M_5,
    CAST(ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_5, 0) as float) as SALES_MOIS_M_5,
    0 as RESA_ETAIL_MOIS_M_5,
    0 as RESA_RETAIL_MOIS_M_5,

    CAST(ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_6, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m_6, 0) as float) as BUY_MOIS_M_6,
    CAST(ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_6, 0) as float) as SALES_MOIS_M_6,
    0 as RESA_ETAIL_MOIS_M_6,
    0 as RESA_RETAIL_MOIS_M_6,

    /* =======================
   STOCK A TERME CALCULÉ
   ======================= */

    CAST(
    ISNULL(sa.Stock_actuel, 0)
    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)
    AS float) AS STOCK_A_TERME_M,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)
    AS float) AS STOCK_A_TERME_M_1,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)
    AS float) AS STOCK_A_TERME_M_2,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_3, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0)
    AS float) AS STOCK_A_TERME_M_3,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_3, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_4, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_4, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_4, 0)
    AS float) AS STOCK_A_TERME_M_4,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_3, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_4, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_4, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_4, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_5, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_5, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_5, 0)
    AS float) AS STOCK_A_TERME_M_5,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0) + ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_3, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_4, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_4, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_4, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_5, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_5, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_5, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_6, 0)  + ISNULL(production.Quantites_produites_a_recevoir_mois_m_6, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_6, 0)
    AS float) AS STOCK_A_TERME_M_6


FROM #produits p

    LEFT JOIN #stock_aggregated sa ON p.[ItemNo] = sa.[ItemNo] AND p.Variant_Code = sa.[VariantCode]
    LEFT JOIN #ventes as ventes ON p.[ItemNo] = ventes.ItemNo AND p.Variant_Code = ventes.[VariantCode]
    LEFT JOIN #achats as achats ON p.[ItemNo] = achats.ItemNo AND p.Variant_Code = achats.[VariantCode]
    LEFT JOIN #production as production ON p.[ItemNo] = production.[ItemNo] AND p.Variant_Code = production.[VariantCode]
    LEFT JOIN #segmentation seg ON p.[ItemNo] = seg.Item_No AND p.Variant_Code = seg.Variant_Code and seg.Series_No = p.[LastSeriesNo] and seg.ItemFamilyCode = p.[ItemFamilyCode]
WHERE p.[ItemFamilyCode] IN ('1 FOOTWEAR', '2 TEXTILE', '3 HARDWARE')
  and p.Variant_Code <> 'SPL'
  and p.[ItemNo] <> ''
  AND (
    ISNULL(sa.Stock_actuel, 0) <> 0 OR
    ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0) <> 0 OR
    ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0) <> 0 OR
    ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0) <> 0 OR
    ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0) <> 0 OR
    ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_4, 0) <> 0 OR
    ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_5, 0) <> 0 OR
    ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_6, 0) <> 0 OR
    ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0) <> 0 OR
    ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0) <> 0 OR
    ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0) <> 0 OR
    ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0) <> 0 OR
    ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_4, 0) <> 0 OR
    ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_5, 0) <> 0 OR
    ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_6, 0) <> 0 OR
    ISNULL(production.Quantites_produites_a_recevoir_mois_m, 0) <> 0 OR
    ISNULL(production.Quantites_produites_a_recevoir_mois_m_1, 0) <> 0 OR
    ISNULL(production.Quantites_produites_a_recevoir_mois_m_2, 0) <> 0 OR
    ISNULL(production.Quantites_produites_a_recevoir_mois_m_3, 0) <> 0 OR
    ISNULL(production.Quantites_produites_a_recevoir_mois_m_4, 0) <> 0 OR
    ISNULL(production.Quantites_produites_a_recevoir_mois_m_5, 0) <> 0 OR
    ISNULL(production.Quantites_produites_a_recevoir_mois_m_6, 0) <> 0
    );

-- on nettoie a la fin de la requete
DROP TABLE #stock_aggregated, #achats, #ventes, #production, #produits, #segmentation;
