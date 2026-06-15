# Spec — Dashboard Sell Out

**Date :** 2026-06-15
**Périmètre :** Nouveau dashboard dédié aux données sell-out SPORT2000 et INTERSPORT, accessible depuis le header de l'intranet LCS.

---

## 1. Contexte

Les données sell-out (ventes et stocks chez les revendeurs) sont importées chaque lundi depuis `SEI_X3_LCS.LCS_SELLOUT_SALES` pour deux sources : `SPORT2000` et `INTERSPORT`. Ce dashboard expose ces données dans une interface identique au dashboard principal (`/`), avec les mêmes composants visuels (ApexCharts, Bootstrap boxes).

La table `templates/sales/sell_out.html.twig` existante est un prototype statique sans lien avec ce dashboard — elle reste en place mais n'est pas modifiée.

---

## 2. Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Service/Dashboards/SellOutDashboard.php` | Service dédié : méthodes refresh (INSERT dans MASTER_TABLES) + méthodes de lecture |
| `src/Command/Import/ImportDonneesSellOutCommand.php` | Commande `app:import-donnees-sellout` — appelle `SellOutDashboard::refreshAll()` |
| `src/Controller/SellOutDashboardController.php` | Route page `/dashboard/sell-out` + 7 endpoints JSON `/api/sell-out/*` |
| `templates/home/dashboard_sellout.html.twig` | Template identique en layout à `dashboard.html.twig` |
| `public/assets/js/dashboard_sellout.js` | JS miroir de `dashboard.js` — fonctions de chargement des blocs |

## 3. Fichiers à modifier

| Fichier | Changement |
|---|---|
| `templates/partials/_header.html.twig` | Bouton "Sell Out" à côté du network-switcher ; network-switcher masqué sur `app_dashboard_sellout` |
| `src/Service/Dashboards/MainDashboard.php` | `refreshBacklogClient()` : ajout de `customer_no`, `customer_name` dans l'INSERT et le GROUP BY |

---

## 4. Tables de staging MASTER_TABLES

Même convention que `MainDashboard` : suffixe `_DEV` en environnement dev via `$this->table()`.

### 4.1 INTRANET_SELLOUT_WEEKLY *(nouvelle)*

Agrégation hebdomadaire des ventes sell-out.

| Colonne | Type | Description |
|---|---|---|
| `week_code` | INT | Ex. 202623 (YYYYSS) |
| `annee` | INT | Année |
| `semaine` | INT | Numéro de semaine (1-53) |
| `sourcename` | VARCHAR(20) | `SPORT2000` ou `INTERSPORT` |
| `salesamt` | DECIMAL(18,2) | CA en devise source |
| `salesqty` | INT | Quantité vendue |

**Requête INSERT :**
```sql
INSERT INTO MASTER_TABLES.INTRANET_SELLOUT_WEEKLY
    (week_code, annee, semaine, sourcename, salesamt, salesqty)
SELECT
    WEEK_CODE,
    YEAR,
    CAST(RIGHT(CAST(WEEK_CODE AS VARCHAR(6)), 2) AS INT) AS semaine,
    SOURCENAME,
    SUM(SALESAMT) AS salesamt,
    SUM(SALESQTY) AS salesqty
FROM SEI_X3_LCS.LCS_SELLOUT_SALES
WHERE SOURCENAME IN ('SPORT2000', 'INTERSPORT')
GROUP BY WEEK_CODE, YEAR, SOURCENAME
```

### 4.2 INTRANET_SELLOUT_FAMILY *(nouvelle)*

Répartition du CA 2026 par famille produit (join ITMMASTER).

| Colonne | Type | Description |
|---|---|---|
| `annee` | INT | Année |
| `sourcename` | VARCHAR(20) | `SPORT2000` ou `INTERSPORT` |
| `famille` | VARCHAR(50) | `TCLCOD_0` de ITMMASTER |
| `salesamt` | DECIMAL(18,2) | CA |

**Requête INSERT :**
```sql
INSERT INTO MASTER_TABLES.INTRANET_SELLOUT_FAMILY
    (annee, sourcename, famille, salesamt)
SELECT
    S.YEAR,
    S.SOURCENAME,
    ISNULL(ITM.TCLCOD_0, 'INCONNU') AS famille,
    SUM(S.SALESAMT) AS salesamt
FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
LEFT JOIN X3_LCS.ITMMASTER ITM
    ON  LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0 + '_') - 1)
        = S.ITEMN
    AND SUBSTRING(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0 + '_') + 1, LEN(ITM.ITMREF_0))
        = S.VARIANTCODE
WHERE S.SOURCENAME IN ('SPORT2000', 'INTERSPORT')
  AND S.YEAR = YEAR(GETDATE())
GROUP BY S.YEAR, S.SOURCENAME, ITM.TCLCOD_0
```

