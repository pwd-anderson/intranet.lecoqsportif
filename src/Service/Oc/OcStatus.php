<?php

namespace App\Service\Oc;

/**
 * Statuts d'une offre commerciale (cycle de vie commun à tous les types d'OC).
 */
enum OcStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    case SENT_TO_X3 = 'sent_to_x3';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumise',
            self::VALIDATED => 'Validée',
            self::REJECTED => 'Rejetée',
            self::SENT_TO_X3 => 'Envoyée à X3',
        };
    }

    /** Classe Bootstrap du badge. */
    public function badge(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'warning',
            self::VALIDATED => 'info',
            self::REJECTED => 'danger',
            self::SENT_TO_X3 => 'success',
        };
    }
}
