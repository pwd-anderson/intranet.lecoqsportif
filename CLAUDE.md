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

**Bouton « Import » dans la stat (2026-10-28).** Placé juste avant « Date ETD » (ligne de la légende), visible pour PURCHASING / MANAGEMENT / CONTROLLING. POST `plan_transport_prevision_import` (`AchatController`) : lance `app:import-plan-transport` **dans le même processus** (`Application::run`, comme le cron), puis renvoie du JSON affiché dans un popup Bootstrap (spinner pendant l'import, ✅ nombre de lignes + fichier puis rechargement de la page, ❌ message clair + cause ; réponse non JSON = timeout proxy : le popup invite à vérifier la date du dernier import). Gardes côté serveur : rôle, exclusion de la stat, jeton CSRF `plan_transport_import`, verrou `flock` (`var/plan_transport_import.lock`, un seul import à la fois). En cas d'échec la table est inchangée et le mail d'alerte IT part comme pour le cron. Test : `tests/Controller/PlanTransportImportAccessTest.php`. Mesuré en dev : 3 649 lignes en 6 s ; en préprod/prod, surveiller le délai du proxy (524 vu sur d'autres modules au-delà de ≈ 100 s).

**Piège encodage (corrigé).** Le pilote `dblib` (FreeTDS) travaille en ISO-8859-1 par défaut : écrire « BOOKÉ — ON HOLD » via la connexion partagée stockait `BOOKÃ? â??` (« É » et tiret long déformés, irrécupérables) et relisait le tiret long en `?`. `PlanTransportImporter` utilise donc **sa propre connexion PDO avec `;charset=UTF-8`** (écriture et lecture exactes, vérifié : `UNICODE()` = 201 pour « É »). Ne pas repasser par `MssqlManager` pour ce module, et penser à ce piège pour tout futur import avec accents / tirets longs. (`MssqlManager` avale aussi les erreurs SQL : le module utilise PDO avec exceptions.)