### 4.3 INTRANET_SELLOUT_TOP_ITEMS *(nouvelle)*

Top produits vendus en 2026 (année en cours).

| Colonne | Type | Description |
|---|---|---|
| `annee` | INT | Année |
| `sourcename` | VARCHAR(20) | `SPORT2000` ou `INTERSPORT` |
| `itemn` | VARCHAR(50) | Référence article |
| `salesamt` | DECIMAL(18,2) | CA |
| `salesqty` | INT | Quantité |

**Requête INSERT :**
```sql
INSERT INTO MASTER_TABLES.INTRANET_SELLOUT_TOP_ITEMS
    (annee, sourcename, itemn, salesamt, salesqty)
SELECT
    YEAR,
    SOURCENAME,
    ITEMN,
    SUM(SALESAMT) AS salesamt,
    SUM(SALESQTY) AS salesqty
FROM SEI_X3_LCS.LCS_SELLOUT_SALES
WHERE SOURCENAME IN ('SPORT2000', 'INTERSPORT')
  AND YEAR = YEAR(GETDATE())
GROUP BY YEAR, SOURCENAME, ITEMN
```

### 4.4 INTRANET_BACKLOG_CLI *(modification)*

Ajout de deux colonnes à la table existante pour permettre le filtrage par client dans le sell-out dashboard, sans casser le dashboard principal.

**Colonnes ajoutées :**
- `customer_no VARCHAR(20)` — numéro client X3 (`BPC.BPCNUM_0`)
- `customer_name VARCHAR(100)` — nom client (`BPC.BPCNAM_0`)

**Impact dashboard principal :** nul — `getBacklogClientDonut()` fait `SUM(...) GROUP BY retard` sans filtre client ; le résultat est identique quelle que soit la granularité stockée.

**Modification de `refreshBacklogClient()` :** ajouter `BPC.BPCNUM_0` et `BPC.BPCNAM_0` dans l'inner SELECT, dans l'INSERT column list, et dans le GROUP BY final.

---

## 5. Layout du dashboard (8 blocs)

Design identique au dashboard principal — même composants Bootstrap `box`, même ApexCharts.

```
Row 1 — col-lg-3 × 4
┌───────────────┬───────────────┬───────────────┬───────────────┐
│ Bloc 1        │ Bloc 2a       │ Bloc 3a       │ Info semaine  │
│ Taux change   │ CA 2026 YTD   │ CA sem. S-1   │ N° semaine    │
│ EUR/USD       │ vs 2025 (KPI) │ vs S-1 N-1    │ + date MAJ    │
└───────────────┴───────────────┴───────────────┴───────────────┘

Row 2 — col-lg-8 + col-lg-4
┌────────────────────────────────────┬──────────────────────────┐
│ Bloc 2b                            │ Bloc 3b                  │
│ Ventes S01→Scurrent                │ Ventes semaine S-1       │
│ line chart 2026 vs 2025            │ bar chart 2026 vs 2025   │
│ (série par semaine)                │                          │
└────────────────────────────────────┴──────────────────────────┘

Row 3 — col-lg-4 × 3
┌───────────────┬───────────────┬───────────────────────────────┐
│ Bloc 4        │ Bloc 5        │ Bloc 6                        │
│ Top clients   │ CA par        │ Top 10 produits               │
│ INTERSPORT    │ famille       │ ITEMN progress bars           │
│ vs SPORT2000  │ donut         │                               │
└───────────────┴───────────────┴───────────────────────────────┘

Row 4 — col-lg-4 + col-lg-8
┌───────────────┬───────────────────────────────────────────────┐
│ Bloc 7        │ Bloc 8                                        │
│ Backlog       │ Évolution 12 semaines glissantes              │
│ donut         │ line chart 2026 vs 2025                       │
│ (filtré sur   │                                               │
│ 2 clients)    │                                               │
└───────────────┴───────────────────────────────────────────────┘
```

---

## 6. Endpoints JSON

Route de base : `/dashboard/sell-out` (`app_dashboard_sellout`)

