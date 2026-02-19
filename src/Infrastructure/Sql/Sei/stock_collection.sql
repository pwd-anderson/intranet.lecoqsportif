DECLARE @colsPivot   NVARCHAR(MAX);
DECLARE @colsSelect  NVARCHAR(MAX);
DECLARE @totalExpr   NVARCHAR(MAX);
DECLARE @sql         NVARCHAR(MAX);

DECLARE @location NVARCHAR(20) = '{{LOCATION}}';
DECLARE @status   NVARCHAR(10) = '{{STATUS}}';

;WITH Cols AS (
    SELECT DISTINCT YIT.YCOLLECT_0
    FROM X3_LCS.ITMMASTER ITM
             LEFT JOIN X3_LCS.YITMCOLLECT YIT
                       ON ITM.ITMREF_0 = YIT.ITMREF_0
             LEFT JOIN X3_LCS.ITMMVT ITV
                       ON ITM.ITMREF_0 = ITV.ITMREF_0
             LEFT JOIN X3_LCS.STOCK STK
                       ON ITM.ITMREF_0 = STK.ITMREF_0
                           AND ITV.STOFCY_0 = STK.STOFCY_0
    WHERE
        STK.STOFCY_0 IS NOT NULL
      AND (@location = '' OR STK.STOFCY_0 = @location)

      AND (
        (@status = 'ALL' AND STK.STA_0 IN ('A1','A2','R'))
            OR
        (@status <> 'ALL' AND STK.STA_0 = @status)
        )

      AND YIT.YCOLLECT_0 IS NOT NULL
      AND (YIT.YCOLLECT_0 LIKE '2025%' OR YIT.YCOLLECT_0 LIKE '2026%')
      AND ISNULL(STK.QTYSTU_0,0) <> 0
)

 SELECT
                                                     @colsPivot  = STRING_AGG(QUOTENAME(YCOLLECT_0), ',')
                                                         WITHIN GROUP (ORDER BY YCOLLECT_0),

    @colsSelect = STRING_AGG(
        'ISNULL(' + QUOTENAME(YCOLLECT_0) + ',0) AS ' + QUOTENAME(YCOLLECT_0),
        ','
    ) WITHIN GROUP (ORDER BY YCOLLECT_0),

                                                     @totalExpr  = STRING_AGG(
                                                     'ISNULL(' + QUOTENAME(YCOLLECT_0) + ',0)',
                                                     ' + '
                                                     ) WITHIN GROUP (ORDER BY YCOLLECT_0)
 FROM Cols;

IF @colsPivot IS NULL
BEGIN
SELECT CAST(NULL AS NVARCHAR(50)) AS FAMILLE WHERE 1 = 0;
RETURN;
END

SET @sql = '
WITH Base AS (
    SELECT
        ITM.TCLCOD_0 AS FAMILLE,
        YIT.YCOLLECT_0 AS COLLECTION,
        STK.QTYSTU_0 AS STOCK_INTERNE
    FROM X3_LCS.ITMMASTER ITM
    LEFT JOIN X3_LCS.YITMCOLLECT YIT
        ON ITM.ITMREF_0 = YIT.ITMREF_0
    LEFT JOIN X3_LCS.ITMMVT ITV
        ON ITM.ITMREF_0 = ITV.ITMREF_0
    LEFT JOIN X3_LCS.STOCK STK
        ON ITM.ITMREF_0 = STK.ITMREF_0
        AND ITV.STOFCY_0 = STK.STOFCY_0
    WHERE
        STK.STOFCY_0 IS NOT NULL
        AND (@location = '''' OR STK.STOFCY_0 = @location)

        AND (
            (@status = ''ALL'' AND STK.STA_0 IN (''A1'',''A2'',''R''))
            OR
            (@status <> ''ALL'' AND STK.STA_0 = @status)
        )

        AND YIT.YCOLLECT_0 IS NOT NULL
        AND (YIT.YCOLLECT_0 LIKE ''2025%'' OR YIT.YCOLLECT_0 LIKE ''2026%'')
)

SELECT
    p.FAMILLE,
    ' + @colsSelect + ',
    (' + @totalExpr + ') AS TOTAL
FROM (
    SELECT FAMILLE, COLLECTION, STOCK_INTERNE
    FROM Base
) src
PIVOT (
    SUM(STOCK_INTERNE)
    FOR COLLECTION IN (' + @colsPivot + ')
) p
ORDER BY p.FAMILLE;
';

EXEC sp_executesql
    @sql,
    N'@location NVARCHAR(20), @status NVARCHAR(10)',
    @location = @location,
    @status   = @status;
