# Module MDF (Market Development Funds) — Spec

## Contexte

MDF permet à un commercial de créer une demande de fonds de développement marché pour un client, validée en deux temps par la Direction Commerciale, puis génère un avoir dans X3 (comme SOA). C'est une copie du module SOA (`src/**/Soa*`, `templates/soa/*`) avec un bloc "articles" (plusieurs lignes) remplacé par un bloc "activité" (une seule ligne, sans notion d'article/quantité vendue).

Branche : `mdf` (créée depuis `dev`). Ne jamais commiter — l'utilisateur gère ses commits lui-même.

## Ce qui NE change PAS par rapport à SOA

- Workflow à 6 statuts : `brouillon` → `attente_validation` → `valide_direction` → `attente_val_finale` → `archive` (ou `refuse`, terminal, atteignable depuis `attente_validation` ou `attente_val_finale`)
- Rôles : `ROLE_SALES` crée/modifie (brouillon)/dépose preuves ; `ROLE_MANAGEMENT` ou `ROLE_ADMIN` valide/refuse depuis la vue `validation`
- Vue `show` toujours lecture seule ; vue `edit` à 3 modes (`form` / `validation` / `preuves`) selon statut + rôle
- Recherche client X3 : réutilise `SoaX3Service::searchClients()` tel quel (aucune raison de dupliquer)
- Stockage preuves : `var/uploads/mdf/{id}/`, téléchargement via route authentifiée
- Emplacement sidebar : section Ventes, juste à côté du lien SOA
- Numéro : format `MDFMMAAXXXXXXXXXX`, même algorithme que `SoaRequestRepository::generateNumero()` mais préfixe `MDF`

## Différences avec SOA

### 1. Bloc "activité" remplace le bloc "articles"

Positionné juste après le bloc client dans le formulaire. Une seule ligne, pas de collection Doctrine — champs directement sur `MdfRequest` :

| Champ | Type | Origine |
|---|---|---|
| `typeActivite` | string | Saisie (liste fermée, voir ci-dessous) |
| `montantMdf` | decimal(15,2) | Saisie manuelle |
| `montantFacture` | decimal(15,2), nullable | Snapshot X3 au moment du calcul |
| `montantBacklogClient` | decimal(15,2), nullable | Snapshot X3 au moment du calcul |
| `montantMdfTotalClient` | decimal(15,2), nullable | Snapshot MySQL (autres MDF du client) |
| `roi` | decimal(8,2), nullable | Calculé |
| `roiTotal` | decimal(8,2), nullable | Calculé |

**Liste fermée `typeActivite`** (pas de saisie libre, même si "Other" est choisi — stocké tel quel) :
`Event - Retailer specific`, `Print - Retailer catalog`, `Print - Cobranded advertising`, `Print - Outdoor`, `Instore - POP`, `Instore - Space`, `Digital - Retailer specific`, `Digital - Cobranded`, `S.O.A`, `Other`.

**Formules** (recalculées côté serveur à la sauvegarde, jamais côté client) :
```
ROI       = montantMdf / (montantFacture + montantBacklogClient) × 100
ROI Total = montantMdf / (montantFacture + montantBacklogClient + montantMdfTotalClient) × 100
```
Si le dénominateur est `0` ou `null` → `roi`/`roiTotal` = `null` (pas de division par zéro), même pattern que `SoaRequestProduct::recalculate()`.

