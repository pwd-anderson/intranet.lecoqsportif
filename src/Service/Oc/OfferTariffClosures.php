<?php

namespace App\Service\Oc;

/**
 * Règle de clôture des tarifs spéciaux existants quand une nouvelle OC est envoyée à X3 : le prix de l'OC la plus récente
 * remplace l'ancien, qui prend fin la VEILLE du début de la nouvelle OC (flux WSUPD7TSPE), avant la création (WSCRESPL).
 *
 * Un ancien tarif qui commence le jour du début de la nouvelle OC ou après ne peut pas être clôturé la veille (la fin serait
 * avant son propre début) : il resterait actif en même temps que le nouveau. C'est un CONFLIT, signalé et bloquant à la
 * validation (décision du 2026-10-28) : la Direction change la date de début de l'OC ou fait corriger la fiche dans X3.
 */
final class OfferTariffClosures
{
    /**
     * @param list<array{FICHE:string,CLIENT:string,ARTICLE:string,DATE_DEBUT:string,DATE_FIN:string,PRIX:float,DEVISE:string}> $existing
     *        tarifs 7TSPEC du client encore ouverts à la date de début de l'OC (OcX3Lookup::getExistingSpecialTariffs)
     *
     * @return array{closures: list<array{fiche:string,article:string,endDate:string,row:array<string,mixed>}>, conflicts: list<array<string,mixed>>}
     *         endDate = AAAAMMJJ (format du webservice) ; conflicts = les lignes X3 en cause
     */
    public static function plan(array $existing, \DateTimeInterface $offerStart): array
    {
        $start = $offerStart->format('Y-m-d');
        $endDate = \DateTimeImmutable::createFromInterface($offerStart)->modify('-1 day')->format('Ymd');

        $closures = [];
        $conflicts = [];
        foreach ($existing as $row) {
            if ($row['DATE_DEBUT'] >= $start) {
                $conflicts[] = $row;
                continue;
            }
            $closures[] = ['fiche' => $row['FICHE'], 'article' => $row['ARTICLE'], 'endDate' => $endDate, 'row' => $row];
        }

        return ['closures' => $closures, 'conflicts' => $conflicts];
    }

    /** Texte du conflit, pour la Direction. @param array<string,mixed> $row */
    public static function conflictMessage(array $row, \DateTimeInterface $offerStart): string
    {
        return sprintf(
            'Article %s : la fiche %s (prix %s %s) est déjà en place du %s au %s, elle commence le jour du début de cette OC (%s) ou après : elle ne peut pas être clôturée et resterait active en même temps que le nouveau prix. Changez la date de début de l\'OC, ou faites corriger cette fiche dans X3.',
            $row['ARTICLE'], $row['FICHE'],
            rtrim(rtrim(number_format((float) $row['PRIX'], 5, ',', ''), '0'), ','), $row['DEVISE'],
            (new \DateTimeImmutable($row['DATE_DEBUT']))->format('d/m/Y'), (new \DateTimeImmutable($row['DATE_FIN']))->format('d/m/Y'),
            $offerStart->format('d/m/Y'),
        );
    }
}
