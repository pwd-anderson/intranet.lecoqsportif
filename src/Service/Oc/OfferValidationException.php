<?php

namespace App\Service\Oc;

/** Données d'OC invalides ou illisibles dans X3 : porte la liste des messages à afficher à l'utilisateur. */
final class OfferValidationException extends \RuntimeException
{
    /** @param list<string> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(implode(' | ', $errors));
    }

    /** @return list<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
