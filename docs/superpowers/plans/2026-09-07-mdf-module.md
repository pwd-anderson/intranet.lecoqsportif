# Module MDF (Market Development Funds) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the MDF module (Market Development Funds) — a workflow tool for commercial reps to request market-development funding for a client, validated in two steps by Direction Commerciale, generating an X3 avoir on archival. It is a structural copy of the SOA module with the multi-line "articles" block replaced by a single-line "activité" block.

**Architecture:** Doctrine entities in MySQL (`mdf_status`, `mdf_request`, `mdf_request_document`, `mdf_history`) + a controller serving JSON APIs consumed by vanilla-JS Twig templates (no framework, matches SOA's pattern exactly) + an XML builder posting to Sage X3 via the existing `SageX3Client`/`WSCRESIH` webservice, sent by a cron command.

**Tech Stack:** Symfony 7.4, Doctrine ORM, MySQL, vanilla JS + Bootstrap (no AG Grid except on the list page), `SimpleXMLElement` for XML generation.

**Spec:** `docs/superpowers/specs/2026-09-07-mdf-design.md`

## Global Constraints

- **Never commit** — the user manages all commits themselves. Every task ends with `git add` of the new/changed files, never `git commit`.
- Branch: `mdf` (already created from `dev`; the SOA module lives only on branch `soa` and is NOT present on `dev`/`mdf` — MDF must be **fully self-contained**, it must not `use` or depend on any `Soa*` class, since none exist on this branch.
- **No automated test suite exists for this kind of feature** (no PHPUnit tests for SOA either, project has no MSSQL/MySQL test fixtures). Every task's verification step is therefore: `php -l` on every touched PHP file, a direct query against the real database (MySQL via `dbal:run-sql`, MSSQL via a throwaway PHP script using the pattern established in this project — a raw PDO connection using the credentials in `.env`) where SQL is involved, and a manual walkthrough in the browser for the final task. This replaces the "write failing test" step used in typical plans for this codebase.
- Money fields: `DECIMAL(15,2)` for amounts, `DECIMAL(8,2)` for percentages (ROI) — same precision as SOA.
- Numero format: `MDF` + `date('mY')` + 10-digit zero-padded sequence (e.g. `MDF09202600000001`), identical algorithm to `SoaRequestRepository::generateNumero()`, just the prefix changes.
- `typeActivite` closed list (exact strings, stored verbatim including `Other`): `Event - Retailer specific`, `Print - Retailer catalog`, `Print - Cobranded advertising`, `Print - Outdoor`, `Instore - POP`, `Instore - Space`, `Digital - Retailer specific`, `Digital - Cobranded`, `S.O.A`, `Other`.
- Workflow statuses (6, no dead "attente_preuves" state — SOA's original migration seeded one that turned out unused; MDF starts clean): `brouillon` → `attente_validation` → `valide_direction` → `attente_val_finale` → `archive`, with `refuse` reachable as a terminal state from `attente_validation` or `attente_val_finale`.
- ROI formulas (compute server-side at save time, `null` if denominator is `0` or missing — never divide by zero):
  ```
  roi      = montantMdf / (montantFacture + montantBacklogClient) × 100
  roiTotal = montantMdf / (montantFacture + montantBacklogClient + montantMdfTotalClient) × 100
  ```
- `montantMdfTotalClient` = `SUM(montant_mdf)` from `mdf_request` where `client_code` matches, `status.code = 'archive'`, `YEAR(created_at) = YEAR(CURDATE())`, excluding the request currently being saved.
- Preuves step (`valide_direction` → `attente_val_finale`) requires **only** at least one uploaded document — no quantity/numeric input, unlike SOA's `qte_vendue`.
- XML: `WSIVTYP = 'AVSOA'` (same document type as SOA, per explicit user instruction), webservice name `WSCRESIH`, single `LIN` with `WITMREF = 'MDF'` (fixed placeholder), `WQTY = 1`, `WPRI = round(montantMdf, 2)`, `WFREFLG = 1`.
- Sidebar: section **Ventes** (`ROLE_SALES`/`ROLE_MARKETING` + superusers), placed right after the existing `app_sales_ventes_qte_ca_client` entry (`templates/partials/_sidebar.html.twig:57`).
- CSS/JS class prefixes: reuse the exact `soa-*` visual system (`.soa-card`, `.soa-stepper`, `.soa-input`, `.soa-readonly`, `.soa-dropdown`, etc.) renamed to `mdf-*` — same look and feel, no visual redesign.

---

### Task 1: Entities, repositories, migration

**Files:**
- Create: `src/Entity/MdfStatus.php`
- Create: `src/Entity/MdfRequest.php`
- Create: `src/Entity/MdfRequestDocument.php`
- Create: `src/Entity/MdfHistory.php`
- Create: `src/Repository/MdfStatusRepository.php`
- Create: `src/Repository/MdfRequestRepository.php`
- Create: `src/Repository/MdfRequestDocumentRepository.php`
- Create: `src/Repository/MdfHistoryRepository.php`
- Modify: `src/Entity/SalesWebService.php`
- Modify: `src/Repository/SalesWebServiceRepository.php`
- Create: `migrations/VersionYYYYMMDDHHMMSS.php` (timestamp generated at implementation time via `php bin/console doctrine:migrations:generate` or manually, matching the project's existing filename convention)

**Interfaces:**
- Produces: `MdfStatus::getCode(): string`, `getLabel(): string`, `getColor(): string`, `getTextColor(): string`, `getOrderIndex(): int`; `MdfStatusRepository::findByCode(string): ?MdfStatus`, `findAllOrdered(): array`
- Produces: `MdfRequest` getters/setters for `id, numero, titre, representant, status (MdfStatus), clientCode, clientNom, clientLangue, clientDevise, clientEmails (array), dateDebut, dateFin, typeActivite, montantMdf, montantFacture (?string), montantBacklogClient (?string), montantMdfTotalClient (?string), roi (?string), roiTotal (?string), focusProduit (?string), audience (?string), commentaire (?string), createdAt, updatedAt, documents (Collection<MdfRequestDocument>)`; method `recalculate(): void` (computes `roi`/`roiTotal` from the other fields, mutates in place)
- Produces: `MdfRequestRepository::findAllForList(): array`, `findByRepresentant(string): array`, `generateNumero(): string`, `getMontantTotalArchiveParClient(string $clientCode, ?int $excludeId): float`
- Produces: `MdfRequestDocument` with constants `TYPE_CONTRAT`, `TYPE_PREUVE`, `TYPE_AUTRE`, getters/setters `type, nomFichier, chemin, mimeType, taille, uploadedBy, uploadedAt, mdfRequest`
- Produces: `MdfHistory` getters/setters `mdfRequest, user, statut, statutLabel, createdAt`
- Produces: `SalesWebService::getMdfRequestId(): ?int`, `setMdfRequestId(?int): static`; `SalesWebServiceRepository::findPendingMdf(): array`

- [ ] **Step 1: Create `MdfStatus` entity**

```php
<?php

namespace App\Entity;

use App\Repository\MdfStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MdfStatusRepository::class)]
#[ORM\Table(name: 'mdf_status')]
class MdfStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 100)]
    private string $label = '';

    #[ORM\Column(length: 20)]
    private string $color = '#6c757d';

    #[ORM\Column(length: 20)]
    private string $textColor = '#ffffff';

    #[ORM\Column]
    private int $orderIndex = 0;

    public function getId(): ?int { return $this->id; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function getColor(): string { return $this->color; }
    public function setColor(string $color): static { $this->color = $color; return $this; }

    public function getTextColor(): string { return $this->textColor; }
    public function setTextColor(string $textColor): static { $this->textColor = $textColor; return $this; }

    public function getOrderIndex(): int { return $this->orderIndex; }
    public function setOrderIndex(int $orderIndex): static { $this->orderIndex = $orderIndex; return $this; }
}
```

- [ ] **Step 2: Create `MdfStatusRepository`**

```php
<?php

namespace App\Repository;

use App\Entity\MdfStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MdfStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MdfStatus::class);
    }

    public function findByCode(string $code): ?MdfStatus
    {
        return $this->findOneBy(['code' => $code]);
    }

    /** @return MdfStatus[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

- [ ] **Step 3: Create `MdfRequestDocument` entity**

```php
<?php

namespace App\Entity;

use App\Repository\MdfRequestDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MdfRequestDocumentRepository::class)]
#[ORM\Table(name: 'mdf_request_document')]
class MdfRequestDocument
{
    public const TYPE_CONTRAT = 'contrat';
    public const TYPE_PREUVE  = 'preuve';
    public const TYPE_AUTRE   = 'autre';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MdfRequest::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private MdfRequest $mdfRequest;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_AUTRE;

    #[ORM\Column(length: 255)]
    private string $nomFichier = '';

    #[ORM\Column(length: 500)]
    private string $chemin = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    private ?int $taille = null;

    #[ORM\Column(length: 150)]
    private string $uploadedBy = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getMdfRequest(): MdfRequest { return $this->mdfRequest; }
    public function setMdfRequest(MdfRequest $mdfRequest): static { $this->mdfRequest = $mdfRequest; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getNomFichier(): string { return $this->nomFichier; }
    public function setNomFichier(string $nomFichier): static { $this->nomFichier = $nomFichier; return $this; }

    public function getChemin(): string { return $this->chemin; }
    public function setChemin(string $chemin): static { $this->chemin = $chemin; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): static { $this->mimeType = $mimeType; return $this; }

    public function getTaille(): ?int { return $this->taille; }
    public function setTaille(?int $taille): static { $this->taille = $taille; return $this; }

    public function getUploadedBy(): string { return $this->uploadedBy; }
    public function setUploadedBy(string $uploadedBy): static { $this->uploadedBy = $uploadedBy; return $this; }

    public function getUploadedAt(): \DateTimeInterface { return $this->uploadedAt; }
}
```

- [ ] **Step 4: Create `MdfRequestDocumentRepository`**

```php
<?php

namespace App\Repository;

use App\Entity\MdfRequestDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MdfRequestDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MdfRequestDocument::class);
    }
}
```

- [ ] **Step 5: Create `MdfHistory` entity**

```php
<?php

namespace App\Entity;

use App\Repository\MdfHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MdfHistoryRepository::class)]
#[ORM\Table(name: 'mdf_history')]
class MdfHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MdfRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private MdfRequest $mdfRequest;

    #[ORM\Column(length: 150)]
    private string $user = '';

    #[ORM\Column(length: 50)]
    private string $statut = '';

    #[ORM\Column(length: 255)]
    private string $statutLabel = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getMdfRequest(): MdfRequest { return $this->mdfRequest; }
    public function setMdfRequest(MdfRequest $mdfRequest): static { $this->mdfRequest = $mdfRequest; return $this; }

    public function getUser(): string { return $this->user; }
    public function setUser(string $user): static { $this->user = $user; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getStatutLabel(): string { return $this->statutLabel; }
    public function setStatutLabel(string $statutLabel): static { $this->statutLabel = $statutLabel; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
```

- [ ] **Step 6: Create `MdfHistoryRepository`**

```php
<?php

namespace App\Repository;

use App\Entity\MdfHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MdfHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MdfHistory::class);
    }
}
```

- [ ] **Step 7: Create `MdfRequest` entity**

```php
<?php

namespace App\Entity;

