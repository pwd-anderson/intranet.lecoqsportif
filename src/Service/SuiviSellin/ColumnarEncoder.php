<?php

namespace App\Service\SuiviSellin;

/**
 * Encodage « colonnes » attendu par le JavaScript du template (public/assets/suivi_sellin/suivi.js) : des tableaux
 * typés mis bout à bout (float32, int32, uint16, uint8), compressés en gzip puis en base64. Le navigateur les
 * relit avec DecompressionStream + Float32Array / Int32Array / Uint16Array / Uint8Array. Little-endian.
 */
final class ColumnarEncoder
{
    public const NR = '(non renseigné)';

    /** @param list<float|int> $values */
    public static function float32(array $values): string
    {
        return self::pack('g*', $values);
    }

    /** @param list<int> $values */
    public static function int32(array $values): string
    {
        return self::pack('V*', $values);
    }

    /** @param list<int> $values */
    public static function uint16(array $values): string
    {
        return self::pack('v*', $values);
    }

    /** @param list<int> $values */
    public static function uint8(array $values): string
    {
        return self::pack('C*', $values);
    }

    public static function gzipBase64(string $binary): string
    {
        return base64_encode(gzencode($binary, 6));
    }

    /** @param list<float|int> $values */
    private static function pack(string $format, array $values): string
    {
        // pack() prend ses valeurs en arguments : par paquets pour ne pas dépasser les limites d'arguments.
        $out = '';
        foreach (array_chunk($values, 20000) as $chunk) {
            $out .= pack($format, ...$chunk);
        }

        return $out;
    }

    /**
     * Dictionnaire à valeurs distinctes : ajoute la valeur si elle est nouvelle et renvoie son indice provisoire
     * (ordre d'apparition). L'ordre définitif s'obtient avec finalOrder().
     *
     * @param array<string,int> $dict
     */
    public static function index(array &$dict, string $value): int
    {
        return $dict[$value] ??= count($dict);
    }

    /**
     * Ordre définitif d'un dictionnaire : tri binaire croissant, « (non renseigné) » en dernier (comme le template).
     * Renvoie [liste ordonnée, table de conversion indice provisoire => indice définitif].
     *
     * @param array<string,int> $dict valeur => indice provisoire
     * @param list<string>|null $fixedOrder ordre imposé (familles, périmètre…) : les valeurs absentes en fin de liste
     * @return array{0:list<string>, 1:array<int,int>}
     */
    public static function finalOrder(array $dict, ?array $fixedOrder = null): array
    {
        // array_keys() rend des entiers pour les clés numériques (« 3035 ») : le JavaScript attend des chaînes.
        $values = array_map('strval', array_keys($dict));
        if ($fixedOrder !== null) {
            $rank = array_flip($fixedOrder);
            usort($values, static fn (string $a, string $b): int => ($rank[$a] ?? PHP_INT_MAX) <=> ($rank[$b] ?? PHP_INT_MAX) ?: strcmp($a, $b));
        } else {
            usort($values, static function (string $a, string $b): int {
                if ($a === self::NR) {
                    return 1;
                }
                if ($b === self::NR) {
                    return -1;
                }

                return strcmp($a, $b);
            });
        }
        $map = [];
        foreach ($values as $newIndex => $value) {
            $map[$dict[$value]] = $newIndex;
        }

        return [$values, $map];
    }

    /**
     * Ordre définitif d'un dictionnaire du backlog : tri binaire croissant tel quel (« (non renseigné) » et la valeur
     * vide passent donc en tête, comme dans les dictionnaires du template).
     *
     * @param array<string,int> $dict valeur => indice provisoire
     * @return array{0:list<string>, 1:array<int,int>}
     */
    public static function plainOrder(array $dict): array
    {
        // array_keys() rend des entiers pour les clés numériques (« 3035 ») : le JavaScript attend des chaînes.
        $values = array_map('strval', array_keys($dict));
        usort($values, static fn (string $a, string $b): int => strcmp($a, $b));
        $map = [];
        foreach ($values as $newIndex => $value) {
            $map[$dict[$value]] = $newIndex;
        }

        return [$values, $map];
    }
}
