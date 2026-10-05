# Checklist de validation

Avant de déclarer une stat terminée, parcourir cette liste et rapporter pour chaque ligne : **vérifié** (avec la preuve), **non vérifié** (et pourquoi), ou **non applicable**.

## SQL
- [ ] La requête s'exécute (ou, si MSSQL inaccessible, le dire) ; nombre de lignes et durée notés
- [ ] Alias = `field` de la config AG Grid, sans doublon ni accent
- [ ] Dates en `varchar(10)` format 23
- [ ] Pas de `SELECT *`, pas de `ORDER BY` pour une grille client-side
- [ ] Skill `x3-lcs-sql` consulté pour les tables X3
- [ ] Valeurs utilisateur paramétrées ou en liste blanche

## Type de stat
- [ ] Générique / flux client-side / SSRM / spécifique : choix justifié par le volume mesuré ou estimé
- [ ] SSRM seulement : JOIN présent dans les 3 requêtes, colonnes dans le `fieldMap`, filtre Set déclaré dans `ssrmSetFilterFields`

## AG Grid
- [ ] Fichier `src/Infrastructure/Sql/AgGrid/{grid_name}.sql` fourni avec `SELECT` + `DELETE` + `INSERT`
- [ ] `field` exact, types/formatters/comparators cohérents, `agg_func` seulement sur les colonnes sommables
- [ ] Filtres adaptés au type, tris, largeurs, ordre des colonnes
- [ ] `initGrid` utilisé (pas `createGrid`), `visibility: hidden` initial, `stateKey` unique
- [ ] Template générique partagé non modifié (stat générique)

## Performance
- [ ] Pas de traitement lourd inutile côté navigateur
- [ ] Gros volume : flux (`StreamedResponse` + `iterateQuery`), pas de `fetchAll` en mémoire
- [ ] Plusieurs requêtes lourdes enchaînées, pas en `Promise.all`

## Chargement
- [ ] Loader standard ou avancé choisi selon la durée de la requête, et la barre ne reste jamais figée
- [ ] Erreur de chargement affichée (pas de loader infini)
- [ ] Rechargement : ancienne grille détruite, écouteurs non dupliqués

## Export
- [ ] Besoin d'export tranché ; Excel absent si gros volume
- [ ] CSV serveur en flux pour la totalité ; modale d'avertissement > 50 000 lignes pour le CSV écran
- [ ] Colonnes et en-têtes cohérents avec la grille

## Intégration
- [ ] Alias de route + route JSON, entrée `$config`, erreurs de service notifiées (`GraphMailer`) et loguées
- [ ] Sidebar : lien avec `is_stat_excluded`, route ajoutée à `currentRoute in [...]` de la bonne section
- [ ] `StatRegistry::all()` mis à jour (section, label, rôles)
- [ ] Traductions `messages.fr.yaml` **et** `messages.en.yaml`
- [ ] `cache:clear` fait, page ouverte, section du menu qui se déroule

## Hygiène
- [ ] Code propre, commentaires qui expliquent le pourquoi (style du dépôt), aucune logique dupliquée avec un composant existant
- [ ] Aucun commit ; `git add` de chaque fichier nouveau
- [ ] Branche courante ≠ `main`
- [ ] Rien d'écrit dans une table `MASTER_TABLES` de prod depuis le dev (pattern `_DEV`)

## Compte rendu final

Courte liste : fichiers créés/modifiés, type de stat retenu et pourquoi, scripts SQL que l'utilisateur doit exécuter (AG Grid, éventuels `CREATE TABLE`), ce qui reste non vérifié.
