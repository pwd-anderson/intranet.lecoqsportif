# Base de connaissance X3 / LCS_X3 — domaine Ventes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** créer le dépôt de connaissance `x3-lcs-knowledge` (skill global + domaine Ventes
documenté), utilisable par Claude Code dans n'importe quel projet Le Coq Sportif touchant les
données Sage X3/LCS_X3, pour générer des requêtes SQL correctes sans exploration préalable.

**Architecture:** un dépôt git dédié (`~/messites/x3-lcs-knowledge/`), un fichier markdown par
sous-modèle Sage (Normal Orders, Cumulative Orders, Invoice, Deliveries, Returns, Price), plus
un fichier de conventions transverses et un fichier d'exemples. Un skill Claude Code global
(`~/.claude/skills/x3-lcs-sql/`) pointe vers ce dépôt et impose sa lecture avant toute requête
SQL X3/LCS_X3.

**Tech Stack:** markdown, git, skill Claude Code, exploration via `INFORMATION_SCHEMA` sur le
Cube SEI (connexion déjà configurée dans `intranet.lecoqsportif` via
`App\Service\Tools\MssqlManager`).

**Spec:** docs/superpowers/specs/2026-09-29-x3-lcs-sql-knowledge-design.md

## Global Constraints

- Format markdown uniquement, aucun outillage de parsing (YAML/JSON exclus).
- Un fichier par sous-modèle Sage, calqué sur le découpage officiel Sage (6 sous-modèles Ventes).
- Convention à documenter partout où elle s'applique : suffixe `_0` = champ standard Sage X3 ;
  préfixe `Z` (ex. `ZNOOSFLG_0`) = champ spécifique LCS ajouté au-dessus du standard.
- Dépôt cloné en `~/messites/x3-lcs-knowledge/` (chemin fixe, en dur dans le skill).
- Skill global (niveau utilisateur `~/.claude/skills/`), jamais projet-local — doit rester actif
  quel que soit le dépôt de travail courant.
- Toute affirmation sur le schéma réel LCS_X3 doit être vérifiée par une requête
  `INFORMATION_SCHEMA` réellement exécutée avant d'être écrite dans un fichier — jamais de
  colonne "supposée" sans vérification.
- Ce dépôt (`intranet.lecoqsportif`) ne committe jamais automatiquement — seul `x3-lcs-knowledge`
  (nouveau dépôt, hors de cette règle) est committé au fil des tâches, puisque c'est son propre
  historique qu'on construit.

---

### Task 1: Scaffolding du dépôt de connaissance et du skill global

**Files:**
- Create: `~/messites/x3-lcs-knowledge/README.md`
- Create: `~/messites/x3-lcs-knowledge/achats/.gitkeep` (dossiers vides posés maintenant, `ventes/`
  n'a pas besoin de `.gitkeep` : il reçoit un vrai fichier dès la Task 2)
- Create: `~/messites/x3-lcs-knowledge/comptabilite/.gitkeep`
- Create: `~/messites/x3-lcs-knowledge/stocks/.gitkeep`
- Create: `~/.claude/skills/x3-lcs-sql/SKILL.md`

**Interfaces:**
- Produces: le chemin `~/messites/x3-lcs-knowledge/<domaine>/<fichier>.md` que toutes les tâches
  suivantes utilisent pour créer leurs fichiers ; le skill `x3-lcs-sql` que les tâches suivantes
  n'ont pas besoin de retoucher (il ne contient aucune connaissance métier, seulement le réflexe
  et le chemin).

- [ ] **Step 1: Initialiser le dépôt**

```bash
mkdir -p ~/messites/x3-lcs-knowledge/{ventes,achats,comptabilite,stocks}
cd ~/messites/x3-lcs-knowledge
git init
touch achats/.gitkeep comptabilite/.gitkeep stocks/.gitkeep
```

- [ ] **Step 2: Écrire le README**

```markdown
# x3-lcs-knowledge

Base de connaissance du schéma Sage X3 réel de Le Coq Sportif (schéma `LCS_X3`), par rapport
au modèle standard Sage ERP X3 (Data Physical Model, v6.0, 02/2009). Un domaine métier = un
dossier ; chaque sous-modèle Sage à l'intérieur = un fichier markdown.

Utilisée par le skill Claude Code global `x3-lcs-sql` (voir `~/.claude/skills/x3-lcs-sql/SKILL.md`) :
avant d'écrire une requête SQL sur des données X3/LCS_X3, le domaine concerné est lu ici en
premier, dans n'importe quel projet.

## Convention de nommage

- Suffixe `_0` : champ standard Sage X3 (ex. `SOHNUM_0`, `BPCORD_0`).
- Préfixe `Z` (en plus du suffixe `_0`) : champ spécifique ajouté par LCS, absent du standard
  (ex. `ZNOOSFLG_0`, `ZDROPPED_0`, `ZSOUSGROUPE_0`, `ZCTREXIST_0`).

## Domaines

- `ventes/` — documenté (voir ci-dessous)
- `achats/`, `comptabilite/`, `stocks/` — pas encore documentés

## Structure d'un domaine

- Un fichier par sous-modèle Sage (ex. `orders-normal.md`, `invoice.md`...)
- `relations.md` — jointures transverses entre sous-modèles du domaine
- `regles-metier.md` — conventions et pièges propres au domaine
- `exemples.md` — requêtes validées, une intention métier par exemple
```

- [ ] **Step 3: Écrire le skill global**

