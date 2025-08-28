<?php

namespace App\Service;

use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Stock
{

    public function __construct(
        private MssqlManager $mssqlManager,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function getStockATerme(): array
    {
        try {
            $query = "WITH
                    stock_aggregated AS (
                        SELECT
                        stock.[Item No_],
                        stock.[Variant Code],
                        SUM(CASE
                        WHEN stock.[Location Code] NOT IN ('TRANSIT IN', 'TRANSIT EX')
                        AND stock.[Location Code] NOT LIKE 'LOGTX%'
                        AND stock.[Location Code] NOT LIKE 'DIRECT%'
                        THEN stock.Quantity ELSE 0 END) AS Stock_channel,

                        SUM(CASE
                        WHEN stock.[Location Code] LIKE 'LOGTX%'
                        THEN stock.Quantity ELSE 0 END) AS Stock_log_tex,

                        SUM(CASE
                        WHEN stock.[Location Code] IN ('TRANSIT IN', 'TRANSIT EX')
                        THEN stock.Quantity ELSE 0 END) AS Stock_en_transit_interne,

                        SUM(CASE
                        WHEN stock.[Location Code] LIKE 'DIRECT%'
                        THEN stock.Quantity ELSE 0 END) AS Stock_direct
                        FROM [DB_Datalake].[nav].[Item Ledger Entry] stock

                        WHERE stock.[Item No_] <> ''
                        GROUP BY stock.[Item No_], stock.[Variant Code]

                        HAVING
                            SUM(CASE
                            WHEN stock.[Location Code] NOT IN ('TRANSIT IN', 'TRANSIT EX')
                            AND stock.[Location Code] NOT LIKE 'LOGTX%'
                            AND stock.[Location Code] NOT LIKE 'DIRECT%'
                            THEN stock.Quantity ELSE 0 END) <> 0
                        OR
                            SUM(CASE
                            WHEN stock.[Location Code] LIKE 'LOGTX%'
                            THEN stock.Quantity ELSE 0 END) <> 0
                        OR
                            SUM(CASE
                            WHEN stock.[Location Code] IN ('TRANSIT IN', 'TRANSIT EX')
                            THEN stock.Quantity ELSE 0 END) <> 0
                        OR
                            SUM(CASE
                            WHEN stock.[Location Code] LIKE 'DIRECT%'
                            THEN stock.Quantity ELSE 0 END) <> 0

                    ),

                    ventes AS (
                        SELECT
                            s.No_
                            ,s.[Variant Code]
                            ,SUM([Outstanding Quantity]) AS Qty_vendues_non_livrees
                        FROM
                            [DB_Datalake].[nav].[Sales Line] s
                        WHERE
                            s.No_ <> ''
                            AND s.[Type] = 2
                            AND s.[Document Type] = 1
                        GROUP BY
                            s.No_
                            ,s.[Variant Code]
                        HAVING
                            SUM([Outstanding Quantity]) <> 0
                    ),
                    achats AS (
                        SELECT
                            p.No_
                            ,p.[Variant Code]
                            ,SUM(p.[Outstanding Quantity]) AS Qty_achats_non_receptiones
                        FROM
                            [DB_Datalake].[nav].[Purchase Line] p
                        WHERE
                            p.No_ <> ''
                            AND YEAR(p.ETD) = 1753
                            AND p.[Type] = 2
                            AND p.[Document Type] = 1
                        GROUP BY
                            p.No_
                            ,p.[Variant Code]
                        HAVING
                            SUM(p.[Outstanding Quantity]) <> 0
                    ),
                    achats_flottant AS (
                        SELECT
                            pf.No_
                            ,pf.[Variant Code]
                            ,SUM(pf.[Outstanding Quantity]) AS Stock_flottant
                        FROM
                            [DB_Datalake].[nav].[Purchase Line] pf
                        WHERE
                            pf.No_ <> ''
                            AND YEAR(pf.ETD) <> 1753
                            AND pf.[Type] = 2
                            AND pf.[Document Type] = 1
                        GROUP BY
                            pf.No_
                            ,pf.[Variant Code]
                        HAVING
                            SUM(pf.[Outstanding Quantity]) <> 0
                    ),
                    production AS (
                        SELECT
                            pro.[Item No_]
                            ,pro.[Variant Code]
                            ,SUM(pro.[Remaining Quantity]) AS Qty_to_be_produced
                        FROM
                            [DB_Datalake].[nav].[Prod_ Order Line] pro
                        WHERE
                            pro.[Remaining Quantity] <> 0
                            AND pro.[Item No_] <> ''
                        GROUP BY
                            pro.[Item No_]
                            ,pro.[Variant Code]
                        HAVING
                            SUM(pro.[Remaining Quantity]) <> 0
                    )

                    ,
                    produits as (
                        SELECT
                            p.*
                            ,I.[Item Family Code]
                            ,I.Description [Item Description]
                            ,I.[Last Series No_]
                        FROM
                        (
                            SELECT DISTINCT [Item No_],[Variant Code] Variant_Code FROM (
                                SELECT DISTINCT [Item No_],[Variant Code] FROM stock_aggregated
                                UNION ALL SELECT DISTINCT No_,[Variant Code] FROM ventes
                                UNION ALL SELECT DISTINCT No_,[Variant Code] FROM achats
                                UNION ALL SELECT DISTINCT No_,[Variant Code] FROM achats_flottant
                                UNION ALL SELECT DISTINCT [Item No_],[Variant Code] FROM production
                            )T
                        ) p
                        LEFT JOIN DB_Datalake.[nav lcsi bv].Item I ON I.No_ = p.[Item No_]
                    )

                    SELECT
                        p.[Last Series No_]
                        ,p.[Item Family Code]

                        ,p.[Item No_]
                        ,p.[Item Description]
                        ,p.Variant_Code

                        ,ISNULL(sa.Stock_channel, 0) AS Stock_channel
                        ,ISNULL(sa.Stock_log_tex, 0) AS Stock_log_tex
                        ,ISNULL(sa.Stock_en_transit_interne, 0) AS Stock_en_transit_interne
                        ,ISNULL(achats_flottant.Stock_flottant, 0) AS Stock_flottant
                        ,ISNULL(achats.Qty_achats_non_receptiones, 0) AS Qty_achats_non_receptiones
                        ,ISNULL(sa.Stock_direct, 0) AS Stock_direct
                        ,ISNULL(production.Qty_to_be_produced, 0) AS Qty_to_be_produced
                        ,ISNULL(ventes.Qty_vendues_non_livrees, 0) AS Qty_vendues_non_livrees

                    -- INTO #RESULT

                    FROM produits p
                        LEFT JOIN stock_aggregated sa ON p.[Item No_] = sa.[Item No_] AND p.Variant_Code = sa.[Variant Code]
                        LEFT JOIN ventes ON p.[Item No_] = ventes.No_ AND p.Variant_Code = ventes.[Variant Code]
                        LEFT JOIN achats ON p.[Item No_] = achats.No_ AND p.Variant_Code = achats.[Variant Code]
                        LEFT JOIN achats_flottant ON p.[Item No_] = achats_flottant.No_ AND p.Variant_Code = achats_flottant.[Variant Code]
                        LEFT JOIN production ON p.[Item No_] = production.[Item No_] AND p.Variant_Code = production.[Variant Code]

                    WHERE
                        p.[Item Family Code] IN ('1 FOOTWEAR', '2 TEXTILE', '3 HARDWARE')";

            $data = $this->mssqlManager->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme : Récupération de données stock', ['exception' => $e]);
        }
    }



}
