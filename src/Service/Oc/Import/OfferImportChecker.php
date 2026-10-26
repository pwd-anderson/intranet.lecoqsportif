<?php

namespace App\Service\Oc\Import;

use App\Entity\Oc\OfferLine;
use App\Service\Oc\OcX3Lookup;

/**
 * Contrôle des lignes lues par OfferImportReader contre X3, pour le client de l'écran : l'article existe (parent ou taille) et, pour
 * une taille, elle existe bien chez son parent. Mêmes règles que l'enregistrement d'une OC (OfferService::save), pour qu'une ligne
 * annoncée valide ne soit pas refusée ensuite.
 *
 * Une lecture X3 qui ne répond pas n'est jamais présentée comme « article inconnu » quand on peut le distinguer : elle donne un
 * message « X3 indisponible » (les tailles) ou « introuvable (ou X3 indisponible) » (les articles, dont la requête ne distingue
 * pas les deux cas, comme à l'enregistrement).
 */
final class OfferImportChecker
{
    public function __construct(private readonly OcX3Lookup $x3)
    {
    }

    /**
     * @param list<array{line:int,article:string,tariff:?float,minQty:?int,maxQty:?int,comment:?string,errors:list<string>}> $rows
     *
     * @return list<array<string,mixed>> les mêmes lignes + status (ok / error), errors, label, collection, x3Tariff, x3Msrp
     *
     * @throws OfferImportException client introuvable dans X3
     */
    public function check(string $customerCode, array $rows): array
    {
        $customerCode = trim($customerCode);
        if ($customerCode === '' || $this->x3->getCustomer($customerCode) === null) {
            throw new OfferImportException('Client introuvable dans X3 (ou X3 indisponible) : sélectionnez le client avant d\'importer.');
        }

        $codes = [];
        foreach ($rows as $r) {
            if ($r['article'] !== '' && mb_strlen($r['article']) <= 50) {
                $codes[] = $r['article'];
            }
        }
        $prices = $codes === [] ? [] : $this->x3->getArticlesPrices($customerCode, $codes);

        // Tailles : une seule lecture pour tous les parents concernés.
        $parents = [];
        foreach ($codes as $code) {
            if (str_contains($code, '_')) {
                $parents[] = OfferLine::parentOf($code);
            }
        }
        $sizes = [];
        $sizesDown = false;
        if ($parents !== []) {
            try {
                $sizes = $this->x3->getVariants(array_values(array_unique($parents)));
            } catch (\Throwable) {
                $sizesDown = true;
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $errors = $r['errors'];
            $code = $r['article'];
            $x3 = $code !== '' ? ($prices[$code] ?? null) : null;

            if ($code !== '' && !$this->hasArticleError($errors)) {
                if ($x3 === null) {
                    $errors[] = str_contains($code, '_')
                        ? sprintf('Taille %s introuvable dans X3 (ou X3 indisponible).', $code)
                        : 'Article introuvable dans X3 (ou X3 indisponible).';
                } elseif (str_contains($code, '_')) {
                    if ($sizesDown) {
                        $errors[] = 'Taille non contrôlable : X3 indisponible, réessayez.';
                    } elseif (!in_array($code, $sizes[OfferLine::parentOf($code)] ?? [], true)) {
                        $errors[] = sprintf('La taille %s n\'existe pas pour l\'article %s dans X3.', $code, OfferLine::parentOf($code));
                    }
                }
            }

            $num = static fn (mixed $v): ?float => $v === null || $v === '' ? null : (float) $v;
            $out[] = $r + [
                'status' => $errors === [] ? 'ok' : 'error',
                'label' => $x3['DESIGNATION'] ?? null,
                'collection' => $x3['COLLECTION'] ?? null,
                'x3Tariff' => $num($x3['TARIF'] ?? null),
                'x3Msrp' => $num($x3['MSRP'] ?? null),
                'isSize' => str_contains($code, '_'),
            ];
            $out[count($out) - 1]['errors'] = $errors;
        }

        return $out;
    }

    /** Le code article lui-même est déjà en erreur (manquant, trop long, doublon) : inutile d'interroger X3 pour lui. */
    private function hasArticleError(array $errors): bool
    {
        foreach ($errors as $e) {
            if (str_starts_with($e, 'Article ') || str_starts_with($e, 'Code article')) {
                return true;
            }
        }

        return false;
    }
}
