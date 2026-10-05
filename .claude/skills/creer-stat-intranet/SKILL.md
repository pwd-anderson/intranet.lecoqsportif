---
name: creer-stat-intranet
description: Créer ou refondre une statistique (tableau AG Grid) dans l'intranet Le Coq Sportif à partir d'une requête SQL ou d'une description fonctionnelle. À utiliser dès que l'utilisateur veut ajouter une stat, un tableau, un écran de suivi ou un export dans une section Ventes/ADV/Achats/Stock/IT/SAV, même s'il donne seulement une requête SQL brute ou dit « fais-moi une stat sur… ». Couvre SQL, choix générique/spécifique, config AG Grid, performance, barre de chargement, export, droits et validation.
---

# Créer une statistique de l'intranet

Tu agis comme l'expert des stats de cet intranet, pas comme un générateur de code. La chaîne à raisonner d'un bout à l'autre :

**besoin → SQL → type de stat → AG Grid → performance → chargement → export → droits/sidebar → validation**

Le but de ce skill : éviter d'inventer une nouvelle façon de faire. Le projet a déjà des mécanismes éprouvés (certains ont coûté cher à mettre au point : timeouts 524, exports qui figent le navigateur, driver MSSQL 10× plus lent). Les réutiliser ou les améliorer est toujours préférable à une nouvelle implémentation.

## 0. Avant de coder

1. Lire `CLAUDE.md` à la racine (architecture, 7 étapes de la stat générique, règles critiques). Il fait foi ; ce skill ne le recopie pas.
2. **Toute requête X3 / LCS_X3 → invoquer d'abord le skill `x3-lcs-sql`** : il contient les écarts avec le Sage X3 standard. Ne jamais deviner un schéma.
3. Chercher une stat existante proche (`references/catalogue-stats.md`), la lire, et partir d'elle.
4. Entrée acceptée : SQL seul, SQL + courte description, ou description seule. Si une décision structurante manque, **poser la question** plutôt que deviner (liste en §9).

## 1. SQL (`references/sql-et-performance.md`)

Comprendre le besoin, identifier tables/champs, produire une requête cohérente et légère. Estimer le **volume de lignes** dès cette étape : c'est lui qui décide du type de stat et du chargement. Si une requête fournie est lourde, le dire et proposer une version allégée avant d'aller plus loin.

Règles minimales : dates en `CONVERT(varchar(10), [Col], 23) AS [Col]`, alias de colonnes = futurs `field` AG Grid, un fichier `.sql` dans `src/Infrastructure/Sql/{Sei|Navision}/`, aucun `ORDER BY` si la grille trie côté navigateur.

## 2. Choisir le type de stat (`references/choix-du-type.md`)

| Situation | Approche |
|---|---|
| Jusqu'à quelques dizaines de milliers de lignes | **Générique** : 7 étapes de CLAUDE.md, template `{domaine}_generic.html.twig` partagé, jamais modifié |
| Beaucoup de lignes (≈ 50 000+), tout charger reste acceptable | **Client-side en flux** : modèle Backlog Clients (`backlog_client_v3`) |
| Données qu'on ne peut pas charger en entier / filtre serveur indispensable | **SSRM** : modèle historique Backlog X3 (voir CLAUDE.md) — dernier recours, plus lourd à maintenir |
| Recherche ciblée par n° de document | **Spécifique avec filtres obligatoires** : modèle Suivi complet de commande |
| Écran avec saisie / édition | Spécifique, template dédié |

Dire explicitement à l'utilisateur quel type est retenu et pourquoi, en une ou deux phrases.

## 3. AG Grid (`references/aggrid-config.md`)

Tous les tableaux utilisent AG Grid, via `AgGridCommon.initGrid()` (jamais `createGrid`, jamais `setGridOption`). Deux modes de configuration des colonnes :

- **En base** (`aggrid_option`, fichier INSERT versionné dans `src/Infrastructure/Sql/AgGrid/{grid_name}.sql`) : choix par défaut, colonnes éditables par l'admin.
- **Dans le code/Twig** : seulement si les colonnes sont dynamiques ou propres à un écran unique.

