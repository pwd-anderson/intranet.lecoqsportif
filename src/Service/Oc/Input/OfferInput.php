<?php

namespace App\Service\Oc\Input;

use App\Service\Oc\OfferValidationException;

/** Données d'une OC reçues du navigateur, validées et typées. Construire via fromArray(). */
final class OfferInput
{
    public const MAX_LINES = 200;
    public const MAX_COMMENT = 500;
    public const MAX_QTY = 999999;

    /** @param list<OfferLineInput> $lines */
    private function __construct(
        public readonly string $customerCode,
        public readonly \DateTimeImmutable $startDate,
        public readonly \DateTimeImmutable $endDate,
        public readonly ?string $comment,
        public readonly array $lines,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     *
     * @throws OfferValidationException avec TOUTES les erreurs trouvées (pas seulement la première)
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        $customerCode = trim((string) ($data['customerCode'] ?? ''));
        if ($customerCode === '') {
            $errors[] = 'Le client est obligatoire.';
        } elseif (mb_strlen($customerCode) > 30) {
            $errors[] = 'Code client trop long.';
        }

        $start = self::date($data['startDate'] ?? null);
        $end = self::date($data['endDate'] ?? null);
        if ($start === null) {
            $errors[] = 'La date de début est invalide.';
        }
        if ($end === null) {
            $errors[] = 'La date de fin est invalide.';
        }
        if ($start !== null && $end !== null && $end < $start) {
            $errors[] = 'La date de fin doit être postérieure ou égale à la date de début.';
        }

        $comment = self::text($data['comment'] ?? null);
        if ($comment !== null && mb_strlen($comment) > self::MAX_COMMENT) {
            $errors[] = 'Le commentaire dépasse ' . self::MAX_COMMENT . ' caractères.';
        }

        $rawLines = $data['lines'] ?? null;
        $lines = [];
        if (!is_array($rawLines) || $rawLines === []) {
            $errors[] = 'Ajoutez au moins un article.';
        } elseif (count($rawLines) > self::MAX_LINES) {
            $errors[] = 'Une OC ne peut pas dépasser ' . self::MAX_LINES . ' articles.';
        } else {
            $seen = [];
            foreach (array_values($rawLines) as $i => $raw) {
                $n = $i + 1;
                if (!is_array($raw)) {
                    $errors[] = "Ligne $n : format invalide.";
                    continue;
                }

                $article = trim((string) ($raw['articleCode'] ?? ''));
                if ($article === '' || mb_strlen($article) > 50) {
                    $errors[] = "Ligne $n : article invalide.";
                    continue;
                }
                if (isset($seen[$article])) {
                    $errors[] = "Ligne $n : l'article $article est en double.";
                    continue;
                }
                $seen[$article] = true;

                $tariff = self::number($raw['tariff'] ?? null);
                if ($tariff === null || $tariff <= 0 || $tariff > 9999999) {
                    $errors[] = "Article $article : le tarif OC est obligatoire et doit être positif.";
                    continue;
                }

                $msrp = self::number($raw['msrp'] ?? null);
                if (($raw['msrp'] ?? '') !== '' && ($raw['msrp'] ?? null) !== null && ($msrp === null || $msrp < 0)) {
                    $errors[] = "Article $article : MSRP invalide.";
                    continue;
                }

                $vat = self::number($raw['vatRate'] ?? null);
                if ($vat !== null && ($vat < 0 || $vat > 100)) {
                    $errors[] = "Article $article : TVA invalide.";
                    continue;
                }

                $min = self::integer($raw['minQty'] ?? 0);
                $max = self::integer($raw['maxQty'] ?? self::MAX_QTY);
                if ($min === null || $max === null || $min < 0 || $max < 0 || $max > self::MAX_QTY * 1000) {
                    $errors[] = "Article $article : quantités invalides.";
                    continue;
                }
                if ($min > $max) {
                    $errors[] = "Article $article : la quantité min dépasse la quantité max.";
                    continue;
                }

                $lineComment = self::text($raw['comment'] ?? null);
                if ($lineComment !== null && mb_strlen($lineComment) > self::MAX_COMMENT) {
                    $errors[] = "Article $article : commentaire trop long.";
                    continue;
                }

                $lines[] = new OfferLineInput($article, $tariff, $msrp, $vat, $min, $max, $lineComment);
            }
        }

        if ($errors !== []) {
            throw new OfferValidationException($errors);
        }

        return new self($customerCode, $start, $end, $comment, $lines);
    }

    private static function date(mixed $v): ?\DateTimeImmutable
    {
        if (!is_string($v) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v);
        $err = \DateTimeImmutable::getLastErrors();

        return $d !== false && ($err === false || ($err['warning_count'] === 0 && $err['error_count'] === 0)) ? $d : null;
    }

    private static function number(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_string($v)) {
            $v = str_replace([' ', ','], ['', '.'], trim($v));
        }

        return is_numeric($v) ? (float) $v : null;
    }

    private static function integer(mixed $v): ?int
    {
        $n = self::number($v);

        return $n !== null && $n == floor($n) ? (int) $n : null;
    }

    private static function text(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }
}
