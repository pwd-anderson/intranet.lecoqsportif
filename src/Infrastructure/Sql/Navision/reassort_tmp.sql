WITH BaseData AS (
    SELECT
        f.CompanyCode,
        i.ItemFamilyCode,
        f.SeriesNo,
        f.CustomerNo,
        c.Name,
        f.SalesOrderType,
        f.BOH_Quantity
    FROM BI.DWH.F_Sales f
             LEFT JOIN BI.DWH.D_Item i
                       ON f.ItemNo = i.ItemNo
             LEFT JOIN BI.DWH.D_Customer c
                       ON f.CustomerNo = c.Code
                           AND f.CompanyCode = c.CompanyCode
    WHERE i.ItemFamilyCode IN ('1 FOOTWEAR', '2 TEXTILE')
      AND f.SeriesNo IN ('2026-01-SS', '2026-02-FW', '2025-01-SS', '2025-02-FW', '2024-01-SS', '2024-02-FW')
      AND f.SalesOrderType IN ('PS', 'RE')
      AND f.CompanyCode = 'LCSI BV'
      AND c.BusinessModelCode NOT IN ('3_BTOC', '5_INTERCO')
),

     Aggregated AS (
         SELECT
             CompanyCode,
             ItemFamilyCode,
             SeriesNo,
             CustomerNo,
             Name,
             SUM(CASE WHEN SalesOrderType = 'PS' THEN BOH_Quantity ELSE 0 END) AS quantity_ps,
             SUM(CASE WHEN SalesOrderType = 'RE' THEN BOH_Quantity ELSE 0 END) AS quantity_re
         FROM BaseData
         GROUP BY
             CompanyCode,
             ItemFamilyCode,
             SeriesNo,
             CustomerNo,
             Name
     ),

     Filtered AS (
         SELECT *
         FROM Aggregated
         WHERE quantity_ps > 0
     )

SELECT
    CustomerNo,
    Name,
    ItemFamilyCode,

    -- 2024-01-SS
    SUM(CASE WHEN SeriesNo = '2024-01-SS' THEN quantity_ps ELSE 0 END) AS [2024-01-SS_ps],
    SUM(CASE WHEN SeriesNo = '2024-01-SS' THEN quantity_re ELSE 0 END) AS [2024-01-SS_re],
    CAST(
        CASE
            WHEN SUM(CASE WHEN SeriesNo = '2024-01-SS' THEN quantity_ps ELSE 0 END) = 0 THEN NULL
            ELSE
                100.0 * SUM(CASE WHEN SeriesNo = '2024-01-SS' THEN quantity_re ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN SeriesNo = '2024-01-SS' THEN quantity_ps ELSE 0 END), 0)
            END
        AS DECIMAL(10,2)
    ) AS [2024-01-SS_pct],

    -- 2024-02-FW
    SUM(CASE WHEN SeriesNo = '2024-02-FW' THEN quantity_ps ELSE 0 END) AS [2024-02-FW_ps],
    SUM(CASE WHEN SeriesNo = '2024-02-FW' THEN quantity_re ELSE 0 END) AS [2024-02-FW_re],
    CAST(
        CASE
            WHEN SUM(CASE WHEN SeriesNo = '2024-02-FW' THEN quantity_ps ELSE 0 END) = 0 THEN NULL
            ELSE
                100.0 * SUM(CASE WHEN SeriesNo = '2024-02-FW' THEN quantity_re ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN SeriesNo = '2024-02-FW' THEN quantity_ps ELSE 0 END), 0)
            END
        AS DECIMAL(10,2)
    ) AS [2024-02-FW_pct],

    -- 2025-01-SS
    SUM(CASE WHEN SeriesNo = '2025-01-SS' THEN quantity_ps ELSE 0 END) AS [2025-01-SS_ps],
    SUM(CASE WHEN SeriesNo = '2025-01-SS' THEN quantity_re ELSE 0 END) AS [2025-01-SS_re],
    CAST(
        CASE
            WHEN SUM(CASE WHEN SeriesNo = '2025-01-SS' THEN quantity_ps ELSE 0 END) = 0 THEN NULL
            ELSE
                100.0 * SUM(CASE WHEN SeriesNo = '2025-01-SS' THEN quantity_re ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN SeriesNo = '2025-01-SS' THEN quantity_ps ELSE 0 END), 0)
            END
        AS DECIMAL(10,2)
    ) AS [2025-01-SS_pct],

    -- 2025-02-FW
    SUM(CASE WHEN SeriesNo = '2025-02-FW' THEN quantity_ps ELSE 0 END) AS [2025-02-FW_ps],
    SUM(CASE WHEN SeriesNo = '2025-02-FW' THEN quantity_re ELSE 0 END) AS [2025-02-FW_re],
    CAST(
        CASE
            WHEN SUM(CASE WHEN SeriesNo = '2025-02-FW' THEN quantity_ps ELSE 0 END) = 0 THEN NULL
            ELSE
                100.0 * SUM(CASE WHEN SeriesNo = '2025-02-FW' THEN quantity_re ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN SeriesNo = '2025-02-FW' THEN quantity_ps ELSE 0 END), 0)
            END
        AS DECIMAL(10,2)
    ) AS [2025-02-FW_pct],

    -- Total moyen avant le dernier bloc
    CAST(
        CASE
            WHEN SUM(CASE WHEN SeriesNo NOT LIKE '2026%' THEN quantity_ps ELSE 0 END) = 0 THEN NULL
            ELSE
                100.0 * SUM(CASE WHEN SeriesNo NOT LIKE '2026%' THEN quantity_re ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN SeriesNo NOT LIKE '2026%' THEN quantity_ps ELSE 0 END), 0)
            END
        AS DECIMAL(10,2)
    ) AS total_avg_pct,

    -- Dernier bloc placé à la fin : 2026-01-SS
    SUM(CASE WHEN SeriesNo = '2026-01-SS' THEN quantity_ps ELSE 0 END) AS [2026-01-SS_ps],
    SUM(CASE WHEN SeriesNo = '2026-01-SS' THEN quantity_re ELSE 0 END) AS [2026-01-SS_re],
    CAST(
        CASE
            WHEN SUM(CASE WHEN SeriesNo = '2026-01-SS' THEN quantity_ps ELSE 0 END) = 0 THEN NULL
            ELSE
                100.0 * SUM(CASE WHEN SeriesNo = '2026-01-SS' THEN quantity_re ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN SeriesNo = '2026-01-SS' THEN quantity_ps ELSE 0 END), 0)
            END
        AS DECIMAL(10,2)
    ) AS [2026-01-SS_pct],

    -- Bloc final tout à la fin : 2026-02-FW
    SUM(CASE WHEN SeriesNo = '2026-02-FW' THEN quantity_ps ELSE 0 END) AS [2026-02-FW_ps],
    SUM(CASE WHEN SeriesNo = '2026-02-FW' THEN quantity_re ELSE 0 END) AS [2026-02-FW_re],
    CAST(
        CASE
            WHEN SUM(CASE WHEN SeriesNo = '2026-02-FW' THEN quantity_ps ELSE 0 END) = 0 THEN NULL
            ELSE
                100.0 * SUM(CASE WHEN SeriesNo = '2026-02-FW' THEN quantity_re ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN SeriesNo = '2026-02-FW' THEN quantity_ps ELSE 0 END), 0)
            END
        AS DECIMAL(10,2)
    ) AS [2026-02-FW_pct]

FROM Filtered
GROUP BY
    CustomerNo,
    ItemFamilyCode,
    Name
ORDER BY
    CustomerNo,
    ItemFamilyCode;