```markdown
---
name: x3-lcs-sql
description: Utiliser avant d'écrire toute requête SQL, stat, ou analyse portant sur des données Sage X3 ou LCS_X3 (Le Coq Sportif) — quel que soit le projet.
---

# Connaissance du schéma X3 / LCS_X3

Avant d'écrire une requête SQL sur des données Sage X3 ou LCS_X3, lire le(s) fichier(s) du
domaine concerné dans `~/messites/x3-lcs-knowledge/` :

1. Identifier le domaine (ventes, achats, comptabilite, stocks) et, si le domaine est `ventes`,
   le ou les sous-modèles Sage concernés (commandes, factures, livraisons, retours, prix,
   cumuls). En cas de doute sur le sous-modèle, lire `ventes/relations.md` qui décrit les
   enchaînements entre sous-modèles.
2. Lire aussi `<domaine>/regles-metier.md` — il documente les conventions qui ne sont pas
   propres à un sous-modèle (nommage `_0`/`Z`, alias ATEXTRA/ATABDIV, écarts cube vs
   transactionnel).
3. Si un domaine ou un sous-modèle n'est pas encore documenté (dossier vide ou fichier absent) :
   le dire explicitement à l'utilisateur plutôt que de deviner un schéma à partir de la
   connaissance générique de Sage X3. Proposer de le documenter avant de continuer.
4. `<domaine>/exemples.md`, quand il existe, contient des requêtes déjà validées : les préférer
   comme point de départ plutôt que d'écrire une requête from scratch.

Ne jamais écrire une requête sur des tables/colonnes du domaine sans être passé par cette
lecture, même si le nom d'une table semble familier depuis la connaissance générique de Sage X3
— le schéma réel LCS_X3 s'en écarte souvent (tables renommées côté cube d'analyse, champs
ajoutés, alias de table diverse réutilisés d'une convention interne).
```

- [ ] **Step 4: Vérifier le déclenchement du skill**

Ouvrir une nouvelle session Claude Code dans n'importe quel dossier (pas forcément
`x3-lcs-knowledge` ni `intranet.lecoqsportif`) et taper une demande du type *"écris-moi une
requête SQL sur les commandes clients X3 de Le Coq Sportif"*. Attendu : le skill `x3-lcs-sql`
apparaît dans la liste des skills disponibles et est invoqué avant toute tentative de requête.

- [ ] **Step 5: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add -A
git commit -m "Scaffolding : structure des domaines + README"
```

(Le skill global, lui, vit hors de tout dépôt versionné par cette tâche — pas de commit pour lui.)

---

### Task 2: `ventes/regles-metier.md` — conventions transverses

**Files:**
- Create: `~/messites/x3-lcs-knowledge/ventes/regles-metier.md`

**Interfaces:**
- Consumes: rien (première tâche de contenu, base pour toutes les suivantes).
- Produces: la convention `_0`/`Z`, la liste des alias ATEXTRA en usage (réutilisée telle quelle
  par les tâches 3 à 8 quand elles documentent une colonne dérivée d'ATEXTRA), et la distinction
  cube (`SEI_X3_LCS`) vs transactionnel (`X3_LCS`) que les tâches suivantes doivent respecter
  dans leurs propres tableaux de colonnes.

- [ ] **Step 1: Vérifier en direct la liste actuelle des alias ATEXTRA sur le domaine Ventes**

Depuis `intranet.lecoqsportif` (connexion déjà configurée) :

```bash
cd /Users/ajacob/messites/intranet.lecoqsportif
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach ([32,33,34,6021,6028] as $ident1) {
  $rows = $m->executeQuery("SELECT DISTINCT TEXTE_0 FROM X3_LCS.ATEXTRA WHERE CODFIC_0={$q}ATABDIV{$q} AND LANGUE_0={$q}FRA{$q} AND ZONE_0={$q}LNGDES{$q} AND IDENT1_0={$q}$ident1{$q} ORDER BY TEXTE_0");
  echo "IDENT1=$ident1 : ", count($rows), " valeurs, ex: ", implode(", ", array_slice(array_map(fn($r)=>$r->TEXTE_0, $rows),0,3)), "\n";
}
'
```

Expected: 5 lignes, une par `IDENT1_0` (32, 33, 34, 6021, 6028), chacune avec au moins une
valeur. Confirme que ces codes sont toujours actifs dans la base réelle avant de les documenter.

- [ ] **Step 2: Écrire le fichier**

```markdown
# Ventes — Règles et conventions transverses

## Convention de nommage des colonnes

- Suffixe `_0` : champ standard Sage X3 (ex. `SOHNUM_0`, `BPCORD_0`, `ITMREF_0`).
- Préfixe `Z` en plus du suffixe `_0` : champ spécifique ajouté par LCS, absent du modèle
  standard (ex. `ZNOOSFLG_0` — NOOS, `ZDROPPED_0` — article droppé, `ZSOUSGROUPE_0` — sous-groupe
  client, `ZCTREXIST_0` — contrat fournisseur existant, `ZCLASSE_0` sur SORDER).

## Deux bases distinctes

- `X3_LCS` : schéma transactionnel X3 (tables standard : `SORDER`, `SORDERQ`, `SINVOICE`...).
  C'est la source de vérité pour une opération en cours (commande pas encore facturée, etc.).
- `SEI_X3_LCS` : cube d'analyse SEI. Ce n'est **pas** un miroir 1:1 des tables transactionnelles :
  certaines tables du cube recomposent/agrègent plusieurs tables standard (ex. `CONSO_INVOICES`
  regroupe facture, avoir et commande livrée en une seule table avec un champ `DOCUMENTTYPE`
  discriminant — voir `invoice.md`). Préférer le cube pour les requêtes d'analyse/reporting
  (volumétrie, performance), les tables transactionnelles pour une opération précise en cours.

## Alias ATEXTRA/ATABDIV en usage sur le domaine Ventes

Table de valeurs diverses paramétrables (`X3_LCS.ATEXTRA`, `CODFIC_0 = 'ATABDIV'`), accédée par
`IDENT1_0` (catégorie de valeur) + `IDENT2_0` (code interne à joindre). Alias déjà en usage,
**ne jamais réutiliser un alias existant — toujours incrémenter (ATX8, ATX9...)** :

