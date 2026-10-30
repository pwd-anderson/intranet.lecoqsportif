<?php

namespace App\Service\Oc;

/**
 * Règle de clôture des tarifs spéciaux existants quand une nouvelle OC est envoyée à X3 : le prix de l'OC la plus récente
 * remplace l'ancien, qui prend fin la VEILLE du début de la nouvelle OC (flux WSUPD7TSPE), avant la création (WSCRESPL).
 *
 * Un ancien tarif qui commence le jour du début de la nouvelle OC ou après est signalé comme « conflit » : sa fin (la veille)
 * serait avant son propre début. Ce n'est PLUS bloquant (décision du 2026-10-30) : la clôture part quand même et X3 l'accepte
 * ou la refuse ; le conflit n'est qu'un avertissement (fenêtre à la soumission).
 */
final class OfferTariffClosures
{
    /**
     * @param list<array{FICHE:string,CLIENT:string,ARTICLE:string,DATE_DEBUT:string,DATE_FIN:string,PRIX:float,DEVISE:string}> $existing
     *        tarifs 7TSPEC du client encore ouverts à la date de début de l'OC (OcX3Lookup::getExistingSpecialTariffs)
     *
     * @return array{closures: list<array{fiche:string,article:string,endDate:string,row:array<string,mixed>}>, conflicts: list<array<string,mixed>>}
     *         closures = TOUTES les fiches ouvertes (endDate = AAAAMMJJ, format du webservice) ; conflicts = celles qui commencent
     *         le jour du début de l'OC ou après (sous-ensemble de closures, pour l'avertissement)
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
            }
            $closures[] = ['fiche' => $row['FICHE'], 'article' => $row['ARTICLE'], 'endDate' => $endDate, 'row' => $row];
        }

        return ['closures' => $closures, 'conflicts' => $conflicts];
    }

    /** Texte de l'avertissement pour un tarif qui commence le jour du début de l'OC ou après. @param array<string,mixed> $row */
    public static function conflictMessage(array $row, \DateTimeInterface $offerStart): string
    {
        return sprintf(
            'Article %s : la fiche %s (prix %s %s) est déjà en place du %s au %s, elle commence le jour du début de cette OC (%s) ou après : sa clôture la veille est antérieure à son propre début : X3 peut la refuser et la fiche resterait alors active en même temps que le nouveau prix.',
            $row['ARTICLE'], $row['FICHE'],
            rtrim(rtrim(number_format((float) $row['PRIX'], 5, ',', ''), '0'), ','), $row['DEVISE'],
            (new \DateTimeImmutable($row['DATE_DEBUT']))->format('d/m/Y'), (new \DateTimeImmutable($row['DATE_FIN']))->format('d/m/Y'),
            $offerStart->format('d/m/Y'),
        );
    }
}
