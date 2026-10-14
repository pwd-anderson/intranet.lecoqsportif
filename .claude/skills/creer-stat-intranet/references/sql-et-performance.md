# SQL et performance

La performance se pense dès l'écriture de la requête : c'est là que se jouent la plupart des secondes perdues.

## Avant d'écrire

1. Invoquer le skill `x3-lcs-sql` pour tout ce qui touche X3 / LCS_X3 (nommage `_0`, colonnes `Z…`, alias ATEXTRA/ATABDIV, écarts cube vs transactionnel). S'il manque un sous-modèle dans la base de connaissance, le dire à l'utilisateur au lieu de deviner.
2. Chercher une requête déjà validée proche (`src/Infrastructure/Sql/Sei/*.sql`, exemples du skill X3) et partir d'elle.
3. Pour une requête fournie par l'utilisateur : la lire entièrement, repérer les produits cartésiens, `SELECT *`, sous-requêtes corrélées, fonctions sur colonnes filtrées, `DISTINCT` inutiles, jointures sur des tables volumineuses sans filtre préalable.

## Règles du projet

- Dates : `CONVERT(varchar(10), [Col], 23) AS [Col]` (le `dateFormatter` attend `AAAA-MM-JJ`).
- Les alias de sortie deviennent les `field` d'AG Grid : noms stables, sans accents. Seule exception : les requêtes du Pilotage Livraisons, dont les alias bracketés `[NOM AVEC ESPACES]` sont voulus.
- Un fichier `.sql` par requête, chargé par `SqlFileLoader::load('Sei/xxx.sql')`. Placeholders `{{WHERE}}`, `{{ORDER_BY}}`, `{{PAGINATION}}` remplacés par `str_replace` côté service. Le SQL devient inline seulement si un nom de table est dynamique (pattern `_DEV` des tables `MASTER_TABLES`, sans crochets autour de `{$table}`).
- Valeurs utilisateur : **toujours `executeQuery()` sans paramètres liés** (règle de l'utilisateur, 2026-10-14) : placeholder `{{NOM}}` dans le `.sql`, remplacé par `str_replace` côté service avec la valeur échappée (apostrophes doublées `str_replace("'", "''", $v)`, et pour un `LIKE` neutraliser aussi `\ % _ [` avec `ESCAPE '\'`) ou une liste blanche. Ne pas utiliser `executeQueryWithParams` dans le nouveau code. En SSRM, `AgGridSqlBuilder` n'accepte que les colonnes du `fieldMap`.
- Aucun `ORDER BY` quand la grille est client-side : SQL Server devrait trier tout le résultat avant de rendre la première ligne, et ça retarde l'ensemble. Le tri est fait par AG Grid.
- Sélectionner uniquement les colonnes affichées. Les montants en devise : `[PRIX UNITAIRE]` + `[DEVISE]` côté SQL, conversion EUR côté PHP via `Divers::getExchangeRatesValues()` (même formule que le Backlog Clients), jamais le prix brut comme montant final.
- **Décimales (règle importante, constatée le 2026-10-14)** : toute quantité, tout montant, prix ou taux susceptible d'avoir des décimales est converti **dans la requête** : `CAST(SUM(x) AS float) AS QUANTITE`, `CAST(col AS float) AS PRIX`. Côté PHP, **ne jamais** faire `(int)` ni `round(..)` à l'entier sur une quantité : `round((float) $v, 3)` au plus (3 décimales, comme l'affichage). Côté grille, une colonne `integer` avec `integerFormatter` n'ajoute aucun zéro (5 reste « 5 », 10,5 reste « 10,5 ») : inutile de la passer en `decimal`, dont le formateur force 2 décimales (réservé aux prix).
- Listes de référence quasi statiques (collections X3) : lire le cache MySQL (`Divers::getCollections()`) plutôt que d'interroger le cube à chaque appel.

## Volume et transport

| Volume | Conséquence |
|---|---|
| quelques milliers de lignes | `executeQuery()` + `JsonResponse` suffisent |
| dizaines de milliers | idem, surveiller la mémoire PHP et le poids JSON |
| ~100 000+ | `iterateQuery()` + `StreamedResponse` ligne à ligne (`BacklogClientV2::writeAllRowsAsJson`), `set_time_limit(0)`, en-tête `X-Accel-Buffering: no` |

Le Backlog Clients complet (~176 000 lignes) tenait à environ 1,4 Go en mémoire chargé d'un bloc, d'où le flux.

## Pièges déjà rencontrés

- **MARS** : les serveurs utilisent `sqlsrv` (MARS activé par défaut), le poste local `dblib`. `MssqlManager::normalizeDsn()` désactive MARS ; ne jamais le retirer. Avant de comparer les temps entre environnements, vérifier que le driver est le même.
- **Timeouts 524 en préprod** : plusieurs requêtes lourdes simultanées saturent MSSQL. Les enchaîner, pas de `Promise.all`.
- **Compression** : les gros JSON ne sont pas compressés par nginx (piste notée, non activée). Ne pas promettre de gain de transfert sans cela.
- **Connexion MSSQL** : ouverture paresseuse (jusqu'à ~15 s pour `mssqlLcs`).
- **JOIN manquant** : en SSRM, un JOIN absent d'une des 3 requêtes (données, comptage, agrégat) provoque « multi-part identifier could not be bound ».
- **Encodage** : toujours passer les lignes par `Helpers::convertArrayToUtf8()` avant `JsonResponse`.

## Décimales tronquées par le PHP-FPM local (dblib) — piège critique

Le pilote `pdo_dblib` du **serveur web local** (PHP-FPM lancé par `symfony serve`) renvoie les `SUM()` et certaines colonnes décimales **tronquées à l'entier** (5,5 → 5), alors que le **même PHP en ligne de commande** les renvoie exactes. Conséquence : une vérification faite uniquement avec un script CLI (`php bin/console`, script de test) **ne voit pas le bug** ; la page, elle, affiche des totaux faux. Cas réels : Réception fournisseur (total 202 au lieu de 204,5 pour `CALCSI26-00555`) et Backlog fournisseur intersites (273 lignes décimales, total 172 903 au lieu de 173 039,5). Le `CAST(… AS float)` dans la requête règle le problème pour tous les drivers (dblib, sqlsrv de la prod).

Vérifier une stat avec des quantités / montants décimaux **dans le PHP-FPM** : déposer un script temporaire `public/_diag_tmp.php` (boot de `.env`, PDO sur le DSN, exécution de la requête, somme des colonnes), l'appeler avec `curl http://127.0.0.1:8000/_diag_tmp.php` **et** avec `php public/_diag_tmp.php`, comparer les deux sommes, puis **supprimer le fichier**. Comparer aussi avec le total exact obtenu en SQL (`SELECT SUM(...)`). Un écart entre FPM et CLI = colonne à caster. En cas de doute sur un total affiché, lancer cette comparaison avant de chercher côté AG Grid (qui n'arrondit pas).

## Mesurer

Si MSSQL est joignable : exécuter la requête, noter nombre de lignes, durée, poids JSON. Ces trois chiffres justifient le choix de type et de loader. Sinon, dire qu'ils sont estimés et non mesurés.
