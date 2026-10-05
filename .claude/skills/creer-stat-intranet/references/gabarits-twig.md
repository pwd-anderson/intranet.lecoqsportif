# Gabarits Twig AG Grid

Squelettes repris de l’ancien agent `aggrid tableau` (remplacé par ce skill). Type A = colonnes définies dans le Twig (exemple `templates/soa/index.html.twig`) ; Type B = colonnes en base `aggrid_option` (exemple `templates/achat/achat_generic.html.twig`). Pour une stat générique, ne pas recopier : le template partagé existe déjà. Pour un écran spécifique, partir du template voisin le plus récent et vérifier ces gabarits contre lui.

## Structure à respecter

### Bloc `{% block stylesheets %}`
```twig
{% block stylesheets %}
    {{ parent() }}
    <link href="{{ asset('assets/css/ag-grid/ag-theme-alpine.css') }}" rel="stylesheet">
    <style>
        .ag-theme-alpine {
            --ag-header-background-color: rgb(2, 65, 133) !important;
            --ag-header-foreground-color: #FFFFFF !important;
        }
        .ag-text-field-input { color: #000000 !important; }
        .grid-wrapper { position: relative; min-height: 600px; }
        .grid-custom-loader {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            background: #ffffff; z-index: 10;
        }
        .grid-loader-spinner {
            width: 42px; height: 42px;
            border: 4px solid #d9d9d9; border-top: 4px solid #024185;
            border-radius: 50%; animation: gridSpin 0.8s linear infinite; margin-bottom: 12px;
        }
        .grid-loader-text { font-size: 14px; color: #555; }
        @keyframes gridSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
{% endblock %}
```

### Bloc `{% block body %}`
```twig
{% block body %}
<section class="content">
    <div class="row"><div class="col-12"><div class="box">
        <div class="box-header with-border d-flex justify-content-between align-items-center">
            <h3 class="box-title mb-0">{{ title }}</h3>
            <div class="d-flex align-items-center gap-2">
                <button id="exportBtn" class="btn btn-primary btn-sm">Excel</button>
                {{-- boutons d'action supplémentaires ici --}}
            </div>
        </div>
        <div class="box-body">
            <div class="table-responsive-sm">
                <div id="myGridWrapper" class="grid-wrapper">
                    <div id="gridCustomLoader" class="grid-custom-loader">
                        <div class="grid-loader-spinner"></div>
                        <div class="grid-loader-text">Chargement de la grille...</div>
                    </div>
                    <div id="myGrid" class="ag-theme-alpine" style="height: 700px; visibility: hidden;"></div>
                </div>
            </div>
        </div>
    </div></div></div>
</section>
{% endblock %}
```

### Bloc `{% block javascripts %}` — Type A (inline)
```twig
{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('assets/js/pages/ag-grid-community.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/ag-grid-enterprise.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/ag-grid-common.js') }}?v=2.0"></script>
    <script>
    const columnDefs = [ /* définir les colonnes ici */ ];

    const agGridConfig = {
        columnDefs,
        dataUrl: '{{ path("nom_route_json") }}',
        numericColumns: ['champ_decimal'],   // champs avec décimales
        integerColumns: ['champ_entier'],    // champs entiers
        totalColumns: [],                    // champs avec ligne de total pinned
        headerClass: 'excelHeaderBlue',
        stateKey: 'aggrid-state-{nom-unique}-user-{{ app.user.id|default("local") }}-v1',
        excelStyles: [
            {
                id: 'excelHeaderBlue',
                font: { bold: true, color: '#FFFFFF' },
                alignment: { horizontal: 'Center' },
                interior: { color: '#024185', pattern: 'Solid' }
            }
        ],
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.gridOptions = window.AgGridCommon.initGrid('#myGrid', agGridConfig);

        document.getElementById('exportBtn').addEventListener('click', () => {
            window.gridOptions.api.exportDataAsExcel();
        });
    });
    </script>
{% endblock %}
```

### Bloc `{% block javascripts %}` — Type B (aggrid_option)
```twig
{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('assets/js/pages/ag-grid-community.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/ag-grid-enterprise.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/ag-grid-common.js') }}?v=2.0"></script>
    <script src="{{ asset('assets/js/pages/excel-export-standard.js') }}"></script>
    <script>
    const columnDefs = {{ columns|json_encode|raw }};

    const agGridConfig = {
        columnDefs,
        dataUrl: '{{ dataUrl }}',
        numericColumns: {{ numericColumns|json_encode|raw }},
        integerColumns: {{ integerColumns|json_encode|raw }},
        totalColumns: {{ totalColumns|json_encode|raw }},
        headerClass: 'excelHeaderBlue',
        stateKey: 'aggrid-state-{{ type }}-user-{{ app.user.id|default("local") }}-v1',
        excelStyles: [
            {
                id: 'excelHeaderBlue',
                font: { bold: true, color: '#FFFFFF' },
                alignment: { horizontal: 'Center' },
                interior: { color: '#024185', pattern: 'Solid' }
            }
        ]
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.gridOptions = window.AgGridCommon.initGrid('#myGrid', agGridConfig);
        window.AgGridCommon.patchSidebarCheckboxes('#myGrid');
        window.onBtExportExcel = async function () {
            await ExcelExportStandard.export(window.gridOptions, '{{ title|replace({" ": "_"}) }}.xlsx');
        };
        document.getElementById('exportBtn').addEventListener('click', () => window.onBtExportExcel());
    });
    </script>
{% endblock %}
```

---

## Règles critiques

1. **`{{ parent() }}`** est obligatoire dans `{% block stylesheets %}` ET `{% block javascripts %}` — sans ça Bootstrap et FontAwesome ne se chargent pas.
2. **`ag-grid-common.js` doit toujours être chargé** — c'est lui qui applique la licence Enterprise. Sans lui, l'avertissement de licence s'affiche.
3. **Ne jamais utiliser `agGrid.createGrid()`** — la version installée utilise `new agGrid.Grid()`, exposé via `AgGridCommon.initGrid()`.
4. **Ne jamais utiliser `grid.setGridOption('rowData', data)`** — utiliser `gridOptions.api.setRowData(data)` ou laisser `initGrid` faire le fetch via `dataUrl`.
5. **`visibility: hidden`** sur `#myGrid` au départ — `AgGridCommon` le rend visible après le chargement des données.
6. **Pour les dates MSSQL** : toujours caster en `CONVERT(varchar(10), [DateCol], 23) AS [DateCol]` dans la requête SQL.

---

## Vérification avant de terminer

- [ ] `{{ parent() }}` présent dans les deux blocs
- [ ] Les trois scripts AG Grid chargés dans le bon ordre (community → enterprise → common)
- [ ] `agGridConfig.dataUrl` pointe vers une route qui retourne du JSON valide
- [ ] `stateKey` unique par grille et par utilisateur
- [ ] Le thème bleu `rgb(2, 65, 133)` appliqué via CSS variable
- [ ] `visibility: hidden` sur `#myGrid` au départ
- [ ] Le résultat visuel est cohérent avec les autres tableaux de l'intranet