| Endpoint | Nom route | Données source | Description |
|---|---|---|---|
| `GET /api/sell-out/ventes-annuelles` | `api_sellout_ventes_annuelles` | `INTRANET_SELLOUT_WEEKLY` | KPI YTD + séries S01→Scurrent pour les 2 années |
| `GET /api/sell-out/ventes-semaine` | `api_sellout_ventes_semaine` | `INTRANET_SELLOUT_WEEKLY` | KPI + détail semaine S-1 vs même semaine N-1 |
| `GET /api/sell-out/top-clients` | `api_sellout_top_clients` | `INTRANET_SELLOUT_WEEKLY` | CA 2026 agrégé par SOURCENAME (2 barres) |
| `GET /api/sell-out/famille-ca` | `api_sellout_famille_ca` | `INTRANET_SELLOUT_FAMILY` | Répartition CA par famille (donut) |
| `GET /api/sell-out/top-produits` | `api_sellout_top_produits` | `INTRANET_SELLOUT_TOP_ITEMS` | Top 10 ITEMN par SALESAMT |
| `GET /api/sell-out/backlog` | `api_sellout_backlog` | `INTRANET_BACKLOG_CLI` | Backlog filtré sur les `customer_no` correspondant à INTERSPORT et SPORT2000 (valeurs à confirmer en base X3 lors de l'implémentation) |
| `GET /api/sell-out/evolution-semaines` | `api_sellout_evolution_semaines` | `INTRANET_SELLOUT_WEEKLY` | 12 semaines glissantes 2026 vs 2025 |
| Taux de change | `api_dashboard_exchange_rate` | `ExchangeRatesMoyenRepository` | Réutilisation — aucun code nouveau |

---

## 7. Header — modifications

Fichier : `templates/partials/_header.html.twig`

**Comportement actuel :** le network-switcher (Global / Boutique / E-com / …) s'affiche uniquement sur `app_home`.

**Nouveau comportement :**
- Sur `app_home` : network-switcher visible + bouton lien "Sell Out" à sa droite
- Sur `app_dashboard_sellout` : network-switcher masqué + bouton "Sell Out" affiché en actif (style distinct)
- Sur toutes les autres routes : rien (comportement inchangé)

Le bouton "Sell Out" est un `<a>` Bootstrap de classe `btn btn-sm`, pointant vers `path('app_dashboard_sellout')`.

---

## 8. Service SellOutDashboard — méthodes

```
SellOutDashboard
├── refreshWeekly(): int
├── refreshFamily(): int
├── refreshTopItems(): int
├── refreshAll(): array            ← appelé par la commande
├── getVentesAnnuelles(): array    ← KPI YTD + séries hebdo
├── getVentesSemaine(): array      ← dernière semaine vs N-1
├── getTopClients(): array
├── getFamilyCa(): array
├── getTopProduits(): array
├── getBacklogSellOut(): array     ← réutilise INTRANET_BACKLOG_CLI avec filtre client
└── getEvolution12Semaines(): array
```

Connexion : `%db.lcs_sei%` (même que `MainDashboard`, alias `mssqlMade2design`).
Pattern `$this->isDev` appliqué via méthode `table()` identique.

---

## 9. Commande d'import

```
app:import-donnees-sellout
```

- Appelle `SellOutDashboard::refreshAll()`
- Affiche le nombre de lignes insérées par table
- Envoie un email de confirmation via `GraphMailer::send()` (même destinataire que `ImportDonneesCubeVenteIntranetCommand`)
- Planifiée le lundi (données importées hebdomadairement)

---

## 10. Contraintes techniques

- Toutes les requêtes SQL passent par `MssqlManager::executeQuery()` / `insertData()` / `executeDelete()`
- Aucune requête directe sur `SEI_X3_LCS.LCS_SELLOUT_SALES` depuis le contrôleur — uniquement les tables de staging
- Le JS `dashboard_sellout.js` suit le même pattern que `dashboard.js` : fonctions indépendantes par bloc, appel dans `$(function() {...})` depuis le template Twig
- La semaine courante est dérivée du `MAX(week_code)` disponible dans `INTRANET_SELLOUT_WEEKLY` (pas de `GETDATE()` car données hebdomadaires avec délai d'import)
- Le filtre backlog sur les deux clients utilise `customer_no` (plus fiable que `customer_name` dont le libellé exact dans `X3_LCS.BPCUSTOMER.BPCNAM_0` est à vérifier lors de l'implémentation)
