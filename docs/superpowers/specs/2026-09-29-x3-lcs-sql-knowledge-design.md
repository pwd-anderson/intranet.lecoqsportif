# Base de connaissance X3 / LCS_X3 pour la génération de requêtes SQL — Design

> **Pour les exécutants agentiques :** COMPÉTENCE REQUISE : utiliser superpowers:writing-plans
> pour transformer ce design en plan d'implémentation détaillé, étape par étape.

**Objectif :** permettre à Claude Code, dans n'importe quel projet touchant les données Sage X3
de Le Coq Sportif (cet intranet, `rma.lecoqsportif`, et les projets futurs), de générer des
requêtes SQL correctes du premier coup sur l'environnement réel — sans halluciner des tables ou
des colonnes du modèle X3 standard qui n'existent pas dans LCS_X3, et sans ignorer les champs
spécifiques développés localement.

**Architecture :** un skill global (niveau utilisateur, pas niveau projet) qui, avant toute
requête SQL sur des données X3, va lire le domaine concerné dans un dépôt git dédié et
indépendant de tout projet consommateur. Un sous-agent optionnel accélère la phase de
construction/mise à jour de ce dépôt (exploration de schéma), sans intervenir dans la génération
de requêtes elle-même.

**Tech Stack :** markdown (aucun outillage de parsing à construire), git, un skill Claude Code
global.

**Spec :** ce document. Pas de spec amont — première itération du projet.

## Contexte

L'utilisateur (développeur intranet Le Coq Sportif) travaille quotidiennement avec des données
Sage X3 sur plusieurs projets. Le modèle X3 standard (dont il possède la documentation officielle
Sage, schémas "Data Physical Model") ne correspond pas exactement à l'environnement réel : de
nombreuses personnalisations existent dans le schéma `LCS_X3` — tables renommées côté cube
d'analyse (ex. `SINVOICE`/`SINVOICED`/`SINVOICEV` standard vs `CONSO_INVOICES` sur le cube SEI),
champs ajoutés (`ZNOOSFLG_0`, `ZDROPPED_0`, `ZSOUSGROUPE_0`, `ZCTREXIST_0`...), et des conventions
maison (alias ATEXTRA/ATABDIV documentés dans `CLAUDE.md` de l'intranet).

Sans base de connaissance dédiée, chaque session Claude Code redécouvre ces écarts par
tâtonnement SQL en direct sur la base réelle — ce qui a été le mode de fonctionnement observé
tout au long de cette conversation (vérification systématique via `INFORMATION_SCHEMA` et
requêtes exploratoires avant chaque nouvelle stat).

## Objectifs

- Documenter le domaine **Ventes** en premier, jusqu'à ce qu'une requête business simple
  ("CA par client et par mois") puisse être générée correctement sans exploration SQL préalable.
- Rendre cette connaissance **réutilisable dans n'importe quel projet** du même environnement
  X3/LCS_X3, pas seulement l'intranet.
- Garder une architecture **modulaire** : chaque domaine (Ventes, Achats, Comptabilité, Stocks...)
  est un module indépendant, ajoutable sans toucher aux autres.
- Rester **simple à démarrer** : pas d'outillage à construire avant de pouvoir écrire la première
  ligne de documentation.

## Non-objectifs (pour cette première itération)

- Ne pas documenter tous les domaines dès le départ — Ventes seul, validé, avant d'étendre.
- Ne pas construire d'interface, de base de données, ou de format structuré (YAML/JSON) pour
  cette connaissance — du markdown suffit, c'est ce que Claude Code consomme le mieux et c'est le
  même choix déjà fait pour `CLAUDE.md` dans ce dépôt.
- Ne pas automatiser entièrement la génération de la documentation — un sous-agent peut
  accélérer l'exploration, mais la validation humaine reste nécessaire avant qu'un fichier soit
  considéré fiable.

## Architecture

### Vue d'ensemble

```
                    ┌─────────────────────────────┐
                    │  x3-lcs-knowledge (dépôt git │
                    │  dédié, hors de tout projet) │
                    │                              │
                    │  ventes/                     │
                    │    orders-normal.md          │
                    │    orders-cumulative.md      │
                    │    invoice.md                │
                    │    deliveries.md             │
                    │    returns.md                │
                    │    price.md                  │
                    │    relations.md              │
                    │    regles-metier.md          │
                    │    exemples.md               │
                    │  achats/        (vide)       │
                    │  comptabilite/  (vide)        │
                    │  stocks/        (vide)        │
                    └──────────────┬───────────────┘
                                   │ lu par
                                   ▼
          ┌────────────────────────────────────────────┐
          │  Skill global : x3-lcs-sql                  │
          │  ~/.claude/skills/x3-lcs-sql/SKILL.md        │
          │  "avant une requête X3/LCS_X3, lis le        │
          │   domaine concerné dans x3-lcs-knowledge/"   │
          └───────────────┬──────────────────────────────┘
                           │ actif dans
                           ▼
      intranet.lecoqsportif   rma.lecoqsportif   (projets futurs)
```

Le skill ne contient **aucune connaissance métier** lui-même — uniquement le réflexe d'aller la
chercher, et où la chercher. Toute la matière vit dans le dépôt dédié, versionnée
indépendamment de n'importe quel projet consommateur.

### Pourquoi un skill global plutôt qu'un skill par projet

Un skill placé dans `.claude/skills/` d'un seul projet (ex. l'intranet) ne serait actif que dans
ce projet. L'utilisateur a explicitement besoin de la même connaissance sur `rma.lecoqsportif`
et les projets futurs. Un skill global (niveau utilisateur) résout ça sans duplication : un seul
fichier d'instructions, un seul dépôt de connaissance, actif partout.