use App\Repository\MdfRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MdfRequestRepository::class)]
#[ORM\Table(name: 'mdf_request')]
#[ORM\HasLifecycleCallbacks]
class MdfRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Format : MDFMMAAXXXXXXXXXX */
    #[ORM\Column(length: 20, unique: true)]
    private string $numero = '';

    #[ORM\Column(length: 255)]
    private string $titre = '';

    /** Identifiant Azure (email) du commercial créateur */
    #[ORM\Column(length: 150)]
    private string $representant = '';

    #[ORM\ManyToOne(targetEntity: MdfStatus::class)]
    #[ORM\JoinColumn(nullable: false)]
    private MdfStatus $status;

    #[ORM\Column(length: 50)]
    private string $clientCode = '';

    #[ORM\Column(length: 255)]
    private string $clientNom = '';

    #[ORM\Column(length: 10)]
    private string $clientLangue = '';

    #[ORM\Column(length: 10)]
    private string $clientDevise = 'EUR';

    #[ORM\Column(type: Types::JSON)]
    private array $clientEmails = [];

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dateFin;

    /** Une des valeurs de la liste fermée (voir Global Constraints), stockée telle quelle */
    #[ORM\Column(length: 100)]
    private string $typeActivite = '';

    /** Montant MDF saisi manuellement par le commercial */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $montantMdf = '0.00';

    /** Snapshot X3 au moment du calcul (CA facturé année en cours) */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantFacture = null;

    /** Snapshot X3 au moment du calcul (backlog client, montant devise) */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantBacklogClient = null;

    /** Snapshot MySQL : somme des MDF archivées de ce client sur l'année en cours */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantMdfTotalClient = null;

    /** ROI = montantMdf / (montantFacture + montantBacklogClient) x 100 */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $roi = null;

    /** ROI Total = montantMdf / (montantFacture + montantBacklogClient + montantMdfTotalClient) x 100 */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $roiTotal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $focusProduit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $audience = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\OneToMany(targetEntity: MdfRequestDocument::class, mappedBy: 'mdfRequest', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getNumero(): string { return $this->numero; }
    public function setNumero(string $numero): static { $this->numero = $numero; return $this; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getRepresentant(): string { return $this->representant; }
    public function setRepresentant(string $representant): static { $this->representant = $representant; return $this; }

    public function getStatus(): MdfStatus { return $this->status; }
    public function setStatus(MdfStatus $status): static { $this->status = $status; return $this; }

    public function getClientCode(): string { return $this->clientCode; }
    public function setClientCode(string $clientCode): static { $this->clientCode = $clientCode; return $this; }

    public function getClientNom(): string { return $this->clientNom; }
    public function setClientNom(string $clientNom): static { $this->clientNom = $clientNom; return $this; }

    public function getClientLangue(): string { return $this->clientLangue; }
    public function setClientLangue(string $clientLangue): static { $this->clientLangue = $clientLangue; return $this; }

    public function getClientDevise(): string { return $this->clientDevise; }
    public function setClientDevise(string $clientDevise): static { $this->clientDevise = $clientDevise; return $this; }

    public function getClientEmails(): array { return $this->clientEmails; }
    public function setClientEmails(array $clientEmails): static { $this->clientEmails = $clientEmails; return $this; }

    public function getDateDebut(): \DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): \DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getTypeActivite(): string { return $this->typeActivite; }
    public function setTypeActivite(string $typeActivite): static { $this->typeActivite = $typeActivite; return $this; }

    public function getMontantMdf(): string { return $this->montantMdf; }
    public function setMontantMdf(string $montantMdf): static { $this->montantMdf = $montantMdf; return $this; }

    public function getMontantFacture(): ?string { return $this->montantFacture; }
    public function setMontantFacture(?string $montantFacture): static { $this->montantFacture = $montantFacture; return $this; }

    public function getMontantBacklogClient(): ?string { return $this->montantBacklogClient; }
    public function setMontantBacklogClient(?string $montantBacklogClient): static { $this->montantBacklogClient = $montantBacklogClient; return $this; }

    public function getMontantMdfTotalClient(): ?string { return $this->montantMdfTotalClient; }
    public function setMontantMdfTotalClient(?string $montantMdfTotalClient): static { $this->montantMdfTotalClient = $montantMdfTotalClient; return $this; }

    public function getRoi(): ?string { return $this->roi; }
    public function setRoi(?string $roi): static { $this->roi = $roi; return $this; }

    public function getRoiTotal(): ?string { return $this->roiTotal; }
    public function setRoiTotal(?string $roiTotal): static { $this->roiTotal = $roiTotal; return $this; }

    public function getFocusProduit(): ?string { return $this->focusProduit; }
    public function setFocusProduit(?string $focusProduit): static { $this->focusProduit = $focusProduit; return $this; }

    public function getAudience(): ?string { return $this->audience; }
    public function setAudience(?string $audience): static { $this->audience = $audience; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @return Collection<int, MdfRequestDocument> */
    public function getDocuments(): Collection { return $this->documents; }

    public function addDocument(MdfRequestDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setMdfRequest($this);
        }
        return $this;
    }

    public function removeDocument(MdfRequestDocument $document): static
    {
        $this->documents->removeElement($document);
        return $this;
    }

    /**
     * Recalcule roi et roiTotal à partir des montants courants.
     * null si le dénominateur correspondant est nul ou absent (jamais de division par zéro).
     */
    public function recalculate(): void
    {
        $montantMdf = (float) $this->montantMdf;
        $facture    = $this->montantFacture !== null ? (float) $this->montantFacture : 0.0;
        $backlog    = $this->montantBacklogClient !== null ? (float) $this->montantBacklogClient : 0.0;
        $mdfTotal   = $this->montantMdfTotalClient !== null ? (float) $this->montantMdfTotalClient : 0.0;

        $denomRoi = $facture + $backlog;
        $this->roi = $denomRoi > 0
            ? (string) round($montantMdf / $denomRoi * 100, 2)
            : null;

        $denomRoiTotal = $facture + $backlog + $mdfTotal;
        $this->roiTotal = $denomRoiTotal > 0
            ? (string) round($montantMdf / $denomRoiTotal * 100, 2)
            : null;
    }
}
```

- [ ] **Step 8: Create `MdfRequestRepository`**

```php
<?php

namespace App\Repository;

use App\Entity\MdfRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MdfRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MdfRequest::class);
    }

    /** @return MdfRequest[] */
    public function findAllForList(): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('s')
            ->join('r.status', 's')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return MdfRequest[] */
    public function findByRepresentant(string $representant): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('s')
            ->join('r.status', 's')
            ->where('r.representant = :rep')
            ->setParameter('rep', $representant)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function generateNumero(): string
    {
        $prefix = 'MDF' . date('mY');
        $count  = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.numero LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->getQuery()
            ->getSingleScalarResult();

        return $prefix . str_pad((string) ($count + 1), 10, '0', STR_PAD_LEFT);
    }

    /**
     * Somme des montants MDF archivés pour ce client sur l'année civile en cours,
     * en excluant éventuellement la demande en cours d'édition.
     */
    public function getMontantTotalArchiveParClient(string $clientCode, ?int $excludeId = null): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montantMdf), 0) AS total')
            ->join('r.status', 's')
            ->where('r.clientCode = :code')
            ->andWhere('s.code = :statut')
            ->andWhere('YEAR(r.createdAt) = :year')
            ->setParameter('code', $clientCode)
            ->setParameter('statut', 'archive')
            ->setParameter('year', (int) date('Y'));

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }
}
```

- [ ] **Step 9: Add `mdfRequestId` to `SalesWebService`**

Open `src/Entity/SalesWebService.php`. After the `erpDocumentId` property/getter/setter, add:

```php
    #[ORM\Column(nullable: true)]
    private ?int $mdfRequestId = null;
```

and after `setErpDocumentId()`:

```php
    public function getMdfRequestId(): ?int { return $this->mdfRequestId; }
    public function setMdfRequestId(?int $id): static { $this->mdfRequestId = $id; return $this; }
