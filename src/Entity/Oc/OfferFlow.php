<?php

namespace App\Entity\Oc;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Flux XML préparé pour X3 à la validation d'une OC (un flux par ligne d'OC).
 *
 * Statuts : « prepared » = XML généré et conservé ici, RIEN n'est parti vers X3 (envoi désactivé) ;
 * « queued » = XML placé dans sales_web_service, exécuté par la commande app:sales:webservices:execute.
 * Ces lignes ne sont jamais modifiées après coup, hormis le passage de « prepared » à « queued ».
 */
#[ORM\Entity]
#[ORM\Table(name: 'oc_offer_flow')]
#[ORM\Index(name: 'idx_oc_offer_flow_offer', columns: ['offer_id'])]
class OfferFlow
{
    public const PREPARED = 'prepared';
    public const QUEUED = 'queued';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'flows')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Offer $offer;

    /** Code article de la ligne concernée (conservé tel quel, la ligne pouvant évoluer). */
    #[ORM\Column(length: 100)]
    private string $articleCode;

    /** Nom du webservice X3 : WSCRESPL (création d'un tarif spécial). */
    #[ORM\Column(length: 30)]
    private string $webservice;

    #[ORM\Column(type: Types::TEXT)]
    private string $xml;

    #[ORM\Column(length: 12)]
    private string $status = self::PREPARED;

    /** Identifiant de la ligne sales_web_service créée à l'envoi. */
    #[ORM\Column(nullable: true)]
    private ?int $salesWebServiceId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(Offer $offer, string $articleCode, string $webservice, string $xml)
    {
        $this->offer = $offer;
        $this->articleCode = $articleCode;
        $this->webservice = $webservice;
        $this->xml = $xml;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getOffer(): Offer { return $this->offer; }
    public function setOffer(Offer $offer): static { $this->offer = $offer; return $this; }
    public function getArticleCode(): string { return $this->articleCode; }
    public function getWebservice(): string { return $this->webservice; }
    public function getXml(): string { return $this->xml; }
    public function getStatus(): string { return $this->status; }
    public function getSalesWebServiceId(): ?int { return $this->salesWebServiceId; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function markQueued(int $salesWebServiceId): static
    {
        $this->status = self::QUEUED;
        $this->salesWebServiceId = $salesWebServiceId;

        return $this;
    }
}
