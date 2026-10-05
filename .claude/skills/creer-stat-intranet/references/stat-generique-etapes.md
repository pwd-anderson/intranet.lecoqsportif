# Stat générique — les 7 étapes en pratique

Complète la section « Stat pattern (generic) » de `CLAUDE.md`. Aucune étape ne se saute : l'oubli typique est la 7 (la stat marche mais le menu ne s'ouvre pas).

| # | Étape | Fichier |
|---|---|---|
| 1 | SQL | `src/Infrastructure/Sql/Sei/{nom}.sql` (ou `Navision/`) |
| 2 | Méthode de service | `src/Service/{Domaine}.php` |
| 3 | Routes alias + JSON + entrée `$config` | `src/Controller/{Domaine}Controller.php` |
| 4 | Config AG Grid | `src/Infrastructure/Sql/AgGrid/{grid_name}.sql` |
| 5 | Lien sidebar | `templates/partials/_sidebar.html.twig` |
| 6 | Traductions | `translations/messages.fr.yaml` **et** `messages.en.yaml` |
| 7 | Auto-open de la section | liste `currentRoute in [...]` du `<li class="treeview">` |

Étape en plus pour les droits : clé dans `StatRegistry::all()` (section, label, rôles) et lien sidebar enveloppé dans `{% if not is_stat_excluded('app_..._route') %}`.

## Service (modèle `Sales::getSuiviCompletCommande`)

```php
public function getXxx(): array
{
    try {
        $query = $this->sqlFileLoader->load('Sei/xxx.sql');
        return $this->mssqlSei()->executeQuery($query);
    } catch (\Exception $e) {
        $this->graphMailer->notifyError('❌ LCS Erreur {Domaine} : Xxx', $e);
        $this->logger->error('LCS Erreur {Domaine} : Xxx', ['exception' => $e]);
        return [];
    }
}
```

La connexion MSSQL s'obtient via `MssqlManagerFactory::create()` avec `#[Autowire('%db.lcs_sei%')]` (ou `%db.lcs%`, `%db.made2deseign%`), de façon paresseuse (`mssqlSei()`) : ouvrir une connexion coûte jusqu'à ~15 s. Paramètres utilisateur → `executeQueryWithParams` ; jamais de concaténation d'une valeur utilisateur dans le SQL sans échappement ni liste blanche.

## Contrôleur

- Alias : `#[Route('/domaine/ma_stat', name: 'app_domaine_ma_stat')]` → `return $this->domaineGeneric('ma_stat');`
- JSON : `new JsonResponse($helpers->convertArrayToUtf8($service->getXxx()))`, route `domaine_ma_stat_json`.
- `$config['ma_stat'] = ['gridName' => 'ma_stat_grid', 'title' => '…', 'jsonRoute' => '…_json', 'template' => '{domaine}/{domaine}_generic.html.twig', 'gridWidthMode' => 'full'|'auto']`.

Le nom de **route alias** est la clé d'exclusion utilisateur (`user_stat_exclusion.stat_key`) : le choisir définitivement, son renommage casse les exclusions existantes.

## Sidebar / traductions

- Clé : `sidebar.stat.{section}.{identifiant}` (ex. `sidebar.stat.adv.suivi_complet_commande`). Vérifier sous quelle section YAML (`sales`, `adv`, `achat`, `stock`, `it`…) les voisines sont rangées et imiter.
- Copier la ligne `<li>` d'une stat voisine de la même section, changer route, clé, icône identique (`icon-Commit`).
- Rôles de section : Ventes → SALES, MARKETING ; ADV → ADV ; Achats → PURCHASING ; Stock → LOGISTIC, PURCHASING ; IT → IT ; SAV → SAV, IT ; plus MANAGEMENT et CONTROLLING (super-users) partout.

## Après création

`symfony console cache:clear`, ouvrir la page, vérifier que la section du menu se déroule, que le titre est traduit en FR et EN, et que la route JSON renvoie un tableau. `git add` de chaque fichier nouveau.
