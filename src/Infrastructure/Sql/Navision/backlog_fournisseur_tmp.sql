SELECT distinct
    PH.CompanyCode AS CODE_COMPANY
              ,PL.[Location Code] as SITE
    ,PL.[Series No_] AS COLLECTION
    ,ITEM.ItemFamilyCode AS FAMILLE
    ,PL.No_ AS ARTICLE
    ,PL.[Variant Code] as CODE_VARIANT
	,PH.[Buy-from Vendor No_] AS CODE_FOURNISSEUR
    ,V.Name AS NOM_FOURNISSEUR
    ,PH.No_ AS NO_COMMANDE
    ,PH.[Order Date] AS DATE_COMMANDE
    ,COALESCE(
    NULLIF(PH.[Confirmed Shipment date], '1753-01-01'),
    NULLIF(PH.[Expected ETD], '1753-01-01')
    ) AS DATE_EXPEDITION
    ,COALESCE(
    NULLIF(PH.[Confirmed Arrival whse date], '1753-01-01'),
    NULLIF(PH.[Expected ETA], '1753-01-01')
    ) AS DATE_LIVRAISON
             -- si date livraison < date aujourd'hui -> EN RETARD en couleur rouge sinon VIDE
    ,CASE
        WHEN COALESCE(
        NULLIF(PH.[Confirmed Arrival whse date], '1753-01-01'),
        NULLIF(PH.[Expected ETA], '1753-01-01')
        ) IS NOT NULL
        AND
        COALESCE(
        NULLIF(PH.[Confirmed Arrival whse date], '1753-01-01'),
        NULLIF(PH.[Expected ETA], '1753-01-01')
        ) < CAST(GETDATE() AS DATE)
        THEN 'EN RETARD'
        ELSE ''
    END AS STATUS
    ,PH.[Your Reference] as REF_INTERNE
	,CAST(PL.[Outstanding Quantity] as float) AS QUANTITE
	,ISNULL(PL.[Outstanding Quantity] * (PL.[Line Amount] / NULLIF(PL.Quantity, 0)), 0) AS MONTANT_DEVISE
	,PL.[Currency Code] AS DEVISE
    ,ISNULL(PL.[Outstanding Quantity] * (PL.[Line Amount (LCY)] / NULLIF(PL.Quantity, 0)), 0) AS MONTANT_EUR
    ,PH.[Shipment Method Code] AS INCOTERM
    ,CASE PH.[Transport Method]
        WHEN 1 THEN 'Transport maritime - By Boat'
        WHEN 2 THEN 'Transport par chemin de fer'
        WHEN 3 THEN 'Transport par route - By Road'
        WHEN 4 THEN 'Transport par air - By Air'
        WHEN 5 THEN 'Envois postaux'
        WHEN 7 THEN 'Installations de transport fixes'
        WHEN 8 THEN 'Transport par navigation intérieure'
        WHEN 9 THEN 'Propulsion propre'
        WHEN 10 THEN 'Transport Maritime puis par Route (ex. Maroc)'
        WHEN 11 THEN 'Sea Air'
        WHEN 12 THEN 'Air frais du fournisseur - Air supplier costs'
        WHEN 13 THEN 'Air au frais du fournisseur (diff Sea/Air)'
        WHEN 14 THEN 'Sea aux frais du client - Sea at Customer costs'
        WHEN 15 THEN 'Sea aux frais du fournisseur - Sea at Vendor costs'
        WHEN 16 THEN 'Air frais du client - Air customer cost'
        ELSE 'Autre'
    END AS MODE_TRANSPORT
    ,PH.[Affect_ Sales Cust Name] AS CLIENT_CONCERNE

FROM
DB_Datalake.[nav].[Purchase Header] PH
LEFT JOIN DB_Datalake.[nav].[Purchase Line] PL ON PH.No_ = PL.[Document No_] and PH.CompanyCode = PL.CompanyCode
LEFT JOIN DB_Datalake.[nav].Vendor V ON V.CompanyCode = PH.CompanyCode AND PH.[Buy-from Vendor No_] = V.No_
LEFT JOIN BI.DWH.D_Item ITEM ON ITEM.ItemNo = PL.No_
WHERE
	PH.[Document Type] = 1
	AND PL.Type = 2
    AND PL.[Outstanding Quantity] <> 0
    AND PH.CompanyCode = 'LCSI BV'
    AND PL.Quantity <> 0
    AND PL.No_ NOT LIKE 'F%'
    AND PL.No_ NOT LIKE 'TB%'
    AND PL.No_ NOT LIKE 'TT%'
    AND PL.No_ NOT LIKE 'A-P%'
    AND PL.No_ NOT LIKE 'A-C%'
    AND PL.No_ NOT LIKE 'DIV%'
