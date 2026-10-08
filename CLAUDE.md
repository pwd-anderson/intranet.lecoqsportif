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
7. **Section auto-open** : ajouter le nom de la route dans la liste `currentRoute in [...]` du bloc `<li class="treeview {% if currentRoute in [...] %}active{% endif %}">` correspondant à la section (Ventes, ADV, Achats, IT...) dans `_sidebar.html.twig`. Sans ça, le menu ne se déroule pas automatiquement sur la nouvelle stat (elle reste accessible, juste pas mise en évidence dans le sidebar). Ne pas utiliser `path starts with '/...'` pour ce test — plusieurs sections partagent le même préfixe d'URL (ex: Ventes et ADV commencent toutes les deux par `/sales/`), ce qui ouvre les deux blocs en même temps.

When adding a new generic stat, all seven steps are required. The Twig template is shared and never modified.

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

### Plan transport prévision (import SharePoint → `MASTER_TABLES.PLAN_TRANSPORT_PREVISION`)

Livraisons SS27 consolidées à l'article, importées chaque jour depuis un Excel SharePoint (service PURCHASING) et affichées dans la stat Achats **« Plan transport prévision de dates »** (menu Achats, deux lignes dans le sidebar ; **étape 2 à venir : remonter certaines infos dans le Backlog Client — ne pas la démarrer sans l'accord de l'utilisateur**).

| Rôle | Fichier |
|---|---|
| Commande (cron quotidien) | `src/Command/Import/ImportPlanTransportCommand.php` — `php bin/console app:import-plan-transport` (`--dry-run` : télécharge et contrôle sans écrire ; `--file=…` : fichier local ; `--sheet=…`) |
| Lecture Excel + écriture en base | `src/Service/PlanTransport/PlanTransportImporter.php` |
| Accès SharePoint (réutilisable) | `src/Service/Tools/SharePointClient.php` (Microsoft Graph, URL « claire » encodée au format `shares`) |
| DDL (à exécuter à la main, dev ET prod) | `src/Infrastructure/Sql/Sei/create_table_plan_transport_prevision.sql` |
| URL du fichier | variable d'env `PLAN_TRANSPORT_SHAREPOINT_URL` (défaut dans `config/services.yaml`, `%` écrit `%%`) |
| Stat (page) | `AchatController::planTransportPrevision` (route `app_plan_transport_prevision`) + JSON `plan_transport_prevision_json` (`PlanTransportImporter::fetchAll`), template dédié `templates/achat/plan_transport_prevision.html.twig` |
| Colonnes AG Grid (MySQL) | `src/Infrastructure/Sql/AgGrid/plan_transport_prevision.sql` (`plan_transport_prevision_grid`, à charger avec `--default-character-set=utf8mb4` en dev, préprod, prod) |
| Droits / menu | `StatRegistry` (`app_plan_transport_prevision`, ROLE_PURCHASING + super-users), sidebar Achats, traductions `sidebar.stat.purchase.plan_transport_prevision` |

**Accès SharePoint.** L'application Azure **MailGraph** (`GRAPH_CLIENT_ID`, celle des mails) a reçu le droit d'application **`Sites.Read.All`** (consentement admin accordé le 2026-10-08). L'application « intranet » (`AZURE_CLIENT_ID`, connexion des utilisateurs) est distincte et n'est pas utilisée ici. Fichier lu : `https://lecoqsportif.sharepoint.com/Documents%20partages/PURCHASING/Air%20Boat%20Split%20SS27%2006-10-26%20ONE%20DRIVE.xlsx` (35 Mo ; nom **provisoire**, le métier doit fournir un nom logique — penser à changer `PLAN_TRANSPORT_SHAREPOINT_URL`, ou chercher le fichier le plus récent du dossier).