| Alias | IDENT1_0 | Rôle | Colonne source jointe |
|---|---|---|---|
| ATX | 32 | Business Model 1 | `BPCUSTOMER.TSCCOD_2` |
| ATX2 | 33 | Business Model 2 | `BPCUSTOMER.TSCCOD_3` |
| ATX4 | 6021 | Groupement indépendant | `BPCUSTOMER.ZGROUPIND_0` |
| ATX5 | TABLINCFG | Âge (config ligne article) | `ITMMASTER.CFGLIN_0` |
| ATX6 | 6028 | Groupe (code) | `BPCUSTOMER.ZGRPCOD_0` |
| ATX7 | 34 | Canal de distribution (déprécié, plus utilisé côté client — voir note) | `BPCUSTOMER.TSCCOD_4` |

Note : `IDENT1=34` (canal de distribution) est resté un dictionnaire à l'ancien schéma
(valeurs type `AFFILIATED`, `KEY ACCOUNT`) pendant que `IDENT1=32` et `33` ont été relabellisés
en Business Model 1/2 (nouvelles valeurs type `RETAIL BUSINESS`, `E-COMMERCE`,
`RETAILER WHOLESALE FRANCE`, `AMAZON VENDOR`, `ESHOP LCS`...). Sur le cube (`SEI_X3_LCS`), ces
mêmes notions existent directement en colonnes `BUSINESS_MODEL_1` / `BUSINESS_MODEL_2` sur
`LCS_CUSTOMER`, sans passer par ATEXTRA.

## Cascade de recherche photo produit

Les visuels produit ne sont pas stockés en base : ils sont récupérés à la demande sur l'e-shop
Le Coq Sportif, par cascade de noms de fichiers devinés à partir du code article de base
(`https://www.lecoqsportif.com/cdn/shop/files/{article}_2.webp`, puis `_new_1.webp`, `_1.webp`,
`_2.jpg`, `_new_1.jpg`, `_1.jpg`), avec en dernier recours une recherche Shopify. Sans rapport
avec le schéma SQL, mais à connaître si une requête doit exposer un code article "de base"
(sans suffixe de taille) pour cet usage.
```

- [ ] **Step 3: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add ventes/regles-metier.md
git commit -m "Ventes : regles-metier (nommage, ATEXTRA, cube vs transactionnel)"
```

---

### Task 3: `ventes/orders-normal.md` — commandes normales (SOH/SOQ/SOP)

**Files:**
- Create: `~/messites/x3-lcs-knowledge/ventes/orders-normal.md`

**Interfaces:**
- Consumes: convention `_0`/`Z` et alias ATEXTRA de la Task 2.
- Produces: le jeu de colonnes `SOH`/`SOQ`/`SOP` que `relations.md` (Task 9) et `exemples.md`
  (Task 9) réutilisent pour toute requête impliquant une commande de vente.

- [ ] **Step 1: Vérifier en direct les colonnes réelles des trois tables**

```bash
cd /Users/ajacob/messites/intranet.lecoqsportif
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach (["SORDER","SORDERQ","SORDERP"] as $t) {
  echo "=== $t ===\n";
  foreach ($m->executeQuery("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA={$q}X3_LCS{$q} AND TABLE_NAME={$q}$t{$q} ORDER BY ORDINAL_POSITION") as $r) {
    printf("  %-20s %s\n", $r->COLUMN_NAME, $r->DATA_TYPE);
  }
}
'
```

Expected: trois listes de colonnes non vides. Repérer notamment les colonnes préfixées `Z`
(spécifiques LCS) — les distinguer des colonnes standard dans le tableau de l'étape 2.

- [ ] **Step 2: Confronter au modèle standard Sage (déjà fourni dans la conversation ayant produit ce plan) et écrire le fichier**

```markdown
# Ventes — Commandes normales (Normal Sales Orders)

Source standard : Sage ERP X3, Data Physical Model "Normal Sales Orders", v6.0 (02/2009).

## Tables

### SOH — SORDER (Sales orders - header)

| Colonne standard | Type | Rôle | LCS_X3 |
|---|---|---|---|
| SOHNUM_0 | varchar | numéro de commande (clé) | identique |
| BPCORD_0 | varchar | client commande | identique |
| BPCINV_0 | varchar | client facturé (peut différer du client commande) | identique |
| BPCNAM_0 | varchar | nom client commande | identique |
| STOFCY_0 | varchar | site de stockage | identique |
| ORDDAT_0 | date | date de commande | identique |
| CUSORDREF_0 | varchar | référence commande client | identique |
| ZNORIGIN_0 | varchar | référence interne (repli si CUSORDREF_0 vide) | **spécifique LCS** |
| SOQSTA_0 / ZSOHVALSTA_0 | tinyint | statut ligne / statut validation commande (<> 3 = active) | identique |
| BCGCOD_0 (sur BPCUSTOMER, pas SORDER) | — | catégorie client — 'INTER' exclut les commandes intersites | — |
| ZCLASSE_0 | varchar | classe commande, usage interne | **spécifique LCS** |
| CUR_0 | varchar | devise | identique |
| BPAADD_0 | varchar | adresse de livraison | identique |

### SOQ — SORDERQ (Sales orders - quantities)

| Colonne standard | Type | Rôle | LCS_X3 |
|---|---|---|---|
| SOHNUM_0 | varchar | rattachement à SOH | identique |
| SOPLIN_0 | int | numéro de ligne | identique |
| ITMREF_0 | varchar | article (souvent `{base}_{taille}`, ex. `2610865_M`) | identique |
| QTY_0 | decimal | quantité commandée | identique |
| DLVQTY_0 | decimal | quantité déjà livrée | identique |
| ODLQTY_0 | decimal | quantité annulée/soldée | identique |
| DEMDLVDAT_0 | date | date de livraison demandée | identique |
| YCOLLECT_0 | varchar | collection | **spécifique LCS** |

**Quantité restant à livrer (calcul standard, vu partout dans les stats backlog) :**
`QTY_0 - (DLVQTY_0 + ODLQTY_0)`

### SOP — SORDERP (Sales orders - price)

| Colonne standard | Type | Rôle | LCS_X3 |
|---|---|---|---|
| SOHNUM_0 + SOPLIN_0 | — | rattachement à SOQ | identique |
| NETPRINOT_0 | decimal | prix unitaire net HT | identique |
| DISCRGVAL1_0 | decimal | remise auto (ligne) | identique |
| DISCRGVAL2_0 | decimal | remise manuelle (ligne) | identique |

## Split article base/taille

`ITMREF_0` combine souvent article de base et taille en un seul code, séparés par `_`
(ex. `2610865_M`). Découpe standard : tout avant le premier `_` = article, le reste = taille.
En SQL :

```sql
CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0
     THEN LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1)
     ELSE ITM.ITMREF_0
