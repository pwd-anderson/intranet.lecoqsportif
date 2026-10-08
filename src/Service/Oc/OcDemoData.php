<?php

namespace App\Service\Oc;

/**
 * Données factices de la version visuelle (v0) du module OC.
 *
 * À SUPPRIMER dès que les vraies sources sont branchées (requêtes X3_LCS clients / articles /
 * détection de l'existant, entités MySQL des OC). Aucune donnée réelle ici.
 */
final class OcDemoData
{
    /** @return list<array<string,mixed>> */
    public function offers(): array
    {
        return [
            ['id' => 1, 'number' => 'OC202610' . '0033911801' . '48213907', 'type' => 'Tarif Spé', 'customers' => ['0033911801 - FNAC DARTY PARTICIPATIONS'], 'lines' => 3, 'status' => OcStatus::SUBMITTED, 'createdBy' => 'Commercial démo', 'createdAt' => '2026-10-05', 'startDate' => '2026-11-01', 'endDate' => '2027-10-31'],
            ['id' => 2, 'number' => 'OC202610' . '0041250200' . '90417352', 'type' => 'Tarif Spé', 'customers' => ['0041250200 - BOULANGER SA', '0033911801 - FNAC DARTY PARTICIPATIONS'], 'lines' => 12, 'status' => OcStatus::VALIDATED, 'createdBy' => 'Commercial démo', 'createdAt' => '2026-10-02', 'startDate' => '2026-10-15', 'endDate' => '2027-10-14'],
            ['id' => 3, 'number' => 'OC202609' . '0019870400' . '10938475', 'type' => 'Tarif Spé', 'customers' => ['0019870400 - INTERSPORT FRANCE'], 'lines' => 5, 'status' => OcStatus::SENT_TO_X3, 'createdBy' => 'Commercial démo', 'createdAt' => '2026-09-18', 'startDate' => '2026-10-01', 'endDate' => '2027-09-30'],
            ['id' => 4, 'number' => 'OC202609' . '0045510000' . '66120934', 'type' => 'Tarif Spé', 'customers' => ['0045510000 - GO SPORT'], 'lines' => 2, 'status' => OcStatus::REJECTED, 'createdBy' => 'Commercial démo', 'createdAt' => '2026-09-10', 'startDate' => '2026-10-01', 'endDate' => '2027-09-30'],
            ['id' => 5, 'number' => 'OC202610' . '0050010000' . '23847561', 'type' => 'Tarif Spé', 'customers' => ['0050010000 - DECATHLON'], 'lines' => 1, 'status' => OcStatus::DRAFT, 'createdBy' => 'Commercial démo', 'createdAt' => '2026-10-06', 'startDate' => '2026-11-01', 'endDate' => '2027-10-31'],
        ];
    }

    /** @return array<string,mixed>|null */
    public function offer(int $id): ?array
    {
        foreach ($this->offers() as $offer) {
            if ($offer['id'] === $id) {
                return $offer + [
                    'comment' => 'Commentaire de démonstration.',
                    'history' => [
                        ['date' => $offer['createdAt'] . ' 09:12', 'user' => $offer['createdBy'], 'action' => 'Création (brouillon)', 'reason' => null],
                        ['date' => $offer['createdAt'] . ' 09:40', 'user' => $offer['createdBy'], 'action' => 'Soumission pour validation', 'reason' => null],
                    ],
                ];
            }
        }

        return null;
    }
}