**Remontée dans le Backlog Client (étape 2, faite le 2026-10-08 — `src/Infrastructure/Sql/Sei/backlog_client_v2.sql` + `src/Service/BacklogClientV2.php`).** La colonne **`DATE_COMMANDE_FOURNISSEUR`** (« date arrivée prévue », nom historique conservé) vaut maintenant `COALESCE(CI.DATE_INTERSITE, PT.DATE_ETA_FRANCE)` :
1. **CI = intersite** : `MASTER_TABLES.COMMANDES_INTERSITES` filtré `QTE_EN_TRANSIT > 0`, `MIN(EXTRCPDAT_0)` par (article, site de réception = site de la commande) ;
2. sinon **PT = plan transport** : `MIN(DATE_ETA_FRANCE)` par `ARTICLE_SKU` (= `SOQ.ITMREF_0`, SKU complet), **passée ou non**, **toutes lignes du plan quel que soit le statut de départ**, **uniquement pour les commandes des sites `WLOGM` et `WFIFR`** (l'ETA France = arrivée chez Logtex ; `WFIFR` ajouté le 2026-10-21 à la demande de l'utilisateur, dans le SQL et dans `supplierJoins()`).
La date des commandes fournisseur (`PORDERQ`, ancien premier choix) **n'entre plus dans cette colonne** ; le bloc `PO` ne sert plus qu'à `PO_EN_COURS` (quantité, inchangée). Aucune colonne « source de la date » (refusé par l'utilisateur).
- **Cohérence à garder** (règle du service) : la même règle existe dans 4 endroits — SELECT de `backlog_client_v2.sql`, `getFieldMap()` (filtres/tris SSRM), `supplierJoins()` (jointures de la requête des totaux, ajoutées seulement si le filtre porte sur la colonne) et `getDistinctValues()` (via la requête de base). Toute évolution doit être répercutée partout.
- **Table `_DEV` / prod** : le SQL contient `{{PLAN_TRANSPORT_TABLE}}`, remplacé par `BacklogClientV2::planTransportSource()` (nom de table fourni par `PlanTransportImporter::table()`, une seule source de vérité).
- **Protection (le Backlog Client doit TOUJOURS rester disponible)** : si la table du plan est absente, ou n'a pas les colonnes `ARTICLE_SKU` / `DATE_ETA_FRANCE`, ou si le contrôle échoue, `planTransportSource()` la remplace par une source vide de mêmes colonnes (`(SELECT … WHERE 1 = 0)`) et écrit un warning dans le log : le backlog fonctionne comme avant, sans les dates du plan (seules les dates intersite restent). Une table existante mais vide (avant le premier import en prod) est gérée naturellement. Contrôle fait une fois par requête HTTP.
- **Mesures (dev, 2026-10-08, site WLOGM : 154 505 lignes de backlog)** : ancienne règle 110 329 lignes avec date ; nouvelle règle **116 539** (8 341 via l'intersite, le reste via le plan). Table absente : 154 505 lignes, 8 341 avec date, aucune erreur. Page par défaut inchangée (1,0 s) ; tri + filtre sur la date : 7,3 s → 11,2 s.

**À faire / en attente.** Planifier le cron quotidien (`app:import-plan-transport`) ; charger le script `plan_transport_prevision.sql` (MySQL) et le DDL (SEI) en préprod/prod ; vérifier l'affichage du Backlog Client à l'écran ; nom de fichier logique côté métier.

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

### Stat Suivi complet de commande (ADV) — historique Navision (2026-10-26)

Chaîne **commande → bon de préparation → livraison → facture**, chargée **uniquement à la recherche** (un des 4 numéros — commande, BP, BL, facture — ou une période). Route `SalesController::suiviCompletCommande` / `…Json`, service `Sales::getSuiviCompletCommande()`, SQL `src/Infrastructure/Sql/Sei/suivi_complet_commande.sql`, colonnes AG Grid `suivi_complet_commande_grid`.

**Deux sources, même structure de 20 colonnes (`UNION ALL`)** :
1. **X3** : la vue `MASTER_TABLES.PRODXXX_SUIVI_COMPLET_COMMANDE` (commandes `SON` / `B2B`, 16 600 lignes depuis 2025).
2. **Historique Navision** : `SEI_X3_LCS.LCS_X3SALESX` filtré `SOURCE = '0.Nav_Histo'` et `COMPANYCODE = 'LCSI'` (commandes **facturées**, du 2023 au **2026-04-09**), joint à `DWH_LCS.F_Sales_Preparations` (BP) et `X3_LCS.BPCUSTOMER` (client). Une ligne par enchaînement (commande, BP, BL, facture) via `GROUP BY` : la requête fournie à l'origine (`SELECT DISTINCT` ligne à ligne) renvoyait **une ligne par montant de ligne de facture** pour un même enchaînement.
   - **Montants facture HT / TTC = somme des lignes de la facture** (le cube n'a aucun total d'entête ; vérifié : facture `FVB26008024` = 262,30 sur 11 lignes). Une facture à plusieurs commandes donne le même total sur chacune.
   - **États** (prépa 3 / validation BL 2 / facturation BL 2) = « expédié » quand la ligne du BP est `SHIPPED`, **vides (NULL) sinon** — la requête d'origine écrivait `''` dans un `CASE` numérique, ce qui donne 0.
   - Pas de **date de livraison** dans l'historique ; date d'expédition = date d'expédition du BP.
   - Colonnes texte forcées en `Latin1_General_BIN` (collation de la vue X3), types alignés (`datetime2(7)`, `decimal(27,13)`, `tinyint`) : sans cela l'`UNION ALL` échoue.
   - 498 commandes existent dans les deux sources (reprise Navision → X3) avec des factures différentes (0 facture commune) : les deux enchaînements s'affichent.
- **Plafond d'affichage** : `Sales::SUIVI_COMPLET_MAX_ROWS` = **100 000 lignes** (50 000 au départ, relevé à la demande de l'utilisateur le 2026-10-26) (les plus récentes, `ORDER BY DATE_COMMANDE DESC`). Mesures : 12 mois = 25 188 lignes (14 Mo), année 2025 = 87 692 (passe en entier avec 100 000), 30 mois = 198 130 → coupés (50 000 lignes ≈ 27 Mo et ≈ 15 s en local ; 100 000 ≈ 55 Mo). La route renvoie l'en-tête `X-Result-Truncated` et la page affiche « Résultat limité aux 100 000 lignes les plus récentes : choisissez une période plus courte ou cherchez par numéro ». Une recherche par numéro n'est jamais coupée.
- **Périodes** : « derniers mois » 3 / 6 / 12 / 18 / 24 / 30 (`Sales::SUIVI_COMPLET_MONTHS`, liste blanche) et années courante −3 (jusqu'à 2023). Le filtre porte sur **`DATE_CREATION_BP`** (comme l'ancienne vue) : les commandes **sans bon de préparation** (36 % des lignes historiques de 2025) ne sortent donc pas d'une recherche par période, seulement d'une recherche par numéro.
- Durées mesurées (poste local, dblib) : recherche par numéro 2 à 3 s ; 12 mois ≈ 12 s ; 30 mois ≈ 15 s (le téléchargement de 27 Mo domine).
- **Piège poste local** : le pilote `dblib` tronque les décimales (un montant 262,30 s'affiche 262) — pas sur les serveurs (`sqlsrv`).
- Aucune modification de base de données : tout est dans le fichier SQL. Tests : `tests/Service/SalesSuiviCompletCommandeTest.php`.

**Export « CSV complet » (2026-10-26).** Bouton à côté d'Excel : relance la recherche affichée côté serveur **sans plafond de lignes** (route GET `sales_suivi_complet_commande_export_csv`, `Sales::writeSuiviCompletCommandeCsv`, lecture/écriture au fil de l'eau via `MssqlManager::iterateQueryWithParams`, qui contrairement à `executeQueryWithParams` laisse remonter les erreurs). Même requête et mêmes paramètres liés que l'écran (`buildSuiviCompletQuery`), colonnes visibles de `suivi_complet_commande_grid`, format du CSV du Backlog Client (BOM, `sep=;`, `;`). Vérifié : 3 mois = 6 085 lignes, 19 colonnes. Refusé (400) sans critère.

**Détail d'un bon de préparation (2026-10-26).** Le N° bon de prépa est un lien (info-bulle « Voir le détail… ») qui ouvre une fenêtre Bootstrap : colis, article, description, quantité, poids + totaux. SQL `Sei/suivi_complet_bon_prepa_detail.sql` (`STOPREH` / `STOPRED` / `SPACKD`, n° lié en paramètre, quantité et poids castés en float), `Sales::getBonPrepaDetail()`, route GET `sales_suivi_complet_commande_bon_prepa` (`?num=`). Vérifié sur `BPLCSI26-00023978` : 7 lignes, 14 pièces, 12,209 de poids. Pas de détail pour les bons de l'historique Navision s'ils n'existent pas dans X3 (message « Aucun détail trouvé »).

### Export automatique vers SharePoint « ASK SUPPLY DATA SOURCE » (2026-10-27)

Commande `php bin/console app:export-sharepoint-ask-supply-data-source` (`src/Command/Export/ExportSharePointAskSupplyDataSourceCommand.php`) : génère 7 fichiers dont le contenu reproduit le bouton d'export manuel de chaque stat et les dépose dans `PURCHASING/ASK SUPPLY DATA SOURCE` (bibliothèque Documents partagés), **nom fixe, remplacé à chaque exécution**. Lue par des machines (pas de style soigné). Prévue pour un cron quotidien (à planifier). Options : `--only=<clé>` (répétable), `--dry-run` (fichiers dans `var/upload/export/ask_supply_data_source/`, rien déposé, aucun mail).

| Clé | Fichier | Source / filtre |
|---|---|---|
| `backlog-fournisseur` | `Backlog_Fournisseur.xlsx` | `Achat::getBacklogFournisseur()` + `getBacklogFournisseurIntersites()`, sans filtre de collection |
| `backlog-clients` | `Backlog_Clients.xlsx` | contenu du bouton « CSV complet » (toutes collections, avec le stock) mais **en Excel** (le CSV n'est plus déposé, décision du 2026-10-28) : `BacklogClientV2::iterateExportRows`, nombres bruts ; 139 817 lignes, 27,5 Mo, ≈ 41 s |
| `stock-allocation` | `Stock_Allocation.xlsx` | `Stock::getStockAllocation(null)` = « Global » |
| `produits` | `Produits.xlsx` | `Stock::getStockProduits(['2027-01-SS','2027-02-FW'])` — **collections écrites dans la commande** (`PRODUITS_COLLECTIONS`) |
| `reception-fournisseur` | `Reception_Fournisseur.xlsx` | `Achat::getReceptionFournisseur()` |
| `etat-commandes-clients` | `Etat_Commandes_Clients.xlsx` | `Sales::getEtatCommandesClientsX3Paginated` par blocs de 40 000, **3 collections les plus récentes** (`ETAT_COMMANDES_COLLECTIONS_COUNT`, liste `Divers::getCollections()`) |
| `base-articles-tarifs` | `Base_Articles_Tarifs.xlsx` | `Achat::getBaseArticlesTarifs()` (stat réservée MANAGEMENT/CONTROLLING à l'écran, mais déposée dans le dossier PURCHASING : décision de l'utilisateur) |

**Fichiers.** `src/Service/Export/AskSupplyDataExporter.php` (contenu : colonnes visibles de la config `aggrid_option`, ordre `order_index`, en-têtes `header_name`), `XlsxStreamWriter.php` (xlsx écrit **ligne par ligne**, mémoire constante ; chaînes jamais interprétées comme formules), `SharePointClient::upload()` (session de dépôt Graph par blocs de 10 Mo, remplace le fichier existant).

**Format = export manuel.** Grilles Achats/Stock (export AG Grid) : nombres bruts, **dates `jj/mm/aaaa`** (vérifié sur le vrai export de Réception Fournisseur : cellules `16/04/2026` en texte, quantités/prix numériques), texte tel quel ; `Produits` : colonne PHOTO vide et `TAUX_DEPRECIATION` en « NN % » (processCellCallback de la page). Etat des commandes clients (export SSRM ExcelJS) : valeurs brutes de la requête. En-tête bleu `#024185`, ligne figée, filtre automatique, largeurs 12–40.

**Sécurité.** Les services renvoient `[]` en cas d'erreur SQL : **0 ligne = échec**, le fichier n'est jamais déposé (le précédent reste sur SharePoint). Un échec n'arrête pas les autres exports ; un seul mail récapitulatif (`GraphMailer::notifyError`) et code de sortie non nul.

**Prérequis.** Droit d'application d'écriture SharePoint pour MailGraph : un dépôt réel depuis le poste de l'utilisateur a réussi le 2026-10-27 (4 fichiers déposés). Sans ce droit : 403.

**Piège connexions (corrigé le 2026-10-27).** Chaque service ouvre sa connexion MSSQL au démarrage ; celle d'un service resté inactif pendant les exports longs est coupée par le serveur (« Unexpected EOF » puis « DBPROCESS is dead »), et les services renvoient alors `[]`. La commande appelle `MssqlManagerFactory::reconnectAll()` avant chaque export et retente une fois un export à 0 ligne **ou interrompu par une exception** (coupure « Unexpected EOF » intermittente en cours de lecture, constatée le 2026-10-28 sur le CSV). `composer install` inutile (aucune nouvelle dépendance).

**Mesures (dev, dry-run, 2026-10-27).** Réception 47 472 lignes (5 s), Stock Allocation 88 965 (39 s), Backlog Fournisseur 16 983 (6 s), Backlog Clients CSV 142 397 lignes / 61 Mo (35 s), Produits 1 677 (1 s).

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