END AS ARTICLE_BASE,
CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0
     THEN SUBSTRING(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) + 1, 50)
     ELSE NULL
END AS VARIANT_VAL
```

## Écarts notables avec le standard

- Le prix HT d'une ligne n'est pas directement `SOP.NETPRINOT_0` : la remise globale du pied de
  commande (table `SVCRFOOT`, colonne `DTAAMT_0`, jointure sur `VCRNUM_0 = SOHNUM_0` et
  `DTA_0 = 1`) doit être déduite : `NETPRINOT_0 * (1 - ISNULL(DTAAMT_0,0)/100)`.
- Les commandes intersites (client de catégorie `'INTER'` sur `BPCUSTOMER.BCGCOD_0`) sont
  exclues des stats de backlog client "normales" — elles vivent dans un flux séparé (voir
  `orders-cumulative.md`).
```

Vérifier chaque ligne du tableau contre le résultat réel de l'étape 1 avant d'écrire le fichier
définitif ; ajuster si une colonne listée ici n'apparaît pas dans le résultat de la requête (ou
inversement, documenter une colonne réellement présente mais omise ici).

- [ ] **Step 3: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add ventes/orders-normal.md
git commit -m "Ventes : orders-normal (SOH/SOQ/SOP)"
```

---

### Task 4: `ventes/invoice.md` — factures de vente (SIH/SIV/SID + CONSO_INVOICES)

**Files:**
- Create: `~/messites/x3-lcs-knowledge/ventes/invoice.md`

**Interfaces:**
- Consumes: convention `_0`/`Z`, distinction cube/transactionnel et alias ATEXTRA de la Task 2.
- Produces: le jeu de colonnes facture (transactionnel ET cube) réutilisé par `relations.md` et
  `exemples.md` (Task 9) pour toute requête de chiffre d'affaires.

- [ ] **Step 1: Vérifier en direct les colonnes réelles, cube et transactionnel**

```bash
cd /Users/ajacob/messites/intranet.lecoqsportif
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach (["X3_LCS.SINVOICE","X3_LCS.SINVOICED","X3_LCS.SINVOICEV","SEI_X3_LCS.CONSO_INVOICES"] as $full) {
  [$schema,$t] = explode(".", $full);
  echo "=== $full ===\n";
  foreach ($m->executeQuery("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA={$q}$schema{$q} AND TABLE_NAME={$q}$t{$q} ORDER BY ORDINAL_POSITION") as $r) {
    printf("  %-24s %s\n", $r->COLUMN_NAME, $r->DATA_TYPE);
  }
}
'
```

Expected: quatre listes non vides. `CONSO_INVOICES` doit apparaître nettement plus riche que la
simple concaténation des trois tables standard (c'est une table du cube, déjà enrichie de
dimensions client/produit).

- [ ] **Step 2: Écrire le fichier**

```markdown
# Ventes — Factures de vente (Sales Invoice)

Source standard : Sage ERP X3, Data Physical Model "Sales Invoice", v6.0 (02/2009).

## Tables transactionnelles (X3_LCS)

### SIH — SINVOICE (Sales invoices - header)

| Colonne standard | Type | Rôle |
|---|---|---|
| NUM_0 | varchar | numéro de facture (clé) |
| ACCDAT_0 | date | date comptable |
| CRYNAM_0 | varchar | pays |
| BPRPAY_0 | varchar | tiers payeur |
| BPRNAM_0 | varchar | raison sociale |
| GTE_0 | varchar | préfixe type master (ex. 'FACLI', 'AVCLI') |
| RATMLT_0 | decimal | taux de change facture -> devise société |
| STA_0 | tinyint | état (1 non validé, 2 inutilisé, 3 validé) |

### SIV — SINVOICEV (Sales invoices - variant/type)

| Colonne standard | Type | Rôle |
|---|---|---|
| NUM_0 | varchar | rattachement à SIH |
| INVTYP_0 | tinyint | type (1 Facture, 2 Avoir, 3 Note de débit, 4 Note de crédit, 5 Proforma) |
| SIVTYP_0 | varchar | type document détaillé (sert aussi à repérer les types pass-thru) |
| CNOREN_0 | varchar | code motif d'avoir |
| REP_0 | varchar | représentant |
| BPCORD_0 | varchar | client commande |
| CUR_0 | varchar | devise |

### SID — SINVOICED (Bill of sale detail)

| Colonne standard | Type | Rôle |
|---|---|---|
| NUM_0 | varchar | rattachement à SIH/SIV |
| ITMREF_0 | varchar | article |
| ITMDES_0 | varchar | désignation |
| QTY_0 | decimal | quantité facturée |
| NETPRI_0 | decimal | prix net (devise société) |
| NETPRINOT_0 | decimal | prix net HT (devise facture) |
| AMTNOTLIN_0 | decimal | montant HT ligne (devise facture) |
| YCOLLECT_0 | varchar | collection |

**Signe des avoirs (tables transactionnelles) :** `INVTYP_0` IN (2, 4) => ligne à multiplier par
-1. Ce signe est à appliquer manuellement en PHP/SQL sur les tables transactionnelles — ce n'est
**pas** le cas sur le cube (voir plus bas).