```

- [ ] **Step 10: Add `findPendingMdf()` to `SalesWebServiceRepository`**

Open `src/Repository/SalesWebServiceRepository.php`. If the class currently has no methods (verify with `cat src/Repository/SalesWebServiceRepository.php` — on this branch it was generated by `make:entity` and has no custom methods yet), add:

```php
    /** @return SalesWebService[] Flux MDF générés mais pas encore envoyés à X3 */
    public function findPendingMdf(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.name = :name')
            ->andWhere('w.executed = false')
            ->andWhere('w.mdfRequestId IS NOT NULL')
            ->setParameter('name', 'WSCRESIH')
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
```

- [ ] **Step 11: Verify PHP syntax on every new/modified file**

Run:
```bash
php -l src/Entity/MdfStatus.php
php -l src/Entity/MdfRequest.php
php -l src/Entity/MdfRequestDocument.php
php -l src/Entity/MdfHistory.php
php -l src/Repository/MdfStatusRepository.php
php -l src/Repository/MdfRequestRepository.php
php -l src/Repository/MdfRequestDocumentRepository.php
php -l src/Repository/MdfHistoryRepository.php
php -l src/Entity/SalesWebService.php
php -l src/Repository/SalesWebServiceRepository.php
```
Expected: `No syntax errors detected` for every file.

- [ ] **Step 12: Generate and clean up the migration**

Run:
```bash
php bin/console doctrine:migrations:diff --no-interaction
```
This produces a new file in `migrations/`. Open it and **replace its contents entirely** with the hand-written version below (the raw diff will include unrelated schema drift from other in-progress work — same situation documented in `CLAUDE.md` for the `x3_collection` migration; keep only what belongs to MDF). Use the exact class name Doctrine generated (`VersionYYYYMMDDHHMMSS`) but this body:

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionYYYYMMDDHHMMSS extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création du module MDF : mdf_status, mdf_request, mdf_request_document, mdf_history + mdf_request_id sur sales_web_service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE mdf_status (
                id          INT AUTO_INCREMENT NOT NULL,
                code        VARCHAR(50)  NOT NULL,
                label       VARCHAR(100) NOT NULL,
                color       VARCHAR(20)  NOT NULL DEFAULT '#6c757d',
                text_color  VARCHAR(20)  NOT NULL DEFAULT '#ffffff',
                order_index INT          NOT NULL DEFAULT 0,
                UNIQUE INDEX uniq_mdf_status_code (code),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE mdf_request (
                id                        INT AUTO_INCREMENT NOT NULL,
                status_id                 INT           NOT NULL,
                numero                    VARCHAR(20)   NOT NULL,
                titre                     VARCHAR(255)  NOT NULL,
                representant              VARCHAR(150)  NOT NULL,
                client_code               VARCHAR(50)   NOT NULL,
                client_nom                VARCHAR(255)  NOT NULL,
                client_langue             VARCHAR(10)   NOT NULL,
                client_devise             VARCHAR(10)   NOT NULL DEFAULT 'EUR',
                client_emails             JSON          NOT NULL,
                date_debut                DATE          NOT NULL,
                date_fin                  DATE          NOT NULL,
                type_activite             VARCHAR(100)  NOT NULL,
                montant_mdf               NUMERIC(15,2) NOT NULL DEFAULT '0.00',
                montant_facture           NUMERIC(15,2) DEFAULT NULL,
                montant_backlog_client    NUMERIC(15,2) DEFAULT NULL,
                montant_mdf_total_client  NUMERIC(15,2) DEFAULT NULL,
                roi                       NUMERIC(8,2)  DEFAULT NULL,
                roi_total                 NUMERIC(8,2)  DEFAULT NULL,
                focus_produit             LONGTEXT      DEFAULT NULL,
                audience                  LONGTEXT      DEFAULT NULL,
                commentaire               LONGTEXT      DEFAULT NULL,
                created_at                DATETIME      NOT NULL,
                updated_at                DATETIME      NOT NULL,
                UNIQUE INDEX uniq_mdf_request_numero (numero),
                INDEX idx_mdf_request_status (status_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_mdf_request_status FOREIGN KEY (status_id) REFERENCES mdf_status (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE mdf_request_document (
                id             INT AUTO_INCREMENT NOT NULL,
                mdf_request_id INT          NOT NULL,
                type           VARCHAR(20)  NOT NULL DEFAULT 'autre',
                nom_fichier    VARCHAR(255) NOT NULL,
                chemin         VARCHAR(500) NOT NULL,
                mime_type      VARCHAR(100) DEFAULT NULL,
                taille         INT          DEFAULT NULL,
                uploaded_by    VARCHAR(150) NOT NULL,
                uploaded_at    DATETIME     NOT NULL,
                INDEX idx_mdf_document_request (mdf_request_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_mdf_document_request FOREIGN KEY (mdf_request_id) REFERENCES mdf_request (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE mdf_history (
                id             INT AUTO_INCREMENT NOT NULL,
                mdf_request_id INT          NOT NULL,
                user           VARCHAR(150) NOT NULL,
                statut         VARCHAR(50)  NOT NULL,
                statut_label   VARCHAR(255) NOT NULL,
                created_at     DATETIME     NOT NULL,
                INDEX idx_mdf_history_request (mdf_request_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_mdf_history_request FOREIGN KEY (mdf_request_id) REFERENCES mdf_request (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            INSERT INTO mdf_status (code, label, color, text_color, order_index) VALUES
            ('brouillon',          'Brouillon',                        '#6c757d', '#ffffff', 1),
            ('attente_validation', 'En attente de validation',         '#fd7e14', '#ffffff', 2),
            ('valide_direction',   'Validé par la Direction',          '#0d6efd', '#ffffff', 3),
            ('attente_val_finale', 'En attente de validation finale',  '#fd7e14', '#ffffff', 4),
            ('archive',            'Archivé',                          '#198754', '#ffffff', 5),
            ('refuse',             'Refusé',                           '#f8d7da', '#842029', 99)
        ");

        $this->addSql('ALTER TABLE sales_web_service ADD mdf_request_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales_web_service DROP COLUMN mdf_request_id');
        $this->addSql('DROP TABLE IF EXISTS mdf_history');
        $this->addSql('DROP TABLE IF EXISTS mdf_request_document');
        $this->addSql('DROP TABLE IF EXISTS mdf_request');
        $this->addSql('DROP TABLE IF EXISTS mdf_status');
    }
}
```

- [ ] **Step 13: Run the migration and verify**

Run:
```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console dbal:run-sql "SELECT code, label, order_index FROM mdf_status ORDER BY order_index"
php bin/console dbal:run-sql "DESCRIBE sales_web_service" | grep mdf_request_id
```
Expected: 6 rows from `mdf_status` in the order listed above, and `mdf_request_id` present in the `sales_web_service` DESCRIBE output.

- [ ] **Step 14: Stage the files**

```bash
git add src/Entity/MdfStatus.php src/Entity/MdfRequest.php src/Entity/MdfRequestDocument.php src/Entity/MdfHistory.php src/Repository/MdfStatusRepository.php src/Repository/MdfRequestRepository.php src/Repository/MdfRequestDocumentRepository.php src/Repository/MdfHistoryRepository.php src/Entity/SalesWebService.php src/Repository/SalesWebServiceRepository.php migrations/
```
(Do NOT commit — see Global Constraints.)

---

### Task 2: `MdfX3Service` + backlog SQL query

**Files:**
- Create: `src/Service/MdfX3Service.php`
- Create: `src/Infrastructure/Sql/Sei/mdf_backlog_client_montant.sql`

**Interfaces:**
- Consumes: `MssqlManagerFactory::create(string $dbLcs)` (existing, `%db.lcs%` for client/CA queries), `MssqlManagerFactory::create(string $dbLcsSei)` (existing, `%db.lcs_sei%` for the backlog query), `SqlFileLoader::load(string $relativePath): string` (existing)
- Produces: `MdfX3Service::searchClients(string $q): array` (same shape as SOA's: `code, nom, devise, langue, email`), `getMontantFacture(string $clientCode): ?float`, `getMontantBacklogClient(string $clientCode): ?float`

- [ ] **Step 1: Create the lightweight backlog-montant SQL query**

`backlog_client.sql` (used by Backlog Client X3) joins many tables (ATEXTRA x4, ZITMCOL, PO subquery) that are irrelevant here — we only need one aggregate number per client. Write a minimal query:

```sql
SELECT
    SUM(SOP.NETPRINOT_0 * (SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0))) AS MONTANT
FROM X3_LCS.SORDERQ SOQ
INNER JOIN X3_LCS.SORDER  SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
INNER JOIN X3_LCS.SORDERP SOP ON SOQ.SOHNUM_0 = SOP.SOHNUM_0
                               AND SOQ.ITMREF_0 = SOP.ITMREF_0
                               AND SOQ.SOPLIN_0 = SOP.SOPLIN_0
INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
WHERE
    SOQ.SOQSTA_0 <> 3
    AND SOH.ZSOHVALSTA_0 <> 3
    AND BPC.BCGCOD_0 <> 'INTER'
    AND SOH.BPCORD_0 = '{{CLIENT_CODE}}'
    AND CAST(ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) AS INT) > 0
```

Save this to `src/Infrastructure/Sql/Sei/mdf_backlog_client_montant.sql`. The `{{CLIENT_CODE}}` placeholder is replaced in PHP after manual escaping (same pattern as `Pilotage::getBacklogClient()` — never string-interpolate the raw client code without escaping single quotes).

- [ ] **Step 2: Create `MdfX3Service`**

```php
<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\MssqlManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MdfX3Service
{
    private MssqlManager $mssqlLcs;
    private MssqlManager $mssqlSei;

    public function __construct(
        MssqlManagerFactory $mssqlManagerFactory,
        private SqlFileLoader $sqlFileLoader,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    ) {
        $this->mssqlLcs = $mssqlManagerFactory->create($dbLcs);
        $this->mssqlSei = $mssqlManagerFactory->create($dbLcsSei);
    }

    public function searchClients(string $q): array
    {
        $like = str_replace("'", "''", $q);
        $sql  = "
            SELECT TOP 30
                BPC.BPCNUM_0  AS code,
                BPC.BPCNAM_0  AS nom,
                BPC.CUR_0     AS devise,
                BPR.LAN_0     AS langue,
                BPA.WEB_0     AS email
            FROM X3_LCS.BPCUSTOMER BPC
            INNER JOIN X3_LCS.BPADDRESS BPA ON BPC.BPCNUM_0 = BPA.BPANUM_0 AND BPA.BPAADD_0 = '001'
            INNER JOIN X3_LCS.BPARTNER  BPR ON BPC.BPCNUM_0 = BPR.BPRNUM_0
            WHERE (BPC.BPCNUM_0 LIKE '%{$like}%' OR BPC.BPCNAM_0 LIKE '%{$like}%')
            ORDER BY BPC.BPCNUM_0
        ";
        $rows = $this->mssqlLcs->executeQuery($sql);

        return array_map(fn($r) => [
            'code'   => trim($r->code   ?? ''),
            'nom'    => trim($r->nom    ?? ''),
            'devise' => trim($r->devise ?? 'EUR'),
            'langue' => trim($r->langue ?? ''),
            'email'  => trim($r->email  ?? ''),
        ], $rows);
    }

    public function getMontantFacture(string $clientCode): ?float
    {
        $clientCode = str_replace("'", "''", $clientCode);
        $sql        = "
            SELECT SUM(SID.AMTNOTLIN_0 * SIH.SNS_0) AS MONTANT
            FROM X3_LCS.SINVOICE  AS SIH
            JOIN X3_LCS.SINVOICED AS SID ON SIH.NUM_0 = SID.NUM_0
            WHERE SIH.BPRPAY_0   = '{$clientCode}'
              AND SIH.INVTYP_0   IN (1, 2)
              AND SIH.STA_0      = 3
              AND YEAR(SID.INVDAT_0) = YEAR(GETDATE())
        ";
        $rows = $this->mssqlLcs->executeQuery($sql);

        if (empty($rows) || $rows[0]->MONTANT === null) {
            return null;
        }

        return (float) $rows[0]->MONTANT;
    }

    public function getMontantBacklogClient(string $clientCode): ?float
    {
        $clientCode = str_replace("'", "''", $clientCode);
        $sql        = str_replace(
            '{{CLIENT_CODE}}',
            $clientCode,
            $this->sqlFileLoader->load('Sei/mdf_backlog_client_montant.sql')
        );

        $rows = $this->mssqlSei->executeQuery($sql);

        if (empty($rows) || $rows[0]->MONTANT === null) {
            return null;
        }

        return (float) $rows[0]->MONTANT;
    }
}
```

Note: `getMontantFacture()` is a verbatim copy of the logic in `SoaX3Service::getCaFactureClient()` (that class exists only on branch `soa`, unreachable from `mdf` — this duplication is intentional and required, not an oversight).

- [ ] **Step 3: Verify syntax and test the backlog query against the real database**

Run:
```bash
php -l src/Service/MdfX3Service.php
```
Expected: `No syntax errors detected`.

Then write a throwaway script (delete it after running) to confirm the SQL executes without error, following the pattern used elsewhere in this project (raw PDO against the `dblib` driver with the credentials from `.env`'s `MSSQL_LCS_SEI_URL`/`MSSQL_LCS_SEI_USER`/`MSSQL_LCS_SEI_PASS`):

```bash
cat > /tmp/test_mdf_backlog.php <<'EOF'
<?php
$pdo = new PDO('dblib:host=51.91.186.105:11433;dbname=SEICube', 'ADMIN_SEI', 'H4Vsf5xs_PSw9kVC', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = file_get_contents('/Users/ajacob/messites/intranet.lecoqsportif/src/Infrastructure/Sql/Sei/mdf_backlog_client_montant.sql');
// Pick any real client code returned by a quick SELECT TOP 1 BPCORD_0 FROM X3_LCS.SORDER if unsure which to use
$sql = str_replace('{{CLIENT_CODE}}', 'REPLACE_WITH_REAL_CLIENT_CODE', $sql);
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo "OK - "; print_r($rows);
EOF
php /tmp/test_mdf_backlog.php
rm -f /tmp/test_mdf_backlog.php
```
Expected: no SQL error, one row with a `MONTANT` key (possibly `NULL` if that client has no open backlog — pick a client code known to have backlog, e.g. reuse one seen in earlier Backlog Client X3 testing this session, or run `SELECT TOP 1 SOH.BPCORD_0 FROM X3_LCS.SORDER SOH INNER JOIN X3_LCS.SORDERQ SOQ ON SOH.SOHNUM_0=SOQ.SOHNUM_0 WHERE SOQ.SOQSTA_0<>3` first to find one).

- [ ] **Step 4: Stage the files**

```bash
git add src/Service/MdfX3Service.php src/Infrastructure/Sql/Sei/mdf_backlog_client_montant.sql
```

---

### Task 3: XML generation + cron command

**Files:**
- Modify: `src/Service/Webservice/XmlBuilder.php`
- Create: `src/Command/MdfSendXmlCommand.php`

**Interfaces:**
- Consumes: `SalesWebServiceRepository::findPendingMdf(): array` (Task 1), `MdfRequest` getters (Task 1), `SageX3Client::run(string $webserviceName, string $xmlPayload): mixed` (existing, same signature used by SOA)
- Produces: `XmlBuilder::buildMDF(\App\Entity\MdfRequest $mdf): string`

- [ ] **Step 1: Add `buildMDF()` to `XmlBuilder`**

Open `src/Service/Webservice/XmlBuilder.php`. Insert this method right before the existing `private static function forcePairedTags(...)` method (i.e. as the last public method, after `buildSolderCommande`):

```php
    public static function buildMDF(\App\Entity\MdfRequest $mdf): string
    {
        $root = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8' standalone='no'?><PARAM></PARAM>");

        $grp = $root->addChild('GRP');
        $grp->addAttribute('ID', 'INH');

        self::fld($grp, 'WSALFCY',  'SRT');
        self::fld($grp, 'WINVREF',  $mdf->getNumero());
        self::fld($grp, 'WSIVTYP',  'AVSOA');
        self::fld($grp, 'WINVDAT',  (new \DateTime())->format('Ymd'));
        self::fld($grp, 'WBPCINV',  $mdf->getClientCode());
        self::fld($grp, 'WCNOREN',  '');

        $tab = $root->addChild('TAB');
        $tab->addAttribute('ID', 'IND');

        $lin = $tab->addChild('LIN');
        $lin->addAttribute('ID', 'IND');
        $lin->addAttribute('NUM', '1');

        self::fld($lin, 'WITMREF', 'MDF');
        self::fld($lin, 'WQTY',    '1');
        self::fld($lin, 'WPRI',    (string) round((float) $mdf->getMontantMdf(), 2));
        self::fld($lin, 'WFREFLG', '1');

        return self::forcePairedTags($root->asXML());
    }
```

- [ ] **Step 2: Create `MdfSendXmlCommand`**

```php
<?php

namespace App\Command;

use App\Repository\SalesWebServiceRepository;
use App\Service\Webservice\SageX3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mdf:send-xml',
    description: 'Envoie les flux XML MDF en attente vers Sage X3.',
)]
final class MdfSendXmlCommand extends Command
{
    public function __construct(
        private SalesWebServiceRepository $wsRepo,
        private SageX3Client              $sageClient,
        private EntityManagerInterface    $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pending = $this->wsRepo->findPendingMdf();

        if (empty($pending)) {
            $io->success('Aucun flux MDF en attente.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('%d flux MDF à envoyer.', count($pending)));

        foreach ($pending as $ws) {
            $io->text(sprintf('Envoi WS id=%d (MDF request id=%s)...', $ws->getId(), $ws->getMdfRequestId() ?? '?'));

            try {
                $result = $this->sageClient->run('WSCRESIH', $ws->getParameter());

                if (isset($result->resultXml)) {
                    $ws->setResult($result->resultXml);
                    $resultXml = simplexml_load_string($result->resultXml);
                    if ($resultXml instanceof \SimpleXMLElement) {
                        $docId = (string) ($resultXml->GRP[1]?->FLD ?? '');
                        if ($docId !== '') {
                            $ws->setErpDocumentId($docId);
                            $io->text(sprintf('  → ERP Document ID : %s', $docId));
                        }
                    }
                }

                if (isset($result->messages)) {
                    $messages = array_map(fn($m) => $m->message, (array) $result->messages);
                    $ws->setMessage(implode("\n", $messages));
                }

                $ws->setExecuted(true);
                $io->success(sprintf('Flux id=%d envoyé.', $ws->getId()));

            } catch (\Throwable $e) {
                $ws->setMessage($e->getMessage());
                $io->error(sprintf('Erreur flux id=%d : %s', $ws->getId(), $e->getMessage()));
            }

            $ws->setUpdatedAt(new \DateTime());
            $this->em->flush();
        }

        return Command::SUCCESS;
    }
}
```

This is a straight copy of `SoaSendXmlCommand`'s structure (retrieved from branch `soa` for reference), renamed and pointed at `findPendingMdf()`/`getMdfRequestId()`. It is intentionally a separate command rather than a shared/generalized one, since `SoaSendXmlCommand` doesn't exist on this branch and MDF must be self-contained (see Global Constraints).

- [ ] **Step 3: Verify syntax**

```bash
php -l src/Service/Webservice/XmlBuilder.php
php -l src/Command/MdfSendXmlCommand.php
php bin/console list mdf
```
Expected: no syntax errors, and `mdf:send-xml` appears in the command list.

- [ ] **Step 4: Stage the files**

```bash
git add src/Service/Webservice/XmlBuilder.php src/Command/MdfSendXmlCommand.php
```

---

### Task 4: `MdfMailer`

**Files:**
- Create: `src/Service/MdfMailer.php`

**Interfaces:**
- Consumes: `GraphMailer::send(Email $email): void` (existing), `MdfRequest` getters (Task 1)
- Produces: `MdfMailer::sendSoumissionDirection(MdfRequest $mdf): void`, `sendValidationRepresentant(MdfRequest $mdf): void`, `sendValidationFinaleRepresentant(MdfRequest $mdf): void`, `sendRefus(MdfRequest $mdf): void`, `sendArchive(MdfRequest $mdf): void`

Note: SOA's mailer also has `sendContratClient()` which generates and emails a PDF contract to the client. MDF's spec does not mention a client-facing contract email — **omit this method and its PDF generation** (no `generateContratPdf()`, no `dompdf` dependency needed for MDF). If the user later wants a client contract email, that is a follow-up task, not part of this plan.

- [ ] **Step 1: Create `MdfMailer`**

```php
<?php

namespace App\Service;

use App\Entity\MdfRequest;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MdfMailer
{
    public function __construct(
        private GraphMailer           $graphMailer,
        private UrlGeneratorInterface $router,
        private string                $mailHeadSale,
        private string                $adminEmail,
        #[Autowire('%kernel.environment%')]
        private string $env,
    ) {}

    public function sendSoumissionDirection(MdfRequest $mdf): void
    {
        $to   = $this->resolve($this->mailHeadSale);
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Nouveau MDF en attente de validation — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "Nouveau MDF en attente de validation",
                "<p>Bonjour,</p>
                 <p>Un nouveau MDF vient d'être créé et est en attente de votre validation.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    public function sendValidationRepresentant(MdfRequest $mdf): void
    {
        $to   = $this->resolve($mdf->getRepresentant());
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Votre MDF a été validé par la Direction — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "MDF validé par la Direction",
                "<p>Bonjour,</p>
                 <p>Votre MDF a été validé par la Direction commerciale.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    public function sendValidationFinaleRepresentant(MdfRequest $mdf): void
    {
        $to   = $this->resolve($mdf->getRepresentant());
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Votre MDF est en attente de validation finale — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "MDF en attente de validation finale",
                "<p>Bonjour,</p>
                 <p>Votre MDF est désormais en attente de validation finale après soumission des preuves.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    public function sendRefus(MdfRequest $mdf): void
    {
        $to   = $this->resolve($mdf->getRepresentant());
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Votre MDF a été refusé — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "MDF refusé",
                "<p>Bonjour,</p>
                 <p>Votre MDF a été refusé par la Direction commerciale.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    public function sendArchive(MdfRequest $mdf): void
    {
        $to   = $this->resolve($mdf->getRepresentant());
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Votre MDF a été validé et archivé — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "MDF validé et archivé",
                "<p>Bonjour,</p>
                 <p>Votre MDF a été validé définitivement et archivé.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    private function resolve(string $email): string
    {
        return $this->env === 'dev' ? $this->adminEmail : $email;
    }

    private function mdfLink(MdfRequest $mdf): string
    {
        return $this->router->generate(
            'app_mdf_show',
            ['id' => $mdf->getId(), '_locale' => 'fr'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function mdfInfoBlock(MdfRequest $mdf): string
    {
        return sprintf(
            '<table style="border-collapse:collapse;margin:16px 0;font-size:13px;">
                <tr><td style="padding:4px 12px 4px 0;color:#666;">N° MDF</td><td><strong>%s</strong></td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Client</td><td>%s (%s)</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Représentant</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Type d\'activité</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Période</td><td>%s → %s</td></tr>
            </table>',
            htmlspecialchars($mdf->getNumero()),
            htmlspecialchars($mdf->getClientNom()),
            htmlspecialchars($mdf->getClientCode()),
            htmlspecialchars($mdf->getRepresentant()),
            htmlspecialchars($mdf->getTypeActivite()),
            $mdf->getDateDebut()->format('d/m/Y'),
            $mdf->getDateFin()->format('d/m/Y')
        );
    }

    private function htmlWrapper(string $title, string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;font-size:14px;color:#222;max-width:600px;margin:0 auto;padding:20px;">
            <div style="border-top:4px solid #1a3767;padding-top:20px;margin-bottom:20px;">
                <span style="font-size:18px;font-weight:bold;color:#1a3767;">Le Coq Sportif — Intranet</span>
            </div>
            <h2 style="color:#1a3767;font-size:16px;">' . $title . '</h2>
            ' . $body . '
            <hr style="margin-top:30px;border:none;border-top:1px solid #eee;">
            <p style="font-size:11px;color:#aaa;">Ce message est généré automatiquement par l\'intranet Le Coq Sportif. Merci de ne pas y répondre directement.</p>
        </body></html>';
    }

    private function btnStyle(): string
    {
        return 'display:inline-block;background:#1a3767;color:#fff;padding:10px 22px;border-radius:5px;text-decoration:none;font-weight:bold;font-size:13px;margin-top:12px;';
    }
}
```

- [ ] **Step 2: Register the `$mailHeadSale`/`$adminEmail` string arguments**

Open `config/services.yaml`. Find the existing parameter bindings for `admin.email` (used by `GraphMailer`, see `CLAUDE.md`) and check whether a `mail_head_sale` parameter already exists (the `.env` file already has `MAIL_HEAD_SALE` per project memory). Add a service definition block:

```yaml
    App\Service\MdfMailer:
        arguments:
            $mailHeadSale: '%env(MAIL_HEAD_SALE)%'
            $adminEmail: '%env(ADMIN_EMAIL)%'
```

If `SoaMailer` had an equivalent block on branch `soa`, run `git show soa:config/services.yaml | grep -A5 SoaMailer` to confirm the exact parameter names/env vars used there, and mirror them precisely instead of guessing.

- [ ] **Step 3: Verify**

```bash
php -l src/Service/MdfMailer.php
php bin/console lint:yaml config/services.yaml
php bin/console debug:container MdfMailer
```
Expected: no syntax errors, YAML lints, and the container can resolve `MdfMailer` without missing-argument errors.

- [ ] **Step 4: Stage the files**

```bash
git add src/Service/MdfMailer.php config/services.yaml
```

---

### Task 5: `MdfController`

**Files:**
- Create: `src/Controller/MdfController.php`

**Interfaces:**
- Consumes: everything produced by Tasks 1–4 (`MdfRequestRepository`, `MdfStatusRepository`, `MdfHistoryRepository`, `MdfRequestDocumentRepository`, `MdfX3Service`, `MdfMailer`, `XmlBuilder::buildMDF()`, `Helpers::convertArrayToUtf8()` — existing, used project-wide)
- Produces: routes `app_mdf_index` (`/mdf`), `app_mdf_new` (`/mdf/new`), `app_mdf_edit` (`/mdf/{id}/edit`), `app_mdf_show` (`/mdf/{id}`), `mdf_proof_download` (`/mdf/{id}/proof/{docId}/download`), `api_mdf_clients` (`/mdf/api/clients`), `api_mdf_ca_facture` (`/mdf/api/ca-facture`), `api_mdf_backlog` (`/mdf/api/backlog`), `api_mdf_mdf_total` (`/mdf/api/mdf-total`), `api_mdf_list` (`/mdf/api/list`), `api_mdf_show` (`/mdf/api/{id}`), `api_mdf_transition` (`/mdf/api/{id}/transition`, POST), `api_mdf_upload_proof` (`/mdf/api/{id}/proof/upload`, POST), `api_mdf_delete_proof` (`/mdf/api/{id}/proof/{docId}/delete`, POST), `api_mdf_submit_preuves` (`/mdf/api/{id}/submit-preuves`, POST), `api_mdf_save` (`/mdf/api/save`, POST)

- [ ] **Step 1: Create `MdfController`**

```php
<?php

namespace App\Controller;

use App\Entity\MdfHistory;
use App\Entity\MdfRequest;
use App\Entity\MdfRequestDocument;
use App\Entity\SalesWebService;
use App\Repository\MdfHistoryRepository;
use App\Repository\MdfRequestDocumentRepository;
use App\Repository\MdfRequestRepository;
use App\Repository\MdfStatusRepository;
use App\Service\MdfMailer;
use App\Service\MdfX3Service;
use App\Service\Tools\Helpers;
use App\Service\Webservice\XmlBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MdfController extends AbstractController
{
    // ── Pages ────────────────────────────────────────────────────────────────

    #[Route('/mdf', name: 'app_mdf_index')]
    public function index(): Response
    {
        return $this->render('mdf/index.html.twig');
    }

    #[Route('/mdf/new', name: 'app_mdf_new')]
    public function new(MdfRequestRepository $mdfRepo): Response
    {
        $numero = $mdfRepo->generateNumero();

        return $this->render('mdf/new.html.twig', [
            'numero'       => $numero,
            'representant' => $this->getUser()->getUserIdentifier(),
        ]);
    }

    #[Route('/mdf/{id}/edit', name: 'app_mdf_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, MdfRequestRepository $mdfRepo): Response
    {
        $mdf = $mdfRepo->find($id);

        if (!$mdf) {
            throw $this->createNotFoundException('MDF introuvable.');
        }

        $currentUser    = $this->getUser()->getUserIdentifier();
        $isManagement   = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $isRepresentant = $mdf->getRepresentant() === $currentUser;
        $statusCode     = $mdf->getStatus()->getCode();

        $mode = match($statusCode) {
            'brouillon'                                => $isRepresentant || $isManagement ? 'form'       : null,
            'attente_validation', 'attente_val_finale'  => $isManagement                    ? 'validation' : null,
            'valide_direction'                          => $isRepresentant                  ? 'preuves'    : null,
            default                                     => null,
        };

        if ($mode === null) {
            return $this->redirectToRoute('app_mdf_show', ['id' => $id]);
        }

        return $this->render('mdf/edit.html.twig', [
            'mdf_id'        => $id,
            'mode'          => $mode,
            'is_management' => $isManagement,
        ]);
    }

    #[Route('/mdf/{id}', name: 'app_mdf_show', requirements: ['id' => '\d+'])]
    public function show(int $id, MdfRequestRepository $mdfRepo): Response
    {
        $mdf = $mdfRepo->find($id);

        if (!$mdf) {
            throw $this->createNotFoundException('MDF introuvable.');
        }

        $currentUser    = $this->getUser()->getUserIdentifier();
        $isManagement   = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $isRepresentant = $mdf->getRepresentant() === $currentUser;

        return $this->render('mdf/show.html.twig', [
            'mdf'             => $mdf,
            'is_management'   => $isManagement,
            'is_representant' => $isRepresentant,
        ]);
    }

    // ── Download preuve ───────────────────────────────────────────────────────

    #[Route('/mdf/{id}/proof/{docId}/download', name: 'mdf_proof_download', requirements: ['id' => '\d+', 'docId' => '\d+'])]
    public function downloadProof(int $id, int $docId, MdfRequestDocumentRepository $docRepo): Response
    {
        $doc = $docRepo->find($docId);

        if (!$doc || $doc->getMdfRequest()->getId() !== $id) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $doc->getChemin();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $doc->getNomFichier());

        return $response;
    }

    // ── API X3 ───────────────────────────────────────────────────────────────

    #[Route('/mdf/api/clients', name: 'api_mdf_clients', methods: ['GET'])]
    public function searchClients(Request $request, MdfX3Service $x3, Helpers $helpers): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        return $this->json($helpers->convertArrayToUtf8($x3->searchClients($q)));
    }

    #[Route('/mdf/api/ca-facture', name: 'api_mdf_ca_facture', methods: ['GET'])]
    public function getCaFacture(Request $request, MdfX3Service $x3): JsonResponse
    {
        $client = trim($request->query->get('client', ''));

        if ($client === '') {
            return $this->json(['montant' => null]);
        }

        return $this->json(['montant' => $x3->getMontantFacture($client)]);
    }

    #[Route('/mdf/api/backlog', name: 'api_mdf_backlog', methods: ['GET'])]
    public function getBacklog(Request $request, MdfX3Service $x3): JsonResponse
    {
        $client = trim($request->query->get('client', ''));

        if ($client === '') {
            return $this->json(['montant' => null]);
        }

        return $this->json(['montant' => $x3->getMontantBacklogClient($client)]);
    }

    #[Route('/mdf/api/mdf-total', name: 'api_mdf_mdf_total', methods: ['GET'])]
    public function getMdfTotal(Request $request, MdfRequestRepository $mdfRepo): JsonResponse
    {
        $client    = trim($request->query->get('client', ''));
        $excludeId = $request->query->get('exclude_id');

        if ($client === '') {
            return $this->json(['montant' => 0.0]);
        }

        $montant = $mdfRepo->getMontantTotalArchiveParClient($client, $excludeId !== null ? (int) $excludeId : null);

        return $this->json(['montant' => $montant]);
    }

    #[Route('/mdf/api/list', name: 'api_mdf_list', methods: ['GET'])]
    public function list(MdfRequestRepository $mdfRepo): JsonResponse
    {
        $user    = $this->getUser()->getUserIdentifier();
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $rows    = $isAdmin ? $mdfRepo->findAllForList() : $mdfRepo->findByRepresentant($user);

        $data = array_map(fn($mdf) => [
            'id'            => $mdf->getId(),
            'numero'        => $mdf->getNumero(),
            'representant'  => $mdf->getRepresentant(),
            'client_code'   => $mdf->getClientCode(),
            'client_nom'    => $mdf->getClientNom(),
            'type_activite' => $mdf->getTypeActivite(),
            'date_debut'    => $mdf->getDateDebut()->format('Y-m-d'),
            'date_fin'      => $mdf->getDateFin()->format('Y-m-d'),
            'montant_mdf'   => (float) $mdf->getMontantMdf(),
            'statut'        => $mdf->getStatus()->getCode(),
            'statut_label'  => $mdf->getStatus()->getLabel(),
            'created_at'    => $mdf->getCreatedAt()->format('Y-m-d'),
        ], $rows);

        return $this->json($data);
    }

    #[Route('/mdf/api/{id}', name: 'api_mdf_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function apiShow(int $id, MdfRequestRepository $mdfRepo, MdfHistoryRepository $historyRepo): JsonResponse
    {
        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        $preuves = array_values(array_map(fn($doc) => [
            'id'           => $doc->getId(),
            'nom_fichier'  => $doc->getNomFichier(),
            'taille'       => $doc->getTaille(),
            'uploaded_by'  => $doc->getUploadedBy(),
            'uploaded_at'  => $doc->getUploadedAt()->format('d/m/Y H:i'),
            'download_url' => $this->generateUrl('mdf_proof_download', ['id' => $mdf->getId(), 'docId' => $doc->getId()]),
        ], array_filter($mdf->getDocuments()->toArray(), fn($d) => $d->getType() === MdfRequestDocument::TYPE_PREUVE)));

        return $this->json([
            'id'                       => $mdf->getId(),
            'numero'                   => $mdf->getNumero(),
            'titre'                    => $mdf->getTitre(),
            'representant'             => $mdf->getRepresentant(),
            'statut'                   => $mdf->getStatus()->getCode(),
            'statut_label'             => $mdf->getStatus()->getLabel(),
            'client_code'              => $mdf->getClientCode(),
            'client_nom'               => $mdf->getClientNom(),
            'client_langue'            => $mdf->getClientLangue(),
            'client_devise'            => $mdf->getClientDevise(),
            'client_emails'            => $mdf->getClientEmails(),
            'date_debut'               => $mdf->getDateDebut()->format('Y-m-d'),
            'date_fin'                 => $mdf->getDateFin()->format('Y-m-d'),
            'type_activite'            => $mdf->getTypeActivite(),
            'montant_mdf'              => (float) $mdf->getMontantMdf(),
            'montant_facture'          => $mdf->getMontantFacture() !== null ? (float) $mdf->getMontantFacture() : null,
            'montant_backlog_client'   => $mdf->getMontantBacklogClient() !== null ? (float) $mdf->getMontantBacklogClient() : null,
            'montant_mdf_total_client' => $mdf->getMontantMdfTotalClient() !== null ? (float) $mdf->getMontantMdfTotalClient() : null,
            'roi'                      => $mdf->getRoi() !== null ? (float) $mdf->getRoi() : null,
            'roi_total'                => $mdf->getRoiTotal() !== null ? (float) $mdf->getRoiTotal() : null,
            'focus_produit'            => $mdf->getFocusProduit(),
            'audience'                 => $mdf->getAudience(),
            'commentaire'              => $mdf->getCommentaire(),
            'created_at'               => $mdf->getCreatedAt()->format('d/m/Y'),
            'updated_at'               => $mdf->getUpdatedAt()->format('d/m/Y H:i'),
            'preuves'                  => $preuves,
            'historique'               => array_map(fn($h) => [
                'user'         => $h->getUser(),
                'statut'       => $h->getStatut(),
                'statut_label' => $h->getStatutLabel(),
                'date'         => $h->getCreatedAt()->format('d/m/Y H:i'),
            ], $historyRepo->findBy(['mdfRequest' => $mdf], ['createdAt' => 'ASC'])),
        ]);
    }

    // ── API Transition (valider / refuser) ────────────────────────────────────

    #[Route('/mdf/api/{id}/transition', name: 'api_mdf_transition', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transition(
        int                    $id,
        Request                $request,
        MdfRequestRepository   $mdfRepo,
        MdfStatusRepository    $statusRepo,
        MdfMailer              $mailer,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGEMENT')) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        $data   = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';

        $currentCode = $mdf->getStatus()->getCode();

        $transitions = [
            'attente_validation' => ['valider' => 'valide_direction', 'refuser' => 'refuse'],
            'attente_val_finale' => ['valider' => 'archive',          'refuser' => 'refuse'],
        ];

        if (!isset($transitions[$currentCode][$action])) {
            return $this->json(['error' => "Transition '{$action}' non autorisée depuis '{$currentCode}'."], 400);
        }

        $newCode   = $transitions[$currentCode][$action];
        $newStatus = $statusRepo->findByCode($newCode);
        if (!$newStatus) {
            return $this->json(['error' => "Statut cible inconnu : {$newCode}."], 500);
        }

        $mdf->setStatus($newStatus);
        $em->flush();

        $history = new MdfHistory();
        $history->setMdfRequest($mdf);
        $history->setUser($this->getUser()->getUserIdentifier());
        $history->setStatut($newCode);
        $history->setStatutLabel($newStatus->getLabel());
        $em->persist($history);
        $em->flush();

        $xmlError = null;
        if ($newCode === 'archive') {
            try {
                $ws = new SalesWebService();
                $ws->setName('WSCRESIH');
                $ws->setParameter(XmlBuilder::buildMDF($mdf));
                $ws->setMdfRequestId($mdf->getId());
                $em->persist($ws);
                $em->flush();
            } catch (\Throwable $e) {
                $xmlError = $e->getMessage();
            }
        }

        try {
            if ($newCode === 'valide_direction') {
                $mailer->sendValidationRepresentant($mdf);
            } elseif ($newCode === 'refuse') {
                $mailer->sendRefus($mdf);
            } elseif ($newCode === 'archive') {
                $mailer->sendArchive($mdf);
            }
        } catch (\Throwable) {}

        return $this->json([
            'statut'       => $newCode,
            'statut_label' => $newStatus->getLabel(),
            'xml_error'    => $xmlError,
        ]);
    }

    // ── API Upload preuve ─────────────────────────────────────────────────────

    #[Route('/mdf/api/{id}/proof/upload', name: 'api_mdf_upload_proof', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadProof(
        int                    $id,
        Request                $request,
        MdfRequestRepository   $mdfRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        if ($mdf->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Upload non autorisé dans ce statut.'], 403);
        }

        if ($mdf->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier reçu.'], 400);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $dir        = $projectDir . '/var/uploads/mdf/' . $id . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $originalName = $file->getClientOriginalName();
        $safeName     = uniqid('proof_') . '.' . $file->getClientOriginalExtension();
        $mimeType     = $file->getMimeType() ?? 'application/octet-stream';
        $taille       = $file->getSize() ?? 0;
        $file->move($dir, $safeName);

        $doc = new MdfRequestDocument();
        $doc->setMdfRequest($mdf);
        $doc->setType(MdfRequestDocument::TYPE_PREUVE);
        $doc->setNomFichier($originalName);
        $doc->setChemin('mdf/' . $id . '/' . $safeName);
        $doc->setMimeType($mimeType);
        $doc->setTaille($taille);
        $doc->setUploadedBy($this->getUser()->getUserIdentifier());

        $em->persist($doc);
        $em->flush();

        return $this->json([
            'id'           => $doc->getId(),
            'nom_fichier'  => $doc->getNomFichier(),
            'taille'       => $doc->getTaille(),
            'uploaded_at'  => $doc->getUploadedAt()->format('d/m/Y H:i'),
            'download_url' => $this->generateUrl('mdf_proof_download', ['id' => $id, 'docId' => $doc->getId()]),
        ]);
    }

    // ── API Delete preuve ─────────────────────────────────────────────────────

    #[Route('/mdf/api/{id}/proof/{docId}/delete', name: 'api_mdf_delete_proof', methods: ['POST'], requirements: ['id' => '\d+', 'docId' => '\d+'])]
    public function deleteProof(
        int                          $id,
        int                          $docId,
        MdfRequestRepository         $mdfRepo,
        MdfRequestDocumentRepository $docRepo,
        EntityManagerInterface       $em,
    ): JsonResponse {
        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        if ($mdf->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Suppression non autorisée dans ce statut.'], 403);
        }

        if ($mdf->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $doc = $docRepo->find($docId);
        if (!$doc || $doc->getMdfRequest()->getId() !== $id) {
            return $this->json(['error' => 'Document introuvable.'], 404);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $doc->getChemin();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $em->remove($doc);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    // ── API Soumettre les preuves (simplifié : pas de quantité, juste au moins 1 fichier) ─

    #[Route('/mdf/api/{id}/submit-preuves', name: 'api_mdf_submit_preuves', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function submitPreuves(
        int                    $id,
        MdfRequestRepository   $mdfRepo,
        MdfStatusRepository    $statusRepo,
        MdfMailer              $mailer,
        EntityManagerInterface $em,
    ): JsonResponse {
        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        if ($mdf->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Soumission non autorisée dans ce statut.'], 403);
        }

        if ($mdf->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $preuves = array_filter($mdf->getDocuments()->toArray(), fn($d) => $d->getType() === MdfRequestDocument::TYPE_PREUVE);
        if (empty($preuves)) {
            return $this->json(['error' => 'Vous devez uploader au moins un justificatif avant de soumettre.'], 400);
        }

        $newStatus = $statusRepo->findByCode('attente_val_finale');
        if (!$newStatus) {
            return $this->json(['error' => 'Statut cible introuvable.'], 500);
        }

        $mdf->setStatus($newStatus);
        $em->flush();

        $history = new MdfHistory();
        $history->setMdfRequest($mdf);
        $history->setUser($this->getUser()->getUserIdentifier());
        $history->setStatut('attente_val_finale');
        $history->setStatutLabel($newStatus->getLabel());
        $em->persist($history);
        $em->flush();

        try {
            $mailer->sendValidationFinaleRepresentant($mdf);
        } catch (\Throwable) {}

        return $this->json(['statut' => 'attente_val_finale', 'statut_label' => $newStatus->getLabel()]);
    }

    // ── API Save (brouillon / soumettre) ──────────────────────────────────────

    #[Route('/mdf/api/save', name: 'api_mdf_save', methods: ['POST'])]
    public function save(
        Request                $request,
        MdfRequestRepository   $mdfRepo,
        MdfStatusRepository    $statusRepo,
        MdfX3Service           $x3,
        MdfMailer              $mailer,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides.'], 400);
        }

        $required = ['numero', 'client_code', 'client_nom', 'date_debut', 'date_fin', 'type_activite', 'montant_mdf', 'statut'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return $this->json(['error' => "Champ obligatoire manquant : {$field}."], 400);
            }
        }

        $statusCode = $data['statut'];

        if (!in_array($statusCode, ['brouillon', 'attente_validation'], true)) {
            return $this->json(['error' => "Statut non autorisé via ce endpoint : {$statusCode}."], 400);
        }

        $status = $statusRepo->findByCode($statusCode);
        if (!$status) {
            return $this->json(['error' => "Statut inconnu : {$statusCode}."], 400);
        }

        $numero = $data['numero'];
        $mdf    = $mdfRepo->findOneBy(['numero' => $numero]);

        if (!$mdf) {
            $mdf = new MdfRequest();
            $mdf->setNumero($numero);
            $mdf->setRepresentant($this->getUser()->getUserIdentifier());
        } else {
            if ($mdf->getStatus()->getCode() !== 'brouillon') {
                return $this->json(['error' => 'Ce MDF ne peut plus être modifié.'], 403);
            }
        }

        $previousStatut = $mdf->getId() ? $mdf->getStatus()->getCode() : null;
        $clientCode     = $data['client_code'];

        $mdf->setStatus($status);
        $mdf->setClientCode($clientCode);
        $mdf->setClientNom($data['client_nom']);
        $mdf->setClientLangue($data['client_langue'] ?? '');
        $mdf->setClientDevise($data['client_devise'] ?? 'EUR');
        $mdf->setClientEmails($data['client_emails'] ?? []);
        $mdf->setTitre($data['titre'] ?? ($numero . ' — ' . $data['client_nom']));
        $mdf->setDateDebut(new \DateTime($data['date_debut']));
        $mdf->setDateFin(new \DateTime($data['date_fin']));
        $mdf->setTypeActivite($data['type_activite']);
        $mdf->setMontantMdf((string) $data['montant_mdf']);
        $mdf->setFocusProduit($data['focus_produit'] ?? null);
        $mdf->setAudience($data['audience'] ?? null);
        $mdf->setCommentaire($data['commentaire'] ?? null);

        // Recalcul serveur des montants snapshot — jamais fait confiance aux valeurs envoyées par le client
        $mdf->setMontantFacture(($v = $x3->getMontantFacture($clientCode)) !== null ? (string) $v : null);
        $mdf->setMontantBacklogClient(($v = $x3->getMontantBacklogClient($clientCode)) !== null ? (string) $v : null);
        $mdf->setMontantMdfTotalClient((string) $mdfRepo->getMontantTotalArchiveParClient($clientCode, $mdf->getId()));
        $mdf->recalculate();

        $em->persist($mdf);
        $em->flush();

        $history = new MdfHistory();
        $history->setMdfRequest($mdf);
        $history->setUser($this->getUser()->getUserIdentifier());
        $history->setStatut($mdf->getStatus()->getCode());
        $history->setStatutLabel($mdf->getStatus()->getLabel());
        $em->persist($history);
        $em->flush();

        try {
            $newStatut = $mdf->getStatus()->getCode();

            if ($newStatut === 'attente_validation' && $previousStatut !== 'attente_validation') {
                $mailer->sendSoumissionDirection($mdf);
            }
        } catch (\Throwable) {}

        return $this->json(['id' => $mdf->getId(), 'numero' => $mdf->getNumero()]);
    }
}
```

Note the deliberate divergence from SOA's `save()`: SOA trusts the client-submitted `ca_facture` value per line (`$product->setCaFactureAnnee(...$ligne['ca_facture']...)`). For MDF, `save()` **recomputes `montantFacture`/`montantBacklogClient`/`montantMdfTotalClient` server-side** on every save, ignoring whatever the client sent for those fields — this is more correct (a stale snapshot from when the client search happened, or a tampered value, can never leak into the stored record) and was not explicitly contradicted by the approved spec, which only says these are "snapshot X3 au moment du calcul" without mandating that the calcul happen client-side.

- [ ] **Step 2: Verify syntax and routing**

```bash
php -l src/Controller/MdfController.php
php bin/console debug:router | grep mdf
```
Expected: no syntax errors; all 15 routes listed above appear.

- [ ] **Step 3: Stage the file**

```bash
git add src/Controller/MdfController.php
```

---

### Task 6: Templates — `index.html.twig` and `new.html.twig`

**Files:**
- Create: `templates/mdf/index.html.twig`
- Create: `templates/mdf/new.html.twig`

**Interfaces:**
- Consumes: routes from Task 5 (`api_mdf_list`, `app_mdf_show`, `api_mdf_clients`, `api_mdf_ca_facture`, `api_mdf_backlog`, `api_mdf_mdf_total`, `api_mdf_save`), Twig variables `numero`, `representant` passed by `MdfController::new()`
- Produces: two rendered pages at `/mdf` and `/mdf/new`

- [ ] **Step 1: Create `templates/mdf/index.html.twig`**

Direct adaptation of SOA's `index.html.twig` (fetched from branch `soa`, content shown in full above during design): copy the file content, then apply these replacements throughout: `SOA` → `MDF`, `Soa` → `Mdf`, `soa` → `mdf` (including CSS class names `.soa-badge-status`/`.status-*` → `.mdf-badge-status`/keep `.status-*` suffixes as-is since they key off the same status codes, which are identical between SOA and MDF except MDF has no `attente_preuves`), route names (`api_soa_list` → `api_mdf_list`, `app_soa_show` → `app_mdf_show`, `app_soa_new` → `app_mdf_new`), title `"SOA — Sales Out Allowance"` → `"MDF — Market Development Funds"`, grid state key `aggrid-state-soa-list-user-...` → `aggrid-state-mdf-list-user-...`, and column def header `"Montant total"` (field `montant_total`, computed client-side from products) → `"Montant MDF"` (field `montant_mdf`, already a flat number from `api_mdf_list`, so **remove** the client-side `.reduce()` aggregation entirely — `api_mdf_list` in Task 5 already returns `montant_mdf` as a plain float per row, unlike SOA's `montant_total` which had to be summed from a nested `produits` array):

```javascript
const columnDefs = [
    { headerName: 'N° MDF',        field: 'numero',        width: 210, pinned: 'left',
      cellRenderer: p => `<a href="${ROUTE_MDF_SHOW.replace('/0', '/' + p.data.id)}" class="text-primary fw-semibold">${p.value}</a>` },
    { headerName: 'Représentant',  field: 'representant',  width: 180 },
    { headerName: 'Client',        field: 'client_nom',    flex: 1, minWidth: 180 },
    { headerName: 'Type activité', field: 'type_activite', width: 200 },
    { headerName: 'Date début',    field: 'date_debut',    width: 120 },
    { headerName: 'Date fin',      field: 'date_fin',      width: 120 },
    { headerName: 'Montant MDF',   field: 'montant_mdf',   width: 150,
      valueFormatter: p => p.value != null ? fmt.format(p.value) : '' },
    { headerName: 'Statut',        field: 'statut',        width: 230, cellRenderer: statusCellRenderer },
    { headerName: 'Créé le',       field: 'created_at',    width: 120 },
];
```

Also remove the `attente_preuves` entry from the `soaStatusClass`/`mdfStatusClass` map (only 6 codes: `brouillon`, `attente_validation`, `valide_direction`, `attente_val_finale`, `archive`, `refuse` — add `refuse` which SOA's index.html.twig is missing a CSS class for, using the same colors as `show.html.twig`'s `.status-refuse` rule: `background: #f8d7da; color: #842029;`).

Page title/button text: `"MDF — Market Development Funds"`, `"Nouveau MDF"`.

- [ ] **Step 2: Create `templates/mdf/new.html.twig`**

Copy SOA's `new.html.twig` in full (content captured above). Apply the global `SOA`→`MDF`/`Soa`→`Mdf`/`soa`→`mdf` renames (routes, CSS classes, JS variable names, IDs), **then** make these structural changes:

**A. Stepper (5 steps, no "En attente de preuves"):**
```html
<div class="mdf-stepper">
    <div class="mdf-step active">
        <div class="mdf-step-circle">1</div>
        <div class="mdf-step-label">Brouillon</div>
    </div>
    <div class="mdf-step">
        <div class="mdf-step-circle">2</div>
        <div class="mdf-step-label">En attente de validation</div>
    </div>
    <div class="mdf-step">
        <div class="mdf-step-circle">3</div>
        <div class="mdf-step-label">Validé par la Direction</div>
    </div>
    <div class="mdf-step">
        <div class="mdf-step-circle">4</div>
        <div class="mdf-step-label">En attente de validation finale</div>
    </div>
    <div class="mdf-step">
        <div class="mdf-step-circle"><i class="fa fa-check" style="font-size:12px;"></i></div>
        <div class="mdf-step-label">Archivé</div>
    </div>
</div>
```

**B. Replace "Bloc 1 : En-tête" body** — keep représentant/numéro/titre/dates/client search/nom/langue/devise/emails exactly as SOA has them (just renamed IDs `soa-*`→`mdf-*`), but **remove** the Focus Produit and Commentaire textareas from this block (they move to their own blocks after the activité block, per spec). The block ends right after the emails div:

```html
    {# ── Bloc 1 : En-tête + Client ── #}
    <div class="mdf-card">
        <div class="mdf-card-title">
            <i class="fa fa-info-circle"></i> En-tête
        </div>
        <div class="row g-3">

            <div class="col-md-4">
                <div class="mdf-label">Représentant</div>
                <div class="mdf-readonly" id="mdf-representant">{{ representant }}</div>
            </div>
            <div class="col-md-4">
                <div class="mdf-label">N° MDF</div>
                <div class="mdf-readonly fw-semibold" id="mdf-numero" style="color:#1a3767;">{{ numero }}</div>
            </div>
            <div class="col-md-4">
                <div class="mdf-label">Titre</div>
                <div class="mdf-readonly text-secondary fst-italic" id="mdf-titre">Généré après sélection du client</div>
            </div>

            <div class="col-md-3">
                <div class="mdf-label">Date de début <span class="text-danger">*</span></div>
                <input type="date" class="mdf-input" id="mdf-date-debut">
            </div>
            <div class="col-md-3">
                <div class="mdf-label">Date de fin <span class="text-danger">*</span></div>
                <input type="date" class="mdf-input" id="mdf-date-fin">
            </div>

            <div class="col-md-6">
                <div class="mdf-label">Code Client <span class="text-danger">*</span></div>
                <div class="mdf-search-wrap">
                    <input type="text" class="mdf-input" id="mdf-client-search"
                           placeholder="Rechercher un client (code ou nom)…" autocomplete="off">
                    <i class="fa fa-search mdf-search-icon"></i>
                    <div class="mdf-dropdown" id="mdf-client-dropdown"></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="mdf-label">Nom Client</div>
                <div class="mdf-readonly" id="mdf-client-nom">—</div>
            </div>
            <div class="col-md-2">
                <div class="mdf-label">Langue</div>
                <div class="mdf-readonly" id="mdf-client-langue">—</div>
            </div>
            <div class="col-md-2">
                <div class="mdf-label">Devise</div>
                <div class="mdf-readonly" id="mdf-client-devise">—</div>
            </div>

            <div class="col-12" id="mdf-emails-wrap" style="display:none;">
                <div class="mdf-label">Adresse email du client</div>
                <div id="mdf-email-display"></div>
            </div>

        </div>
    </div>

    {# ── Bloc 2 : Activité (remplace le bloc "Lignes articles" de SOA) ── #}
    <div class="mdf-card">
        <div class="mdf-card-title">
            <i class="fa fa-bullhorn"></i> Activité
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="mdf-label">Type d'activité <span class="text-danger">*</span></div>
                <select class="mdf-input" id="mdf-type-activite" disabled>
                    <option value="">Sélectionnez d'abord un client…</option>
                    <option value="Event - Retailer specific">Event - Retailer specific</option>
                    <option value="Print - Retailer catalog">Print - Retailer catalog</option>
                    <option value="Print - Cobranded advertising">Print - Cobranded advertising</option>
                    <option value="Print - Outdoor">Print - Outdoor</option>
                    <option value="Instore - POP">Instore - POP</option>
                    <option value="Instore - Space">Instore - Space</option>
                    <option value="Digital - Retailer specific">Digital - Retailer specific</option>
                    <option value="Digital - Cobranded">Digital - Cobranded</option>
                    <option value="S.O.A">S.O.A</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="mdf-label">Montant MDF <span class="text-danger">*</span></div>
                <input type="number" class="mdf-input" id="mdf-montant-mdf" min="0" step="0.01" placeholder="0.00" disabled>
            </div>
            <div class="col-md-2">
                <div class="mdf-label">Montant facturé</div>
                <div class="mdf-calc-field" id="mdf-montant-facture">—</div>
            </div>
            <div class="col-md-2">
                <div class="mdf-label">Montant backlog client</div>
                <div class="mdf-calc-field" id="mdf-montant-backlog">—</div>
            </div>
            <div class="col-md-2">
                <div class="mdf-label">Montant MDF total client (année)</div>
                <div class="mdf-calc-field" id="mdf-montant-total-client">—</div>
            </div>
            <div class="col-md-2">
                <div class="mdf-label">ROI</div>
                <div class="mdf-calc-field" id="mdf-roi">—</div>
            </div>
            <div class="col-md-2">
                <div class="mdf-label">ROI Total</div>
                <div class="mdf-calc-field" id="mdf-roi-total">—</div>
            </div>
        </div>
    </div>

    {# ── Bloc 3 : Focus produit / Audience / Commentaire ── #}
    <div class="mdf-card">
        <div class="mdf-card-title">
            <i class="fa fa-align-left"></i> Description
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="mdf-label">Focus Produit</div>
                <textarea class="mdf-input" id="mdf-focus" rows="2"
                          placeholder="Décrire le focus produit…" style="resize:vertical;"></textarea>
            </div>
            <div class="col-md-4">
                <div class="mdf-label">Audience</div>
                <textarea class="mdf-input" id="mdf-audience" rows="2"
                          placeholder="Décrire l'audience visée…" style="resize:vertical;"></textarea>
            </div>
            <div class="col-md-4">
                <div class="mdf-label">Commentaire</div>
                <textarea class="mdf-input" id="mdf-commentaire" rows="2"
                          placeholder="Commentaire libre (optionnel)…" style="resize:vertical;"></textarea>
            </div>
        </div>
    </div>
```

Drop the entire "Bloc 2 : Lignes articles" `<table>`/`btn-add-line` markup — there is no line table in MDF.

**C. Add a `.mdf-calc-field` CSS rule** (copy `.soa-calc-field` verbatim, rename) and remove all `.soa-lines-table`/`.btn-add-line`/`.btn-remove-line` rules (unused in MDF).

**D. Replace the JS** (client search / addLine / article search / calcRoi / collectFormData / saveSoa) with:

```javascript
const MDF_NUMERO = '{{ numero }}';

function showAlert(msg, title) {
    document.getElementById('modal-alert-title').textContent = title || 'Erreur';
    document.getElementById('modal-alert-body').innerHTML = msg;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-alert')).show();
}
function showWarning(msg, title) {
    document.getElementById('modal-warning-title').textContent = title || 'Attention';
    document.getElementById('modal-warning-body').innerHTML = msg;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-warning')).show();
}
const ROUTES = {
    clients:  '{{ path("api_mdf_clients") }}',
    caFacture:'{{ path("api_mdf_ca_facture") }}',
    backlog:  '{{ path("api_mdf_backlog") }}',
    mdfTotal: '{{ path("api_mdf_mdf_total") }}',
    save:     '{{ path("api_mdf_save") }}',
    show:     '{{ path("app_mdf_show", {id: 0}) }}',
};

let clientDevise = 'EUR';
let clientCode   = null;
let montantFacture = null;
let montantBacklog = null;
let montantMdfTotalClient = null;
let clientEmails = [];

const clientSearch   = document.getElementById('mdf-client-search');
const clientDropdown = document.getElementById('mdf-client-dropdown');

let clientSearchTimer = null;
clientSearch.addEventListener('input', () => {
    const q = clientSearch.value.trim();
    if (q.length < 2) { clientDropdown.style.display = 'none'; return; }
    clearTimeout(clientSearchTimer);
    clientSearchTimer = setTimeout(() => {
        fetch(`${ROUTES.clients}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => renderClientDropdown(data))
            .catch(() => {});
    }, 300);
});

