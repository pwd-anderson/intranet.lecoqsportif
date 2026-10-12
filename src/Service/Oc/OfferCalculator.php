<?php

namespace App\Service\Oc;

/**
 * Calculs d'une ligne d'OC, refaits côté serveur (jamais pris tels quels du navigateur).
 *
 * Marge (reprise de l'ancien module, sans markup ni triple net) :
 *   PV net HT = MSRP × 100 / (100 + TVA)
 *   marge %   = (PV net HT − tarif OC) / PV net HT × 100
 * La TVA est un placeholder (20 %) et le MSRP est traité comme TTC tant que le métier n'a pas tranché.
 */
final class OfferCalculator
{
    public const DEFAULT_VAT = 20.0;

    /** Décimales du prix envoyé à X3 (comme l'ancien module : 2 ; PRI_0 en accepte 5, à valider avec X3). */
    public const INJECTED_SCALE = 2;

    public function margin(?float $msrp, ?float $vatRate, float $tariff): ?float
    {
        if ($msrp === null || $msrp <= 0) {
            return null;
        }

        $netSalePrice = $msrp * 100 / (100 + ($vatRate ?? self::DEFAULT_VAT));

        return $netSalePrice > 0 ? round(($netSalePrice - $tariff) / $netSalePrice * 100, 4) : null;
    }

    /**
     * Prix à ENVOYER à X3 (WDISPRI) pour obtenir le prix net voulu par le commercial.
     *
     * La remise client REMCLI est appliquée par X3 à TOUTES les lignes du client (règle métier du 2026-10-09,
     * modifiable : cette méthode est le seul endroit où elle vit). Le commercial saisit donc le prix NET facturé et
     * on envoie le prix « précompensé » :  prix injecté = prix net / (1 − remise/100)
     * (même principe que l'ancien module, qui divisait aussi par (1 + markup/100) ; plus de markup chez LCS).
     *
     * Le prix est arrondi à INJECTED_SCALE décimales ; comme chaque centime du prix injecté pèse moins d'un centime
     * une fois la remise appliquée, on retient, parmi les valeurs voisines, celle dont le net recalculé par X3 est
     * EXACTEMENT le prix saisi (l'ancien module arrondissait sans contrôle : écarts d'un centime possibles).
     *
     * @return array{price: float, resultingNet: float, exact: bool} exact = false si aucun arrondi ne redonne le net saisi
     *
     * @throws \InvalidArgumentException prix net ≤ 0 ou remise hors de [0 ; 100[ (division par zéro ou prix négatif)
     */
    public function injectedPrice(float $netPrice, ?float $discountPercent): array
    {
        $discount = $discountPercent ?? 0.0;
        if ($netPrice <= 0) {
            throw new \InvalidArgumentException('Le prix net doit être positif.');
        }
        if ($discount < 0 || $discount >= 100) {
            throw new \InvalidArgumentException(sprintf('Remise client invalide (%s %%) : elle doit être comprise entre 0 et 100 exclus.', $discountPercent));
        }

        $factor = 1 - $discount / 100;
        $target = round($netPrice, 2);
        $step = 10 ** -self::INJECTED_SCALE;
        $exactPrice = $netPrice / $factor;
        $base = round($exactPrice, self::INJECTED_SCALE);

        $best = null;
        foreach ([0, -1, 1, -2, 2] as $k) {
            $candidate = round($base + $k * $step, self::INJECTED_SCALE);
            if ($candidate <= 0) {
                continue;
            }
            $net = round($candidate * $factor, 2);
            $gap = abs($net - $target);
            $distance = abs($candidate - $exactPrice);
            // On privilégie le net exact, puis le prix injecté le plus proche du prix théorique.
            if ($best === null || $gap < $best['gap'] - 1e-9 || (abs($gap - $best['gap']) < 1e-9 && $distance < $best['distance'])) {
                $best = ['price' => $candidate, 'net' => $net, 'gap' => $gap, 'distance' => $distance];
            }
        }

        return ['price' => $best['price'], 'resultingNet' => $best['net'], 'exact' => $best['gap'] < 1e-9];
    }
}