## Table du cube (SEI_X3_LCS) : CONSO_INVOICES

Ne pas confondre avec les trois tables ci-dessus : `CONSO_INVOICES` est une table du **cube**
d'analyse, pas un miroir des tables transactionnelles. Une seule ligne par article facturé,
colonnes déjà enrichies :

| Colonne | Type | Rôle |
|---|---|---|
| DOCUMENTTYPE | varchar | 'INVOICE', 'CREDITMEMO', ou 'ORDER' (commande livrée, statut à filtrer via ORDERSTATUS=3 AND DLVQTY>0) |
| DOCUMENTNO | varchar | numéro de document — le préfixe (3 premiers caractères) indique le type master : 'AVB'/'AVY' = avoir, 'FVB'/'FVY' = facture |
| DOCUMENTPOSTINGDATE | date | date comptable |
| CUSTOMERNO | varchar | code client (facturé) |
| ITEMNO | varchar | article de base |
| VARIANTCODE | varchar | taille/variante (colonne séparée, pas de suffixe `_` sur ITEMNO comme sur les tables transactionnelles) |
| QUANTITY | decimal | quantité, **déjà signée** (négative sur un avoir) |
| AMOUNTEURTM | decimal | montant HT **déjà en EUR** — pas de taux de change à appliquer |
| AMOUNTCURRENCY | decimal | montant HT dans la devise de la facture |
| CURRENCYCODE | varchar | devise |
| BUSINESS_MODEL_1 / BUSINESS_MODEL_2 | varchar | voir `regles-metier.md`, colonnes directes sur `LCS_CUSTOMER`, pas besoin d'ATEXTRA sur le cube |
| ISBOHPERIMETERPRODUCT | bit | filtre périmètre "produits suivis" — quasi toujours `= 1` dans les requêtes métier |
| NOOS | tinyint | 2 = Oui (voir convention `Z*FLG_0` côté transactionnel) |

**Piège découvert en pratique :** `AMOUNTEURTM` est déjà converti en EUR. Ne jamais le diviser à
nouveau par un taux de change calculé sur `AMOUNTCURRENCY / AMOUNTEURTM` — ce taux inversé
reconvertirait le montant hors de l'EUR par erreur (bug réel rencontré et corrigé sur l'export
CA/Marge).

**Jointures usuelles sur le cube :**

```sql
FROM SEI_X3_LCS.CONSO_INVOICES I
LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
    ON I.ITEMNO = C.ITEM_ID AND I.SERIESNO = C.SERIESCODE
LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
    ON I.COMPANYCODE = CUST.COMPANY_ID AND I.CUSTOMERNO = CUST.CUSTOMER_ID
```

## Périmètre standard des requêtes de chiffre d'affaires

```sql
WHERE I.ISBOHPERIMETERPRODUCT = 1
  AND (I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO')
       OR (I.DOCUMENTTYPE = 'ORDER' AND I.ORDERSTATUS = 3 AND I.DLVQTY > 0))
  AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
```

Filtrer en plus `CUST.REPORTINGDIMENSION NOT IN ('RETAIL', 'E-COMMERCE')` pour exclure le B2C
si la demande porte spécifiquement sur le grossiste (wholesale).
```

- [ ] **Step 3: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add ventes/invoice.md
git commit -m "Ventes : invoice (SIH/SIV/SID + CONSO_INVOICES du cube)"
```

---

### Task 5: `ventes/price.md` — structure de prix (SPL/SPF/SPC/PRS)

**Files:**
- Create: `~/messites/x3-lcs-knowledge/ventes/price.md`

**Interfaces:**
- Consumes: convention `_0`/`Z` de la Task 2.
- Produces: le jeu de colonnes remise/tarif réutilisé par `exemples.md` (Task 9) pour toute
  requête impliquant une remise ou un groupe tarifaire.

- [ ] **Step 1: Vérifier en direct les colonnes réelles**

```bash
cd /Users/ajacob/messites/intranet.lecoqsportif
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach ($m->executeQuery("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA={$q}X3_LCS{$q} AND TABLE_NAME={$q}SPRICLIST{$q} ORDER BY ORDINAL_POSITION") as $r) {
  printf("  %-20s %s\n", $r->COLUMN_NAME, $r->DATA_TYPE);
}
echo "--- valeurs PLI_0 en usage ---\n";
foreach ($m->executeQuery("SELECT DISTINCT PLI_0 FROM X3_LCS.SPRICLIST") as $r) { echo "  ", $r->PLI_0, "\n"; }
'
```

Expected: liste de colonnes de `SPRICLIST` (au minimum `PLI_0`, `PLICRI1_0`, `PLICRI2_0`,
`DCGVAL_0`, `BPCBPS_0`/`PLISTC`), et une liste de codes `PLI_0` incluant au moins `R11`, `T10`,
`T11` (déjà utilisés dans `Sales::getExcessForSales()` et `getExcessForSalesTariffGroups()`).

- [ ] **Step 2: Écrire le fichier**

```markdown
# Ventes — Structure de prix (Sales Price)

Source standard : Sage ERP X3, Data Physical Model "Sales Price", v6.0 (02/2009).

## SPL — SPRICLIST (Customer prices)

Table de tarification générique X3, réutilisée pour deux usages distincts selon `PLI_0` :

| PLI_0 | Usage | PLICRI1_0 | PLICRI2_0 | DCGVAL_0 |
|---|---|---|---|---|
| R11 | Remise client par collection | code client | code collection (ex. '2026-02-FW') | taux de remise (%) |
| T10, T11 | Groupe tarifaire | code groupe tarifaire | — | — |

**Groupes tarifaires disponibles (requête déjà en usage, `Sales::getExcessForSalesTariffGroups()`) :**

```sql
SELECT DISTINCT SPL.PLICRI1_0 AS GROUPE_TARIF
FROM X3_LCS.SPRICLIST AS SPL
WHERE SPL.PLI_0 IN ('T10', 'T11')
ORDER BY SPL.PLICRI1_0
```

**Remise client par collection (pattern déjà en usage sur la stat Comptes Clients) :**

```sql
CASE
    WHEN COUNT(DISTINCT CASE WHEN SPL.PLICRI2_0 = '2026-02-FW' THEN SPL.DCGVAL_0 END) = 1
    THEN MAX(CASE WHEN SPL.PLICRI2_0 = '2026-02-FW' THEN SPL.DCGVAL_0 END)
    ELSE NULL  -- plusieurs remises différentes trouvées pour la même collection : ambigu, ne pas en choisir une au hasard