document.addEventListener('click', e => {
    if (!clientDropdown.contains(e.target) && e.target !== clientSearch) {
        clientDropdown.style.display = 'none';
    }
});

function renderClientDropdown(items) {
    if (!items.length) { clientDropdown.style.display = 'none'; return; }
    clientDropdown.innerHTML = items.map(c =>
        `<div class="mdf-dropdown-item" data-code="${c.code}"
              data-nom="${c.nom}" data-langue="${c.langue}" data-devise="${c.devise}"
              data-email="${c.email ?? ''}">
            <span class="item-code">${c.code}</span>
            <span class="item-name">${c.nom}</span>
         </div>`
    ).join('');
    clientDropdown.style.display = 'block';
    clientDropdown.querySelectorAll('.mdf-dropdown-item').forEach(el => {
        el.addEventListener('click', () => selectClient(el));
    });
}

const fmtNum = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function selectClient(el) {
    const code   = el.dataset.code;
    const nom    = el.dataset.nom;
    const langue = el.dataset.langue;
    const devise = el.dataset.devise;
    const email  = el.dataset.email;
    const emails = email ? [email] : [];

    clientSearch.value = `${code} — ${nom}`;
    clientDevise = devise;
    clientCode   = code;
    clientDropdown.style.display = 'none';

    document.getElementById('mdf-client-nom').textContent    = nom;
    document.getElementById('mdf-client-langue').textContent = langue;
    document.getElementById('mdf-client-devise').textContent = devise;
    document.getElementById('mdf-titre').textContent = `${MDF_NUMERO} — ${nom}`;

    renderEmailList(emails);

    document.getElementById('mdf-type-activite').disabled = false;
    document.getElementById('mdf-montant-mdf').disabled   = false;

    fetchMontants(code);
}

