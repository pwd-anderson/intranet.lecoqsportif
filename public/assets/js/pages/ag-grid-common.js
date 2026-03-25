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

    function showGridLoader() {
        const loader = document.getElementById('gridCustomLoader');
        const grid = document.getElementById('myGrid');

        if (loader) {
            loader.style.display = 'flex';
        }

        if (grid) {
            grid.style.visibility = 'hidden';
        }
    }

    function hideGridLoader() {
        const loader = document.getElementById('gridCustomLoader');
        const grid = document.getElementById('myGrid');

        if (loader) {
            loader.style.display = 'none';
        }

        if (grid) {
            grid.style.visibility = 'visible';
        }
    }

    function reloadData(gridOptions, dataUrl, totalColumns, stateKey) {
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
                    }, 50);
                } else {
                    gridOptions.api.hideOverlay();
                    updateRowCountAndTotals(gridOptions, totalColumns);
                    hideGridLoader();
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
        patchSidebarCheckboxes: patchSidebarCheckboxes
    };
})();
