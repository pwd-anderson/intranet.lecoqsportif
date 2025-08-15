<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class JsonToStringTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): mixed
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $value;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return $decoded ?? [];
    }
}