### Pourquoi un dépôt git dédié plutôt qu'un dossier dans l'intranet

- Versionné indépendamment : l'historique du schéma X3/LCS_X3 n'a pas de raison de se mélanger
  avec l'historique du code de l'intranet.
- Partageable plus tard avec des collègues sans leur donner accès au code de l'intranet.
- Aucun projet consommateur ne "possède" la connaissance — elle est neutre, au même niveau que
  les projets qui la consultent.

### Rôle du sous-agent (optionnel, phase de construction uniquement)

Documenter un domaine demande d'interroger `INFORMATION_SCHEMA` sur le Cube SEI, de comparer
avec le modèle standard Sage, et de repérer les écarts — un travail exploratoire répétitif qui
polluerait le contexte d'une session normale s'il était fait en direct. Un sous-agent dédié
("explorateur de schéma") peut prendre un domaine en entrée, produire un premier jet de
documentation en sortie, sans que le contrôle final n'échappe à l'utilisateur : chaque fichier
généré est relu et validé avant d'être considéré fiable.

Ce sous-agent n'intervient jamais au moment de la génération d'une requête SQL business — à ce
moment-là, seul le skill (lecture directe des fichiers déjà validés) est utilisé.

## Format des fichiers de domaine

Chaque sous-modèle Ventes (`orders-normal.md`, `invoice.md`, etc.) suit le même squelette,
calqué sur le découpage utilisé par Sage dans sa propre documentation officielle ("Data Physical
Model", un schéma par sous-modèle) :

````markdown
# Ventes — Commandes normales (Normal Sales Orders)

Source standard : Sage ERP X3, Data Physical Model "Normal Sales Orders", v6.0 (02/2009).

## Tables

### SOH — SORDER (Sales orders - header)

| Colonne standard | Type   | LCS_X3 | Notes |
|---|---|---|---|
| SOHNUM_0 | varchar | identique | clé, numéro de commande |
| BPCORD_0 | varchar | identique | client commande |
| ... | | | |

**Champs LCS_X3 ajoutés (absents du standard) :**

| Colonne | Type | Rôle |
|---|---|---|
| ZCLASSE_0 | varchar | ... |

### SOQ — SORDERQ (Sales orders - quantities)
...

## Relations

- SOH ↔ SOQ : `SOHNUM_0`
- SOQ ↔ SOP : `SOHNUM_0` + `ITMREF_0` + `SOPLIN_0`
- ...

## Écarts notables avec le standard

- Le standard prévoit une table `VOQ - VSORDERQ` (historique cumulatif des commandes) ; côté
  cube SEI, cette agrégation n'existe pas telle quelle — voir `orders-cumulative.md`.
- ...
````

`relations.md` documente les jointures **transverses** entre sous-modèles (ex. commande →
facture) ; `regles-metier.md` documente les conventions qui ne sont pas propres à un sous-modèle
particulier :

- Convention de nommage : suffixe `_0` = champ standard Sage X3 ; préfixe `Z` (ex. `ZNOOSFLG_0`)
  = champ spécifique ajouté par LCS.
- Convention ATEXTRA/ATABDIV : table de valeurs diverses paramétrables, accédée par
  `IDENT1_0`/`IDENT2_0`, avec une liste d'alias déjà en usage (`ATX` = business model,
  `ATX4` = groupement indépendant, etc. — voir `CLAUDE.md` de l'intranet pour la liste actuelle,
  à terme rapatriée ici).
