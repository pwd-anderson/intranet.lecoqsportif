<?php

namespace App\Service\Oc\Input;

/** Ligne d'OC telle que saisie par le commercial (aucune valeur X3 : elles sont relues côté serveur). */
final class OfferLineInput
{
    public function __construct(
        public readonly string $articleCode,
        public readonly float $tariff,
        public readonly ?float $msrp,
        public readonly ?float $vatRate,
        public readonly int $minQty,
        public readonly int $maxQty,
        public readonly ?string $comment,
    ) {
    }
}