**Onglet lu : « SS27 LIVRAISONS - ARTICLES »** (le 4ᵉ onglet, pas le 2ᵉ ; lu par son nom, à changer pour une autre saison avec `--sheet`). En-têtes ligne 3 (colonnes B à J contrôlées : une colonne renommée/déplacée fait échouer l'import et envoie un mail), données à partir de la ligne 4, ligne « TOTAL » et lignes vides ignorées. Colonnes : A Fournisseur, B N° PO, C Article (SKU `parent_variant`, ex. `2710685_40`), D Désignation, E Qté, F Moyen de transport (texte libre, **importé tel quel, non normalisé** : « Aérien », « Fast Boat Chine », « FAST BOAT (Chine) », « … — OP DIRECTE, PO ENTIER », etc.), G Départ usine (XF), H ETD, I ETA France (Logtex), J Statut départ (`PARTI`, `BOOKÉ — ON HOLD`, vide = à planifier). Les dates sont des nombres Excel convertis en `date`. `ARTICLE` (parent) et `VARIANT` sont déduits du SKU (coupure au premier `_`) pour la liaison avec le Backlog Client.

**Règles.**
- **Remplacement complet à chaque import, dans UNE transaction PDO** (`DELETE` + `INSERT` par paquets de 100 lignes + contrôle du nombre de lignes). En cas d'échec la table garde sa version précédente et `GraphMailer::notifyError` envoie un mail. Un fichier qui donne 0 ligne n'efface jamais la table.
- Pas de clé unique : 774 couples (PO, article) apparaissent 2 fois avec transports/dates/quantités différents (fractionnements réels, pas des erreurs).
- Table `_DEV` automatique en `APP_ENV=dev` (pattern `kernel.environment`).
- Vérifié le 2026-10-08 : 3 310 lignes, 493 524 pièces ; insertion de test sur table temporaire en 2,2 s ; fichier 35 Mo, lecture de l'onglet seul 1,2 s / 30 Mo de mémoire.

**La stat.** Type **générique avec template dédié** (3 310 lignes : chargement direct, filtres/tri côté navigateur ; les colonnes restent éditables en base) : le template partagé `achat_generic` n'est pas touché. Colonnes = celles de l'Excel (+ `ARTICLE` parent et `VARIANT` masquées, dans le sélecteur de colonnes), tri par défaut ETA France croissante, total des quantités. **Code couleur du PM : la cellule ETD prend la couleur du statut de la ligne** — `PARTI` = vert (`#C6EFCE`), `BOOKÉ — ON HOLD` = orange (`#FFEB9C`), vide (« à planifier ») = sans couleur (mêmes couleurs que l'Excel, vérifié cellule par cellule : 162 vertes = 162 PARTI, 157 orange = 157 BOOKÉ, 2 991 sans couleur = statut vide ; la couleur n'est donc pas lue dans le fichier, elle est déduite de `STATUT_DEPART`). Légende au-dessus de la grille, date et fichier du dernier import affichés en en-tête. Export Excel avec les mêmes couleurs.

**Piège encodage (corrigé).** Le pilote `dblib` (FreeTDS) travaille en ISO-8859-1 par défaut : écrire « BOOKÉ — ON HOLD » via la connexion partagée stockait `BOOKÃ? â??` (« É » et tiret long déformés, irrécupérables) et relisait le tiret long en `?`. `PlanTransportImporter` utilise donc **sa propre connexion PDO avec `;charset=UTF-8`** (écriture et lecture exactes, vérifié : `UNICODE()` = 201 pour « É »). Ne pas repasser par `MssqlManager` pour ce module, et penser à ce piège pour tout futur import avec accents / tirets longs. (`MssqlManager` avale aussi les erreurs SQL : le module utilise PDO avec exceptions.)

**Remontée dans le Backlog Client (étape 2, faite le 2026-10-08 — `src/Infrastructure/Sql/Sei/backlog_client_v2.sql` + `src/Service/BacklogClientV2.php`).** La colonne **`DATE_COMMANDE_FOURNISSEUR`** (« date arrivée prévue », nom historique conservé) vaut maintenant `COALESCE(CI.DATE_INTERSITE, PT.DATE_ETA_FRANCE)` :
1. **CI = intersite** : `MASTER_TABLES.COMMANDES_INTERSITES` filtré `QTE_EN_TRANSIT > 0`, `MIN(EXTRCPDAT_0)` par (article, site de réception = site de la commande) ;
2. sinon **PT = plan transport** : `MIN(DATE_ETA_FRANCE)` par `ARTICLE_SKU` (= `SOQ.ITMREF_0`, SKU complet), **passée ou non**, **toutes lignes du plan quel que soit le statut de départ**, **uniquement pour les commandes du site `WLOGM`** (l'ETA France = arrivée chez Logtex).
La date des commandes fournisseur (`PORDERQ`, ancien premier choix) **n'entre plus dans cette colonne** ; le bloc `PO` ne sert plus qu'à `PO_EN_COURS` (quantité, inchangée). Aucune colonne « source de la date » (refusé par l'utilisateur).
- **Cohérence à garder** (règle du service) : la même règle existe dans 4 endroits — SELECT de `backlog_client_v2.sql`, `getFieldMap()` (filtres/tris SSRM), `supplierJoins()` (jointures de la requête des totaux, ajoutées seulement si le filtre porte sur la colonne) et `getDistinctValues()` (via la requête de base). Toute évolution doit être répercutée partout.
- **Table `_DEV` / prod** : le SQL contient `{{PLAN_TRANSPORT_TABLE}}`, remplacé par `BacklogClientV2::planTransportSource()` (nom de table fourni par `PlanTransportImporter::table()`, une seule source de vérité).
- **Protection (le Backlog Client doit TOUJOURS rester disponible)** : si la table du plan est absente, ou n'a pas les colonnes `ARTICLE_SKU` / `DATE_ETA_FRANCE`, ou si le contrôle échoue, `planTransportSource()` la remplace par une source vide de mêmes colonnes (`(SELECT … WHERE 1 = 0)`) et écrit un warning dans le log : le backlog fonctionne comme avant, sans les dates du plan (seules les dates intersite restent). Une table existante mais vide (avant le premier import en prod) est gérée naturellement. Contrôle fait une fois par requête HTTP.
- **Mesures (dev, 2026-10-08, site WLOGM : 154 505 lignes de backlog)** : ancienne règle 110 329 lignes avec date ; nouvelle règle **116 539** (8 341 via l'intersite, le reste via le plan). Table absente : 154 505 lignes, 8 341 avec date, aucune erreur. Page par défaut inchangée (1,0 s) ; tri + filtre sur la date : 7,3 s → 11,2 s.

**À faire / en attente.** Planifier le cron quotidien (`app:import-plan-transport`) ; charger le script `plan_transport_prevision.sql` (MySQL) et le DDL (SEI) en préprod/prod ; vérifier l'affichage du Backlog Client à l'écran ; nom de fichier logique côté métier.

### Tunnel de dev (callback Azure AD en local)

Pour tester le flux de connexion Azure AD en local, il faut une URL HTTPS publique stable à enregistrer comme Reply URL dans Azure AD. Solution retenue : **Microsoft Dev Tunnels** (`devtunnel`), gratuit, sans limite d'appels (contrairement à ngrok gratuit).

**Lancer le tunnel :**
```bash
scripts/dev-tunnel.sh
```
Démarre le serveur Symfony local (port 8000) si besoin, démarre `devtunnel host` si besoin, affiche l'URL publique à utiliser.

**Config déjà en place (ne pas refaire) :**
- Tunnel devtunnel : ID `peaceful-ant-f4ndxfd.euw`, port 8000 en `http` (pas `https` — le serveur Symfony local ne parle que HTTP, un port en `https` casse la connexion locale avec un 502)
- `config/packages/framework.yaml` : `devtunnels.ms` ajouté à `trusted_hosts`
- `src/EventSubscriber/DevTunnelHostSubscriber.php` : réécrit hôte (`Host`), port (443) et schéma (HTTPS) de chaque requête quand `DEV_TUNNEL_PUBLIC_HOST` (`.env`, local uniquement) est définie. `devtunnel host` réécrit l'en-tête `Host` en `localhost:8000` et envoie le vrai hôte dans `X-Forwarded-Host` ; mais Symfony n'honore ces en-têtes que si l'adresse source est dans les proxys de confiance, et devtunnel se connecte tantôt en IPv4 (`127.0.0.1`), tantôt en IPv6 (`::1`, non couvert par `0.0.0.0/0`). Sans ce listener, une requête sur deux génère ses redirections absolues (callback OAuth inclus) sur `https://localhost:8000/...` → `ERR_SSL_PROTOCOL_ERROR` au retour du login Azure. Désactivé automatiquement (valeur par défaut vide dans `services.yaml`) si la variable n'est pas définie — aucun impact préprod/prod.
- `.env` local : `AZURE_REDIRECT_URI` et `DEV_TUNNEL_PUBLIC_HOST` pointent vers l'URL actuelle du tunnel

**Si l'URL change** (tunnel expiré après 30 jours, ou recréé) :
1. `devtunnel create --allow-anonymous` puis `devtunnel port create -p 8000 --protocol http` → nouvel ID de tunnel et nouvelle URL
2. Mettre à jour `AZURE_REDIRECT_URI` et `DEV_TUNNEL_PUBLIC_HOST` dans `.env`
3. Mettre à jour le Reply URL dans Azure AD (App registrations → Authentication)
4. Mettre à jour `TUNNEL_ID` dans `scripts/dev-tunnel.sh`
5. `php bin/console cache:clear` + relancer `symfony server:start`

**Piège vérifié** : après toute modification de `.env` touchant à Azure/aux hosts, vider le cache (`cache:clear`) **et** redémarrer le serveur Symfony (`symfony server:stop` puis `server:start`) — un simple `cache:clear` ne suffit pas toujours à faire relire la nouvelle valeur par un worker PHP-FPM déjà démarré.