END AS REMISE_COLLECTION
FROM X3_LCS.SPRICLIST SPL
WHERE SPL.PLI_0 = 'R11'
GROUP BY SPL.PLICRI1_0
```

## Écarts notables avec le standard

- Le modèle standard sépare `SPC` (paramètres tarifaires client), `SPF` (prix avec dates de
  validité) et `SPL` (résultat matérialisé) ; en pratique, les requêtes de ce projet n'ont utilisé
  que `SPL` directement — les deux autres tables n'ont pas encore été vérifiées en conditions
  réelles sur LCS_X3 (à documenter si un besoin futur l'exige).
```

- [ ] **Step 3: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add ventes/price.md
git commit -m "Ventes : price (SPRICLIST, remises et groupes tarifaires)"
```

---

### Task 6: `ventes/deliveries.md` — livraisons (SDH/SDD)

**Files:**
- Create: `~/messites/x3-lcs-knowledge/ventes/deliveries.md`

**Interfaces:**
- Consumes: convention `_0`/`Z` de la Task 2.
- Produces: le jeu de colonnes livraison réutilisé par `relations.md` (Task 9) pour le chaînage
  commande → livraison → facture.

- [ ] **Step 1: Vérifier en direct les colonnes réelles**

```bash
cd /Users/ajacob/messites/intranet.lecoqsportif
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach (["SDELIVERY","SDELIVERYD"] as $t) {
  echo "=== $t ===\n";
  foreach ($m->executeQuery("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA={$q}X3_LCS{$q} AND TABLE_NAME={$q}$t{$q} ORDER BY ORDINAL_POSITION") as $r) {
    printf("  %-20s %s\n", $r->COLUMN_NAME, $r->DATA_TYPE);
  }
}
'
```

Expected: deux listes de colonnes non vides (SDH/SDD, entête + détail de livraison). Si l'une
des deux tables n'existe pas sous ce nom exact, chercher la variante réelle avec :

```bash
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach ($m->executeQuery("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA={$q}X3_LCS{$q} AND TABLE_NAME LIKE {$q}%DELIVER%{$q}") as $r) { echo $r->TABLE_NAME, "\n"; }
'
```

- [ ] **Step 2: Écrire le fichier**

Squelette (à remplir avec les colonnes exactes trouvées à l'étape 1 — aucune colonne standard
n'est garantie ici sans la vérification réelle, contrairement aux Tasks 3-5 qui reposent sur des
requêtes déjà validées dans des sessions précédentes) :

```markdown
# Ventes — Livraisons (Sales Deliveries)

Source standard : Sage ERP X3, Data Physical Model "Sales Deliveries", v6.0 (02/2009).

## SDH — SDELIVERY (Shipment header)

| Colonne standard | Type | Rôle | LCS_X3 |
|---|---|---|---|
| (à remplir depuis la vérification Step 1) | | | |

## SDD — SDELIVERYD (Shipment detail)

| Colonne standard | Type | Rôle | LCS_X3 |
|---|---|---|---|
| (à remplir depuis la vérification Step 1) | | | |

## Relation avec les commandes et factures

(à documenter une fois les colonnes de rattachement confirmées — chercher `SOHNUM_0`/`SDHNUM_0`
sur SDD, présent sur les stats backlog déjà construites : `Sales::getBacklogClientsX3FieldMap()`
utilise `SOQ.SDHNUM_0`.)
```

- [ ] **Step 3: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add ventes/deliveries.md
git commit -m "Ventes : deliveries (SDH/SDD)"
```

---

### Task 7: `ventes/orders-cumulative.md` — cumuls commandes et commandes intersites (SOC/POQ)

**Files:**
- Create: `~/messites/x3-lcs-knowledge/ventes/orders-cumulative.md`

**Interfaces:**
- Consumes: convention `_0`/`Z` de la Task 2, jeu de colonnes SOH/SOQ de la Task 3.
- Produces: le jeu de colonnes cumul/intersite réutilisé par `exemples.md` (Task 9) pour les
  requêtes de couverture stock/commande.

- [ ] **Step 1: Vérifier en direct les colonnes réelles**

```bash
cd /Users/ajacob/messites/intranet.lecoqsportif
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach ($m->executeQuery("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA={$q}X3_LCS{$q} AND TABLE_NAME LIKE {$q}SORDER%{$q}") as $r) { echo $r->TABLE_NAME, "\n"; }
echo "--- MASTER_TABLES.COMMANDES_INTERSITES (deja connue, utilisee par Pilotage Livraisons) ---\n";
foreach ($m->executeQuery("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA={$q}MASTER_TABLES{$q} AND TABLE_NAME={$q}COMMANDES_INTERSITES{$q} ORDER BY ORDINAL_POSITION") as $r) {
  printf("  %-24s %s\n", $r->COLUMN_NAME, $r->DATA_TYPE);
}
'
```

Expected: la première requête liste toutes les tables `SORDER*` réellement présentes (confirmer
si `SORDERC` — cumuls, équivalent du standard `SOC` — existe sous ce nom) ; la seconde confirme
les colonnes déjà connues de la table intersites maison `MASTER_TABLES.COMMANDES_INTERSITES`
(déjà utilisée par le module Pilotage Livraisons et par `backlog_fournisseur_pilotage.sql`, côté
achat — ici côté vente, vérifier si un équivalent existe ou si les intersites de vente
transitent par une autre table).

