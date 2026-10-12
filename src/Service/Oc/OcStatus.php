<?php

namespace App\Service\Oc;

/**
 * Statuts d'une offre commerciale (cycle de vie commun à tous les types d'OC).
 *
 * Une OC n'est jamais supprimée : elle devient « Annulée ». Un rejet est définitif : le commercial crée
 * une nouvelle OC (liée à l'OC rejetée), il ne ré-édite pas la même.
 */
enum OcStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case SENT_TO_X3 = 'sent_to_x3';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumise',
            self::VALIDATED => 'Validée',
            self::REJECTED => 'Rejetée',
            self::CANCELLED => 'Annulée',
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
            self::CANCELLED => 'dark',
            self::SENT_TO_X3 => 'success',
        };
    }

    /** Statuts qui bloquent toute nouvelle OC pour le même client et article (règle doublon, à brancher). */
    public function isPending(): bool
    {
        return $this === self::SUBMITTED || $this === self::VALIDATED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::REJECTED, self::CANCELLED, self::SENT_TO_X3], true);
    }
}
