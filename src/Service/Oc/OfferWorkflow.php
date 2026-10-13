<?php

namespace App\Service\Oc;

/**
 * Règles de transition de statut d'une OC (une seule validation : head commercial). Aucune dépendance :
 * qui a le droit de déclencher une action est décidé ailleurs (OfferVoter), ici seulement ce qui est possible.
 */
final class OfferWorkflow
{
    /** @var array<string, array{from: list<OcStatus>, to: OcStatus}> */
    private const TRANSITIONS = [
        'submitted' => ['from' => [OcStatus::DRAFT], 'to' => OcStatus::SUBMITTED],
        'validated' => ['from' => [OcStatus::SUBMITTED], 'to' => OcStatus::VALIDATED],
        'rejected' => ['from' => [OcStatus::SUBMITTED], 'to' => OcStatus::REJECTED],
        'cancelled' => ['from' => [OcStatus::DRAFT, OcStatus::SUBMITTED], 'to' => OcStatus::CANCELLED],
        'sent_to_x3' => ['from' => [OcStatus::VALIDATED], 'to' => OcStatus::SENT_TO_X3],
    ];

    public function can(OcStatus $from, OcAction $action): bool
    {
        $t = self::TRANSITIONS[$action->value] ?? null;

        return $t !== null && in_array($from, $t['from'], true);
    }

    /** @throws \LogicException si la transition est interdite */
    public function target(OcStatus $from, OcAction $action): OcStatus
    {
        if (!$this->can($from, $action)) {
            throw new \LogicException(sprintf('Action « %s » impossible depuis le statut « %s ».', $action->label(), $from->label()));
        }

        return self::TRANSITIONS[$action->value]['to'];
    }
}