function renderEmailList(emails) {
    clientEmails = emails;
    const wrap    = document.getElementById('mdf-emails-wrap');
    const display = document.getElementById('mdf-email-display');
    display.innerHTML = emails.length
        ? `<span class="mdf-email-display"><i class="fa fa-envelope me-2" style="color:#1a3767;font-size:11px;"></i>${emails.join(', ')}</span>`
        : '<span class="text-secondary fst-italic" style="font-size:13px;">Aucune adresse email renseignée dans X3</span>';
    wrap.style.display = '';
}

function fetchMontants(code) {
    ['mdf-montant-facture', 'mdf-montant-backlog', 'mdf-montant-total-client'].forEach(id => {
        document.getElementById(id).textContent = '…';
    });

    Promise.all([
        fetch(`${ROUTES.caFacture}?client=${encodeURIComponent(code)}`).then(r => r.json()),
        fetch(`${ROUTES.backlog}?client=${encodeURIComponent(code)}`).then(r => r.json()),
        fetch(`${ROUTES.mdfTotal}?client=${encodeURIComponent(code)}`).then(r => r.json()),
    ]).then(([ca, backlog, mdfTotal]) => {
        montantFacture         = ca.montant;
        montantBacklog         = backlog.montant;
        montantMdfTotalClient  = mdfTotal.montant ?? 0;

        document.getElementById('mdf-montant-facture').textContent =
            montantFacture !== null ? fmtNum.format(montantFacture) + ' ' + clientDevise : 'N/D';
        document.getElementById('mdf-montant-backlog').textContent =
            montantBacklog !== null ? fmtNum.format(montantBacklog) + ' ' + clientDevise : 'N/D';
        document.getElementById('mdf-montant-total-client').textContent =
            fmtNum.format(montantMdfTotalClient) + ' ' + clientDevise;

        calcRoi();
    }).catch(() => {
        ['mdf-montant-facture', 'mdf-montant-backlog', 'mdf-montant-total-client'].forEach(id => {
            document.getElementById(id).textContent = 'Erreur';
        });
    });
}

