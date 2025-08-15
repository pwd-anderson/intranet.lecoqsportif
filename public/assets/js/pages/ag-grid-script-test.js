


var liceence = "Using_this_AG_Grid_Enterprise_key_( AG-044138 )_in_excess_of_the_licence_granted_is_not_permitted___Please_report_misuse_to_( legal@ag-grid.com )___For_help_with_changing_this_key_please_contact_( info@ag-grid.com )___( PowerLog SA )_is_granted_a_( Multiple Applications )_Developer_License_for_( 1 ))_Front-End_JavaScript_developer___All_Front-End_JavaScript_developers_need_to_be_licensed_in_addition_to_the_ones_working_with_AG_Grid_Enterprise___This_key_has_not_been_granted_a_Deployment_License_Add-on___This_key_works_with_AG_Grid_Enterprise_versions_released_before_( 23 July 2024 )____[v2]_MTcyMTY4OTIwMDAwMA==661ebfc5f4fecff3e234966a0945d25d";
agGrid.LicenseManager.setLicenseKey(liceence);

/** @type {import('ag-grid-community').GridOptions} */
const gridOptions = {
    columnDefs: [
        { field: 'athlete', filter: 'agTextColumnFilter', cellStyle: {textAlign: "center", borderRight: '0.2px solid #CECECEFF', borderBottom: '0.2px solid #CECECEFF'}},
        { field: 'country', filter: 'agTextColumnFilter', minWidth: 200, cellStyle: {textAlign: "center", borderRight: '0.2px solid #CECECEFF', borderBottom: '0.2px solid #CECECEFF'} },
        { field: 'sport', filter: 'agTextColumnFilter', minWidth: 150, cellStyle: {textAlign: "center", borderRight: '0.2px solid #CECECEFF', borderBottom: '0.2px solid #CECECEFF'} },
        { field: 'gold', filter: 'agNumberColumnFilter', cellStyle: {textAlign: "center", borderRight: '0.2px solid #CECECEFF', borderBottom: '0.2px solid #CECECEFF'} },
        { field: 'silver', filter: 'agNumberColumnFilter', cellStyle: {textAlign: "center", borderRight: '0.2px solid #CECECEFF', borderBottom: '0.2px solid #CECECEFF'} },
        { field: 'bronze', filter: 'agNumberColumnFilter', cellStyle: {textAlign: "center", borderRight: '0.2px solid #CECECEFF', borderBottom: '0.2px solid #CECECEFF'} },
        { field: 'total', filter: 'agNumberColumnFilter', cellStyle: {textAlign: "center", borderRight: '0.2px solid #CECECEFF', borderBottom: '0.2px solid #CECECEFF'} },
    ],
    defaultColDef: {
        sortable: true,
        //filter: true,
        resizable: true,
        minWidth: 100,
        flex: 1,
        floatingFilter: true,
    },
    //pagination: true,
    //paginationPageSize: 10000,
    getRowStyle: function(params) {
        if (params.node.rowPinned) {
            return { 'background': '#25b5b5', 'font-weight' : 'bold', 'color': '#FFFFFF'};
        }
    },
};

function onBtExportExcel() {
    gridOptions.api.exportDataAsExcel();
}
function onBtnExportCSV() {
    gridOptions.api.exportDataAsCsv();
}

// setup the grid after the page has finished loading
document.addEventListener('DOMContentLoaded', function () {
    var gridDiv = document.querySelector('#myGrid');
    new agGrid.Grid(gridDiv, gridOptions);

    fetch('https://www.ag-grid.com/example-assets/small-olympic-winners.json')
        .then(response => response.json())
        .then(data => {
            gridOptions.api.setRowData(data);
            // Initialiser l'affichage du nombre de lignes et des totaux
            updateRowCountAndTotals();
            // Ecouter le filtre
            gridOptions.api.addEventListener('filterChanged', function () {
                updateRowCountAndTotals();
            });
        })
        .catch(err => console.error(err));
});

// Fonction qui met à jour le compteur et les totaux
function updateRowCountAndTotals() {
    const rowCount = gridOptions.api.getDisplayedRowCount();

    document.getElementById('rowCount').textContent =
        `${rowCount} lignes`;

    // Calculer les totaux visibles après filtre
    let totalGold = 0;
    let totalSilver = 0;
    let totalBronze = 0;
    let totalTotal = 0;

    gridOptions.api.forEachNodeAfterFilterAndSort(function (node) {
        totalGold += Number(node.data.gold) || 0;
        totalSilver += Number(node.data.silver) || 0;
        totalBronze += Number(node.data.bronze) || 0;
    });

    const totalRow = {
        gold: totalGold,
        silver: totalSilver,
        bronze: totalBronze,
    };

    gridOptions.api.setPinnedBottomRowData([totalRow]);
}
