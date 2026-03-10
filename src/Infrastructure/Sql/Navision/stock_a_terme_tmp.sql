IF OBJECT_ID('tempdb..#stock_aggregated') IS NOT NULL DROP TABLE #stock_aggregated;
IF OBJECT_ID('tempdb..#achats') IS NOT NULL DROP TABLE #achats;
IF OBJECT_ID('tempdb..#ventes') IS NOT NULL DROP TABLE #ventes;
IF OBJECT_ID('tempdb..#production') IS NOT NULL DROP TABLE #production;
IF OBJECT_ID('tempdb..#produits') IS NOT NULL DROP TABLE #produits;

/* =======================
   1. Stock_aggregated
   ======================= */
select sto.[Item No_], sto.[Variant Code], sum(sto.Stock_actuel) as Stock_actuel
into #stock_aggregated
from (
    SELECT stock.[Item No_], stock.[Variant Code], stock.Quantity as Stock_actuel
    FROM [DB_Datalake].[nav].[Item Ledger Entry] stock
    WHERE stock.[Item No_] <> ''
    and stock.[Location Code] = 'LOGTXM-1'
    and stock.Quantity <> 0

    union all

    -- transfert depuis IKS-PF vers LOGTXM-1
    select pro.[Item No_], pro.[Variant Code], (pro.[Finished Quantity] - tl.[Quantity Shipped EDI]) as Stock_actuel
    FROM [DB_Datalake].[nav].[Prod_ Order Line] pro
    left join DB_Datalake.[nav lcsi bv].Item I on pro.[Item No_] = I.No_
    left join [DB_Datalake].[nav].[Transfer Line] tl on pro.CompanyCode = tl.CompanyCode and pro.[IKS Prod Order No_] = tl.[IKS Prod Order No_] and pro.[Item No_] = tl.[Item No_] and pro.[Variant Code] = tl.[Variant Code]
    where tl.[Transfer-from Code] = 'IKS-PF'
    and tl.[Transfer-to Code] = 'LOGTXM-1'
    and tl.[IKS Prod Order No_] <> ''

    )sto
GROUP BY sto.[Item No_], sto.[Variant Code];
/* =======================
   2. Achats
   ======================= */

