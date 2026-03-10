SELECT
    STK.LastSeriesNo
     ,STK.ItemFamilyCode
     ,STK.ItemNo
     ,STK.VariantCode

     ,sum(CAST(STK.StockMovementQuantity - ISNULL(AOI.AOI_Qty,0) - ISNULL(AOI.NA_Qty,0) as float)) AvailableInventory_Deducted_NA_Quantity
FROM
    (
        select
            INV.LocationCode
             ,INV.LastSeriesNo
             ,I.ItemFamilyCode
             ,INV.ItemNo
             ,INV.VariantCode
             ,sum(INV.StockMovementQuantity) as StockMovementQuantity
             ,sum(INV.StockMovementValueAmountEur) as StockMovementValueAmountEur


        FROM
            [BI].[DWH].[F_Inventory] INV
            LEFT JOIN [BI].[DWH].D_Item I ON INV.ItemNo = I.ItemNo
        WHERE
            (I.ItemFamilyCode <> '' AND (I.ItemFamilyCode IN ('1 FOOTWEAR', '2 TEXTILE', '3 HARDWARE') )
           OR (I.IsComponent = 1))
        GROUP BY
            INV.LastSeriesNo
            ,I.ItemFamilyCode
            ,INV.LocationCode
            , INV.ItemNo, INV.VariantCode
        HAVING
            SUM(INV.StockMovementQuantity) <> 0
    ) STK

        LEFT JOIN (

        SELECT
            I.[Last Series No_]
             ,[Item Family Code]
             ,[Location Code]
             ,ALO.[Item No_]
             ,ALO.[Variant Code]
             ,SUM([Allocate Qty On Inventory]) AOI_Qty
             ,SUM([Not Allocate Qty]) NA_Qty
        FROM
            DB_Datalake.[nav MULTI].[ALO - Allocation] ALO
            LEFT JOIN DB_Datalake.[nav lcsi bv].Item I ON ALO.[Item No_] = I.No_
        WHERE [Allocate Qty On Inventory] <> 0 OR [Not Allocate Qty] <> 0
        GROUP BY I.
            [Last Series No_]
            ,[Item Family Code]
            ,[Location Code]
            ,ALO.[Item No_]
            ,ALO.[Variant Code]
    ) AOI
                  ON
                      AOI.[Item Family Code] = STK.ItemFamilyCode
    and stK.LastSeriesNo = AOI.[Last Series No_]
    AND STK.LocationCode = AOI.[Location Code]
    AND STK.ItemNo = AOI.[Item No_]
    AND AOI.[Variant Code] = STK.VariantCode

group by 	STK.LastSeriesNo
    ,STK.ItemFamilyCode
    ,STK.ItemNo
    ,STK.VariantCode