- Le cube d'analyse SEI (`SEI_X3_LCS`) n'est pas un miroir 1:1 des tables transactionnelles X3
  (`X3_LCS`) : certaines tables du cube (ex. `CONSO_INVOICES`) recomposent/agrègent plusieurs
  tables standard.

`exemples.md` : quelques requêtes validées avec leur intention métier en une ligne, pour donner
des patterns copiables-adaptables plutôt que de repartir de zéro à chaque fois.

## Le skill

Fichier unique, court. Contenu attendu (à affiner en plan d'implémentation) :

- Déclencheur : toute demande de requête SQL, de stat, ou d'analyse portant sur des données
  Sage X3 / LCS_X3.
- Comportement : lire dans `~/messites/x3-lcs-knowledge/`, identifier le(s) domaine(s) concerné(s),
  lire le(s) fichier(s) pertinent(s) avant d'écrire une requête.
- Si le domaine n'est pas encore documenté : le dire explicitement plutôt que de deviner, et
  proposer de le documenter (éventuellement via le sous-agent explorateur).

## Démarrage — Ventes uniquement

1. Créer le dépôt `x3-lcs-knowledge`, cloné en `~/messites/x3-lcs-knowledge/` (à côté de
   `intranet.lecoqsportif` et `rma.lecoqsportif`), avec uniquement `ventes/` à l'intérieur
   (les autres dossiers de domaine sont créés vides, pour poser la structure sans s'y engager
   prématurément).
2. Créer le skill global minimal, pointant vers ce chemin fixe.
3. Remplir les fichiers du sous-modèle Ventes un par un, en partant de :
   - Les captures du modèle standard Sage déjà fournies (6 sous-modèles : Sales Invoice,
     Cumulative Sales Orders, Normal Sales Orders, Sales Price, Sales Returns, Sales Deliveries).
   - Ce qui a déjà été vérifié en conditions réelles dans les sessions Claude Code précédentes sur
     l'intranet (tables, colonnes, alias ATEXTRA déjà validés en production).
   - Une exploration systématique complémentaire du schéma réel (`INFORMATION_SCHEMA`) pour
     combler les trous et confirmer les écarts.
4. Valider avec l'utilisateur sur quelques requêtes business concrètes qu'il pose lui-même, avant
   d'étendre à un autre domaine.

## Extension future

Un nouveau domaine (Achats, Comptabilité, Stocks...) = un nouveau dossier dans
`x3-lcs-knowledge/`, suivant le même squelette de fichiers. Le skill n'a pas besoin d'être modifié
— il lit déjà "le domaine concerné", quel qu'il soit. Aucun autre changement d'architecture
attendu pour ajouter un domaine.
