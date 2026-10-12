<?php

namespace App\Service\Oc;

use App\Repository\Oc\OfferRepository;

/**
 * Numéro d'OC : « OC » + AAAAMM + code client + 8 chiffres aléatoires (ex. OC20261020548 + 48213907).
 * Unicité garantie par un contrôle en base + contrainte UNIQUE ; le numéro reste ≤ 30 caractères pour un code
 * client ≤ 14 (limite du champ référence des lignes de tarif X3).
 */
class OfferNumberGenerator
{
    private const MAX_ATTEMPTS = 10;

    public function __construct(private readonly OfferRepository $offers)
    {
    }

    public function generate(string $customerCode, ?\DateTimeImmutable $now = null): string
    {
        $prefix = 'OC' . ($now ?? new \DateTimeImmutable())->format('Ym') . preg_replace('/[^A-Za-z0-9]/', '', $customerCode);

        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $number = $prefix . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            if (!$this->offers->numberExists($number)) {
                return $number;
            }
        }

        throw new \RuntimeException('Impossible de générer un numéro d\'OC unique.');
    }
}
