# Chargement et export

## Les deux barres de chargement

Markup commun (déjà dans les templates génériques) : `#gridCustomLoader` contenant `.grid-loader-spinner`, `.grid-loader-text`, `#gridLoaderBarFill`, `#gridLoaderPercent`, plus `#gridActions` (masqué tant que ça charge).

### 1. Standard — `AgGridCommon.reloadData` / `startLoaderProgress`

Progression **simulée**, plafonnée à 85 % en ~2,8 s, puis 100 % quand la réponse arrive. Suffisant si la requête dure quelques secondes. Utilisé par toutes les stats génériques : il suffit de passer `dataUrl` à `initGrid`.

À éviter sur une requête longue : la barre reste bloquée à 85 % et l'utilisateur croit à un plantage (retour reçu sur le Backlog Clients).

### 2. Avancé — trois phases réelles

Modèle : `chargerEtAfficher()` dans `templates/sales/backlog_client_v3.html.twig`. Principe :

- `fetch` + `response.body.getReader()` pour lire le flux.
- Phase **requête** 0→35 % (aucun octet reçu, la base travaille, ~15 s) ; phase **téléchargement** 35→92 %, déclenchée par le **premier octet reçu** (pas par un minuteur) et affichant les Mo reçus ; phase **traitement** 92→99 % (`JSON.parse` + construction de la grille), puis 100 %.
- Chaque phase progresse de façon asymptotique vers son plafond (`1 - exp(-3t/durée)`) : la barre avance toujours, ne recule jamais, même si la durée réelle s'écarte de l'estimation. Ne pas afficher de vrai pourcentage de téléchargement : le serveur diffuse sans connaître la taille finale.
- `setTimeout(50)` avant `JSON.parse` pour laisser le navigateur peindre le message.
- Rechargement : `gridOptions.api.destroy()` + vider `#myGrid` sinon deux grilles se superposent.
- Après `setRowData` : `AgGridCommon.updateTotals(gridOptions, totalColumns)` et écouteurs `filterChanged`/`sortChanged`, car `initGrid` sans `dataUrl` ne le fait pas.
- Écouteurs d'export câblés **une seule fois** (`exportBound`), sinon ils s'empilent à chaque rechargement.
- En dev uniquement, une ligne de mesure (`#mesure`) : lignes, Mo, durée de chaque phase.

Choisir l'avancé dès que la requête dépasse environ 5 s ou que le JSON dépasse quelques dizaines de Mo. Les durées des phases (15 s / 30 s / 2 s) sont calibrées pour le backlog : les ajuster à la stat.

Ce code est écrit en ligne dans le template, pas dans `ag-grid-common.js`. Pour une deuxième stat, proposer de l'extraire en fonction partagée plutôt que de le dupliquer, et demander avant de modifier le fichier partagé.

## Chargement déclenché par un bouton

Quand la stat a des paramètres coûteux (collections, option stock), afficher un bouton « Charger » (`#loadBtn`) et ne rien exécuter à l'ouverture. Le bandeau de paramètres reste visible après chargement pour pouvoir relancer. Modèles : `backlog_client_v3`, `distributor_availability`.

## Export

| Besoin | Mécanisme | Limites |
|---|---|---|
| Excel d'un tableau modeste | `ExcelExportStandard.export(gridOptions, 'Nom.xlsx')` + `{{ include('components/_excel_loader.html.twig') }}`, progression simulée avec bouton d'annulation | ExcelJS casse vers 150 000 lignes, export natif AG Grid fige le navigateur |
| CSV de ce que l'utilisateur voit | `gridOptions.api.exportDataAsCsv({ fileName, columnSeparator: ';' })` dans un `setTimeout(50)` avec spinner dans le bouton | Synchrone : au-delà de 50 000 lignes, afficher la modale « Export volumineux » (Annuler / Exporter quand même) |
| CSV de la totalité, filtres ignorés | Formulaire POST caché → `StreamedResponse` qui écrit via `fputcsv` (`BacklogClientV2::writeCsv`), BOM UTF-8 + ligne `sep=;`, séparateur `;`, `X-Accel-Buffering: no` | Pas de signal de fin côté navigateur : rendre la main au bouton après un délai |

Règles :
- Colonnes du CSV serveur = colonnes visibles de la config `aggrid_option`, dans l'ordre, avec leurs `header_name` ; formater selon le `type` (`formatCsvValue`).
- Erreur dans un flux serveur : `GraphMailer::notifyError()` dans un `catch`, `fclose` dans `finally`.
- « Complet » doit vouloir dire complet : l'export serveur ignore volontairement filtres et tris, et le bouton l'indique dans son `title`.
- Boutons `btn btn-primary btn-sm` (le `btn-outline-primary` devient blanc sous le thème), spinner Bootstrap `spinner-border-sm` pendant l'export, libellé d'origine restauré dans un `finally`.
- Si le volume est gros, ne pas proposer Excel du tout ; le justifier dans le résumé.