function calcRoi() {
    const montantMdf = parseFloat(document.getElementById('mdf-montant-mdf').value) || 0;
    const facture     = montantFacture || 0;
    const backlog     = montantBacklog || 0;
    const mdfTotal    = montantMdfTotalClient || 0;

    const roiEl      = document.getElementById('mdf-roi');
    const roiTotalEl = document.getElementById('mdf-roi-total');

    const denomRoi = facture + backlog;
    if (denomRoi > 0 && montantMdf > 0) {
        const roi = (montantMdf / denomRoi) * 100;
        roiEl.textContent = fmtNum.format(roi) + ' %';
        roiEl.classList.toggle('roi-alert', roi >= 5);
    } else {
        roiEl.textContent = '—';
        roiEl.classList.remove('roi-alert');
    }

    const denomRoiTotal = facture + backlog + mdfTotal;
    if (denomRoiTotal > 0 && montantMdf > 0) {
        const roiTotal = (montantMdf / denomRoiTotal) * 100;
        roiTotalEl.textContent = fmtNum.format(roiTotal) + ' %';
        roiTotalEl.classList.toggle('roi-alert', roiTotal >= 5);
    } else {
        roiTotalEl.textContent = '—';
        roiTotalEl.classList.remove('roi-alert');
    }
}

