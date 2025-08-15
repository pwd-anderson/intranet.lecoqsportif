window.AgGridCommon = (function () {

    const LICENSE_KEY = "Using_this_AG_Grid_Enterprise_key_( AG-044138 )_in_excess_of_the_licence_granted_is_not_permitted___Please_report_misuse_to_( legal@ag-grid.com )___For_help_with_changing_this_key_please_contact_( info@ag-grid.com )___( PowerLog SA )_is_granted_a_( Multiple Applications )_Developer_License_for_( 1 ))_Front-End_JavaScript_developer___All_Front-End_JavaScript_developers_need_to_be_licensed_in_addition_to_the_ones_working_with_AG_Grid_Enterprise___This_key_has_not_been_granted_a_Deployment_License_Add-on___This_key_works_with_AG_Grid_Enterprise_versions_released_before_( 23 July 2024 )____[v2]_MTcyMTY4OTIwMDAwMA==661ebfc5f4fecff3e234966a0945d25d";

    function initGrid(gridSelector, config) {
        agGrid.LicenseManager.setLicenseKey(LICENSE_KEY);

        // Formatter decimal
        config.numericColumns.forEach(fieldName => {
            const col = config.columnDefs.find(col => col.field === fieldName);
            if (col) {
                col.valueFormatter = window.decimalFormatter;
            }
        });

        // Formatter integer
        config.integerColumns.forEach(fieldName => {
            const col = config.columnDefs.find(col => col.field === fieldName);
            if (col) {
                col.valueFormatter = window.integerFormatter;
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
            defaultColDef: {
                sortable: true,
                filter: true,
                resizable: true,
                minWidth: 100,
                flex: 1,
                floatingFilter: true,
            },
            getRowStyle: function (params) {
                if (params.node.rowPinned) {
                    return {
                        background: '#25b5b5',
                        fontWeight: 'bold',
                        color: '#FFFFFF'
                    };
                }
            }
        };

        const gridDiv = document.querySelector(gridSelector);
        new agGrid.Grid(gridDiv, gridOptions);

        // Initial load
        window.AgGridCommon.reloadData(gridOptions, config.dataUrl, config.totalColumns);

        window.onBtExportExcel = function () {
            gridOptions.api.exportDataAsExcel();
        };

        window.onBtnExportCSV = function () {
            gridOptions.api.exportDataAsCsv();
        };

        return gridOptions;
    }

    function reloadData(gridOptions, dataUrl, totalColumns) {
        gridOptions.api.showLoadingOverlay();

        fetch(dataUrl)
            .then(response => response.json())
            .then(data => {
                gridOptions.api.setRowData(data);
                gridOptions.api.hideOverlay();
                updateRowCountAndTotals(gridOptions, totalColumns);
                gridOptions.api.addEventListener('filterChanged', function () {
                    updateRowCountAndTotals(gridOptions, totalColumns);
                });
            })
            .catch(err => {
                console.error(err);
                gridOptions.api.showNoRowsOverlay();
            });
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
        return Number(params.value).toFixed(2);
    }

    function integerFormatter(params) {
        if (params.value == null) return "";
        return Number(params.value).toFixed(0);
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

    window.dateFormatter = dateFormatter;
    window.dateComparator = dateComparator;
    window.dateFilterComparator = dateFilterComparator;
    window.decimalFormatter = decimalFormatter;
    window.integerFormatter = integerFormatter;
    window.dorpDownSelect = dorpDownSelect;

    return {
        initGrid: initGrid,
        reloadData: reloadData
    };
})();