- [ ] **Step 2: Écrire le fichier**

```markdown
# Ventes — Cumuls commandes et commandes intersites (Cumulative Sales Orders)

Source standard : Sage ERP X3, Data Physical Model "Cumulative Sales Orders", v6.0 (02/2009).

## SOC — SORDERC (Sales orders - early/late)

| Colonne standard | Type | Rôle | LCS_X3 |
|---|---|---|---|
| (à remplir depuis la vérification Step 1) | | | |

## Commandes intersites côté vente

(à documenter selon le résultat de la vérification Step 1 : soit une table dédiée équivalente à
`MASTER_TABLES.COMMANDES_INTERSITES` côté achat, soit un flux différent — ne pas supposer que le
pattern achat s'applique tel quel côté vente sans l'avoir vérifié.)

## Écarts notables avec le standard

- Le standard prévoit `VOH`/`VOC`/`VOQ` (historique cumulatif figé) en plus de `SOH`/`SOC`/`SOQ`
  (état courant) : vérifier si LCS_X3 alimente réellement ces tables d'historique ou si elles
  sont vides/non utilisées avant de les documenter comme source fiable.
```

- [ ] **Step 3: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add ventes/orders-cumulative.md
git commit -m "Ventes : orders-cumulative (SOC, commandes intersites)"
```

---

### Task 8: `ventes/returns.md` — retours (SRH/SRD)

**Files:**
- Create: `~/messites/x3-lcs-knowledge/ventes/returns.md`

**Interfaces:**
- Consumes: convention `_0`/`Z` de la Task 2.
- Produces: le jeu de colonnes retour réutilisé par `relations.md` (Task 9) si une requête doit
  distinguer vente nette de retours.

- [ ] **Step 1: Vérifier en direct les colonnes réelles**

```bash
cd /Users/ajacob/messites/intranet.lecoqsportif
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach ($m->executeQuery("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA={$q}X3_LCS{$q} AND TABLE_NAME LIKE {$q}SRETURN%{$q}") as $r) { echo $r->TABLE_NAME, "\n"; }
'
```

Expected: `SRETURN` et `SRETURND` (ou noms équivalents réels) listées. Si aucun résultat,
essayer `%RETURN%` plus large — la table peut porter un nom différent sur LCS_X3.

```bash
php -r '
require "vendor/autoload.php";
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
$m = new App\Service\Tools\MssqlManager($_ENV["MSSQL_LCS_SEI_URL"], $_ENV["MSSQL_LCS_SEI_USER"], $_ENV["MSSQL_LCS_SEI_PASS"], new Psr\Log\NullLogger());
$q=chr(39);
foreach ($m->executeQuery("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA={$q}X3_LCS{$q} AND TABLE_NAME={$q}SRETURN{$q} ORDER BY ORDINAL_POSITION") as $r) {
  printf("  %-20s %s\n", $r->COLUMN_NAME, $r->DATA_TYPE);
}
'
```

- [ ] **Step 2: Écrire le fichier**

```markdown
# Ventes — Retours (Sales Returns)

Source standard : Sage ERP X3, Data Physical Model "Sales Returns", v6.0 (02/2009).

## SRH — SRETURN (Sales return header)

| Colonne standard | Type | Rôle | LCS_X3 |
|---|---|---|---|
| (à remplir depuis la vérification Step 1) | | | |

## SRD — SRETURND (Sales return detail)

| Colonne standard | Type | Rôle | LCS_X3 |
|---|---|---|---|
| (à remplir depuis la vérification Step 1) | | | |

## Note

Domaine non exploré dans les sessions précédentes (contrairement aux commandes et factures,
largement vérifiées via les stats Backlog Client et CA/Marge) — toutes les colonnes ci-dessus
doivent être confirmées par la requête `INFORMATION_SCHEMA` de l'étape 1 avant d'être considérées
fiables. Si un avoir de type retour transite en réalité par `SINVOICE`/`SINVOICEV` (type
`INVTYP_0 = 2`, voir `invoice.md`) plutôt que par une table `SRETURN` dédiée, le documenter ici
en renvoyant vers `invoice.md`.
```

- [ ] **Step 3: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add ventes/returns.md
git commit -m "Ventes : returns (SRH/SRD)"
```

---

### Task 9: `ventes/relations.md` et `ventes/exemples.md`

**Files:**
- Create: `~/messites/x3-lcs-knowledge/ventes/relations.md`
- Create: `~/messites/x3-lcs-knowledge/ventes/exemples.md`

**Interfaces:**
- Consumes: le jeu de colonnes documenté par les Tasks 2 à 8 (regles-metier, orders-normal,
  invoice, price, deliveries, orders-cumulative, returns).
- Produces: rien de nouveau — ce sont les fichiers de synthèse que le skill (Task 1) lit en
  premier pour s'orienter entre sous-modèles.

- [ ] **Step 1: Écrire `relations.md`**

