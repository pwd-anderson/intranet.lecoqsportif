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

Livraisons SS27 consolidées à l'article, importées chaque jour depuis un Excel SharePoint (service PURCHASING) et affichées dans la stat Achats **« SS27 Plan transport prévision de dates »** (menu Achats, deux lignes dans le sidebar, nom donné par l'utilisateur le 2026-10-09 ; **étape 2 à venir : remonter certaines infos dans le Backlog Client — ne pas la démarrer sans l'accord de l'utilisateur**).

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

**Onglet lu : « SS27 LIVRAISONS - ARTICLES »** (le 4ᵉ onglet, pas le 2ᵉ ; lu par son nom, à changer pour une autre saison avec `--sheet`). En-têtes ligne 3 (colonnes B à J contrôlées : une colonne renommée/déplacée fait échouer l'import et envoie un mail), données à partir de la ligne 4, ligne « TOTAL » et lignes vides ignorées. Colonnes : A Fournisseur, B N° PO, C Article (SKU `parent_variant`, ex. `2710685_40`), D Désignation, E Qté, F Moyen de transport (en-tête renommé « Transportation Mode » dans le fichier le 2026-10-13 : les deux libellés sont acceptés ; texte libre, **importé tel quel, non normalisé** : « Aérien », « Fast Boat Chine », « FAST BOAT (Chine) », « … — OP DIRECTE, PO ENTIER », etc.), G Départ usine (XF), H ETD, I ETA France (Logtex), J Statut départ (`PARTI`, `BOOKÉ — ON HOLD`, vide = à planifier). Les dates sont des nombres Excel convertis en `date`. `ARTICLE` (parent) et `VARIANT` sont déduits du SKU (coupure au premier `_`) pour la liaison avec le Backlog Client.

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

### Module OC (Offres Commerciales) — branche `oc`

**Module critique pour la société : toute évolution doit être documentée ici (requêtes, calculs, décisions).** Pour répondre à « quelle requête est lancée, quel est le calcul, où est stockée telle valeur ? », partir des tableaux ci-dessous.

**But.** Un commercial crée une OC (tarif) pour un client et un ou plusieurs articles ; une seule validation (valideur = `ROLE_ADMIN` / `ROLE_MANAGEMENT`, comme SOA) ; après validation, le module génère et envoie des flux XML à X3 : **Création** (aucun tarif spé existant pour même client + article + collection) ou **Modification** (un tarif spé existe déjà). V1 = uniquement « Tarif Spé » ; l'architecture doit permettre d'ajouter Référentiel / Devis / Remise sans refonte. L'ancien module (Screeneo, `/Users/ajacob/Sites/intranet.screeneo.local.v12/src/Controller/Sales/Oc`) n'est qu'une **référence métier**, pas à copier.

**État (2026-10-08).** Fait : saisie, **enregistrement en base** (brouillon, modification, soumission, annulation, nouvelle OC à partir d'une OC rejetée), historique, lectures X3, droits. **Prix envoyé à X3 calculé et enregistré** (voir plus bas). **Validation et rejet par la Direction faits (étape 1, 2026-10-09)** : page de détail = vue de validation, mail au head commercial à la soumission, mail au commercial au rejet. **PDF + mail au commercial à la validation faits (étape 2, 2026-10-09)** ; **flux XML de création `WSCRESPL` déposés dans `sales_web_service` à la validation (étape 3)**. **Prix par taille et import d'un fichier : faits le 2026-10-23 (voir « Prix par taille » et « Import d'un fichier de lignes »)**. **Pas encore fait** : format `7TSPEC` à confirmer avec X3 avant la mise en production, retour du résultat X3 par ligne d'OC, détection de l'existant (Création/Modification), blocage doublon, traitement du fichier d'import, multi-clients. Branche `oc` (rebasée sur `dev`), non mergée dans `dev`, jamais poussée.

#### Données (MySQL de l'application — migration `Version20261008154551`)

Le client et toutes les valeurs X3 sont **figés à l'enregistrement** (pour pouvoir expliquer plus tard pourquoi un tarif ou une remise a changé). Une OC n'est **jamais supprimée** (statut « Annulée »).

| Table / entité | Contenu |
|---|---|
| `oc_offer` — `App\Entity\Oc\Offer` | En-tête (une OC = un client) : `number` unique, `type` (`tarif_spe`), `status`, client **figé depuis X3** (`customer_code/name/currency`, `customer_tariff_code` = groupe tarifaire `TSCCOD_0`, `customer_discount` = remise REMCLI en % + `customer_discount_start/end`, `x3_read_at`), `start_date`, `end_date`, `comment`, `created_by` (+ `created_by_name` figé), `created_at`, `updated_at`, `submitted_at`, `decided_at`, `source_offer_id` (OC rejetée d'origine) |
| `oc_offer_line` — `OfferLine` | Une ligne par article parent. **Valeurs X3 à l'enregistrement** : `article_label`, `collection` (la plus récente), `model_code`, `x3_tariff`, `x3_msrp`, `x3_read_at`. **Décision du commercial** : `tariff` (tarif OC = prix **net** voulu), `msrp` (utilisé, modifiable), `vat_rate`, `margin` (recalculée côté serveur), `min_qty`, `max_qty`, `comment`. **Prix envoyé à X3** : `injected_price` (calculé, voir ci-dessous) et `injected_discount` (remise utilisée) |
| `oc_offer_history` — `OfferHistory` | Historique **en ajout seulement** : `action` (`OcAction`), `from_status`, `to_status`, `user` (+ `user_name` figé), `reason` (obligatoire pour un rejet à venir), `context` JSON (nb de lignes, date de lecture X3, OC d'origine, erreurs…), `created_at` |

⚠ **Migration : ne jamais utiliser `doctrine:migrations:diff` tel quel.** La base locale contient les tables des modules SOA / MDF / Price Protection (branches non fusionnées) : le diff généré contenait des `DROP TABLE` de ces tables et des colonnes de `sales_web_service`. La migration a été réduite à la création des trois tables OC ; `Version20261009090000` (ajout de `injected_price` / `injected_discount`) est écrite à la main. En préprod/prod : `php bin/console doctrine:migrations:migrate` (n'exécute que les migrations du module).

#### Statuts et règles

`OcStatus` : `draft` → `submitted` → `validated` → `sent_to_x3` ; `rejected` et `cancelled` sont **définitifs**. Transitions dans `OfferWorkflow` (aucune dépendance) : soumettre (brouillon), valider / rejeter (soumise), annuler (brouillon, soumise), envoyer à X3 (validée). **Rejet → nouvelle OC** (route `/oc/{id}/duplicate`, liée à l'OC d'origine via `source_offer_id`) : on ne ré-édite pas une OC rejetée.

Droits (`src/Security/OfferVoter.php`, testés) : un commercial (`ROLE_SALES`) ne voit et ne modifie **que ses OC** ; modification / soumission = propriétaire + brouillon ; annulation = propriétaire ou valideur, **brouillon ou soumise seulement (une OC validée ne peut plus être annulée, décision du 2026-10-12)** ; valider / rejeter = `ROLE_ADMIN` / `ROLE_MANAGEMENT` sur une OC soumise ; `ROLE_CONTROLLING` consulte seulement. Accès au module : SALES, MANAGEMENT, CONTROLLING, ADMIN (+ exclusion par utilisateur `app_oc_index`).

Numéro : `OC` + `AAAAMM` + code client + 8 chiffres aléatoires (`OfferNumberGenerator`, unicité vérifiée en base + contrainte UNIQUE ; ≤ 30 caractères pour un code client ≤ 14, limite du champ référence X3). **Le client d'une OC ne peut plus changer après création** (le numéro le contient). Une OC en brouillon est relue et ses valeurs X3 re-figées à chaque enregistrement.

#### Fichiers

| Rôle | Fichier |
|---|---|
| Contrôleur (pages + écritures JSON + API de lecture X3) | `src/Controller/Module/OcController.php` |
| Enregistrement / soumission / annulation | `src/Service/Oc/OfferService.php` |
| Validation des données reçues (typées, toutes les erreurs d'un coup) | `src/Service/Oc/Input/OfferInput.php`, `OfferLineInput.php`, `OfferValidationException.php` |
| Calcul de la marge (serveur) | `src/Service/Oc/OfferCalculator.php` |
| Transitions de statut | `src/Service/Oc/OfferWorkflow.php`, enums `OcStatus.php`, `OcAction.php` |
| Numérotation | `src/Service/Oc/OfferNumberGenerator.php` |
| Mails | `src/Service/Oc/OfferMailer.php` (voir « Mails ») |
| Droits | `src/Security/OfferVoter.php` |
| Lectures X3 (connexion UTF-8 paresseuse) | `src/Service/Oc/OcX3Lookup.php` |
| SQL X3 | `src/Infrastructure/Sql/Sei/oc_customer_search.sql`, `oc_customer_get.sql`, `oc_customer_discount.sql`, `oc_article_search.sql`, `oc_article_prices.sql` |
| Entités / dépôts | `src/Entity/Oc/{Offer,OfferLine,OfferHistory}.php`, `src/Repository/Oc/` |
| Pages | `templates/oc/index.html.twig` (liste AG Grid), `new.html.twig` (saisie, modification, nouvelle OC issue d'un rejet + tout le JS), `show.html.twig` (détail : valeurs X3 / décision, historique, actions), `_import_modal.html.twig` (modal d'import, non branché) |
| Menu / droits | `templates/partials/_sidebar.html.twig` : menu **repliable « Offres commerciales »** (Modules) à 3 entrées — **Nouvelle OC** (`app_oc_new`), **Liste OC** (`app_oc_index`), **Validation OC** (`app_oc_validation`, ADMIN / MANAGEMENT seulement : OC « soumises » en attente) ; clés d'exclusion `app_oc_new` / `app_oc_index` / `app_oc_validation` dans `StatRegistry` ; traductions `sidebar.stat.modules.oc*` |
| Tests | `tests/Service/Oc/*`, `tests/Security/OfferVoterTest.php`, `tests/Controller/Module/OcControllerTest.php` (voir ci-dessous) |

Les lectures X3 passent par la connexion `lcs_sei` (cube SEI) mais interrogent le schéma **`X3_LCS`**.

#### Routes (`/{_locale}/oc/...`)

Pages : `GET ''` liste (un commercial ne voit que ses OC), `GET /new`, `GET /{id}/edit` (brouillon, propriétaire), `GET /{id}/duplicate` (OC rejetée), `GET /{id}` détail. Écritures (JSON, **jeton CSRF `X-CSRF-Token`** obligatoire, sinon 419) : `POST /save` (crée ou met à jour un brouillon ; `id` pour une mise à jour, `sourceOfferId` pour une OC issue d'un rejet ; 422 + liste d'erreurs si invalide), `POST /{id}/submit`, `POST /{id}/cancel` (`reason` facultatif), **`POST /{id}/validate`** et **`POST /{id}/reject`** (`reason` obligatoire, ≥ 3 caractères) réservés à la Direction (`OfferVoter::DECIDE` : ADMIN / MANAGEMENT, OC « soumise » seulement ; le propriétaire ne valide pas sa propre OC). API de lecture X3 (pendant la saisie) : voir le tableau suivant.

#### Requêtes X3 (une par action)

| Quand | Route (GET) | Méthode (`OcX3Lookup`) | SQL | Renvoie |
|---|---|---|---|---|
| Frappe dans « Client » (≥ 2 car., 300 ms) | `/oc/api/customers?q=` | `searchCustomers` | `oc_customer_search.sql` (`:q_code`, `:q_name`) | 20 clients max (code, nom, devise, ville, pays), actifs (`BPCSTA_0 > 1`), **une adresse par client**. Mêmes critères que la recherche client du projet RMA |
| Choix du client | `/oc/api/customers/{code}/discount` | `getCustomerDiscount` | `oc_customer_discount.sql` | Remise REMCLI en % (`SPRICLIST`, `PLI_0='REMCLI'`, `DCGVAL_0`) + dates. Aucun filtre de date (toutes valables du 2027-01-01 au 2050-12-31) |
| Frappe dans « Articles » (≥ 2 car.) | `/oc/api/articles?q=` | `searchArticles` | `oc_article_search.sql` | 20 articles **parents** (sans `_variant` : `CHARINDEX('_', ITMREF_0) = 0`), code + désignation |
| Ajout d'un article ou d'un client | `/oc/api/prices?customer=&article=` | `getArticlePrices` | `oc_article_prices.sql` | Collection, code tarif, devise, tarif, MSRP (affichage pendant la saisie) |
| **Enregistrement (serveur)** | — | `getCustomer`, `getCustomerDiscount`, `getArticlesPrices` (UNE requête pour tous les articles) | `oc_customer_get.sql`, `oc_customer_discount.sql`, `oc_article_prices.sql` | Valeurs **figées** dans `oc_offer` / `oc_offer_line`. Le serveur ne fait jamais confiance aux valeurs X3 ni à la marge envoyées par le navigateur ; client ou article introuvable (ou X3 indisponible) → **rien n'est enregistré** |

**`oc_article_prices.sql`** : collection = la plus récente de l'article (CTE `CollectionRecente`, règle de tout l'intranet) ; **Code tarif** = groupe tarifaire du client (`BPCUSTOMER.TSCCOD_0`) ; **Tarif** = `SPRICLIST.PRI_0` pour (`PLICRI1_0` = code tarif du client, `PLICRI2_0` = collection, `PLICRI3_0` = `ITMMASTER.ZMODELCOD_0`) ; **MSRP** = même lecture avec `PLICRI1_0 = 'CSEU'` ; aucun filtre sur `PLI_0` (T10 et T11 n'ont aucune clé commune) ; `TOP 1` rendu déterministe par `ORDER BY PLIENDDAT_0 DESC, PLISTRDAT_0 DESC, PLILIN_0 DESC` (0,6 % des clés ont plusieurs prix à dates identiques). Rejouable dans SSMS en remplaçant les paramètres par des `DECLARE`.

**Données LCS (vérifiées en direct).** `ITMMASTER` : 131 090 lignes dont 10 796 parents (sans `_`) ; `ITMSTA_0 = 1` partout. `SPRICLIST` : types `T10`, `T11` (contient les `CSEU`), `R11`/`R12`, `REMCLI` (520 lignes, une par client). Pas de type `7TSPEC` chez LCS.

#### Validation, rejet et mails (étape 1)

- **Soumission** (`OfferService::submit`) : l'OC passe « Soumise », puis **mail au head commercial** (variable d'env `MAIL_HEAD_SALE`, une ou plusieurs adresses séparées par `,` ou `;`) avec un lien vers l'OC.
- **Validation** (`OfferService::validate`, bouton « Valider » de la page de détail) : **refusée (422) si la remise client REMCLI a changé dans X3 depuis l'enregistrement** (`discountDrift()` relit X3 : le prix envoyé a été calculé avec l'ancienne remise ; le message précise « ou X3 ne répond pas » car une erreur X3 est indiscernable d'une remise supprimée). Sinon l'OC passe « Validée », `decided_at` et l'historique sont renseignés. **Aucun mail ni PDF ni flux X3 à ce stade** (étapes 2 et 3).
- **Rejet** (`OfferService::reject`, fenêtre de saisie du motif) : **motif obligatoire** (≥ 3 caractères, 1 000 max), rejet **définitif** ; le commercial reçoit le motif par mail et peut créer une nouvelle OC à partir de celle-ci.
- **Vue de validation = page de détail** (`show.html.twig`) : bandeau « Cette OC attend votre décision » pour la Direction, photo, article, désignation, collection, valeurs X3 (tarif, MSRP), tarif OC (net), **prix envoyé à X3**, MSRP utilisé, TVA, marge client, quantités, commentaire, remise client et code tarif en en-tête, avertissements, historique. Retiré par rapport à Screeneo (non pertinent LCS) : prix d'achat, remise marque, markup, TAR, triple net, marges Powerdata/SCR, stock.
- **Mails** (`OfferMailer`, via `GraphMailer`) : **prod** = vrais destinataires ; **dev = TOUS les mails redirigés vers `ADMIN_EMAIL`** (même règle que SOA), le destinataire prévu est rappelé dans l'objet (`[DEV → prévu : …]`) ; **test = aucun envoi**, jamais (un test ne doit pas pouvoir écrire à une vraie personne). Un échec d'envoi ne défait jamais l'action métier : journalisé + `GraphMailer::notifyError`. Pour vérifier réellement l'envoi Graph depuis le poste de dev, il faut le déclencher à la main (jamais depuis un test).
- Prérequis de déploiement : la variable `MAIL_HEAD_SALE` doit exister dans chaque environnement.

#### PDF de l'offre et mail au créateur (étape 2, 2026-10-09)

À la **validation**, l'OC génère un PDF (comme Screeneo) et l'envoie **au commercial créateur uniquement** (pièce jointe). La validation est enregistrée AVANT : un échec du PDF ou du mail ne l'annule jamais, il est tracé dans l'historique (`OcAction::PDF_FAILED` + motif, et `GraphMailer::notifyError`) ; un succès ajoute `PDF_SENT`. Statut inchangé dans les deux cas.

| Rôle | Fichier |
|---|---|
| Génération (Dompdf, A4 portrait, FR) | `src/Service/Oc/OfferPdfGenerator.php` (`renderHtml`, `generate`, `filename` = `OC_<numéro>.pdf`) |
| Gabarit | `templates/oc/pdf/offer.html.twig` (tableaux HTML/CSS compatibles Dompdf) |
| Adresse du client | `OcX3Lookup::getCustomerAddress` + `oc_customer_address.sql` (adresse par défaut `BPAADDFLG_0`) |
| Mail avec PDF | `OfferMailer::notifyValidated` ; `isEnabled()` faux en `test` |
| Envoi + historique + renvoi | `OfferService::validate()` puis `sendValidationPdf()` |
| Routes | `GET /oc/{id}/pdf` (`app_oc_pdf`, voter `OC_PDF` : OC validée/envoyée, qui peut voir l'OC) ; `POST /oc/{id}/resend-pdf` (`app_oc_resend_pdf`, voter `OC_RESEND_PDF` : Direction, CSRF) |

- **Contenu** : n° et dates de validité, représentant = créateur, client + adresse, conditions générales (« Tous les prix sont indiqués en EUR dans la limite des stocks disponibles et sous réserve de modifications sans préavis »), tableau PHOTO / INFORMATIONS PRODUIT / QTÉ MIN / QTÉ MAX / PX PUB. INDICATIF (MSRP) / PA HT FACTURÉ (tarif OC net) / MARGE. **Ne montre jamais** le prix injecté dans X3, le tarif X3 interne, le stock (testé).
- **Photos** : même source que les stats (`LecoqsportifImageFetcher`), réduites à 180 px, **cache** 7 jours (trouvée) / 1 h (absente), **budget de 25 s** par PDF (au-delà, pas de nouvelle recherche : cellule vide). Dompdf : `isRemoteEnabled=false`, `isPhpEnabled=false`, cache dans `var/cache/dompdf`.
- **Dev** : le mail part à `ADMIN_EMAIL` (objet `[DEV → prévu : …]`) comme tous les mails OC. **Test** : aucun envoi.
- **Déploiement** : `dompdf/dompdf` est désormais dans `composer.json` → `composer install` dans chaque environnement.
- Mesure (dev, 3 articles) : 5,4 s sans photo en cache, 0,2 s ensuite.

#### Flux XML vers X3 (étape 3, 2026-10-12) — déposés dans `sales_web_service` à la validation (2026-10-15)

À la validation, `OfferService::validate()` appelle `prepareX3Flow()` : un flux **WSCRESPL** (création d'une ligne de tarif, type `7TSPEC`) **par article ENFANT (variant) de chaque ligne d'OC** (décision du 2026-10-15 : l'OC est saisie par article parent, X3 reçoit un tarif par variant ; **le parent n'est jamais envoyé**), généré par `OfferXmlBuilder::creation($offer, $line, $variantCode)` (même structure que l'ancien `WebservicesToX3::getSplXml` : groupe `INH` de champs `FLD NAME=…` : `WPLI`=7TSPEC, `WPLISTRDAT`/`WPLIENDDAT` (Ymd), `WPLICRDIDX`=n° d'OC tronqué à 30, `WITMREF`=**variant** (code `PARENT_VARIANT`), `WBPCNUM`=client, `WMINQTY`, `WMAXQTY`, `WCUR`=devise du client, `WDISPRI`=**prix envoyé** `injected_price`, jamais le tarif net). Création seule : pas de `WSUPD7TSPE` (modification), pas de détection de l'existant.

| Rôle | Fichier |
|---|---|
| Construction du XML | `src/Service/Oc/OfferXmlBuilder.php` |
| Génération / mise en file | `src/Service/Oc/OfferFlowService.php` (`send`) |
| Stockage des flux | table `oc_offer_flow` — `App\Entity\Oc\OfferFlow` (statut `queued` ; `prepared` n'est plus utilisé), migration `Version20261012100000` |
| Affichage | page de détail, bloc « Flux X3 » réservé à la Direction (`OfferVoter::X3_FLOW`) : XML de chaque ligne + statut |
| Historique | `OcAction::X3_FLOW_PREPARED` (« Flux X3 préparé »), `X3_ERROR` en cas d'échec |

**Variants d'un parent (2026-10-15).** `OcX3Lookup::getVariants()` + `src/Infrastructure/Sql/Sei/oc_article_variants.sql` : tous les articles `ITMMASTER` dont le code commence par `PARENT_` (`LIKE 'PARENT[_]%'` + contrôle `LEFT(code, CHARINDEX('_', code) - 1) = PARENT`), **sans tenir compte de la collection ni du statut**, chacun une seule fois, y compris `_SPL` et `_SMS*` (décision de l'utilisateur) ; même prix, mêmes quantités et mêmes dates pour tous les variants d'un parent. Vérifié en direct : 2210037 → 6, 1511960 → 10, 2121416 → 10. Placeholder `{{PARENTS}}` remplacé côté PHP (apostrophes doublées, `executeQuery` sans paramètre). **Contrairement aux autres lectures X3, une erreur remonte** (`iterateQuery`) : `MssqlManager::executeQuery` renverrait `[]` si X3 ne répond pas, ce qui passerait pour « aucun variant » et enverrait un flux incomplet. Un parent **sans variant** fait échouer toute la génération (rien d'enregistré, `X3_ERROR` tracé, validation conservée). Un même variant n'apparaît jamais deux fois dans les flux d'une OC.

- **À la validation** : les XML sont déposés dans `sales_web_service` (via `WebServiceDispatcher`, dans la même transaction que la création de `oc_offer_flow`), flux « en file », et l'OC passe « Envoyée à X3 » ; l'envoi effectif et la lecture du résultat sont ceux de la commande d'exécution existante (le suivi du résultat par ligne d'OC n'est pas encore relié à `oc_offer_flow`). ⚠ Cette commande exécute TOUTES les lignes non exécutées de `sales_web_service` (tous modules).
- **Lien vers l'OC** : `sales_web_service.oc_offer_id` (colonne facultative, nulle pour les autres modules ; migration `Version20261012110000`) est renseignée à chaque dépôt ; `oc_offer_flow.sales_web_service_id` donne le lien inverse.
- **Ne défait jamais la validation** : un échec de génération est tracé (« Erreur d'envoi à X3 », motif) et notifié à l'admin (`OfferMailer::reportError`, qui sert aussi à l'échec du PDF). Les flux ne sont générés qu'une fois par OC.
- **Format à confirmer avec l'équipe X3 avant la mise en production** : fiche tarif `7TSPEC` (inexistante chez LCS, en cours de création), article parent ou variant, critère collection, devise, précision du prix (`WDISPRI` à 2 décimales actuellement, `PRI_0` accepte 5), `WMAXQTY` = 999999 si non renseigné.
- Vérifié avec les vraies données X3 (client 20548) : XML conformes, `WDISPRI` = 21,26 / 45,98.
- Déploiement : migrations `Version20261012100000` et `Version20261012110000`. **Plus d'interrupteur** : dès qu'une OC est validée, ses flux sont dans `sales_web_service` et partent avec `app:sales:webservices:execute` (cette commande n'est **jamais lancée en local**, uniquement sur les serveurs).

#### Prix par taille (2026-10-23, demandé après la présentation) — saisie, flux X3, validation, PDF et e-mails faits

**Règle.** Une ligne d'OC est un article **parent** (`2210037`) ou un **variant** (`2210037_T9`, le code contient `_`). Le prix du parent vaut pour toutes ses tailles ; **une ligne de variant l'emporte sur celle du parent pour cette taille** ; un variant seul (sans ligne parent) ne tarife **que cette taille** (les autres ne partent pas à X3). Chaque ligne a ses propres prix, marge et quantités (une ligne de taille démarre avec ceux du parent, modifiables).

| Parent seul | Parent 35 € + `_L` 30 € + `_S` 43 € | `_L` 30 € seul |
|---|---|---|
| toutes les tailles à 35 € | L 30 €, S 43 €, les autres tailles 35 € | uniquement L à 30 € |

- **Aucune migration** : `oc_offer_line.article_code` (50 car.) porte déjà le code complet ; `OfferLine::isVariant()` / `getParentCode()` / `parentOf()` le déduisent du `_`. Les OC existantes ne changent pas.
- **Flux X3** (`OfferFlowService::send`) : lignes variant → un flux pour cette taille avec **le prix de la ligne** ; lignes parent → un flux par variant du parent **sauf** ceux qui ont leur propre ligne (priorité indépendante de l'ordre des lignes) ; jamais deux flux pour un même variant ; une taille introuvable chez son parent fait échouer toute la génération (rien d'enregistré). Prix envoyé = `tarif de la ligne / (1 − remise client)`, arrondi par `OfferCalculator::injectedPrice()` pour **chaque** ligne.
- **Enregistrement** (`OfferService::assertVariantsExist`) : une taille n'est acceptée que si elle existe chez son parent dans X3 (`getVariants`) ; X3 injoignable → rien n'est enregistré.
- **Prix X3 d'une taille** : tous les variants d'un parent ont le même `ZMODELCOD_0` (vérifié sur 2210037 : 6 variants, modèle `221H028`), donc **mêmes tarif X3 et MSRP** ; `oc_article_prices.sql` fonctionne tel quel avec un code de variant (collection lue par variant : `YITMCOLLECT` a des lignes par variant). La marge de chaque ligne se calcule avec le MSRP du modèle et le prix de la ligne.
- **Saisie** (`templates/oc/new.html.twig`) : la recherche d'article renvoie les parents ; **une saisie contenant `_` cherche un variant par son code** (`oc_article_search_variants.sql`, `OcX3Lookup::searchArticles`) ; le bouton **« Prix par taille »** sous la ligne d'un parent ouvre la liste des tailles lue dans X3 (`GET /oc/api/articles/{parent}/variants`, 503 si X3 ne répond pas, jamais une liste vide trompeuse), à cocher puis « Ajouter les tailles cochées ». Les lignes d'un même article sont regroupées (parent puis tailles), avec la mention « Toutes les tailles à ce prix, sauf : … » ou « Cette taille uniquement ».
- **Photos** : la page de détail et le PDF utilisent le code du **parent** (une taille n'a pas de photo propre).
- **Règle unique `OfferSizing`** (`src/Service/Oc/OfferSizing.php`, sans dépendance) : `plan()` / `assign()` (ce qui part à X3 : un flux par variant avec la ligne dont il prend le prix, `own` = la taille a sa propre ligne, `problems` = lignes qui ne peuvent pas partir), `groups()` (lignes regroupées par article : le parent puis ses tailles triées, ordre de première saisie ; avec les tailles lues dans X3, chaque groupe porte `sent` = ce qui part). **Flux X3, vue de validation, PDF et e-mails l'utilisent tous** : ne jamais recoder la règle ailleurs.
- **Étape 3 faite (2026-10-23)** :
  - **Page de détail** (commercial et consultation) : lignes groupées, taille en pastille, note « Toutes les tailles à ce prix, sauf : … » / « Remplace le prix de l'article pour cette taille » / « Cette taille uniquement » ; titre « Articles (N) · M lignes dont K de taille » ; photo une seule fois par article (celle du parent).
  - **Vue de validation** (Direction, `?from=validation`) : mêmes regroupements + sous chaque article une ligne **« Envoyé à X3 pour 2210037 : 6 tailles · T10 à 43,50 EUR (ligne de taille) · T9 à 30,00 EUR (ligne de taille) · 4 autres tailles à 35,00 EUR (prix de l'article) »** (tailles lues dans X3 ; en cas d'erreur X3, avertissement et page affichée sans ce détail) ; un problème (taille introuvable, parent sans variant) est signalé en rouge « la validation sera refusée ». Prix d'achat et marge LCS par ligne de taille (même prix d'achat : même modèle).
  - **PDF** (`OfferPdfGenerator::renderHtml`) : photo cherchée **une fois par article** (économise le budget de 25 s), tailles à la suite avec la pastille « TAILLE T9 » et leur propre prix ; « Cette taille uniquement » pour une taille seule ; jamais le prix envoyé à X3.
  - **E-mails** : « Articles : 2 (dont 3 lignes de taille) » (compte les articles, pas les lignes).
  - `OfferLine::getSizeLabel()`, `articleCode` initialisé à `''`.
- **À ne pas oublier plus tard** : la détection « tarif spé déjà existant » (Création / Modification) devra se faire **par variant**, pas par parent (une OC peut mêler des tailles à créer et des tailles à modifier).
- **Tests** : `OfferFlowServiceTest` (priorité, ordre des lignes, variant seul, parent entièrement couvert, taille inconnue, deux parents), `OfferServiceTest` (taille acceptée / refusée / X3 injoignable, parent seul sans appel inutile), `OcControllerTest` (route des tailles, page de saisie, bout en bout jusqu'aux flux : taille 27,78 et autres tailles 33,33 avec une remise de 10 %). Vérifié aussi à l'écran sur de vraies données (client 20548, article 2210037 : tailles `SMST10, SMST9, SPL, T10, T8, T9`).

#### Import d'un fichier de lignes (2026-10-23)

Bouton « Importer un fichier (Excel/CSV) » de la page de saisie (actif une fois le client choisi). **Un fichier = un client = une OC** : le client et les dates sont ceux de l'écran, le fichier ne porte que les lignes. **L'import ne crée rien en base** : il analyse le fichier, affiche chaque ligne avec son contrôle (✓ valide / motifs d'erreur / « déjà dans l'OC »), puis « Ajouter les lignes valides » les place dans l'OC en cours de saisie ; le commercial les relit et enregistre le brouillon comme d'habitude. Les lignes en erreur ne sont jamais ajoutées (corriger le fichier et réimporter).

| Rôle | Fichier |
|---|---|
| Route d'analyse (POST multipart `file` + `customerCode`, jeton CSRF `X-CSRF-Token`) | `OcController::import` (`app_oc_import`) → `{ok, rows[], summary{total, valid, errors}}` |
| Fichier modèle téléchargeable | `OcController::importTemplate` (`app_oc_import_template`), `Import/OfferImportTemplate.php` (feuille « Import OC » + feuille « Aide ») |
| Lecture (xlsx, xls, csv) | `src/Service/Oc/Import/OfferImportReader.php` |
| Contrôles contre X3 | `src/Service/Oc/Import/OfferImportChecker.php` |
| Fenêtre + aperçu | `templates/oc/_import_modal.html.twig`, JS dans `new.html.twig` (`importRender`, `ocImportAdd`) |
| Tests | `tests/Service/Oc/Import/*`, `OcControllerTest` (import, CSRF, formats, modèle, droits) |

- **Colonnes** (en-têtes insensibles à la casse, aux accents et à la ponctuation, ordre libre, synonymes reconnus : « Référence », « Prix net »…) : **Article** et **Tarif** obligatoires ; **Qté min** (0 par défaut), **Qté max** (999 999), **Commentaire** facultatives. L'en-tête peut suivre jusqu'à 5 lignes de titre. Lignes vides ignorées. Première feuille seulement, valeurs lues sans calcul de formule.
- **Article** : code parent (`2210037`) ou taille (`2210037_T9`), mêmes règles que la saisie (une taille remplace le prix du parent ; une taille seule ne tarife que cette taille). Un code saisi comme nombre dans Excel redevient un texte sans « .0 ». **Tarif** : virgule ou point décimal, « € » toléré, `1.234,56` compris ; positif obligatoire.
- **Contrôles** (`OfferImportChecker`, mêmes règles que `OfferService::save`, pour qu'une ligne annoncée valide ne soit pas refusée ensuite) : client existant dans X3 ; article existant (une lecture pour tous) ; taille existante chez son parent (X3 injoignable → « Taille non contrôlable : X3 indisponible », jamais « inconnue ») ; doublon dans le fichier ; tarif, quantités (entiers, min ≤ max) et commentaire (500 car.). Limites : **200 lignes, 2 Mo**, `.xlsx` / `.xls` / `.csv` (encodage CSV détecté : UTF-8 ou ISO-8859-1 ; séparateur détecté).
- **Pièges rencontrés** : (1) PHP range un envoi HTTP sous `/tmp/phpXXXX` **sans extension** et PhpSpreadsheet choisit son lecteur d'après l'extension : `OfferImportReader` lit donc une **copie portant l'extension d'origine** (supprimée ensuite) ; (2) fermer le modal avec `bootstrap.Modal.getInstance(...).hide()` laissait un **fond gris bloquant** (le thème charge Bootstrap deux fois, deux fonds apparaissent à l'ouverture) : on clique sur le bouton « Fermer » du modal ; (3) la marge d'une ligne importée se calcule dès que le MSRP X3 de la ligne est connu (`loadPrices`).
- Vérifié à l'écran avec un fichier mêlant lignes valides et erreurs (article inconnu, taille inexistante, tarif illisible) sur de vraies données X3 (client 20548, article 2210037).

#### Environnement X3 choisi (PROD / TEST / DEV) — 2026-10-15

**Pourquoi.** L'équipe X3 teste dans le dossier TEST : articles et prix y sont enregistrés. Un sélecteur dans l'en-tête (à côté de la langue) choisit **le schéma lu ET le dossier où les flux sont envoyés** ; PROD par défaut.

- **Visible uniquement en `APP_ENV=dev`** (`X3EnvironmentProvider::isSwitchable()`). Partout ailleurs (préprod, prod, test, CLI) l'environnement est **toujours PROD**, même si une valeur traîne en session ; la route `POST /x3-environment` (`X3EnvironmentController`, CSRF) répond 404 hors dev. Pastille rouge « X3 TEST / X3 DEV » dans l'en-tête dès qu'on n'est pas sur PROD.
- **Lectures** : les 8 requêtes `oc_*.sql` utilisent `{{X3_SCHEMA}}.` (au lieu de `X3_LCS.`), remplacé par `OcX3Lookup::sql()` d'après `X3Environment::schema()` (`X3_LCS`, `X3_LCS_TEST`, `X3_LCS_DEV`, liste blanche). Les autres modules de l'intranet restent sur `X3_LCS`.
- **Écriture** : l'environnement est figé dans `oc_offer.x3_env` à chaque enregistrement et recopié dans `sales_web_service.x3_env` à chaque flux déposé (migration `Version20261015100000`, défaut `PROD` pour tout l'existant et les autres modules). `ExecuteSalesWebservicesCommand` envoie chaque ligne avec `SageX3Client::run($nom, $xml, $x3Env)` : PROD = `SAGE_X3_POOL_ALIAS`, TEST = `SAGE_X3_POOL_ALIAS_TEST`, DEV = `SAGE_X3_POOL_ALIAS_DEV`. **Un environnement sans pool configuré est refusé (erreur sur la ligne), jamais de repli sur PROD.**
- **Garde-fou** : `OfferService::validate()` refuse de valider une OC enregistrée dans un autre environnement que l'actuel (ses prix viennent d'un autre dossier).
- À renseigner dans le `.env` de l'environnement qui exécute la commande : les alias de pool TEST / DEV (vides par défaut).
- Vérifié en direct : variants de 2210037 = 6 en PROD, 11 en TEST, 6 en DEV (les schémas ont bien des données différentes). Tests : `X3EnvironmentProviderTest`, `SageX3ClientTest`, garde de `OfferServiceTest`.

#### Prix envoyé à X3 (WDISPRI) — remise client appliquée à toutes les lignes

**Règle métier (décidée le 2026-10-09, modifiable) :** la remise client REMCLI s'applique dans X3 à **toutes** les lignes du client. Le commercial saisit donc le prix **net** voulu (« Tarif OC ») et le serveur calcule le prix « précompensé » à envoyer : **`prix envoyé = tarif OC / (1 − remise client / 100)`**. X3 réapplique la remise, le client paie le tarif OC. Cette règle vit à **un seul endroit** : `OfferCalculator::injectedPrice()` (pour la changer, ne toucher que là). Même principe que l'ancien module (`SaleOffer::updateProductsTarifSpe`, `OcController::updateProducts` : `net / ((1 + markup/100) × (1 − remise marque/100))`, remise marque et markup propres à Screeneo) ; chez LCS il n'y a plus de markup. Le **Tarif Spé est un prix** (pas un pourcentage de remise).

- **Arrondi** : 2 décimales (comme l'ancien module ; `PRI_0` est en `numeric(19,5)`, 4-5 décimales seraient possibles, à valider avec X3). Parmi les valeurs voisines (±2 centimes) on retient celle dont le net recalculé par X3 (`prix envoyé × (1 − remise)`, arrondi au centime) est **exactement** le prix saisi ; l'ancien module arrondissait sans contrôle (écarts d'un centime possibles). Test exhaustif : 10 remises × 810 prix, 0 écart ; JS (affichage en direct) et PHP identiques sur 9 048 cas.
- **Garde-fous** : remise < 0 ou ≥ 100 % ou prix ≤ 0 → l'enregistrement est refusé (l'ancien module divisait par zéro à 100 %). Sans remise REMCLI, le prix envoyé = le tarif OC.
- **Traçabilité** : `oc_offer_line.injected_price` + `injected_discount` (la remise utilisée) ; l'historique garde `customerDiscount` et les éventuels `injectionGaps` dans son contexte ; la page de détail affiche « Tarif OC (net) » et « Prix envoyé à X3 ».
- **Remise changée entre la création et l'envoi** (défaut de l'ancien module : remise figée, X3 appliquerait la nouvelle) : `OfferService::discountDrift()` relit REMCLI dans X3 et compare à la valeur figée. **À appeler juste avant la validation et l'envoi à X3 (étape à construire)** : en cas d'écart (ou X3 injoignable), on n'envoie pas, l'OC doit être refaite.
- **Avertissement de date** (`OfferService::warnings()`, affiché à l'enregistrement et au détail) : toutes les REMCLI actuelles débutent le **2027-01-01** ; pour une OC qui débute avant, le client paierait le prix envoyé **sans remise** (donc plus cher que le tarif OC) jusqu'à cette date. Règle à trancher avec le métier (non bloquant pour l'instant).

**Colonnes `DCGVAL_n` de `SPRICLIST` : leur sens dépend du type de tarif.** Screeneo (code de l'ancien module, `Divers.php`) : `CTREM` / `BTREMM` → `DCGVAL_1` = remise marque (client / regroupement) ; `MUETA` / `MUMM` → `DCGVAL_0` = **markup** ; `DTARCH` → `DCGVAL_2` = **TAR**. LCS : `REMCLI` → `DCGVAL_0` = remise client en % (valeurs observées 12,5 / 13 / 15, `DCGVAL_1..3` = 0). Ne jamais présumer du sens d'une colonne `DCGVAL` sans connaître le type de tarif.

#### Prix d'achat et marge LCS (vue de validation seulement, 2026-10-13)

Dans la vue de validation (page de détail ouverte depuis **Validation OC**, `?from=validation`, Direction) le tableau des articles est réduit aux colonnes utiles à la décision : Photo, Produit, **Prix d'achat**, MSRP, Tarif OC (net), Marge souhaitée (%), **Marge LCS (%)**, **Marge LCS (valeur)**, Qté min / max, Commentaire (retirés : devise, code tarif, tarif X3, remise client — déjà dans l'en-tête de l'OC). Les autres vues (détail depuis la liste, commercial) ne montrent **jamais** le prix d'achat ni la marge LCS, pas plus que le PDF et les mails (testé).

- **Prix d'achat** = `PPL.PRI_0 AS PRIX_ACHAT` / `PPL.CUR_0` de la stat « Base Articles/Tarifs » : `PPRICLIST` + `PPRICFICH`, type `1PFCOL`, `PLICRI2_0` = modèle de l'article, `PLICRI1_0` = collection (la plus récente de l'article, comme le reste de l'OC). SQL : `src/Infrastructure/Sql/Sei/oc_article_purchase_prices.sql` (requête **séparée** de `oc_article_prices.sql` : si elle échoue, les prix de vente restent disponibles et le prix d'achat est simplement absent) ; plusieurs lignes pour un même couple (observé, à prix identique) → la plus récente (`PLIENDDAT_0`, `PLISTRDAT_0`, `PLILIN_0`). Appelée par `OcX3Lookup::getArticlesPrices()` (`withPurchasePrices`). Devises observées : EUR et USD.
- **Figé à l'enregistrement** : `oc_offer_line.purchase_price` + `purchase_currency` (migration `Version20261013110000`), comme les autres valeurs X3. Un article sans prix d'achat (ex. collection ancienne) affiche « — » et pas de marge.
- **Marge LCS** (`OfferCalculator::lcsMargin`, reprise de l'ancienne « marge Powerdata / SCR ») : coût = prix d'achat converti dans la devise de l'OC (prix ÷ cours de sa devise × cours de la devise de l'OC ; cours = `Divers::getExchangeRatesValues()`, unités pour 1 EUR, EUR = 1) ; valeur = tarif OC − coût ; **marge % = valeur / tarif OC × 100** (−100 % si le tarif est nul). Calculée à l'affichage (cours du jour), pas stockée. ⚠ La stat Base Articles/Tarifs convertit l'USD avec un taux fixe de 1,15 (`Achat::USD_TO_EUR_RATE`) alors que l'OC utilise le cours X3 du jour (comme l'ancien module et le Backlog Client) : les deux prix d'achat en EUR peuvent différer légèrement.
- Vérifié sur les vraies données X3 : article 2210037 (modèle 221H028, 2026-01-SS) = 4,84 USD (identique à la stat) ; 1511960 (collection 2015-02-FW) sans prix d'achat.

#### Calculs

- **« Marge souhaitée » = marge du CLIENT sur le prix public, pas la marge de LCS** (comme l'ancien écran « marge net facturé souhaitée ») : elle ne dépend ni du prix d'achat ni de la remise (le tarif OC est déjà net). Constat sur les données LCS (2026-10-09) : pour **93 % des articles (6 478 couples collection/modèle sur ~6 900), MSRP (`CSEU`) = exactement 2 × tarif de gros (`WSPEUR`)**, le reste 2,2 à 2,6. Au tarif standard la marge vaut donc **40 %** (MSRP TTC, TVA 20 %, **confirmé**). Une marge calculée sur le coût d'achat de LCS (cf. « marge Powerdata » de l'ancien module) demanderait de trouver le prix d'achat dans X3 (non fait).
- **Marge**, **recalculée côté serveur** (`OfferCalculator`) et affichée en JS pendant la saisie (reprise de l'ancien `calcul()`, sans markup ni triple net) : `PV net HT = MSRP × 100 / (100 + TVA)` puis `marge % = (PV net HT − tarif OC) / PV net HT × 100`. Bidirectionnelle à l'écran. Vérifié sur l'ancien écran (PVC 99,99, TVA 20 %, prix 59,25 → 28,89 %). **MSRP = prix public TTC (confirmé par l'utilisateur le 2026-10-09 ; dans l'ancien module « PVC / MSRP » = `XPVTTC_0` de la ligne 1TSTD, `Divers.php:2150`, `nouvelle_offre_commerciale.html.twig:613`).** ⚠ La **TVA reste un placeholder à 20 %** (l'ancien module la lisait dans `XTVA_0`, colonne propre à Screeneo) : la marge n'est exacte que pour un client dont le MSRP est à 20 % de TVA. Source / règle de la TVA par client (pays) à définir.
- La **remise client** n'entre pas dans la marge (le tarif OC est le prix net) ; elle sert à calculer le **prix envoyé à X3** (voir plus haut).
- Dates par défaut : début = aujourd'hui, fin = +1 an. Photo : même règle que les stats (`excess_for_sales`, cascade `_2.webp`…, puis `lecoqsportif_image_lookup`).
- Un client par OC (sélection multi-clients et raccourci « appliquer à tous » retirés à la demande de l'utilisateur, il y reviendra) ; pas de TAR, markup, triple net, ni mail/PDF client.

#### Tests

`bin/phpunit` (151 tests dont 9 pour la stat Contacts Clients) : validation des données, calcul de la marge et du prix envoyé à X3, transitions, droits (matrice complète), `OfferService` (X3 et base simulés : valeurs figées, historique, rien d'enregistré si X3 ne répond pas, brouillon seul modifiable, remise appliquée…), et `OcControllerTest` (flux complet sur de vrais contrôleurs et une base MySQL locale `intranet_lcs_test` : CSRF, droits, validation, soumission, annulation, nouvelle OC issue d'un rejet). **Créer la base de test une fois** : `mysql -uroot -e "CREATE DATABASE intranet_lcs_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"` puis `APP_ENV=test php bin/console doctrine:schema:create --env=test` (et `doctrine:schema:update --force --env=test` après une nouvelle colonne).

**Les tests fonctionnels ne touchent PAS à X3** (corrigé le 2026-10-09) : `OcX3Lookup` y est simulé avec des valeurs volontairement différentes des vraies (« CLIENT SIMULE », remise 10 %, tarif 11,1…) pour qu'un accès au vrai X3 fasse échouer le test. Deux règles : `$client->disableReboot()` dans `setUp()` (sinon `KernelBrowser` redémarre l'application entre deux requêtes, le service simulé est perdu et les requêtes suivantes interrogent le vrai X3 — c'était le cas des anciens tests OC, qui passaient parce que valeurs réelles et simulées coïncidaient) et le service à simuler déclaré `public: true` dans `config/services_test.yaml`. Toute évolution de lecture X3 reste à vérifier aussi avec les vraies données (un défaut d'encodage n'est visible qu'avec elles).

#### Pièges déjà rencontrés

- **Encodage X3 (corrigé).** Le pilote `dblib` travaille en ISO-8859-1 par défaut : une désignation avec « ° » (ex. « REOUVERTURE Tee SS N°1 ») arrivait en octet invalide et MySQL refusait l'enregistrement (erreur 1366) ; tout caractère hors Latin-1 devenait « ? ». `OcX3Lookup` ouvre donc sa connexion avec `;charset=UTF-8`, **à la première requête seulement** (ouvrir une connexion coûte jusqu'à ~15 s : une connexion ouverte dès le constructeur ralentirait toutes les pages OC). Ce défaut n'apparaissait pas avec X3 simulé.
- `doctrine.yaml` a `controller_resolver: auto_mapping: false` : les actions doivent déclarer `#[MapEntity] Offer $offer`.
- Le champ de recherche (datalist) déclenche un nouvel événement `input` quand on choisit une valeur : ne jamais relancer de recherche sur un libellé « code - nom » exact (sinon la liste mémorisée est écrasée et « Ajouter » échoue).
- Les anciens codes clients Screeneo (ex. `0033911801`) n'existent pas chez LCS ; clients de test utiles : `21451` (DECATHLON / MP, `WSPEUR`, pas de REMCLI), `10012` (REMCLI 13 %), `20548` (T2 SPORT SAS, `WSPEUR`, REMCLI 13 %).
- `MssqlManager::executeQueryWithParams` avale les erreurs SQL (renvoie `[]`) : en cas de résultat vide inattendu, rejouer la requête en PDO direct. Une requête exploratoire lourde peut couper la connexion du cube (« Unexpected EOF »).

#### À faire / questions métier ouvertes

- **Étapes 2 et 3 faites** (voir leurs sections) : reste à confirmer le format avec l'équipe X3 avant la mise en production.
- **Type de tarif « Tarif Spé » chez LCS** (aucun `7TSPEC`) → bloque la détection de l'existant (Création/Modification), le blocage doublon (OC soumises **et** validées non envoyées, deux messages distincts) et les flux `WSCRESPL` / `WSUPD7TSPE` (à relier à `sales_web_service`, envoi par commande à tentatives bornées + `notifyError`).
- Source de la TVA (par pays du client ?) — le MSRP est TTC (confirmé). Marge souhaitée = marge du client sur le MSRP (comme l'ancien module : confirmé en lecture du code, à valider avec le métier). Comportement avant le 2027-01-01 (début des REMCLI) pour une OC qui démarre plus tôt. Précision du prix envoyé (2 ou 4-5 décimales) à valider avec X3.
- Import Excel/CSV (traitement du fichier), stock affiché, multi-clients.

### Backlog Clients — lignes d'articles droppés en rouge clair (2026-10-14, affichage seulement)

Sur la page Backlog Clients (v3), **toute la ligne passe en rouge clair** quand la colonne `DROPPE` vaut OUI (`rowClassRules` + CSS `.ligne-droppee` dans `backlog_client_v3.html.twig`). **Purement graphique, aucune logique de stat modifiée** (requête, service, contrôleur inchangés). Une colonne « ALERTE » (texte « ARTICLE DROPPÉ ») avait été ajoutée puis **retirée à la demande de l'utilisateur** : la colonne filtrable reste `DROPPÉ`. À savoir : un « É » écrit dans une requête SQL est déformé par le pilote dblib (ISO-8859-1), ne jamais en mettre dans un littéral SQL.

### Stat Contacts Clients (ADV, sous « Comptes Clients »)

Pour chaque client, ses contacts pour les **5 fonctions suivies** (codes X3 `13` Email Confirmation de Commande, `14` Email Facture Comptabilité / Relance, `18` Directeur des achats, `19` Email confirmation de commande acheteur FTW, `20` idem acheteur APP). Le `CROSS JOIN (VALUES (13),(14),(18),(19),(20))` garantit **une ligne par client et par fonction même sans contact** (libellé de fonction toujours renseigné, colonnes du contact vides ; **le code de fonction n'est pas affiché**, retiré à la demande de l'utilisateur le 2026-10-09) ; un client avec plusieurs contacts pour une fonction ressort sur plusieurs lignes (154 cas). Tous les clients sont renvoyés, **actifs ET inactifs** (`STATUT_CLIENT` : 2 = Actif, 1 = Inactif, sinon « Non défini ») : mesuré le 2026-10-09, **15 620 lignes, 3 084 clients, 1,1 s, 6 Mo de JSON** (45 % de lignes de clients inactifs) → stat **générique** (très en dessous des 50 000 lignes qui justifieraient un autre mode).

| Rôle | Fichier |
|---|---|
| SQL (requête fournie par l'utilisateur, inchangée sur le fond) | `src/Infrastructure/Sql/Sei/contacts_clients.sql` |
| Service | `Sales::getContactsClients()` (erreur → `GraphMailer::notifyError` + log) |
| Routes | `SalesController` : alias `/sales/contacts_clients` (`app_sales_contacts_clients`), JSON `contacts_clients_json`, entrée `$config['contacts_clients']`, type ajouté à la liste de `app_sales_generic` |
| Colonnes AG Grid (MySQL) | `src/Infrastructure/Sql/AgGrid/contacts_clients.sql` (`contacts_clients_grid`, 13 colonnes, filtres liste sur représentants / business model / groupement / statut / fonction, **à charger avec `--default-character-set=utf8mb4`** en dev, préprod, prod) |
| Menu / droits | `_sidebar.html.twig` (ADV, sous Comptes Clients, route ajoutée à `currentRoute in [...]`), `StatRegistry` (`app_sales_contacts_clients`, ROLE_ADV), traductions `sidebar.stat.adv.contacts_clients` (« Contacts Clients » / « Customer Contacts »). Pluriel comme « Comptes Clients » |
| Test | `tests/Controller/ContactsClientsAccessTest.php` |

**Accès protégé par rôle côté serveur** (`SalesController::denyUnlessContactsAccess()`, sur la page ET sur la route JSON) : ADV, MANAGEMENT, CONTROLLING, ADMIN ; les autres rôles reçoivent **403 même en tapant l'adresse**. Ce n'est pas le cas des autres stats `/sales/*` : leurs routes ne sont protégées que par `ROLE_USER` (`security.yaml`), le rôle ne gouverne que l'affichage du menu — d'où cette garde spécifique ici, les emails et téléphones étant des données personnelles. L'exclusion par utilisateur reste gérée par `StatAccessSubscriber`.

Les tables `CONTACT`, `CONTACTCRM`, `APLSTD` (libellés de fonction : `LANCHP_0 = 233`, `LAN_0 = 'FRA'`) ne sont **pas encore documentées** dans la base de connaissance X3 (`~/messites/x3-lcs-knowledge`). Le libellé de la fonction 20 est écrit « Emain confirmation… » dans X3 (faute de frappe dans la donnée source, à corriger côté X3).

**Piège de test (valable pour tous les tests fonctionnels).** `KernelBrowser` **redémarre l'application entre deux requêtes** : un service simulé via `static::getContainer()->set(...)` est perdu dès la 2e requête et le test interroge alors le vrai X3 sans que rien ne le signale. Toujours appeler `$client->disableReboot()` dans `setUp()` ; les services à simuler sont déclarés `public: true` dans `config/services_test.yaml` (avec `_defaults: autowire: true`, sinon Symfony ne les câble plus).

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