select
    achat.No_,
    achat.[Variant Code],

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
    p.No_,
    p.[Variant Code],

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = year(getdate()) AND MONTH(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = month(getdate())
    THEN p.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = YEAR(DATEADD(MONTH, 1, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = MONTH(DATEADD(MONTH, 1, GETDATE()))
    THEN p.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_1,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = YEAR(DATEADD(MONTH, 2, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = MONTH(DATEADD(MONTH, 2, GETDATE()))
    THEN p.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_2,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = YEAR(DATEADD(MONTH, 3, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = MONTH(DATEADD(MONTH, 3, GETDATE()))
    THEN p.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_3,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = YEAR(DATEADD(MONTH, 4, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = MONTH(DATEADD(MONTH, 4, GETDATE()))
    THEN p.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_4,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = YEAR(DATEADD(MONTH, 5, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = MONTH(DATEADD(MONTH, 5, GETDATE()))
    THEN p.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_5,

    (CASE
    WHEN YEAR(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = YEAR(DATEADD(MONTH, 6, GETDATE())) AND MONTH(DATEADD(DAY, 45, p.[Resqueted Ex factory date])) = MONTH(DATEADD(MONTH, 6, GETDATE()))
    THEN p.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_commandees_a_recevoir_mois_m_6

    FROM
    [DB_Datalake].[nav].[Purchase Line] p
    WHERE
    p.No_ <> ''
    AND p.[Type] = 2
    AND p.[Document Type] = 1
    AND DATEADD(DAY, 45, p.[Resqueted Ex factory date]) < DATEADD(DAY, 300, GETDATE())
    and p.[Outstanding Quantity] <> 0
    )achat
group by achat.No_, achat.[Variant Code];

/* =======================
   3. Ventes
   ======================= */

select

    vente.No_,
    vente.[Variant Code],

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
    s.No_,
    s.[Variant Code],

    (CASE
    WHEN YEAR(s.[Requested Delivery Date]) = year(getdate()) AND MONTH(s.[Requested Delivery Date]) = month(getdate())
    THEN s.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m,

    (CASE
    WHEN YEAR(s.[Requested Delivery Date]) = YEAR(DATEADD(MONTH, 1, GETDATE())) AND MONTH(s.[Requested Delivery Date]) = MONTH(DATEADD(MONTH, 1, GETDATE()))
    THEN s.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_1,

    (CASE
    WHEN YEAR(s.[Requested Delivery Date]) = YEAR(DATEADD(MONTH, 2, GETDATE())) AND MONTH(s.[Requested Delivery Date]) = MONTH(DATEADD(MONTH, 2, GETDATE()))
    THEN s.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_2,

    (CASE
    WHEN YEAR(s.[Requested Delivery Date]) = YEAR(DATEADD(MONTH, 3, GETDATE())) AND MONTH(s.[Requested Delivery Date]) = MONTH(DATEADD(MONTH, 3, GETDATE()))
    THEN s.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_3,

    (CASE
    WHEN YEAR(s.[Requested Delivery Date]) = YEAR(DATEADD(MONTH, 4, GETDATE())) AND MONTH(s.[Requested Delivery Date]) = MONTH(DATEADD(MONTH, 4, GETDATE()))
    THEN s.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_4,

    (CASE
    WHEN YEAR(s.[Requested Delivery Date]) = YEAR(DATEADD(MONTH, 5, GETDATE())) AND MONTH(s.[Requested Delivery Date]) = MONTH(DATEADD(MONTH, 5, GETDATE()))
    THEN s.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_5,

    (CASE
    WHEN YEAR(s.[Requested Delivery Date]) = YEAR(DATEADD(MONTH, 6, GETDATE())) AND MONTH(s.[Requested Delivery Date]) = MONTH(DATEADD(MONTH, 6, GETDATE()))
    THEN s.[Outstanding Quantity]
    ELSE 0
    END) AS Quantites_vendues_a_livrer_mois_m_6

    FROM
    [DB_Datalake].[nav].[Sales Line] s
    LEFT JOIN DB_Datalake.[nav].[Sales Header] SH
    ON SH.CompanyCode = S.CompanyCode
    AND SH.No_ = S.[Document No_]
    AND SH.[Document Type] = S.[Document Type]
    left join [DB_Datalake].[nav].[Customer] as c on s.CompanyCode = c.CompanyCode and s.[Bill-to Customer No_] = c.No_
    WHERE
    s.No_ <> ''
    AND s.[Type] = 2
    AND s.[Document Type] = 1
    AND (SH.[Sales order typ] <> 'IR' OR SH.[Order Date] <= '20200101') -- exclusion des forecast JO 2024
    AND s.[Requested Delivery Date] < DATEADD(DAY, 300, GETDATE())
    and c.[Business Model] in ('1_WHOLESALE', '2_DISTRIBUTORS', '3_BTOC')
    and s.[Outstanding Quantity] <> 0
    )vente
group by vente.No_, vente.[Variant Code];

/* =======================
   4. Produits
   ======================= */
SELECT
    p.*,
    I.[Item Family Code],
		I.Description [Item Description],
		I.[Last Series No_]
INTO #produits
FROM
    (
    SELECT DISTINCT [Item No_],[Variant Code] Variant_Code FROM (
    SELECT DISTINCT [Item No_],[Variant Code] FROM #stock_aggregated
    UNION ALL SELECT DISTINCT No_,[Variant Code] FROM #ventes
    UNION ALL SELECT DISTINCT No_,[Variant Code] FROM #achats
    --UNION ALL SELECT DISTINCT [Item No_],[Variant Code] FROM production
    )T
    ) p
    LEFT JOIN DB_Datalake.[nav lcsi bv].Item I ON I.No_ = p.[Item No_];

/* =======================
   4. production
   ======================= */

select
    production.[Item No_],
production.[Variant Code],

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
    pro.[Item No_],
    pro.[Variant Code],

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[Shipment Date])) = year(getdate()) AND MONTH(DATEADD(DAY, 4, tl.[Shipment Date])) = month(getdate())
    THEN pro.[Remaining Quantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[Shipment Date])) = YEAR(DATEADD(MONTH, 1, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[Shipment Date])) = MONTH(DATEADD(MONTH, 1, GETDATE()))
    THEN pro.[Remaining Quantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_1,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[Shipment Date])) = YEAR(DATEADD(MONTH, 2, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[Shipment Date])) = MONTH(DATEADD(MONTH, 2, GETDATE()))
    THEN pro.[Remaining Quantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_2,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[Shipment Date])) = YEAR(DATEADD(MONTH, 3, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[Shipment Date])) = MONTH(DATEADD(MONTH, 3, GETDATE()))
    THEN pro.[Remaining Quantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_3,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[Shipment Date])) = YEAR(DATEADD(MONTH, 4, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[Shipment Date])) = MONTH(DATEADD(MONTH, 4, GETDATE()))
    THEN pro.[Remaining Quantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_4,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[Shipment Date])) = YEAR(DATEADD(MONTH, 5, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[Shipment Date])) = MONTH(DATEADD(MONTH, 5, GETDATE()))
    THEN pro.[Remaining Quantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_5,

    (CASE
    WHEN YEAR(DATEADD(DAY, 4, tl.[Shipment Date])) = YEAR(DATEADD(MONTH, 6, GETDATE())) AND MONTH(DATEADD(DAY, 4, tl.[Shipment Date])) = MONTH(DATEADD(MONTH, 6, GETDATE()))
    THEN pro.[Remaining Quantity]
    ELSE 0
    END) AS Quantites_produites_a_recevoir_mois_m_6

    FROM
    [DB_Datalake].[nav].[Prod_ Order Line] pro
    left join DB_Datalake.[nav lcsi bv].Item I on pro.[Item No_] = I.No_
    left join [DB_Datalake].[nav].[Transfer Line] tl on pro.CompanyCode = tl.CompanyCode and pro.[IKS Prod Order No_] = tl.[IKS Prod Order No_] and pro.[Item No_] = tl.[Item No_]
    WHERE
    pro.[Remaining Quantity] <> 0
    AND pro.[Item No_] <> ''
    AND DATEADD(DAY, 4, tl.[Shipment Date]) < DATEADD(DAY, 300, GETDATE())

    )production
group by production.[Item No_], production.[Variant Code];

SELECT
    p.[Last Series No_],
    p.[Item Family Code],
    p.[Item No_],
    p.[Item Description],
    p.Variant_Code,

    CAST(ISNULL(sa.Stock_actuel, 0) as float) as Stock_actuel,

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
    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)
    AS float) AS STOCK_A_TERME_M,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)
    AS float) AS STOCK_A_TERME_M_1,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)
    AS float) AS STOCK_A_TERME_M_2,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0)
    AS float) AS STOCK_A_TERME_M_3,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_4, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_4, 0)
    AS float) AS STOCK_A_TERME_M_4,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_4, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_4, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_5, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_5, 0)
    AS float) AS STOCK_A_TERME_M_5,

    CAST(
    ISNULL(sa.Stock_actuel, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_1, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_1, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_2, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_2, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_3, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_3, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_4, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_4, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_5, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_5, 0)

    + ISNULL(achats.Quantites_commandees_a_recevoir_mois_m_6, 0)
    - ISNULL(ventes.Quantites_vendues_a_livrer_mois_m_6, 0)
    AS float) AS STOCK_A_TERME_M_6


FROM #produits p
    LEFT JOIN #stock_aggregated sa ON p.[Item No_] = sa.[Item No_] AND p.Variant_Code = sa.[Variant Code]
    LEFT JOIN #ventes as ventes ON p.[Item No_] = ventes.No_ AND p.Variant_Code = ventes.[Variant Code]
    LEFT JOIN #achats as achats ON p.[Item No_] = achats.No_ AND p.Variant_Code = achats.[Variant Code]
    LEFT JOIN #production as production ON p.[Item No_] = production.[Item No_] AND p.Variant_Code = production.[Variant Code]
WHERE p.[Item Family Code] IN ('1 FOOTWEAR', '2 TEXTILE', '3 HARDWARE')
  and p.Variant_Code <> 'SPL'
  and p.[Item No_] <> ''
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
DROP TABLE #stock_aggregated, #achats, #ventes, #production, #produits;
