<?php

namespace App\Service\Oc;

/** Actions tracées dans l'historique d'une OC (table oc_offer_history, ajout seulement). */
enum OcAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case SUBMITTED = 'submitted';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case SENT_TO_X3 = 'sent_to_x3';
    case X3_ERROR = 'x3_error';
    case PDF_SENT = 'pdf_sent';
    case PDF_FAILED = 'pdf_failed';
    case X3_FLOW_PREPARED = 'x3_flow_prepared';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Création (brouillon)',
            self::UPDATED => 'Modification',
            self::SUBMITTED => 'Soumission pour validation',
            self::VALIDATED => 'Validation',
            self::REJECTED => 'Rejet',
            self::CANCELLED => 'Annulation',
            self::SENT_TO_X3 => 'Envoi à X3',
            self::X3_ERROR => 'Erreur d\'envoi à X3',
            self::PDF_SENT => 'PDF envoyé au commercial',
            self::PDF_FAILED => 'Échec de l\'envoi du PDF',
            self::X3_FLOW_PREPARED => 'Flux X3 préparé',
        };
    }
}
