<?php

namespace App\Service\X3;

/**
 * Environnement Sage X3 : schéma lu dans le cube SEI et dossier (pool) visé par les webservices.
 */
enum X3Environment: string
{
    case PROD = 'PROD';
    case TEST = 'TEST';
    case DEV = 'DEV';

    /** Schéma du cube SEI (liste blanche : seule source de la valeur injectée dans les requêtes). */
    public function schema(): string
    {
        return match ($this) {
            self::PROD => 'X3_LCS',
            self::TEST => 'X3_LCS_TEST',
            self::DEV => 'X3_LCS_DEV',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PROD => 'X3 PROD',
            self::TEST => 'X3 TEST',
            self::DEV => 'X3 DEV',
        };
    }
}
