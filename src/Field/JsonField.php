<?php

// src/Field/JsonField.php
namespace App\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use App\Form\Type\JsonFieldType;

final class JsonField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            // C'est cette ligne qui lie le champ EasyAdmin au template Twig
            ->setTemplatePath('admin/field/json_field.html.twig')
            ->setFormType(JsonFieldType::class) // Utilise votre JsonFieldType pour le formulaire
            ->setFormTypeOptions([
                'attr' => [
                    'rows' => 10,
                    'class' => 'ea-textarea-json-editor',
                ],
                'help' => 'Saisissez un JSON valide. Exemple: {"color": "red"}',
            ]);
    }
    // Rappel: pas de méthode setFormType() customisée ici pour éviter l'erreur "Cannot use parent"
}