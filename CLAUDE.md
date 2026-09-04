# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Règles importantes

- **Ne jamais commiter** — l'utilisateur gère tous les commits lui-même.
- **Toujours `git add` les nouveaux fichiers créés** (jamais de commit) — dès qu'un fichier est créé (entité, migration, commande, SQL, template…), le suivre immédiatement avec `git add <fichier>` pour qu'il apparaisse en staged et ne soit pas oublié lors du prochain commit manuel de l'utilisateur.
- **Ne jamais travailler sur `main`** — si la branche courante est `main`, basculer immédiatement sur `dev` (`git checkout dev`) avant de commencer tout travail. Ne s'applique qu'à `main` ; ne pas changer de branche dans les autres cas (rester sur la branche déjà active si ce n'est pas `main`).

## Commands

```bash
# Start dev server
symfony serve

# Clear cache
symfony console cache:clear

# Database migrations
symfony console doctrine:migrations:migrate
symfony console doctrine:migrations:generate

# Install dependencies
composer install

# Run tests
bin/phpunit
```

## Architecture

### Stack
Symfony 7.4, Twig, AG Grid Enterprise, Bootstrap. PHP ≥ 8.2.

### Databases

Two databases are in use simultaneously:

- **MySQL** (`DATABASE_URL`) — Symfony app database: users, AG Grid column configuration (`aggrid_option` table).
- **MSSQL** — Three separate SQL Server instances for analytical data, configured in `config/services.yaml` and injected via `MssqlManagerFactory`:
  - `%db.made2deseign%` — Made2Design warehouse
  - `%db.lcs%` — LCS Production BI
  - `%db.lcs_sei%` — SEI Cube (used by the IT domain)

Services receive a specific MSSQL connection via `#[Autowire]` and `MssqlManagerFactory::create()`. See `src/Service/It.php` for the pattern.

### SQL files

Raw SQL queries live in `src/Infrastructure/Sql/`, organized by source:
- `Sei/` — SEI Cube queries (MSSQL, used by IT stats)
- `Navision/` — Navision X3 queries
- `AgGrid/` — MySQL INSERT scripts for AG Grid column configuration (one file per grid, named `{grid_name}.sql`)

`SqlFileLoader` (injected as a service) loads them by relative path: `$this->sqlFileLoader->load('Sei/my_query.sql')`.

### Stat pattern (generic)

Every business domain (Achats, Ventes, Stock, IT) follows the same structure for **generic stats**:

1. **SQL file** in `src/Infrastructure/Sql/Sei/` (or equivalent subfolder)
2. **Service method** in `src/Service/{Domain}.php` — loads the SQL file, executes it on the correct MSSQL instance, catches exceptions and notifies via `GraphMailer::notifyError()`
3. **Controller** in `src/Controller/{Domain}Controller.php`:
   - Alias route: named URL → calls the generic handler
   - JSON route: returns `JsonResponse` with `Helpers::convertArrayToUtf8($data)`
   - Generic handler: reads a `$config` array keyed by type identifier, loads AG Grid columns from DB via `AgGridColumnBuilder::build()`, renders `{domain}/{domain}_generic.html.twig`
4. **AG Grid config** stored in MySQL `aggrid_option` table (`grid_name` matches the key in `$config`). INSERT scripts are versioned in `src/Infrastructure/Sql/AgGrid/`
5. **Sidebar link** in `templates/partials/_sidebar.html.twig`
6. **Translation key** `sidebar.stat.{domain}.{identifier}` in `translations/messages.fr.yaml` and `messages.en.yaml`

When adding a new generic stat, all six steps are required. The Twig template is shared and never modified.

### Stat pattern (special / SSRM)

Used for large datasets where AG Grid fetches data server-side. The **Backlog Client X3** (`backlog_clients_x3`) is the reference implementation.

- `AgGridSqlBuilder` (`src/Service/AgGrid/Ssrm/`) converts AG Grid's `filterModel` / `sortModel` into SQL WHERE / ORDER BY / OFFSET-FETCH clauses.
- Allowed columns must be whitelisted explicitly in `AgGridSqlBuilder` to prevent injection.
- `SsrmRequest` is the DTO parsed from the frontend request body.
- The JSON route receives a POST, builds the query, and returns paginated results.

#### Backlog Client X3 — architecture complète

**Fichiers impliqués :**

| Rôle | Fichier |
|---|---|
| Requête principale (données + export) | `src/Infrastructure/Sql/Sei/backlog_client.sql` |
| Requête de comptage (pagination) | `src/Infrastructure/Sql/Sei/backlog_client_count.sql` |
| Agrégats/totaux (construite en dur PHP) | `Sales::buildBacklogClientsX3AggregateSql()` |
| Whitelist colonnes + expressions SQL | `Sales::getBacklogClientsX3FieldMap()` |
| Logique SSRM paginée | `Sales::getBacklogClientsX3Paginated()` |
| Valeurs distinctes pour filtre Set | `Sales::getBacklogClientsX3DistinctValues()` |
| Config colonnes AG Grid (MySQL) | `src/Infrastructure/Sql/AgGrid/backlog_client_x3.sql` |
| Template + JS AG Grid | `templates/sales/sales_generic.html.twig` |
| Routes | `SalesController` : `backlog_clients_x3_ssrm_json` (POST), `backlog_clients_x3_filter_values` (POST) |

**Règle critique — toute nouvelle colonne avec JOIN doit être ajoutée dans les 3 requêtes :**
1. `backlog_client.sql` — SELECT + JOIN
2. `backlog_client_count.sql` — JOIN uniquement (pas de SELECT)
3. `buildBacklogClientsX3AggregateSql()` dans `Sales.php` — JOIN uniquement

Si un JOIN est manquant dans l'une des trois, le filtre ou les totaux échouent avec une erreur MSSQL "multi-part identifier could not be bound".

**Règle critique — filtre Set (liste déroulante style Excel) :**
- La méthode `getBacklogClientsX3DistinctValues()` utilise le FROM de `backlog_client.sql` pour construire un `SELECT DISTINCT`.
- Pour qu'une colonne ait la liste déroulante, ajouter son nom dans `ssrmSetFilterFields` dans `sales_generic.html.twig` (ligne ~263).
- Le `fieldMap` dans `Sales::getBacklogClientsX3FieldMap()` doit contenir l'expression SQL exacte (ex: `ATX6.TEXTE_0`), pas l'alias.

**Règle critique — alias ATEXTRA :**
- `ATX` = MAINNETWORK (TSCCOD_2, IDENT1=32)
- `ATX4` = INDEPENDANT_GROUPMENT (ZGROUPIND_0, IDENT1=6021)
- `ATX5` = AGE (TABLINCFG / CFGLIN_0)
- `ATX6` = GROUP_CODE (ZGRPCOD_0, IDENT1=6028)
- `ATX7` = DISTRIBUTION_CHANNEL (TSCCOD_4, IDENT1=34) — utilisé dans `etat_commandes_clients.sql` / `etat_commandes_clients_count.sql` (stat "État des commandes clients")
- Ne jamais réutiliser un alias existant — toujours incrémenter (ATX8, ATX9…).

### AG Grid column configuration

Columns are stored in MySQL (`aggrid_option` entity). Key fields: `grid_name`, `field` (exact DB column name as returned by the query), `header_name`, `type` (`string`/`integer`/`date`/`decimal`), `filter`, `cell_style` (JSON), `value_formatter`, `comparator`, `order_index`.

For dates in MSSQL queries, always cast to `varchar(10)` with format 23: `CONVERT(varchar(10), [MyDateCol], 23) AS [MyDateCol]`.

### Authentication

Azure AD via a custom `AzureAuthenticator` (`src/Security/`). Public routes are `/connect` and `/callback`. All other routes require `ROLE_USER`. Admin panel (`/admin/*`) requires `ROLE_ADMIN`.

### Gestion des rôles et droits d'accès

#### Deux couches distinctes

**Couche 1 — Rôle : visibilité des sections sidebar**
Les rôles déterminent quelles sections du menu sont visibles. Ils proviennent des groupes Azure AD, lus via `GET /me/memberOf` (Microsoft Graph) dans `UserManagerAzure::fetchUserRoles()`.

Mapping groupes Azure → rôles Symfony :

| Groupe Azure AD | Rôle Symfony |
|---|---|
| GS_INTRA_ACCOUNTING | ROLE_ACCOUNTING |
| GS_INTRA_ADV | ROLE_ADV |
| GS_INTRA_CONTROLLING | ROLE_CONTROLLING |
| GS_INTRA_IT | ROLE_IT + ROLE_ADMIN |
| GS_INTRA_LOGISTIC | ROLE_LOGISTIC |
| GS_INTRA_MANAGEMENT | ROLE_MANAGEMENT |
| GS_INTRA_MARKETING | ROLE_MARKETING |
| GS_INTRA_MODETIQEXP | ROLE_MODETIQEXP |
| GS_INTRA_PURCHASING | ROLE_PURCHASING |
| GS_INTRA_SALES | ROLE_SALES |
| GS_INTRA_SAV | ROLE_SAV |

`ROLE_MANAGEMENT` et `ROLE_CONTROLLING` sont des **super-users** : ils voient toutes les sections. Variable Twig `isSuperUser` définie en haut du sidebar.

Sections sidebar et rôles autorisés :
- **Ventes** → SALES, MARKETING + superusers
- **ADV** → ADV + superusers
- **Achats** → PURCHASING + superusers
- **Stock** → LOGISTIC, PURCHASING + superusers
- **IT** → IT + superusers
- **SAV** → SAV, IT + superusers
- **Modules / Étiquettes** → MODETIQEXP, LOGISTIC + superusers
- **Modules / Import OD** → ACCOUNTING + superusers

**Couche 2 — Exclusions par user : visibilité fine des stats**
En complément des rôles, un admin peut exclure un utilisateur spécifique d'une stat, d'un widget dashboard, d'un filtre ou d'un module.

- Table MySQL : `user_stat_exclusion` (`id`, `user_id`, `stat_key`)
- `stat_key` = nom de route Symfony (ex: `app_kpi_retail`) ou clé fonctionnelle (ex: `dashboard_filter_boutique`)
- Registre central : `src/Service/StatRegistry.php` — liste toutes les clés avec section, label, rôles requis
- Fonction Twig : `is_stat_excluded('stat_key')` — chargée une fois par requête (`src/Twig/StatAccessExtension.php`)
- Blocage route : `src/EventSubscriber/StatAccessSubscriber.php` — redirige vers l'accueil si la route est dans les exclusions de l'user
- Page admin : `/admin/permissions` — accessible à ROLE_ADMIN et ROLE_MANAGEMENT

#### Ajouter une nouvelle stat au système d'exclusion

1. Ajouter la clé dans `StatRegistry::all()` avec section, label et rôles
2. Wrapper le lien sidebar avec `{% if not is_stat_excluded('ma_route') %}`
3. La page admin `/admin/permissions` l'affiche automatiquement

#### Règle importante
Ne jamais bypasser les deux couches. Un user sans le bon rôle ne voit pas la section. Un user avec le bon rôle mais une exclusion ne voit pas la stat — ni dans le sidebar, ni en accès direct à la route.

### Error notifications

All service-level exceptions are caught, logged via PSR logger, and sent as email alerts via `GraphMailer::notifyError()` (Microsoft Graph API).

### Cache local des collections X3 (`x3_collection`)

`Divers::getCollections()` (liste des collections X3, utilisée par tous les multi-select collections du site — Excess For Sales, État des commandes clients, etc.) interrogeait directement `SEI_X3_LCS.LCS_COLLECTION` sur le SEI Cube à chaque appel — lent, alors que cette liste change à peine 2 fois par an.

**Solution :** table MySQL `x3_collection` (entité `App\Entity\X3Collection`, repo `X3CollectionRepository::findAllCodesDesc()`) qui sert de cache. `Divers::getCollections()` lit ce cache en priorité, avec fallback direct sur le SEI Cube si la table locale est vide (permet un fonctionnement même avant le premier refresh).

**Rafraîchissement :** `php bin/console app:x3-collection:refresh` (`src/Command/Import/RefreshX3CollectionCommand.php`) — resynchronise depuis le SEI Cube (ajoute les nouvelles collections, supprime celles qui n'existent plus côté X3). À exécuter :
- manuellement après un déploiement sur chaque environnement (préprod, prod) pour le premier peuplement
- idéalement via **cron quotidien** (ex. 5h du matin, avant le cron Pilotage Livraisons) pour que les nouvelles collections apparaissent sans intervention manuelle

**Règle critique** : si une nouvelle collection est créée côté X3 et que le cron de refresh n'a pas encore tourné, elle n'apparaîtra pas dans les multi-select tant que `app:x3-collection:refresh` n'a pas été relancé (manuellement ou via cron).

### Module Pilotage Livraisons

Remplace un outil HTML autonome développé par un PM (`public/template/Pilotage_Livraisons_LCS-2.html`, gardé comme référence de design ET de règles métier — à consulter en cas de doute). Croise backlog client, backlog fournisseur et stock pour piloter les livraisons : couverture, ETA, statut par commande.

**Fichiers :**

| Rôle | Fichier |
|---|---|
| Page + moteur de calcul JS (mode navigateur) | `templates/pilotage/pilotage.html.twig` |
| SQL backlog client (allégé, alias bracketés) | `src/Infrastructure/Sql/Sei/backlog_client_pilotage.sql` |
| SQL backlog fournisseur (UNION PO + intersites) | `src/Infrastructure/Sql/Sei/backlog_fournisseur_pilotage.sql` |
| SQL stock (tous sites, statut A1) | `src/Infrastructure/Sql/Sei/stock_pilotage.sql` |
| Service (requêtes + conversion EUR) | `src/Service/Pilotage.php` |
| Contrôleur (page + 3 routes JSON) | `src/Controller/PilotageController.php` |
| Moteur de calcul PHP (portage du JS, pour la commande) | `src/Service/Pilotage/PilotageEngine.php` |
| Export Excel 3 onglets (PhpSpreadsheet) | `src/Service/Pilotage/PilotageExcelExporter.php` |
| Commande cron (export + email) | `src/Command/Pilotage/ExportPilotageLivraisonsCommand.php` (`app:pilotage:export-livraisons`) |

**Deux chemins de calcul indépendants, doivent rester synchronisés :**
1. **Navigateur** — `compute()` en JS dans `pilotage.html.twig`, alimenté soit par les 3 routes API (mode live), soit par import de fichiers Excel/CSV (mode test, via SheetJS, détection auto des colonnes).
2. **Commande CLI** — `PilotageEngine::compute()` en PHP, portage ligne à ligne du JS, utilisé par la commande cron pour générer l'export Excel envoyé par email. **Toute évolution des règles de calcul (transit, ETA, FFOB, statuts…) doit être répercutée dans les deux fichiers.**

**Règle critique — montant EUR :**
Le prix n'est disponible en base qu'en devise d'origine (`SOP.NETPRINOT_0`, prix unitaire). `backlog_client_pilotage.sql` renvoie `[PRIX UNITAIRE]` + `[DEVISE]` (`SOH.CUR_0`), et `Pilotage::applyEurConversion()` calcule `[PRIX EUR] = (prix unitaire / taux de change) × quantité` côté PHP, via `Divers::getExchangeRatesValues()` — même formule que `Sales::enrichBacklogClientsX3Rows()` pour le Backlog Client X3. Ne jamais renvoyer le prix unitaire brut comme montant final.

**Règle critique — alias SQL bracketés :**
Les 3 requêtes utilisent des alias `AS [NOM AVEC ESPACES]` correspondant exactement aux noms de colonnes attendus par le moteur JS (`colMap()`/`G()` normalise accents/espaces mais pas les underscores). Toute nouvelle colonne doit suivre ce format bracketé et son nom doit matcher ce qu'attend `compute()` des deux côtés (JS et PHP).

**Filtrage collection :** seul le backlog client est filtré par collection (paramètre `collections[]`, multi-select sur `2026-02-FW` / `2027-01-SS` / `2027-02-FW`). Le backlog fournisseur et le stock restent volontairement **non filtrés** (décision explicite de l'utilisateur) — le filtrage se fait uniquement côté moteur de calcul via la constante `R.COLLECTIONS` (JS) / `PilotageEngine::COLLECTIONS` (PHP).

**Chargement séquentiel, pas `Promise.all` :** les 3 requêtes API sont volontairement enchaînées l'une après l'autre dans `pilotage.html.twig` (pas en parallèle) — un `Promise.all` avait provoqué des timeouts 524 en préprod en saturant MSSQL avec 3 requêtes lourdes simultanées.

**Export Excel — 3 onglets obligatoires** (SYNTHÈSE DIRECTION, PILOTAGE LIVRAISONS, DETAIL PAR ARTICLE), structure identique entre l'export navigateur (`doExcel()` en JS) et l'export commande (`PilotageExcelExporter`). PhpSpreadsheet v5 : utiliser `setCellValue('A1', $v)`, pas `setCellValueByColumnAndRow()` (supprimé).

**Commande cron :** `php bin/console app:pilotage:export-livraisons` — sauvegarde dans `var/upload/export/pilotage_livraison/`, envoie par email via `GraphMailer` aux destinataires de la variable d'env `MAIL_PILOTAGE_LIVRAISON` (liste séparée par virgules), lue directement via `#[Autowire(env: 'MAIL_PILOTAGE_LIVRAISON')]` (pas de paramètre dans `services.yaml`). Prévue pour crontab quotidien (ex. 6h du matin).

**Dépendance ajoutée :** `phpoffice/phpspreadsheet` — penser à `composer install` sur chaque environnement après déploiement (préprod, prod).
