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
    public function convertArrayToUtf8(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->convertArrayToUtf8($value);
            }
            return $data;
        }

        if ($data instanceof \stdClass) {
            return $this->convertArrayToUtf8((array) $data);
        }

        if (is_string($data)) {
            // ✅ déjà UTF-8 valide → on ne touche PAS
            if (mb_check_encoding($data, 'UTF-8')) {
                return $data;
            }

            // ⚠️ sinon, tentative de récupération
            return mb_convert_encoding($data, 'UTF-8', 'UTF-16LE, ISO-8859-1, CP1252');
        }

        return $data;
    }

    public function variation(float $current, float $reference): float
    {
        if ($reference <= 0.0) {
            return 0.0;
        }

        return round((($current - $reference) / $reference) * 100, 1);
    }
}