Pour un template spécifique (hors générique), les squelettes Twig sont dans `references/gabarits-twig.md`.

La table s'appelle `aggrid_option` (pas `grid_aggrid_option`). Les INSERT sont exécutés par l'utilisateur sur sa base : fournir le script complet, ne pas supposer qu'il est joué.

## 4. Performance et chargement (`references/chargement-et-export.md`)

Deux mécanismes existent. Choisir selon la durée de la requête :

- **Standard** (`AgGridCommon.reloadData`, plafond 85 %) : requête rapide (quelques secondes).
- **Avancé, 3 phases réelles** (requête → téléchargement → traitement, dans `backlog_client_v3.html.twig`) : requête longue ou gros volume. Une barre qui stagne donne l'impression d'un plantage.

Pas de `Promise.all` sur plusieurs requêtes MSSQL lourdes (timeouts 524 constatés en préprod). Gros volume : `StreamedResponse` + `iterateQuery()`.

## 5. Export (`references/chargement-et-export.md`)

Excel via `ExcelExportStandard` + `_excel_loader.html.twig` pour les volumes modestes. Au-delà d'environ 100 000 lignes : **pas d'Excel navigateur** (ExcelJS casse vers 150 000), CSV serveur en flux, et CSV écran avec modale d'avertissement au-delà de 50 000 lignes.

## 6. Droits, sidebar, traductions

Pour une page ajoutée : lien sidebar avec `is_stat_excluded('route')`, route ajoutée à la liste `currentRoute in [...]` de la section (jamais `path starts with`), clé `StatRegistry::all()`, clés de traduction FR **et** EN. Détail dans `references/stat-generique-etapes.md`.

## 7. Réutiliser avant de créer

Avant tout nouveau code : chercher une fonction, un composant ou un pattern équivalent (`AgGridCommon`, `ExcelExportStandard`, `AgGridSqlBuilder`, `Helpers`, `Divers`, `GraphMailer`, `SqlFileLoader`). S'il existe mais ne convient pas tout à fait, **proposer de l'améliorer** plutôt que de dupliquer. Exemple connu : le loader 3 phases est écrit en ligne dans `backlog_client_v3.html.twig` ; si une seconde stat en a besoin, proposer de l'extraire dans `ag-grid-common.js` plutôt que de le copier-coller (et demander avant de toucher à un fichier partagé par toutes les stats).

## 8. Validation avant de dire « terminé »

Dérouler `references/checklist-validation.md` et rapporter ce qui est vérifié **réellement** (requête exécutée, route testée, page chargée) par opposition à ce qui ne l'est pas. Ne pas affirmer qu'une stat fonctionne sans l'avoir exécutée ; si la base MSSQL n'est pas joignable en local, le dire.

## 9. Questions à poser quand l'info manque

Section/domaine et rôles concernés · volume attendu de lignes · filtres de saisie obligatoires (période, collection, client) · colonnes à totaliser · besoin d'export et de quel format · stat qui remplace une existante (parité stricte à vérifier) · base source (SEI Cube vs Navision).

## Règles du dépôt à ne jamais oublier

- **Ne jamais commiter.** `git add` de chaque nouveau fichier créé (SQL, Twig, entité…) dès sa création.
- Ne jamais travailler sur `main` : si c'est la branche courante, basculer sur `dev`.
- Une table `MASTER_TABLES.*` lue ou écrite → pattern `_DEV` (voir mémoire du projet `feedback-dev-prod-table`).
- Toute exception de service : `GraphMailer::notifyError()` + log PSR.

## Faire évoluer ce skill

Ce skill est une v1 volontairement découpée : `SKILL.md` ne contient que la démarche, le détail vit dans `references/`. Pour l'enrichir : nouvelle convention → fichier concerné ; nouveau modèle de stat → ligne dans `catalogue-stats.md` ; règle propre à un domaine (Achats, Stock…) → `references/domaines/{domaine}.md` référencé ici ; nouveau mécanisme de chargement/export → `chargement-et-export.md`. Garder `SKILL.md` sous ~150 lignes.
