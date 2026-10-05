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
- Valeurs utilisateur : paramètres (`executeQueryWithParams`) ou liste blanche + échappement. En SSRM, `AgGridSqlBuilder` n'accepte que les colonnes du `fieldMap`.
- Aucun `ORDER BY` quand la grille est client-side : SQL Server devrait trier tout le résultat avant de rendre la première ligne, et ça retarde l'ensemble. Le tri est fait par AG Grid.
- Sélectionner uniquement les colonnes affichées. Les montants en devise : `[PRIX UNITAIRE]` + `[DEVISE]` côté SQL, conversion EUR côté PHP via `Divers::getExchangeRatesValues()` (même formule que le Backlog Clients), jamais le prix brut comme montant final.
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

## Mesurer

Si MSSQL est joignable : exécuter la requête, noter nombre de lignes, durée, poids JSON. Ces trois chiffres justifient le choix de type et de loader. Sinon, dire qu'ils sont estimés et non mesurés.
