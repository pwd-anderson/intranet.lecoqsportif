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
        let isFirstLoad = true;   // 🆕 drapeau

        return {
            getRows: function (params) {
                const body = {
                    startRow:    params.request.startRow,
                    endRow:      params.request.endRow,
                    filterModel: params.request.filterModel || {},
                    sortModel:   params.request.sortModel   || [],
                };

                // 🆕 Loader popup au premier chargement uniquement
                if (isFirstLoad) {
                    _showInitialLoader('Chargement des données...');
                }

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

                        // Pinned bottom row (totaux)
                        if (data.totals && Object.keys(data.totals).length > 0 && gridOptionsRef.api) {
                            gridOptionsRef.api.setPinnedBottomRowData([data.totals]);
                        }

                        // 🆕 Cache le loader popup après le premier chargement
                        if (isFirstLoad) {
                            _hideInitialLoader();
                            isFirstLoad = false;
                        }
                    })
                    .catch(err => {
                        console.error('SSRM getRows error:', err);
                        params.fail();

                        // 🆕 Cache le loader même en cas d'erreur
                        if (isFirstLoad) {
                            _hideInitialLoader();
                            isFirstLoad = false;
                        }
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
            getRowStyle: function (params) {
                if (params.node.rowPinned) {
                    return {
                        background: '#024185FF',
                        fontWeight: 'bold',
                        color: '#FFFFFF'
                    };
                }
            },

            // 🆕 onGridReady : restaure le state PUIS assigne le datasource
            onGridReady: function (params) {
                _restoreGridState(params, config.stateKey);

                // Cache le loader custom de la page (#gridCustomLoader) → la grille devient visible
                _hideLoader();

                // Le datasource va déclencher _showInitialLoader (popup) tout seul au 1er getRows
                params.api.setServerSideDatasource(_createDatasource(config, gridOptions));
            }
        };

        const gridDiv = document.querySelector(gridSelector);
        new agGrid.Grid(gridDiv, gridOptions);

        return gridOptions;
    }

    /**
     * Restaure depuis localStorage : ordre/largeur des colonnes + filtres + tri.
     * À appeler AVANT setServerSideDatasource pour éviter un double appel SQL.
     */
    function _restoreGridState(params, stateKey) {
        if (!stateKey) return;

        const rawState = localStorage.getItem(stateKey);
        if (!rawState) return;

        try {
            const state = JSON.parse(rawState);

            // Ordre/largeur des colonnes
            if (state.columnState && Array.isArray(state.columnState)) {
                params.columnApi.applyColumnState({
                    state: state.columnState,
                    applyOrder: true,
                });
            }

            // Filtres (Ag-Grid déclenchera le datasource avec ces filtres)
            if (state.filterModel) {
                params.api.setFilterModel(state.filterModel);
            }
        } catch (e) {
            console.error('Erreur lors du chargement du state SSRM :', e);
        }
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

    /**
     * Loader popup au chargement initial de la grille.
     * Réutilise le composant _excel_loader.html.twig (overlay + barre de progression).
     */
    function _showInitialLoader(title) {
        const overlay = document.getElementById('excelLoaderOverlay');
        const percent = document.getElementById('excelLoaderPercent');
        const fill    = document.getElementById('excelLoaderBarFill');
        const sub     = document.getElementById('excelLoaderSub');
        const titleEl = document.querySelector('.excel-loader-title');

        if (!overlay) return;

        if (percent) percent.textContent = '0%';
        if (fill)    fill.style.width = '0%';
        if (sub)     sub.textContent = title || 'Chargement des données...';
        if (titleEl) titleEl.textContent = 'Chargement de la grille';

        overlay.classList.add('active');

        // Progression simulée (incrément aléatoire jusqu'à 85%)
        let current = 0;
        _initialLoaderTimer = setInterval(() => {
            if (current < 85) {
                current = Math.min(current + Math.random() * 10, 85);
                if (percent) percent.textContent = Math.round(current) + '%';
                if (fill)    fill.style.width = Math.round(current) + '%';
            }
        }, 200);
    }

    function _hideInitialLoader() {
        const overlay = document.getElementById('excelLoaderOverlay');
        const percent = document.getElementById('excelLoaderPercent');
        const fill    = document.getElementById('excelLoaderBarFill');
        const titleEl = document.querySelector('.excel-loader-title');

        if (_initialLoaderTimer) {
            clearInterval(_initialLoaderTimer);
            _initialLoaderTimer = null;
        }

        if (percent) percent.textContent = '100%';
        if (fill)    fill.style.width = '100%';

        setTimeout(() => {
            if (overlay) overlay.classList.remove('active');
            // Restaure le titre original pour ne pas perturber l'export Excel ensuite
            if (titleEl) titleEl.textContent = "Génération de l'export Excel";
        }, 400);
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
    async function exportExcel(gridOptions, config, fileName, exportOptions = {}) {
        if (!config.dataUrl) {
            console.error('AgGridSsrm.exportExcel : dataUrl manquant');
            return;
        }

        const api = gridOptions.api;
        const filterModel = api.getFilterModel() || {};
        const sortModel = (gridOptions.columnApi.getColumnState() || [])
            .filter(s => s.sort)
            .sort((a, b) => (a.sortIndex ?? 0) - (b.sortIndex ?? 0))
            .map(s => ({ colId: s.colId, sort: s.sort }));

        // 🆕 Options custom passées au backend
        const includeStock = exportOptions.includeStock ?? false;

        _showExcelLoader('Récupération des données...');
        _updateExcelLoader(0, includeStock
            ? 'Récupération des données + stocks (peut être long)...'
            : 'Récupération des données...');

        // Progression simulée
        let current = 0;
        const progressTimer = setInterval(() => {
            if (current < 85) {
                current = Math.min(current + Math.random() * 8, 85);
                _updateExcelLoader(Math.round(current));
            }
        }, 200);

        await new Promise(resolve => setTimeout(resolve, 50));

        try {
            const response = await fetch(config.dataUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    startRow: 0,
                    endRow: 999999,         // sentinel "toutes les lignes"
                    filterModel,
                    sortModel,
                    options: {
                        includeStock,
                        isExport: true,
                    },
                }),
            });

            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            const rows = data.rows || [];

            clearInterval(progressTimer);
            _updateExcelLoader(90, `Génération du fichier Excel (${rows.length.toLocaleString('fr-FR')} lignes)...`);
            await new Promise(r => setTimeout(r, 50));

            // 🆕 Filtre les colonnes stock si non demandées
            const columnDefs = includeStock
                ? config.columnDefs
                : config.columnDefs.filter(c => !c.field || !c.field.startsWith('STOCK_'));

            await _exportRowsToExcel(rows, columnDefs, fileName);

            _updateExcelLoader(100, 'Téléchargement en cours...');
            setTimeout(() => _hideExcelLoader(), 600);

        } catch (err) {
            clearInterval(progressTimer);
            _hideExcelLoader();
            console.error('Export Excel SSRM error:', err);
            alert("Erreur lors de l'export Excel.\n" + err.message);
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
