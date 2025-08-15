<?php

namespace App\Service\Tools;

class Helpers
{
    /**
     * Convertit récursivement tous les strings d'une structure en UTF-8
     *
     * @param mixed $data
     * @param string $fromEncoding
     * @return mixed
     */
    public function convertArrayToUtf8(mixed $data, string $fromEncoding = 'ISO-8859-1'): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->convertArrayToUtf8($value, $fromEncoding);
            }
            return $data;
        }

        if ($data instanceof \stdClass) {
            // On le convertit en array, puis on traite récursivement
            return $this->convertArrayToUtf8((array) $data, $fromEncoding);
        }

        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', $fromEncoding);
        }

        return $data;
    }
}