```markdown
# Ventes — Relations transverses entre sous-modèles

## Chaîne commande -> livraison -> facture

```
SORDER (SOH) --SOHNUM_0--> SORDERQ (SOQ) --SDHNUM_0--> SDELIVERY (SDH) --?--> SINVOICE (SIH)
```

(Compléter les colonnes de rattachement exactes une fois `deliveries.md` rempli avec les
colonnes réelles — Task 6 Step 1.)

## Client commande vs client facturé

Deux notions distinctes à ne pas confondre dans une requête :
- `SORDER.BPCORD_0` — client qui a passé la commande.
- `SORDER.BPCINV_0` — client qui sera effectivement facturé (peut différer : facturation
  groupée, tiers payeur...).
Sur le cube `CONSO_INVOICES`, seul `CUSTOMERNO` existe (client facturé) — pas d'équivalent
"client commande" sur le cube (vérifié : voir `invoice.md`).

## Article : deux découpages différents selon la source

- Tables transactionnelles (`SORDER*`, `SINVOICE*`) : `ITMREF_0` combine article et taille en un
  seul code (`{base}_{taille}`), à découper en SQL (voir `orders-normal.md`).
- Cube (`CONSO_INVOICES`) : `ITEMNO` (article de base) et `VARIANTCODE` (taille) sont déjà deux
  colonnes séparées, pas de découpe à faire.

## Représentants

Deux représentants sur un client (`BPCUSTOMER.REP_0` et `REP_1`), avec une convention
d'affichage établie par l'usage (voir stats Backlog Client / Sell-In / Sell-Out déjà
construites) : REPRESENTANT 1 affiché = `SALESMANNAMENPLUS1` (responsable de zone, suffixe
"RZ"), REPRESENTANT 2 affiché = `SALESMANNAME` (commercial terrain) — l'ordre des colonnes SQL
source est contre-intuitif, à ne pas inverser par réflexe.
```

- [ ] **Step 2: Écrire `exemples.md`**

```markdown
# Ventes — Exemples de requêtes validées

## Chiffre d'affaires par client et par mois (cube, EUR)

```sql
SELECT
    YEAR(I.DOCUMENTPOSTINGDATE)  AS annee,
    MONTH(I.DOCUMENTPOSTINGDATE) AS mois,
    I.CUSTOMERNO,
    CUST.CUSTOMER_NAME,
    SUM(I.AMOUNTEURTM) AS ca_eur
FROM SEI_X3_LCS.CONSO_INVOICES I
LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
    ON I.ITEMNO = C.ITEM_ID AND I.SERIESNO = C.SERIESCODE
LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
    ON I.COMPANYCODE = CUST.COMPANY_ID AND I.CUSTOMERNO = CUST.CUSTOMER_ID
WHERE
    I.ISBOHPERIMETERPRODUCT = 1
    AND (I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO')
         OR (I.DOCUMENTTYPE = 'ORDER' AND I.ORDERSTATUS = 3 AND I.DLVQTY > 0))
    AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
GROUP BY
    YEAR(I.DOCUMENTPOSTINGDATE), MONTH(I.DOCUMENTPOSTINGDATE),
    I.CUSTOMERNO, CUST.CUSTOMER_NAME
ORDER BY annee, mois, ca_eur DESC;
```

## Backlog client (quantité restant à livrer, prix HT)

```sql
SELECT
    SOH.BPCORD_0,
    SPLIT.ARTICLE_BASE,
    CAST(ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) AS INT) AS QUANTITE,
    SOP.NETPRINOT_0 * (1 - (ISNULL(SVT.DTAAMT_0, 0)/100)) AS PRIX_UNITAIRE
FROM X3_LCS.SORDERQ SOQ
INNER JOIN X3_LCS.SORDER  SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
INNER JOIN X3_LCS.SORDERP SOP
    ON SOQ.SOHNUM_0 = SOP.SOHNUM_0 AND SOQ.ITMREF_0 = SOP.ITMREF_0 AND SOQ.SOPLIN_0 = SOP.SOPLIN_0
INNER JOIN X3_LCS.ITMMASTER ITM ON SOQ.ITMREF_0 = ITM.ITMREF_0
CROSS APPLY (
    SELECT
        CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0
             THEN LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1)
             ELSE ITM.ITMREF_0 END AS ARTICLE_BASE
) AS SPLIT
LEFT JOIN X3_LCS.SVCRFOOT SVT ON SOH.SOHNUM_0 = SVT.VCRNUM_0 AND SVT.DTA_0 = 1
INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
WHERE
    SOQ.SOQSTA_0 <> 3
    AND SOH.ZSOHVALSTA_0 <> 3
    AND BPC.BCGCOD_0 <> 'INTER'
    AND (SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)) > 0;
```

## Groupe tarifaire et remise collection d'un client

Voir `price.md` — requêtes déjà validées sur les stats Excess for Sales et Comptes Clients.
```

- [ ] **Step 3: Commit**

```bash
cd ~/messites/x3-lcs-knowledge
git add ventes/relations.md ventes/exemples.md
git commit -m "Ventes : relations transverses et exemples de requetes"
```

---

### Task 10: Validation à froid

**Files:**
- Modify: aucun fichier de contenu — tâche de vérification uniquement.

**Interfaces:**
- Consumes: l'intégralité du domaine `ventes/` (Tasks 2 à 9).
- Produces: rien — confirme que l'objectif de la spec est atteint avant de considérer le domaine
  Ventes "terminé".

- [ ] **Step 1: Poser une question business non couverte textuellement par les exemples**

Dans une nouvelle session Claude Code (mémoire vide, n'importe quel projet du même
environnement), demander une requête que `exemples.md` ne donne pas déjà toute faite — par
exemple *"quelles commandes sont en retard de livraison de plus de 15 jours, avec le nom du
représentant responsable de zone ?"*.

Expected : le skill `x3-lcs-sql` se déclenche, lit `ventes/orders-normal.md` et
`ventes/relations.md`, et produit une requête qui utilise `SOQ.DEMDLVDAT_0`, le calcul quantité
restante déjà documenté, et la bonne colonne représentant (`SALESMANNAMENPLUS1` pour le
responsable de zone, pas l'inverse).

- [ ] **Step 2: Exécuter la requête générée sur la base réelle**

Confirmer qu'elle s'exécute sans erreur SQL et renvoie un résultat plausible (pas de colonne
inconnue, pas de jointure manquante).

- [ ] **Step 3: Si un écart apparaît, corriger le fichier concerné et recommencer l'étape 1**

Pas de commit à cette tâche au-delà des corrections déjà commitées aux tâches précédentes —
cette tâche ne fait que valider, elle ne produit pas de nouveau contenu si tout est correct du
premier coup.
