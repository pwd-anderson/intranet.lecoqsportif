# Choisir le type de stat

Le volume de lignes et la durée de la requête décident. Les seuils ci-dessous viennent des mesures faites sur le projet ; ce sont des ordres de grandeur, à confirmer en exécutant la requête (`SELECT COUNT(*)` d'abord).

## Arbre de décision

1. **Combien de lignes ?** (estimer via COUNT, pas au jugé)
2. **Combien de temps dure la requête ?**
3. **L'utilisateur filtre-t-il avant ou après chargement ?**

| Cas | Type | Modèle à lire | Chargement | Export |
|---|---|---|---|---|
| < ~50 000 lignes, requête < ~5 s | Générique | `comptes_clients`, `ItController::itGeneric` | Standard | Excel (`ExcelExportStandard`) |
| ≥ ~50 000 lignes, tout charger est acceptable | Client-side en flux | `backlog_client_v3.html.twig`, `BacklogClientV2::writeAllRowsAsJson` | Avancé 3 phases | CSV écran + CSV serveur |
| Volume ingérable en navigateur, ou filtre serveur obligatoire | SSRM | Backlog Client X3 (`CLAUDE.md`), `AgGridSqlBuilder` | Standard SSRM | Serveur |
| Recherche par n° de document, résultat petit | Spécifique + filtres obligatoires | `suivi_complet_commande.html.twig` | Standard | Excel |

## Pourquoi le client-side en flux plutôt que le SSRM

Le Backlog Clients a été migré du SSRM vers du client-side : 176 000 lignes, ~298 Mo de JSON, 14 s de SQL, 30 s de transfert, mais seulement 0,9 s côté navigateur. Le SSRM imposait des requêtes de comptage, d'agrégat et de valeurs distinctes en plus, et trois requêtes à garder synchrones (règle du JOIN). Une fois chargé, le client-side rend filtres, tris, totaux et export instantanés. Le SSRM reste justifié seulement quand les données ne tiennent vraiment pas en mémoire.

## Cas hybrides

« Un mélange des deux » : une stat générique peut avoir une route JSON qui reçoit des paramètres (collections, option « inclure le stock ») et un bouton « Charger » au lieu d'un chargement automatique (voir `backlog_client_v3`, `distributor_availability`). Le contrôleur reste générique pour la config des colonnes, seul le template change.

## Où vit la config des colonnes

- Colonnes stables, éditables par un admin → `aggrid_option` (défaut).
- Colonnes qui dépendent des paramètres (ex. colonnes stock retirées si l'option n'est pas cochée) → config en base **plus** filtrage JS dans le template, comme `backlog_client_v3` (`STOCK_FIELD_RE`).
- Colonnes construites en JS (`buildColumnDefs()`) → voir `templates/sales/ventes_qte_ca_client.html.twig`.

## À dire à l'utilisateur

Une ou deux phrases : « Je pars sur du client-side en flux car ~170 000 lignes… » Si l'estimation de volume est impossible (pas d'accès MSSQL), le signaler et choisir l'approche prudente (flux + CSV serveur).