document.getElementById('mdf-montant-mdf').addEventListener('input', calcRoi);

function collectFormData(statut) {
    return {
        numero:                   MDF_NUMERO,
        statut,
        titre:                    document.getElementById('mdf-titre').textContent.trim(),
        client_code:              clientCode,
        client_nom:               document.getElementById('mdf-client-nom').textContent.trim(),
        client_langue:            document.getElementById('mdf-client-langue').textContent.trim(),
        client_devise:            clientDevise,
        client_emails:            clientEmails,
        date_debut:               document.getElementById('mdf-date-debut').value,
        date_fin:                 document.getElementById('mdf-date-fin').value,
        type_activite:            document.getElementById('mdf-type-activite').value,
        montant_mdf:              parseFloat(document.getElementById('mdf-montant-mdf').value) || 0,
        focus_produit:            document.getElementById('mdf-focus').value.trim() || null,
        audience:                 document.getElementById('mdf-audience').value.trim() || null,
        commentaire:              document.getElementById('mdf-commentaire').value.trim() || null,
    };
}

function validateForm(data) {
    if (!data.client_code)   { showWarning('Veuillez sélectionner un client.'); return false; }
    if (!data.date_debut)    { showWarning('La date de début est obligatoire.'); return false; }
    if (!data.date_fin)      { showWarning('La date de fin est obligatoire.'); return false; }
    if (!data.type_activite) { showWarning("Le type d'activité est obligatoire."); return false; }
    if (!data.montant_mdf || data.montant_mdf <= 0) { showWarning('Le montant MDF doit être supérieur à 0.'); return false; }
    return true;
}

function saveMdf(statut) {
    const data = collectFormData(statut);
    if (!validateForm(data)) return;

    const btn = statut === 'brouillon'
        ? document.getElementById('btn-save-draft')
        : document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Enregistrement…';

    fetch(ROUTES.save, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) {
            showAlert(res.error);
            btn.disabled = false;
            btn.innerHTML = statut === 'brouillon'
                ? '<i class="fa fa-save me-1"></i>Enregistrer en brouillon'
                : '<i class="fa fa-paper-plane me-1"></i>Valider — Envoyer à la Direction';
            return;
        }
        window.location.href = ROUTES.show.replace('/0', '/' + res.id);
    })
    .catch(() => {
        showAlert('Une erreur réseau est survenue.', 'Erreur réseau');
        btn.disabled = false;
    });
}

document.getElementById('btn-save-draft').addEventListener('click', () => saveMdf('brouillon'));
document.getElementById('btn-submit').addEventListener('click', () => saveMdf('attente_validation'));
```

Note the modal markup (`#modal-alert`, `#modal-warning`) referenced by `showAlert()`/`showWarning()` must exist in the template body — check `git show soa:templates/soa/new.html.twig` for these two Bootstrap modal blocks (they were not shown in the excerpt captured during design because they likely sit right before `{% endblock %}` of the body or in a separate include; locate them with `git show soa:templates/soa/new.html.twig | grep -n "modal-alert\|modal-warning"` before writing this file, and copy them verbatim with only the ID/text unchanged — they are generic Bootstrap modals, not SOA-specific).

- [ ] **Step 2: Verify Twig syntax**

```bash
php bin/console lint:twig templates/mdf/index.html.twig templates/mdf/new.html.twig
```
Expected: `[OK] All 2 Twig files contain valid syntax.`

- [ ] **Step 3: Stage the files**

```bash
git add templates/mdf/index.html.twig templates/mdf/new.html.twig
```

---

### Task 7: Template — `show.html.twig`

**Files:**
- Create: `templates/mdf/show.html.twig`

**Interfaces:**
- Consumes: route `api_mdf_show` (Task 5), Twig variables `mdf`, `is_management`, `is_representant` passed by `MdfController::show()`

- [ ] **Step 1: Create `templates/mdf/show.html.twig`**

Copy SOA's `show.html.twig` in full (content captured above). Apply the global renames, then:

**A.** `SOA_STEPS` array: same 5 steps as the `new.html.twig` stepper (no `attente_preuves`).

**B.** Remove the "Détail SOA — Lignes articles" `<table>` card entirely and replace it with an "Activité" card:

```javascript
function renderContent(d) {
    const emailsHtml = d.client_emails.length
        ? `<span class="mdf-email-display"><i class="fa fa-envelope me-2" style="font-size:11px;"></i>${d.client_emails.join(', ')}</span>`
        : '<span class="text-secondary fst-italic" style="font-size:13px;">Aucune adresse email renseignée</span>';

    const historiqueHtml = d.historique && d.historique.length > 0
        ? d.historique.map(h => {
            const cls = mdfStatusClass[h.statut] || '';
            return `<tr>
                <td>${h.user}</td>
                <td><span class="mdf-badge-status ${cls}">${h.statut_label}</span></td>
                <td>${h.date}</td>
            </tr>`;
        }).join('')
        : '<tr><td colspan="3" class="text-center text-secondary fst-italic py-2">Aucun historique.</td></tr>';

    const preuvesHtml = (d.preuves && d.preuves.length > 0)
        ? d.preuves.map(p => `
            <div class="proof-file-item">
                <i class="fa fa-file text-secondary"></i>
                <a href="${p.download_url}" class="file-name text-decoration-none">${p.nom_fichier}</a>
                <span class="file-size">${fmtSize(p.taille)}</span>
                <small class="text-secondary">${p.uploaded_by} — ${p.uploaded_at}</small>
            </div>`).join('')
        : '<p class="text-secondary fst-italic mb-0" style="font-size:13px;">Aucun justificatif déposé.</p>';

    const roiCls      = d.roi !== null && d.roi >= 5 ? 'roi-alert' : '';
    const roiTotalCls = d.roi_total !== null && d.roi_total >= 5 ? 'roi-alert' : '';

    document.getElementById('mdf-content').innerHTML = `
        <div class="mdf-card">
            <div class="mdf-card-title"><i class="fa fa-info-circle"></i> En-tête</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mdf-label">Représentant</div>
                    <div class="mdf-value">${val(d.representant)}</div>
                </div>
                <div class="col-md-4">
                    <div class="mdf-label">N° MDF</div>
                    <div class="mdf-value fw-semibold" style="color:#1a3767;">${d.numero}</div>
                </div>
                <div class="col-md-4">
                    <div class="mdf-label">Titre</div>
                    <div class="mdf-value">${val(d.titre)}</div>
                </div>
                <div class="col-md-3">
                    <div class="mdf-label">Date de début</div>
                    <div class="mdf-value">${fmtDate(d.date_debut)}</div>
                </div>
                <div class="col-md-3">
                    <div class="mdf-label">Date de fin</div>
                    <div class="mdf-value">${fmtDate(d.date_fin)}</div>
                </div>
                <div class="col-md-3">
                    <div class="mdf-label">Client</div>
                    <div class="mdf-value"><span class="fw-semibold me-1">${d.client_code}</span> ${d.client_nom}</div>
                </div>
                <div class="col-md-2">
                    <div class="mdf-label">Devise</div>
                    <div class="mdf-value">${val(d.client_devise)}</div>
                </div>
                <div class="col-12">
                    <div class="mdf-label">Adresse email</div>
                    ${emailsHtml}
                </div>
                <div class="col-12 text-end" style="font-size:11px; color:#adb5bd;">
                    Créé le ${d.created_at} — Mis à jour le ${d.updated_at}
                </div>
            </div>
        </div>

        <div class="mdf-card">
            <div class="mdf-card-title"><i class="fa fa-bullhorn"></i> Activité</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mdf-label">Type d'activité</div>
                    <div class="mdf-value">${val(d.type_activite)}</div>
                </div>
                <div class="col-md-2">
                    <div class="mdf-label">Montant MDF</div>
                    <div class="mdf-value fw-semibold" style="color:#1a3767;">${fmtNum.format(d.montant_mdf)} ${d.client_devise}</div>
                </div>
                <div class="col-md-2">
                    <div class="mdf-label">Montant facturé</div>
                    <div class="mdf-value">${d.montant_facture !== null ? fmtNum.format(d.montant_facture) + ' ' + d.client_devise : '—'}</div>
                </div>
                <div class="col-md-2">
                    <div class="mdf-label">Montant backlog client</div>
                    <div class="mdf-value">${d.montant_backlog_client !== null ? fmtNum.format(d.montant_backlog_client) + ' ' + d.client_devise : '—'}</div>
                </div>
                <div class="col-md-2">
                    <div class="mdf-label">MDF total client (année)</div>
                    <div class="mdf-value">${d.montant_mdf_total_client !== null ? fmtNum.format(d.montant_mdf_total_client) + ' ' + d.client_devise : '—'}</div>
                </div>
                <div class="col-md-2">
                    <div class="mdf-label">ROI</div>
                    <span class="mdf-calc-field ${roiCls}">${d.roi !== null ? fmtNum.format(d.roi) + ' %' : '—'}</span>
                </div>
                <div class="col-md-2">
                    <div class="mdf-label">ROI Total</div>
                    <span class="mdf-calc-field ${roiTotalCls}">${d.roi_total !== null ? fmtNum.format(d.roi_total) + ' %' : '—'}</span>
                </div>
                ${d.focus_produit ? `
                <div class="col-md-4">
                    <div class="mdf-label">Focus Produit</div>
                    <div class="mdf-value" style="white-space:pre-wrap;">${d.focus_produit}</div>
                </div>` : ''}
                ${d.audience ? `
                <div class="col-md-4">
                    <div class="mdf-label">Audience</div>
                    <div class="mdf-value" style="white-space:pre-wrap;">${d.audience}</div>
                </div>` : ''}
                ${d.commentaire ? `
                <div class="col-md-4">
                    <div class="mdf-label">Commentaire</div>
                    <div class="mdf-value" style="white-space:pre-wrap;">${d.commentaire}</div>
                </div>` : ''}
            </div>
        </div>

        <div class="mdf-card">
            <div class="mdf-card-title"><i class="fa fa-paperclip"></i> Justificatifs</div>
            ${preuvesHtml}
        </div>

        <div class="mdf-card">
            <div class="mdf-card-title"><i class="fa fa-history"></i> Historique</div>
            <table class="mdf-history-table">
                <thead>
                    <tr>
                        <th style="width:220px;">Utilisateur</th>
                        <th style="width:230px;">Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>${historiqueHtml}</tbody>
            </table>
        </div>
    `;
}
```

**C.** Update the action-button conditions in the body block (`{% if soa.status.code == ... %}` → `{% if mdf.status.code == ... %}`, `app_soa_edit`/`app_soa_index` → `app_mdf_edit`/`app_mdf_index`, `soa.id`→`mdf.id`).

**D.** `mdfStatusClass` map must include all 6 codes (`brouillon`, `attente_validation`, `valide_direction`, `attente_val_finale`, `archive`, `refuse`) with the same colors as SOA's CSS.

- [ ] **Step 2: Verify**

```bash
php bin/console lint:twig templates/mdf/show.html.twig
```
Expected: `[OK]`.

- [ ] **Step 3: Stage the file**

```bash
git add templates/mdf/show.html.twig
```

---

### Task 8: Template — `edit.html.twig` (3 modes)

**Files:**
- Create: `templates/mdf/edit.html.twig`

**Interfaces:**
- Consumes: routes `api_mdf_show`, `api_mdf_save`, `api_mdf_transition`, `api_mdf_upload_proof`, `api_mdf_delete_proof`, `mdf_proof_download`, `api_mdf_submit_preuves` (Task 5), Twig variables `mdf_id`, `mode`, `is_management`

