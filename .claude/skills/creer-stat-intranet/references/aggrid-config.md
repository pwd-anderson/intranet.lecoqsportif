# Configuration AG Grid

## Règles du thème et de l'initialisation

- Scripts dans cet ordre : `ag-grid-community.min.js` (si le template voisin le charge), `ag-grid-enterprise.min.js`, `ag-grid-common.js` (porte la licence Enterprise), puis `exceljs.min.js`, `fileSaver.min.js`, `excel-export-standard.js` si export Excel.
- `{{ parent() }}` dans les blocs `stylesheets` et `javascripts`.
- Init via `window.AgGridCommon.initGrid('#myGrid', agGridConfig)`. Jamais `agGrid.createGrid()`, jamais `setGridOption('rowData', …)` : la version installée utilise `new agGrid.Grid()` et `gridOptions.api.setRowData()`.
- `#myGrid` en `visibility: hidden` au départ (le loader le révèle).
- Thème bleu : `--ag-header-background-color: rgb(2, 65, 133)`, texte blanc.
- `stateKey` unique par grille et par utilisateur : `'aggrid-state-{type}-user-{{ app.user.id|default("local") }}-v1'`.
- Pour une stat générique, le template partagé n'est jamais modifié.

## Table `aggrid_option` (MySQL `intranet_lcs`)

Un fichier `src/Infrastructure/Sql/AgGrid/{grid_name}.sql`, une ligne par colonne, le script commence par un `SELECT` de contrôle et un `DELETE` pour pouvoir le rejouer :

```sql
SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'ma_stat_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'ma_stat_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES
('ma_stat_grid','CLIENT','Client','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),
...
```

`field` = nom **exact** de la colonne renvoyée par la requête (sensible à la casse). `order_index` commence à 1 et suit l'ordre d'affichage voulu. `visible = 0` masque par défaut (la colonne reste dans les données et le sélecteur de colonnes).

## Correspondance type → colonne

| Donnée | `type` | `filter` | `value_formatter` / `comparator` | `cell_style.textAlign` | `agg_func` |
|---|---|---|---|---|---|
| Texte | `string` | `agTextColumnFilter` | NULL | `left` | NULL |
| Date (reçue en `varchar(10)` format 23) | `date` | `agDateColumnFilter` | NULL (le builder pose `dateFormatter`/`dateComparator`) | `left` | NULL |
| Entier | `integer` | `agNumberColumnFilter` | `integerFormatter` / `integerComparator` | `right` | `sum` si on totalise |
| Décimal / montant | `decimal` | `agNumberColumnFilter` | `decimalFormatter` / `decimalComparator` | `right` | `sum` si on totalise |
| Pourcentage déjà en % | `decimal` | `agNumberColumnFilter` | `percentRawFormatter` / `percentComparator` | `right` | NULL |

Un prix unitaire est numérique mais ne se somme pas : ne pas mettre `agg_func` dessus (voir le calcul de `$totalColumns` dans `backlogClientsV3()`). `AgGridColumnBuilder::build()` dérive `numericColumns`, `integerColumns`, `totalColumns` de ces valeurs, donc la config en base **est** la source de vérité.

Dernier modèle à suivre pour le style des colonnes : `src/Infrastructure/Sql/AgGrid/suivi_complet_commande.sql` (les anciens scripts ajoutent `fontSize: 16px`, les récents non ; choisir celui de la stat voisine pour rester cohérent à l'écran).

## Déjà fourni par `AgGridCommon`

Filtre flottant sur toutes les colonnes, tri, redimensionnement, `flex`, ligne de totaux épinglée (fond bleu, texte blanc), sauvegarde/restauration d'état (`saveGridState`, `loadGridState`, `clearGridState`), `setupAutoWidth`, `setupAutoHeight`, `patchSidebarCheckboxes`, `updateTotals`. Ne pas les réécrire.

## Regroupements, agrégations, comportements spécifiques

Pas de convention globale pour le row grouping : le seul exemple est `templates/sales/suivi_facturation.html.twig` (`rowGroup` / `autoGroupColumnDef`), à lire avant d'en créer un. Pour une cellule spécifique (lien, couleur conditionnelle), passer par `rowClassRules` / `getRowStyle` via `config.extraOptions` ou `config.rowClassRules` de `initGrid` ; `suivi_complet_commande.html.twig` montre des cellules en lien vers des PDF.

## Alias ATEXTRA (colonnes libres X3)

Ne jamais réutiliser un alias (`ATX` … `ATX7` pris), toujours incrémenter. Détail dans CLAUDE.md.
