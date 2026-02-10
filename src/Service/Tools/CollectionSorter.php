<?php

namespace App\Service\Tools;

final class CollectionSorter
{
    /**
     * Tri décroissant sur une clé simple (ex: amount_n)
     */
    public static function sortDescByKey(array &$items, string $key): void
    {
        usort($items, static function (array $a, array $b) use ($key) {
            return ((float) ($b[$key] ?? 0)) <=> ((float) ($a[$key] ?? 0));
        });
    }

    /**
     * Tri décroissant sur une clé imbriquée (ex: ['ca', 'reel'])
     */
    public static function sortDescByPath(array &$items, array $path): void
    {
        usort($items, static function (array $a, array $b) use ($path) {
            return self::getValueByPath($b, $path)
                <=> self::getValueByPath($a, $path);
        });
    }

    private static function getValueByPath(array $data, array $path): float
    {
        foreach ($path as $key) {
            if (!isset($data[$key])) {
                return 0.0;
            }
            $data = $data[$key];
        }

        return (float) $data;
    }
}