Before writing this file, run:
```bash
git show soa:templates/soa/edit.html.twig | sed -n '236,270p'
git show soa:templates/soa/edit.html.twig | sed -n '780,1097p'
```
to read the exact `validation` and `preuves` mode bodies/JS in full (this plan's design pass read the function list and the `form` mode body, but not every line of `initValidation`/`doTransition`/`initPreuves`/`uploadFile`/`submitPreuves` — read them now to adapt precisely rather than re-deriving from the function names alone).

- [ ] **Step 1: Create `templates/mdf/edit.html.twig`** with this structure:

**Body**, three `{% if mode == %}` branches:

- `form` mode: identical structure to `new.html.twig`'s body (Bloc En-tête+Client / Bloc Activité / Bloc Description), but fields start empty/hidden and get filled by a `prefillForm(data)` JS function called after `fetch(ROUTE_API)` resolves (mirrors SOA's `edit.html.twig` `form` mode, which is `new.html.twig`'s form pre-filled from the API instead of blank). Reuse the exact activité-block HTML from Task 6 Step 1.B, and write:

```javascript
function prefillForm(data) {
    document.getElementById('mdf-representant').textContent = data.representant;
    document.getElementById('mdf-numero').textContent = data.numero;
    document.getElementById('mdf-titre').textContent = data.titre;
    document.getElementById('mdf-date-debut').value = data.date_debut;
    document.getElementById('mdf-date-fin').value = data.date_fin;

    clientCode   = data.client_code;
    clientDevise = data.client_devise;
    document.getElementById('mdf-client-search').value = `${data.client_code} — ${data.client_nom}`;
    document.getElementById('mdf-client-nom').textContent = data.client_nom;
    document.getElementById('mdf-client-langue').textContent = data.client_langue;
    document.getElementById('mdf-client-devise').textContent = data.client_devise;
    renderEmailList(data.client_emails || []);

    document.getElementById('mdf-type-activite').disabled = false;
    document.getElementById('mdf-type-activite').value = data.type_activite;
    document.getElementById('mdf-montant-mdf').disabled = false;
    document.getElementById('mdf-montant-mdf').value = data.montant_mdf;

    montantFacture        = data.montant_facture;
    montantBacklog        = data.montant_backlog_client;
    montantMdfTotalClient = data.montant_mdf_total_client ?? 0;
    document.getElementById('mdf-montant-facture').textContent =
        montantFacture !== null ? fmtNum.format(montantFacture) + ' ' + clientDevise : 'N/D';
    document.getElementById('mdf-montant-backlog').textContent =
        montantBacklog !== null ? fmtNum.format(montantBacklog) + ' ' + clientDevise : 'N/D';
    document.getElementById('mdf-montant-total-client').textContent =
        fmtNum.format(montantMdfTotalClient) + ' ' + clientDevise;
    calcRoi();

    document.getElementById('mdf-focus').value = data.focus_produit || '';
    document.getElementById('mdf-audience').value = data.audience || '';
    document.getElementById('mdf-commentaire').value = data.commentaire || '';
}
```
`collectFormData()`/`saveMdf()`/`validateForm()`/`calcRoi()`/client-search functions: identical to `new.html.twig` (Task 6), but `saveMdf()` posts the same `numero` (already existing) so the backend's `save()` updates in place rather than creating a new row — no change needed there since `MdfController::save()` already keys off `numero`.

- `validation` mode: read-only rendering (reuse `show.html.twig`'s `renderContent()` verbatim by copy-pasting it into this file — do not attempt to share code between templates, this codebase does not use shared JS partials for this pattern, confirmed by SOA doing the same duplication) plus an action bar:

```javascript
function initValidation(d) {
    const bar = document.getElementById('action-bar');
    bar.style.display = 'flex';
    document.getElementById('action-label').textContent =
        `Cette demande MDF est en attente de ${d.statut === 'attente_val_finale' ? 'validation finale' : 'validation'}.`;
}

function doTransition(action) {
    const label = action === 'valider' ? 'valider' : 'refuser';
    showConfirm(`Confirmez-vous vouloir ${label} ce MDF ?`, () => {
        fetch(`{{ path("api_mdf_transition", {id: mdf_id}) }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showAlert(res.error); return; }
            window.location.href = '{{ path("app_mdf_show", {id: mdf_id}) }}';
        })
        .catch(() => showAlert('Une erreur réseau est survenue.', 'Erreur réseau'));
    });
}
```
with buttons in the body:
```html
    {% elseif mode == 'validation' %}
    <div id="mdf-ro-content" style="display:none;"></div>
    <div id="action-bar" style="display:none;" class="mdf-card d-flex align-items-center gap-3 py-3">
        <span id="action-label" style="flex:1; font-size:13px; font-weight:600; color:#495057;"></span>
        <button class="btn btn-sm btn-mdf-valider" onclick="doTransition('valider')">
            <i class="fa fa-check me-1"></i>Valider
        </button>
        <button class="btn btn-sm btn-mdf-refuser" onclick="doTransition('refuser')">
            <i class="fa fa-times me-1"></i>Refuser
        </button>
    </div>
```

- `preuves` mode: **simplified from SOA** — no quantity inputs (SOA's `initPreuves()` renders a `<table>` with a `qte_${p.id}` number input per product line; MDF has no products, so this table is dropped entirely). Render only the upload zone + list of uploaded proofs + a single submit button:

```javascript
let currentMdfData = null;

function initPreuves(d) {
    currentMdfData = d;
    document.getElementById('preuves-block').innerHTML = `
        <div class="mdf-card">
            <div class="mdf-card-title"><i class="fa fa-paperclip"></i> Justificatifs</div>
            <div id="proofs-list">${renderProofsList()}</div>
            <div class="upload-zone mt-2" id="upload-zone" onclick="document.getElementById('proof-input').click()">
                <i class="fa fa-cloud-upload-alt me-1"></i> Cliquez pour ajouter un justificatif
            </div>
            <input type="file" id="proof-input" style="display:none;" onchange="uploadFile(this)">
        </div>
        <div class="mdf-actions">
            <button class="btn btn-primary px-5" id="btn-submit-preuves" onclick="submitPreuves()" ${d.preuves.length === 0 ? 'disabled' : ''}>
                <i class="fa fa-paper-plane me-1"></i>Soumettre pour validation finale
            </button>
        </div>
    `;
    document.getElementById('preuves-block').style.display = '';
}

function renderProofsList() {
    if (!currentMdfData.preuves.length) {
        return '<p class="text-secondary fst-italic mb-0" style="font-size:13px;">Aucun justificatif déposé.</p>';
    }
    return currentMdfData.preuves.map(p => `
        <div class="proof-file-item" id="proof-item-${p.id}">
            <i class="fa fa-file text-secondary"></i>
            <a href="${p.download_url}" class="file-name text-decoration-none">${p.nom_fichier}</a>
            <span class="file-size">${fmtSize(p.taille)}</span>
            <button class="btn-remove-line" onclick="deleteProof(${p.id})" title="Supprimer"><i class="fa fa-times"></i></button>
        </div>`).join('');
}

function checkSubmitBtn() {
    const btn = document.getElementById('btn-submit-preuves');
    if (btn) btn.disabled = currentMdfData.preuves.length === 0;
}

function uploadFile(input) {
    const file = input.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    fetch(`{{ path("api_mdf_upload_proof", {id: mdf_id}) }}`, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showAlert(res.error); return; }
            currentMdfData.preuves.push(res);
            document.getElementById('proofs-list').innerHTML = renderProofsList();
            checkSubmitBtn();
            input.value = '';
        })
        .catch(() => showAlert('Une erreur réseau est survenue.', 'Erreur réseau'));
}

function deleteProof(docId) {
    showConfirm('Supprimer ce justificatif ?', () => _doDeleteProof(docId));
}

function _doDeleteProof(docId) {
    fetch(`{{ path("api_mdf_delete_proof", {id: mdf_id, docId: 0}) }}`.replace('/0/delete', `/${docId}/delete`), { method: 'POST' })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showAlert(res.error); return; }
            currentMdfData.preuves = currentMdfData.preuves.filter(p => p.id !== docId);
            document.getElementById('proofs-list').innerHTML = renderProofsList();
            checkSubmitBtn();
        })
        .catch(() => showAlert('Une erreur réseau est survenue.', 'Erreur réseau'));
}

function submitPreuves() {
    fetch(`{{ path("api_mdf_submit_preuves", {id: mdf_id}) }}`, { method: 'POST' })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showAlert(res.error); return; }
            window.location.href = '{{ path("app_mdf_show", {id: mdf_id}) }}';
        })
        .catch(() => showAlert('Une erreur réseau est survenue.', 'Erreur réseau'));
}
```
with body:
```html
    {% elseif mode == 'preuves' %}
    <div id="mdf-ro-content" style="display:none;"></div>
    <div id="preuves-block" style="display:none;"></div>
```

**Bootstrapping** (shared across all 3 modes, at the end of the script): fetch `api_mdf_show`, then `renderStepper(data.statut)`, then branch on `{{ mode }}` (a Twig-emitted JS string constant `const MODE = '{{ mode }}';`) to call `prefillForm(data)` / `renderReadOnly(data); initValidation(data)` / `renderReadOnly(data); initPreuves(data)`.

- [ ] **Step 2: Verify**

```bash
php bin/console lint:twig templates/mdf/edit.html.twig
```
Expected: `[OK]`.

- [ ] **Step 3: Stage the file**

```bash
git add templates/mdf/edit.html.twig
```

---

### Task 9: Sidebar, StatRegistry, translations

**Files:**
- Modify: `templates/partials/_sidebar.html.twig`
- Modify: `src/Service/StatRegistry.php`
- Modify: `translations/messages.fr.yaml`
- Modify: `translations/messages.en.yaml`

**Interfaces:**
- Consumes: route `app_mdf_index` (Task 5)

- [ ] **Step 1: Add the sidebar link**

Open `templates/partials/_sidebar.html.twig`. Find line 57 (`app_sales_ventes_qte_ca_client`) and insert immediately after it:

```twig
                            {% if not is_stat_excluded('app_mdf_index') %}<li class="{% if currentRoute starts with 'app_mdf' %}active{% endif %}"><a href="{{ path('app_mdf_index') }}"><i class="icon-Commit"></i>{{ 'sidebar.stat.sales.mdf'|trans }}</a></li>{% endif %}
```

(Uses `currentRoute starts with 'app_mdf'` rather than an exact match, since MDF's `show`/`edit`/`new` sub-pages should also keep the sidebar item highlighted — check whether other multi-page stats in this sidebar use this pattern already with `grep -n "starts with" templates/partials/_sidebar.html.twig`; if none do, fall back to the exact-match style used everywhere else: `currentRoute == 'app_mdf_index'`, consistent with the rest of the file even though it means the link won't highlight on sub-pages.)

- [ ] **Step 2: Register in `StatRegistry`**

Open `src/Service/StatRegistry.php`. Find the line for `app_sales_ventes_qte_ca_client` (per `CLAUDE.md`, format `'route' => ['section' => ..., 'label' => ..., 'roles' => [...]]`) and add immediately after:

```php
            'app_mdf_index'                               => ['section' => 'Ventes',  'label' => 'MDF (Market Development Funds)',    'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
```

- [ ] **Step 3: Add translation keys**

Open `translations/messages.fr.yaml`. Find the `sidebar.stat.sales.*` block and add:
```yaml
    mdf: 'MDF'
```
Open `translations/messages.en.yaml` and add the same key (same value `MDF` — it's an acronym, no translation needed).

- [ ] **Step 4: Verify**

```bash
php bin/console lint:twig templates/partials/_sidebar.html.twig
php -l src/Service/StatRegistry.php
php bin/console lint:yaml translations/messages.fr.yaml translations/messages.en.yaml
```
Expected: no errors on any of the four checks.

- [ ] **Step 5: Stage the files**

```bash
git add templates/partials/_sidebar.html.twig src/Service/StatRegistry.php translations/messages.fr.yaml translations/messages.en.yaml
```

---

### Task 10: End-to-end manual verification

**Files:** none (verification only)

- [ ] **Step 1: Start the dev server and open the module**

```bash
symfony serve
```
Navigate to `/fr/mdf` (or via the sidebar link added in Task 9). Confirm the empty list renders without a PHP error (an empty AG Grid, no rows, is the expected state before any MDF exists).

- [ ] **Step 2: Create a brouillon**

Click "Nouveau MDF". Search for a real client (type at least 2 characters of a known client code/name), select it, confirm:
- Nom/Langue/Devise populate
- Montant facturé / Montant backlog client / Montant MDF total client all resolve to a number (or "N/D" — not stuck on "…" or "Erreur")
- Selecting a "Type d'activité" and typing a "Montant MDF" updates ROI/ROI Total live
- Fill dates, focus produit, audience, commentaire, click "Enregistrer en brouillon" — confirm redirect to `/fr/mdf/{id}` and the show page renders the Activité block with the correct numbers.

- [ ] **Step 3: Walk the full workflow with two browser sessions (or two roles)**

As `ROLE_SALES`: edit the brouillon, click "Valider — Envoyer à la Direction" (submits `attente_validation`).
As `ROLE_MANAGEMENT`/`ROLE_ADMIN`: open `/fr/mdf/{id}/edit`, confirm mode is `validation`, click "Valider" → confirm redirect to show page with status `Validé par la Direction`.
As `ROLE_SALES` (must be the original `representant`): open `/fr/mdf/{id}/edit`, confirm mode is `preuves`, upload a file, confirm the "Soumettre" button becomes enabled only after upload, submit → confirm status becomes `En attente de validation finale`.
As `ROLE_MANAGEMENT`: validate again → confirm status becomes `Archivé`.

- [ ] **Step 4: Verify the XML flow was queued**

```bash
php bin/console dbal:run-sql "SELECT id, name, executed, mdf_request_id FROM sales_web_service WHERE name='WSCRESIH' AND mdf_request_id IS NOT NULL ORDER BY id DESC LIMIT 1"
```
Expected: one row, `executed = 0`.

Do **not** run `php bin/console mdf:send-xml` against production X3 without explicit confirmation from the user — this creates a real avoir in the ERP. Confirming the row exists with the correct `WITMREF`/`WPRI` in its `parameter` XML is sufficient for this plan; ask the user before triggering an actual send.

- [ ] **Step 5: Report results to the user**

Summarize what was tested and any discrepancy found against this plan's expectations (e.g. if the real `edit.html.twig` validation/preuves JS read in Task 8 Step "before writing this file" differs meaningfully from what was assumed here). Do not commit — remind the user the branch `mdf` has all changes staged, ready for their own commit.