**Sources des 3 montants snapshot**, via un nouveau service `MdfX3Service` :
- `montantFacture` : **réutilise exactement** `SoaX3Service::getCaFactureClient($clientCode)` (déjà existant, ne pas dupliquer)
- `montantBacklogClient` : nouvelle requête `src/Infrastructure/Sql/Sei/mdf_backlog_client_montant.sql`, basée sur `backlog_client.sql`, filtrée `WHERE SOH.BPCORD_0 = :clientCode`, retourne `SUM(SOP.NETPRINOT_0 * (SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)))` (montant devise du backlog, même formule que `PRIX` dans `backlog_client.sql`) — **pas besoin de toutes les jointures** de `backlog_client.sql` (pas de filtre Set ni d'export sur cette page), une requête allégée JOIN SORDERQ+SORDER+SORDERP+BPCUSTOMER suffit
- `montantMdfTotalClient` : requête MySQL locale (repository `MdfRequestRepository::getMontantTotalArchiveParClient(clientCode, excludeId)`) : `SUM(montant_mdf) FROM mdf_request WHERE client_code = :code AND status = 'archive' AND YEAR(created_at) = YEAR(CURDATE()) AND id != :excludeId`

Ces 3 valeurs + ROI/ROI Total sont calculés à chaque `save()` (comme SOA recalcule `caFactureAnnee`/`roi` par produit), pas en live à l'affichage.

### 2. Étape "preuves" simplifiée

Pas de `qte_vendue` à saisir (il n'y a pas d'article). L'étape "preuves" consiste **uniquement** à uploader des documents. Le montant de l'avoir final = `montantMdf` saisi à la création, jamais modifié.

### 3. Blocs texte

- `focusProduit` (text, identique à SOA)
- `audience` (text, **nouveau**, même traitement que `focusProduit`)
- `commentaire` (text, nullable, non obligatoire — identique à SOA)

### 4. Flux XML vers X3

`XmlBuilder::buildMDF(MdfRequest $mdf): string` — structure identique à `buildSOA()` :
- Même `WSIVTYP = 'AVSOA'`, même webservice `WSCRESIH`
- Une seule `LIN` : `WITMREF = 'MDF'` (placeholder fixe, à ajuster plus tard si besoin d'un vrai code article X3), `WQTY = 1`, `WPRI = round(montantMdf, 2)`, `WFREFLG = 1`
- Déclenché au passage en `archive`, crée un `SalesWebService` (`name='WSCRESIH'`, `executed=false`, lié via un nouveau champ `mdf_request_id` sur `sales_web_service` — même pattern que `soa_request_id`)
- Envoi : **étendre `SoaSendXmlCommand`** pour traiter aussi les MDF en attente (renommer en commande générique, ou dupliquer en `MdfSendXmlCommand` si la commande SOA est jugée trop spécifique à modifier — décision prise en implémentation après relecture de `SoaSendXmlCommand`)

## Entités (MySQL/Doctrine)

- `MdfStatus` — copie conforme de `SoaStatus` (mêmes 6 codes/couleurs)
- `MdfRequest` — copie de `SoaRequest` avec :
  - Suppression : relation `products` (OneToMany vers une entité ligne)
  - Ajout : les 7 champs du bloc activité (section 1) + `audience` (text, nullable)
  - Conservé : `numero`, `titre`, `representant`, `status`, `clientCode/Nom/Langue/Devise/Emails`, `dateDebut`, `dateFin`, `focusProduit`, `commentaire`, `createdAt`/`updatedAt`, relation `documents`
- `MdfRequestDocument` — copie conforme de `SoaRequestDocument` (mêmes types `contrat`/`preuve`/`autre`)
- Pas d'équivalent `SoaRequestProduct`

## Fichiers à créer

| Rôle | Fichier |
|---|---|
| Entités | `src/Entity/MdfStatus.php`, `MdfRequest.php`, `MdfRequestDocument.php` |
| Repos | `src/Repository/MdfStatusRepository.php`, `MdfRequestRepository.php`, `MdfRequestDocumentRepository.php` |
| Service X3 | `src/Service/MdfX3Service.php` (`getMontantBacklogClient()`; réutilise `SoaX3Service::getCaFactureClient()` par injection) |
| Mailer | `src/Service/MdfMailer.php` (copie de `SoaMailer.php`, adapté au wording MDF) |
| XML | `XmlBuilder::buildMDF()` ajouté dans `src/Service/Webservice/XmlBuilder.php` existant |
| Commande cron | Extension de `src/Command/SoaSendXmlCommand.php` (ou nouvelle `MdfSendXmlCommand.php`) |
| Contrôleur | `src/Controller/MdfController.php` (copie de `SoaController.php`, routes `/mdf/*`, `app_mdf_*`) |
| Templates | `templates/mdf/index.html.twig`, `new.html.twig`, `edit.html.twig`, `show.html.twig` |
| SQL | `src/Infrastructure/Sql/Sei/mdf_backlog_client_montant.sql` |
| Migration | Une migration Doctrine pour les 3 nouvelles tables + colonne `mdf_request_id` sur `sales_web_service` |
| Sidebar/registre | Entrée dans `templates/partials/_sidebar.html.twig` (section Ventes, à côté de SOA), `src/Service/StatRegistry.php`, clés `translations/messages.{fr,en}.yaml` |

## Hors périmètre / décisions différées

- Le code article X3 (`WITMREF`) pour la ligne MDF reste le placeholder `'MDF'` — à corriger plus tard si un vrai code est fourni.
- Le choix entre étendre `SoaSendXmlCommand` vs créer une commande dédiée est tranché au moment de l'implémentation, après relecture précise de ce fichier.
