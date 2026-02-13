SELECT
    SH.CompanyCode
     ,SH.[Sell-to Customer No_]
     ,C.Name
     ,C.[Customer Blocking Code]
     ,C.[Customer Posting Group]
    ,SH.[Currency Code]
    ,CAST(SUM(SL.Quantity-SL.[Quantity Invoiced]) as float) RemainingQuantity
    ,CAST(SUM((SL.Quantity-SL.[Quantity Invoiced]) * SL.[Unit Price]) as float) RemainingAmount
    ,DATEDIFF(DAY,GETDATE(),[Requested Delivery Date]) NbDaysToRDD
FROM
    DB_Datalake.nav.[Sales Line] SL
    LEFT JOIN DB_Datalake.nav.[Sales Header] SH ON SH.No_ = SL.[Document No_] AND SH.CompanyCode = SL.CompanyCode
    LEFT JOIN DB_Datalake.nav.Customer C ON C.No_ = SH.[Sell-to Customer No_] AND SH.CompanyCode = C.CompanyCode
WHERE
    SL.Quantity <> SL.[Quantity Invoiced]
  AND SH.[Document Type] = 1
  AND SH.[Created Date Time] >= DATEADD(MONTH,-18,GETDATE())
  AND C.[Gen_ Bus_ Posting Group] <> 'DOTFACT'
    AND SH.[N° Contrat Dotation] = ''
GROUP BY
    SH.CompanyCode
    ,SH.[Sell-to Customer No_]
    ,C.[Customer Blocking Code]
    ,C.[Customer Posting Group]
    ,C.Name
    ,SH.[Currency Code]
    ,DATEDIFF(DAY,GETDATE(),[Requested Delivery Date])
