# Catalogue des stats de référence

Point de départ pour « trouver une stat qui ressemble ». À enrichir à chaque nouveau modèle (une ligne suffit). Les chemins sont relatifs à la racine du dépôt.

| Modèle | À copier quand… | Fichiers clés |
|---|---|---|
| **Générique simple** | Petit/moyen volume, chargement auto, Excel | `ItController::itGeneric`, `templates/it/it_generic.html.twig` (le plus court : 204 lignes), `src/Infrastructure/Sql/AgGrid/comptes_clients.sql` |
| **Générique par domaine** | Même chose dans Achats / Stock / Ventes | `AchatController::achatGeneric`, `StockController::stockGeneric`, `SalesController::salesGeneric` + `templates/{domaine}/{domaine}_generic.html.twig` |
| **Client-side en flux, gros volume** | ≥ 50 000 lignes, option(s) avant chargement, CSV serveur | `SalesController::backlogClientsV3*`, `BacklogClientV2` (`writeAllRowsAsJson`, `writeCsv`), `templates/sales/backlog_client_v3.html.twig` |
| **SSRM** | Données non chargeables en entier, filtres/tri serveur | `SalesController::backlogClientsX3*`, `Sales::getBacklogClientsX3*`, `AgGridSqlBuilder`, `SsrmRequest`, `ag-grid-ssrm.js`, `sales_generic.html.twig` (`serverSide`) |
| **Recherche ciblée, filtres obligatoires** | Résultat par n° de document ; renvoie `[]` sans critère | `SalesController::suiviCompletCommande*`, `Sales::getSuiviCompletCommande`, `templates/sales/suivi_complet_commande.html.twig` |
| **Chargement par bouton avec paramètres** | Paramètres coûteux à choisir avant de charger | `templates/distributor_availability/distributor_availability.html.twig`, `DistributorAvailability` |
| **Regroupement de lignes** | Row grouping AG Grid | `templates/sales/suivi_facturation.html.twig`, `SalesController::suiviFacturationJson` |
| **Colonnes construites en JS** | Colonnes dépendant des données | `templates/sales/ventes_qte_ca_client.html.twig` (`buildColumnDefs()`) |
| **Édition en grille** | Saisie utilisateur persistée | `sell_in_suivi_ps` (routes `save`, `save_batch`), tables `MASTER_TABLES` avec suffixe `_DEV` |
| **Moteur de calcul + export planifié** | Calcul croisé de plusieurs sources, Excel par cron | module Pilotage Livraisons (`CLAUDE.md`) |
| **Cache local d'une liste de référence** | Liste X3 lente et quasi statique | `X3Collection`, `app:x3-collection:refresh` |

## Ajouter une entrée

Quand une nouvelle stat introduit un mécanisme qui n'existait pas (nouvelle forme de chargement, d'export, de filtre), ajouter ici une ligne « modèle / quand l'utiliser / fichiers clés » **et**, si c'est une règle, la porter dans le fichier de référence concerné. Supprimer les lignes qui pointent vers du code retiré (le backlog X3 et la v2 sont conservés hors menu, à vérifier avant de les citer).
