/**
 * AG Grid Server-Side Row Model wrapper.
 * - Pagination / filtre / tri envoyés au serveur (POST JSON).
 * - Export Excel sur endpoint dédié (toutes lignes filtrées/triées).
 * - Réutilise les formatters/comparators de AgGridCommon.
 */
window.AgGridSsrm = (function () {

    const LICENSE_KEY = "Using_this_AG_Grid_Enterprise_key_( AG-044138 )_in_excess_of_the_licence_granted_is_not_permitted___Please_report_misuse_to_( legal@ag-grid.com )___For_help_with_changing_this_key_please_contact_( info@ag-grid.com )___( PowerLog SA )_is_granted_a_( Multiple Applications )_Developer_License_for_( 1 ))_Front-End_JavaScript_developer___All_Front-End_JavaScript_developers_need_to_be_licensed_in_addition_to_the_ones_working_with_AG_Grid_Enterprise___This_key_has_not_been_granted_a_Deployment_License_Add-on___This_key_works_with_AG_Grid_Enterprise_versions_released_before_( 23 July 2024 )____[v2]_MTcyMTY4OTIwMDAwMA==661ebfc5f4fecff3e234966a0945d25d";

    function _hideLoader() {
        const loader = document.getElementById('gridCustomLoader');
        const grid = document.getElementById('myGrid');
        if (loader) loader.style.display = 'none';
        if (grid) grid.style.visibility = 'visible';

        const actions = document.getElementById('gridActions');
        if (actions) {
            actions.classList.remove('grid-actions-hidden');
            actions.classList.add('grid-actions-visible');
        }
    }

    function _applyFormatters(config) {
        // Mêmes règles que AgGridCommon.initGrid pour conserver le rendu
        (config.numericColumns || []).forEach(field => {
            const col = config.columnDefs.find(c => c.field === field);
            if (col) {
                col.valueFormatter = window.decimalFormatter;
                col.comparator = window.decimalComparator;
            }
        });
        (config.integerColumns || []).forEach(field => {
            const col = config.columnDefs.find(c => c.field === field);
            if (col) {
                col.valueFormatter = window.integerFormatter;
                col.comparator = window.integerComparator;
            }
        });
        config.columnDefs.forEach(col => {
            if (typeof col.valueFormatter === 'string') {
                col.valueFormatter = window[col.valueFormatter];
            }
            if (typeof col.comparator === 'string') {
                col.comparator = window[col.comparator];
            }
            if (col.filterParams && typeof col.filterParams.comparator === 'string') {
                col.filterParams.comparator = window[col.filterParams.comparator];
            }
        });
    }

    function _createDatasource(config, gridOptionsRef) {
        return {
            getRows: function (params) {
                const body = {
                    startRow:    params.request.startRow,
                    endRow:      params.request.endRow,
                    filterModel: params.request.filterModel || {},
                    sortModel:   params.request.sortModel   || [],
                };

                fetch(config.dataUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                })
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(data => {
                        const rows = data.rows || [];
                        const lastRow = (data.lastRow !== undefined && data.lastRow !== null)
                            ? data.lastRow
                            : undefined;

                        params.success({ rowData: rows, rowCount: lastRow });

                        // Compteur affiché en haut à droite
                        const el = document.getElementById('rowCount');
                        if (el && lastRow !== undefined) {
                            el.textContent = lastRow.toLocaleString('fr-FR') + ' lignes';
                        }

                        // 🆕 Pinned bottom row (totaux) : uniquement renvoyés au premier bloc
                        if (data.totals && Object.keys(data.totals).length > 0 && gridOptionsRef.api) {
                            gridOptionsRef.api.setPinnedBottomRowData([data.totals]);
                        }
                    })
                    .catch(err => {
                        console.error('SSRM getRows error:', err);
                        params.fail();
                    });
            }
        };
    }

    function initGrid(gridSelector, config) {
        agGrid.LicenseManager.setLicenseKey(LICENSE_KEY);
        _applyFormatters(config);

        const gridOptions = {
            columnDefs: config.columnDefs,
            rowModelType: 'serverSide',
            cacheBlockSize: config.blockSize || 200,
            maxBlocksInCache: 10,
            blockLoadDebounceMillis: 100,
            purgeClosedRowNodes: true,
            animateRows: false,
            excelStyles: config.excelStyles || [],
            defaultColDef: {
                sortable: true,
                filter: true,
                resizable: true,
                minWidth: 100,
                flex: 1,
                floatingFilter: true,
                headerClass: config.headerClass || 'excelHeader',
            },
            // 🆕 Style ligne pinned bleue (cohérent AgGridCommon)
            getRowStyle: function (params) {
                if (params.node.rowPinned) {
                    return {
                        background: '#024185FF',
                        fontWeight: 'bold',
                        color: '#FFFFFF'
                    };
                }
            },
            onGridReady: function () {
                _hideLoader();
            }
        };

        // 🆕 Datasource assignée APRÈS pour pouvoir référencer gridOptions
        gridOptions.serverSideDatasource = _createDatasource(config, gridOptions);

        const gridDiv = document.querySelector(gridSelector);
        new agGrid.Grid(gridDiv, gridOptions);

        return gridOptions;
    }

    /**
     * Helpers loader (mêmes IDs que _excel_loader.html.twig)
     */
    function _showExcelLoader(title) {
        const overlay = document.getElementById('excelLoaderOverlay');
        const percent = document.getElementById('excelLoaderPercent');
        const fill    = document.getElementById('excelLoaderBarFill');
        const sub     = document.getElementById('excelLoaderSub');

        if (!overlay) return;

        if (percent) percent.textContent = '0%';
        if (fill)    fill.style.width = '0%';
        if (sub)     sub.textContent = title || 'Génération du fichier Excel...';

        overlay.classList.add('active');
    }

    function _hideExcelLoader() {
        const overlay = document.getElementById('excelLoaderOverlay');
        if (overlay) overlay.classList.remove('active');
    }

    function _updateExcelLoader(percent, subText) {
        const percentEl = document.getElementById('excelLoaderPercent');
        const fill      = document.getElementById('excelLoaderBarFill');
        const sub       = document.getElementById('excelLoaderSub');

        if (percentEl) percentEl.textContent = percent + '%';
        if (fill)      fill.style.width = percent + '%';
        if (subText && sub) sub.textContent = subText;
    }

    /**
     * Export Excel SSRM : POST endpoint full → ExcelJS côté client.
     */
    async function exportExcel(gridOptions, config, fileName) {
        if (!config.exportUrl) {
            console.error('AgGridSsrm.exportExcel : exportUrl manquant');
            return;
        }

        const api = gridOptions.api;
        const filterModel = api.getFilterModel() || {};
        const sortModel = (gridOptions.columnApi.getColumnState() || [])
            .filter(s => s.sort)
            .sort((a, b) => (a.sortIndex ?? 0) - (b.sortIndex ?? 0))
            .map(s => ({ colId: s.colId, sort: s.sort }));

        // 1. Affiche le loader
        _showExcelLoader('Récupération des données...');

        // 2. Progression simulée (comme ExcelExportStandard)
        let current = 0;
        const progressTimer = setInterval(() => {
            if (current < 85) {
                current = Math.min(current + Math.random() * 10, 85);
                _updateExcelLoader(Math.round(current));
            }
        }, 200);

        // Laisse le DOM afficher le loader avant la requête
        await new Promise(resolve => setTimeout(resolve, 50));

        try {
            // 3. POST full export
            const response = await fetch(config.exportUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    startRow: 0,
                    endRow: 0,
                    filterModel,
                    sortModel,
                }),
            });

            if (!response.ok) throw new Error('HTTP ' + response.status);
            const rows = await response.json();

            // 4. Génération du fichier
            _updateExcelLoader(90, 'Génération du fichier Excel...');
            await _exportRowsToExcel(rows, config.columnDefs, fileName);

            // 5. Finalisation
            clearInterval(progressTimer);
            _updateExcelLoader(100, 'Téléchargement en cours...');

            setTimeout(() => {
                _hideExcelLoader();
            }, 600);

        } catch (err) {
            clearInterval(progressTimer);
            _hideExcelLoader();
            console.error('Export Excel SSRM error:', err);
            alert("Erreur lors de l'export Excel.");
        }
    }

    /**
     * Génère le XLSX à partir d'un tableau de lignes via ExcelJS.
     * Conserve le style header (bleu blanc) pour rester cohérent.
     */
    async function _exportRowsToExcel(rows, columnDefs, fileName) {
        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet('Export');

        const visibleCols = columnDefs.filter(c => c.hide !== true);

        ws.columns = visibleCols.map(col => ({
            header: col.headerName || col.field,
            key: col.field,
            width: Math.min(40, Math.max(12, (col.headerName || col.field).length + 4)),
        }));

        // Style header bleu (cohérent avec excelHeaderBlue)
        const headerRow = ws.getRow(1);
        headerRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
        headerRow.alignment = { horizontal: 'center', vertical: 'middle' };
        headerRow.fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF024185' }
        };
        headerRow.height = 22;

        // Ajout des lignes (par lot pour ne pas bloquer le thread)
        const BATCH = 5000;
        for (let i = 0; i < rows.length; i += BATCH) {
            const slice = rows.slice(i, i + BATCH);
            slice.forEach(row => {
                const flat = {};
                visibleCols.forEach(col => {
                    flat[col.field] = row[col.field] ?? '';
                });
                ws.addRow(flat);
            });
            // Yield au thread pour laisser le loader respirer
            if (i + BATCH < rows.length) {
                await new Promise(r => setTimeout(r, 0));
            }
        }

        ws.views = [{ state: 'frozen', ySplit: 1 }];
        ws.autoFilter = {
            from: { row: 1, column: 1 },
            to:   { row: 1, column: visibleCols.length }
        };

        const buf = await wb.xlsx.writeBuffer();
        const blob = new Blob([buf], { type: 'application/octet-stream' });
        saveAs(blob, fileName);
    }

    return {
        initGrid: initGrid,
        exportExcel: exportExcel,
    };
})();
