window.AgGridCommon = (function () {

    const LICENSE_KEY = "Using_this_AG_Grid_Enterprise_key_( AG-044138 )_in_excess_of_the_licence_granted_is_not_permitted___Please_report_misuse_to_( legal@ag-grid.com )___For_help_with_changing_this_key_please_contact_( info@ag-grid.com )___( PowerLog SA )_is_granted_a_( Multiple Applications )_Developer_License_for_( 1 ))_Front-End_JavaScript_developer___All_Front-End_JavaScript_developers_need_to_be_licensed_in_addition_to_the_ones_working_with_AG_Grid_Enterprise___This_key_has_not_been_granted_a_Deployment_License_Add-on___This_key_works_with_AG_Grid_Enterprise_versions_released_before_( 23 July 2024 )____[v2]_MTcyMTY4OTIwMDAwMA==661ebfc5f4fecff3e234966a0945d25d";

    function initGrid(gridSelector, config) {
        agGrid.LicenseManager.setLicenseKey(LICENSE_KEY);

        // Formatter decimal
        config.numericColumns.forEach(fieldName => {
            const col = config.columnDefs.find(col => col.field === fieldName);
            if (col) {
                col.valueFormatter = window.decimalFormatter;
                col.comparator = window.decimalComparator;
            }
        });

        // Formatter integer
        config.integerColumns.forEach(fieldName => {
            const col = config.columnDefs.find(col => col.field === fieldName);
            if (col) {
                col.valueFormatter = window.integerFormatter;
                col.comparator = window.integerComparator;
            }
        });

        // Remplace les strings par des fonctions
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

        const gridOptions = {
            columnDefs: config.columnDefs,
            excelStyles: config.excelStyles || [],
            getRowHeight: config.getRowHeight || undefined,
            defaultColDef: {
                sortable: true,
                filter: true,
                resizable: true,
                minWidth: 100,
                flex: 1,
                floatingFilter: true,
                headerClass: config.headerClass || 'excelHeader'
            },
            getRowStyle: function (params) {
                if (params.node.rowPinned) {
                    return {
                        background: '#024185FF',
                        fontWeight: 'bold',
                        color: '#FFFFFF'
                    };
                }
            }
        };

        const gridDiv = document.querySelector(gridSelector);
        new agGrid.Grid(gridDiv, gridOptions);

        // Initial load
        if (config.dataUrl) {
            hideGridActions();

            window.AgGridCommon.reloadData(
                gridOptions,
                config.dataUrl,
                config.totalColumns,
                config.stateKey
            );
        }

        window.onBtExportExcel = function () {
            gridOptions.api.exportDataAsExcel();
        };

        window.onBtnExportCSV = function () {
            gridOptions.api.exportDataAsCsv();
        };

        return gridOptions;
    }

    let _loaderTimer = null;
    let _loaderPercent = 0;

    function startLoaderProgress() {
        const fill = document.getElementById('gridLoaderBarFill');
        const text = document.getElementById('gridLoaderPercent');

        _loaderPercent = 0;
        _updateLoaderUI(fill, text, 0);

        const steps = [
            { target: 30, duration: 300 },
            { target: 60, duration: 500 },
            { target: 80, duration: 800 },
            { target: 85, duration: 1200 },
        ];

        let stepIndex = 0;

        function runStep() {
            if (stepIndex >= steps.length) return;

            const step = steps[stepIndex];
            const start = _loaderPercent;
            const delta = step.target - start;
            const startTime = performance.now();

            function animate(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / step.duration, 1);
                const eased = 1 - Math.pow(1 - progress, 2);
                const current = Math.round(start + delta * eased);

                _loaderPercent = current;
                _updateLoaderUI(fill, text, current);

                if (progress < 1) {
                    _loaderTimer = requestAnimationFrame(animate);
                } else {
                    stepIndex++;
                    runStep();
                }
            }

            _loaderTimer = requestAnimationFrame(animate);
        }

        runStep();
    }

    function completeLoaderProgress() {
        if (_loaderTimer) {
            cancelAnimationFrame(_loaderTimer);
            _loaderTimer = null;
        }

        const fill = document.getElementById('gridLoaderBarFill');
        const text = document.getElementById('gridLoaderPercent');

        _updateLoaderUI(fill, text, 100);
    }

    function _updateLoaderUI(fill, text, percent) {
        if (fill) fill.style.width = percent + '%';
        if (text) text.textContent = percent + '%';
    }

    function showGridLoader(gridSelector = '#myGrid') {
        const loader = document.getElementById('gridCustomLoader');
        const grid = document.querySelector(gridSelector);

        if (loader) loader.style.display = 'flex';
        if (grid) grid.style.visibility = 'hidden';

        startLoaderProgress();
    }

    function hideGridLoader(gridSelector = '#myGrid') {
        const loader = document.getElementById('gridCustomLoader');
        const grid = document.querySelector(gridSelector);

        completeLoaderProgress();

        setTimeout(() => {
            if (loader) loader.style.display = 'none';
            if (grid) grid.style.visibility = 'visible';
        }, 350);
    }

    function hideGridActions() {
        const actions = document.getElementById('gridActions');

        if (actions) {
            actions.classList.remove('grid-actions-visible');
            actions.classList.add('grid-actions-hidden');
        }
    }

    function showGridActions() {
        const actions = document.getElementById('gridActions');

        if (actions) {
            actions.classList.remove('grid-actions-hidden');
            actions.classList.add('grid-actions-visible');
        }
    }

    function reloadData(gridOptions, dataUrl, totalColumns, stateKey) {
        hideGridActions();
        showGridLoader();
        gridOptions.api.showLoadingOverlay();

        fetch(dataUrl)
            .then(response => response.json())
            .then(data => {
                gridOptions.api.setRowData(data);

                if (stateKey) {
                    setTimeout(() => {
                        loadGridState(gridOptions, stateKey);
                        updateRowCountAndTotals(gridOptions, totalColumns);
                        gridOptions.api.hideOverlay();
                        hideGridLoader();
                        showGridActions();
                    }, 50);
                } else {
                    gridOptions.api.hideOverlay();
                    updateRowCountAndTotals(gridOptions, totalColumns);
                    hideGridLoader();
                    showGridActions();
                }

                if (!gridOptions._agGridCommonFilterListenerAdded) {
                    gridOptions.api.addEventListener('filterChanged', function () {
                        updateRowCountAndTotals(gridOptions, totalColumns);
                    });

                    gridOptions.api.addEventListener('sortChanged', function () {
                        updateRowCountAndTotals(gridOptions, totalColumns);
                    });

                    gridOptions._agGridCommonFilterListenerAdded = true;
                }
            })
            .catch(err => {
                console.error(err);
                gridOptions.api.showNoRowsOverlay();
                hideGridLoader();
                showGridActions();
            });
    }

    function saveGridState(gridOptions, stateKey) {
        if (!gridOptions || !gridOptions.api || !gridOptions.columnApi || !stateKey) {
            return;
        }

        const state = {
            columnState: gridOptions.columnApi.getColumnState(),
            filterModel: gridOptions.api.getFilterModel(),
            savedAt: new Date().toISOString()
        };

        localStorage.setItem(stateKey, JSON.stringify(state));
    }

    function loadGridState(gridOptions, stateKey) {
        if (!gridOptions || !gridOptions.api || !gridOptions.columnApi || !stateKey) {
            return;
        }

        const rawState = localStorage.getItem(stateKey);

        if (!rawState) {
            return;
        }

        try {
            const state = JSON.parse(rawState);

            if (state.columnState && Array.isArray(state.columnState)) {
                gridOptions.columnApi.applyColumnState({
                    state: state.columnState,
                    applyOrder: true
                });

                const orderedColIds = state.columnState
                    .map(col => col.colId)
                    .filter(Boolean);

                if (orderedColIds.length > 0) {
                    gridOptions.columnApi.moveColumns(orderedColIds, 0);
                }
            }

            if (state.filterModel) {
                gridOptions.api.setFilterModel(state.filterModel);
            }

            gridOptions.api.refreshHeader();
            gridOptions.api.onFilterChanged();

        } catch (e) {
            console.error('Erreur lors du chargement de la configuration ag-Grid :', e);
        }
    }

    function clearGridState(stateKey) {
        if (!stateKey) {
            return;
        }

        localStorage.removeItem(stateKey);
    }

    function updateRowCountAndTotals(gridOptions, totalColumns = []) {
        const rowCount = gridOptions.api.getDisplayedRowCount();
        const rowCountElement = document.getElementById('rowCount');

        if (rowCountElement) {
            rowCountElement.textContent = `${rowCount} lignes`;
        }

        const totals = {};

        totalColumns.forEach(col => {
            totals[col] = 0;
        });

        gridOptions.api.forEachNodeAfterFilterAndSort(function (node) {
            totalColumns.forEach(col => {
                totals[col] += Number(node.data[col]) || 0;
            });
        });

        const totalRow = {};
        totalColumns.forEach(col => {
            totalRow[col] = totals[col];
        });

        gridOptions.api.setPinnedBottomRowData([totalRow]);
    }

    function dateFormatter(params) {
        if (!params.value) return "";
        var dateParts = params.value.split('-');
        return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
    }

    function dateComparator(date1, date2) {
        if (date1 == null && date2 == null) return 0;
        if (date1 == null) return -1;
        if (date2 == null) return 1;
        var d1 = parseDate(date1);
        var d2 = parseDate(date2);
        return d1 - d2;
    }

    function parseDate(dateStr) {
        var dateParts = dateStr.split('-');
        var year = Number(dateParts[0]);
        var month = Number(dateParts[1]) - 1;
        var day = Number(dateParts[2]);
        return new Date(year, month, day).getTime();
    }

    function dateFilterComparator(filterLocalDateAtMidnight, cellValue) {
        if (cellValue == null) return 0;
        var dateParts = cellValue.split('-');
        var year = Number(dateParts[0]);
        var month = Number(dateParts[1]) - 1;
        var day = Number(dateParts[2]);
        var cellDate = new Date(year, month, day);
        if (cellDate < filterLocalDateAtMidnight) return -1;
        if (cellDate > filterLocalDateAtMidnight) return 1;
        return 0;
    }

    function decimalFormatter(params) {
        if (params.value == null) return "";
        return Number(params.value).toLocaleString('fr-CH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function integerFormatter(params) {
        if (params.value == null) return "";
        return Number(params.value).toLocaleString('fr-CH');
    }

    function decimalComparator(a, b) {
        return Number(a) - Number(b);
    }

    function integerComparator(a, b) {
        return Number(a) - Number(b);
    }

    function dorpDownSelect(selector, startYear, selectedYear, callback) {
        const yearSelect = document.querySelector(selector);
        const currentYear = new Date().getFullYear();

        for (let year = startYear; year <= currentYear; year++) {
            const option = document.createElement('option');
            option.value = year;
            option.text = year;
            if (year == selectedYear) {
                option.selected = true;
            }
            yearSelect.appendChild(option);
        }

        yearSelect.addEventListener('change', () => {
            const newYear = yearSelect.value;
            callback(newYear);
        });
    }

    function percentFormatter(params) {
        if (params.value == null) return "";
        return (Number(params.value) * 100).toFixed(0) + " %";
    }

    function percentComparator(a, b) {
        return Number(a) - Number(b);
    }

    function patchSidebarCheckboxes(gridSelector) {
        const gridDiv = document.querySelector(gridSelector);

        if (!gridDiv) {
            return;
        }

        const patchOneCheckbox = function (wrapper) {
            if (!wrapper || wrapper.dataset.checkboxPatched === '1') {
                return;
            }

            const input = wrapper.querySelector('input');
            const label = wrapper.querySelector('label');

            if (!input) {
                return;
            }

            wrapper.dataset.checkboxPatched = '1';
            wrapper.style.cursor = 'pointer';

            // Rend la zone entière cliquable immédiatement
            wrapper.addEventListener('mousedown', function (e) {
                // laisse le vrai input / label vivre normalement
                if (
                    e.target === input ||
                    e.target === label ||
                    (label && label.contains(e.target))
                ) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                input.checked = !input.checked;

                input.dispatchEvent(new Event('click', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }, true);
        };

        const bindAll = function () {
            const checkboxes = gridDiv.querySelectorAll('.ag-column-select-checkbox');
            checkboxes.forEach(patchOneCheckbox);
        };

        bindAll();

        const observer = new MutationObserver(function () {
            bindAll();
        });

        observer.observe(gridDiv, {
            childList: true,
            subtree: true
        });

        // Optionnel: stocker l'observer sur le DOM si un jour tu veux le couper
        gridDiv._agSidebarCheckboxObserver = observer;
    }

    /**
     * Active le mode "auto-width" sur une grille AG Grid.
     * La grille s'adapte à la largeur cumulée des colonnes affichées
     * (utile pour les grilles avec peu de colonnes).
     *
     * @param {Object} gridOptions - Les gridOptions retournés par initGrid
     * @param {Object} options
     * @param {string} options.widthMode - 'auto' | 'full' (défaut: 'full')
     * @param {string} options.outerSelector - sélecteur du conteneur externe (défaut: '#gridOuterContainer')
     * @param {string} options.innerSelector - sélecteur du conteneur interne (défaut: '#gridInnerContainer')
     * @param {number} options.extraSpace - marge additionnelle en px (défaut: 58)
     * @returns {Function|null} Fonction adjust() pour forcer un re-calcul manuel, ou null si désactivé
     */
    function setupAutoWidth(gridOptions, options = {}) {
        const widthMode = options.widthMode || 'full';
        const outerSelector = options.outerSelector || '#gridOuterContainer';
        const innerSelector = options.innerSelector || '#gridInnerContainer';
        const extraSpace = options.extraSpace ?? 58;

        const outer = document.querySelector(outerSelector);
        const inner = document.querySelector(innerSelector);

        if (!outer || !inner) {
            console.warn('[AgGridCommon.setupAutoWidth] Conteneurs introuvables :', outerSelector, innerSelector);
            return null;
        }

        // Mode 'full' : on s'assure que le conteneur prend toute la largeur et on ne fait rien de plus
        if (widthMode !== 'auto') {
            outer.classList.remove('grid-width-auto');
            inner.style.width = '100%';
            inner.style.maxWidth = '100%';
            return null;
        }

        // Mode 'auto' : on active la classe CSS et on calcule dynamiquement la largeur
        outer.classList.add('grid-width-auto');

        function adjust() {
            if (!gridOptions?.columnApi) {
                return;
            }

            const displayedColumns = gridOptions.columnApi.getAllDisplayedColumns() || [];
            if (!displayedColumns.length) {
                return;
            }

            let totalWidth = 0;
            displayedColumns.forEach(col => {
                totalWidth += col.getActualWidth();
            });

            inner.style.width = (totalWidth + extraSpace) + 'px';
            inner.style.maxWidth = '100%';
        }

        function debounce(fn, delay) {
            let timer = null;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        }

        // Bind sur les événements AG Grid qui peuvent changer la largeur
        const api = gridOptions.api;
        if (api) {
            const events = [
                'firstDataRendered',
                'modelUpdated',
                'columnResized',
                'columnVisible',
                'columnMoved',
                'columnPinned',
            ];

            events.forEach(eventName => {
                api.addEventListener(eventName, () => setTimeout(adjust, 50));
            });
        }

        // Re-calculer aussi sur le redimensionnement de la fenêtre
        window.addEventListener('resize', debounce(adjust, 150));

        // Premier ajustement immédiat
        adjust();

        return adjust;
    }

    /**
     * Active le mode "auto-height avec plafond" sur une grille AG Grid.
     * La grille s'adapte au contenu (pas d'espace blanc) mais ne dépasse pas maxHeight.
     * Au-delà de maxHeight, on bascule en hauteur fixe avec scroll interne.
     *
     * @param {Object} gridOptions - Les gridOptions retournés par initGrid
     * @param {Object} options
     * @param {string} options.gridSelector - sélecteur du div grid (défaut: '#myGrid')
     * @param {number} options.maxHeight - hauteur maximale en px (défaut: 1000)
     * @returns {Function|null} Fonction adjust() pour forcer un re-calcul manuel
     */
    function setupAutoHeight(gridOptions, options = {}) {
        const gridSelector = options.gridSelector || '#myGrid';
        const maxHeight = options.maxHeight ?? 1000;

        const gridDiv = document.querySelector(gridSelector);

        if (!gridDiv) {
            console.warn('[AgGridCommon.setupAutoHeight] Grille introuvable :', gridSelector);
            return null;
        }

        let currentLayout = null; // 'autoHeight' | 'normal'

        function adjust() {
            if (!gridOptions?.api) {
                return;
            }

            // Hauteur estimée du contenu
            const headerHeight = 40 + 32; // header AG Grid + filtres flottants
            const rowCount = gridOptions.api.getDisplayedRowCount() || 0;

            // Pour estimer la hauteur, on additionne la hauteur de chaque ligne
            let dataHeight = 0;
            gridOptions.api.forEachNodeAfterFilterAndSort((node) => {
                dataHeight += node.rowHeight || 42;
            });

            // Hauteur ligne pinned bottom (totaux) si présente
            const pinnedNode = gridOptions.api.getPinnedBottomRow(0);
            const pinnedHeight = pinnedNode ? (pinnedNode.rowHeight || 42) : 0;

            const totalContentHeight = headerHeight + dataHeight + pinnedHeight + 4; // +4 marge sécurité

            if (totalContentHeight <= maxHeight) {
                // Mode autoHeight : la grille fait exactement la hauteur de son contenu
                if (currentLayout !== 'autoHeight') {
                    gridOptions.api.setDomLayout('autoHeight');
                    currentLayout = 'autoHeight';
                }
                // En mode autoHeight, AG Grid exige height: '' sur le conteneur
                gridDiv.style.height = '';
            } else {
                // Mode normal : hauteur fixe avec scroll interne
                if (currentLayout !== 'normal') {
                    gridOptions.api.setDomLayout('normal');
                    currentLayout = 'normal';
                }
                gridDiv.style.height = maxHeight + 'px';
            }
        }

        function debounce(fn, delay) {
            let timer = null;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        }

        // Bind sur les events qui peuvent changer le nombre/hauteur de lignes
        const api = gridOptions.api;
        if (api) {
            const events = [
                'firstDataRendered',
                'modelUpdated',       // changement de filtre, tri, données
                'rowDataUpdated',
                'rowHeightChanged',
            ];

            events.forEach(eventName => {
                api.addEventListener(eventName, () => setTimeout(adjust, 50));
            });
        }

        // Recalculer aussi au resize (au cas où maxHeight serait dynamique un jour)
        window.addEventListener('resize', debounce(adjust, 150));

        return adjust;
    }

    window.dateFormatter = dateFormatter;
    window.dateComparator = dateComparator;
    window.dateFilterComparator = dateFilterComparator;
    window.decimalFormatter = decimalFormatter;
    window.integerFormatter = integerFormatter;
    window.dorpDownSelect = dorpDownSelect;
    window.decimalComparator = decimalComparator;
    window.integerComparator = integerComparator;
    window.percentFormatter = percentFormatter;
    window.percentComparator = percentComparator;

    return {
        initGrid: initGrid,
        reloadData: reloadData,
        updateTotals: updateRowCountAndTotals,
        saveGridState: saveGridState,
        loadGridState: loadGridState,
        clearGridState: clearGridState,
        patchSidebarCheckboxes: patchSidebarCheckboxes,
        showGridActions: showGridActions,
        hideGridActions: hideGridActions,
        setupAutoWidth: setupAutoWidth,
        setupAutoHeight: setupAutoHeight,
        showGridLoader: showGridLoader,
        hideGridLoader: hideGridLoader
    };
})();
