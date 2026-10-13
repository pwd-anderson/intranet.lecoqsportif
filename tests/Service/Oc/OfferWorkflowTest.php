<?php

namespace App\Tests\Service\Oc;

use App\Service\Oc\OcAction;
use App\Service\Oc\OcStatus;
use App\Service\Oc\OfferWorkflow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OfferWorkflowTest extends TestCase
{
    /** @return iterable<string, array{OcStatus, OcAction, OcStatus}> */
    public static function allowedProvider(): iterable
    {
        yield 'soumettre un brouillon' => [OcStatus::DRAFT, OcAction::SUBMITTED, OcStatus::SUBMITTED];
        yield 'valider' => [OcStatus::SUBMITTED, OcAction::VALIDATED, OcStatus::VALIDATED];
        yield 'rejeter' => [OcStatus::SUBMITTED, OcAction::REJECTED, OcStatus::REJECTED];
        yield 'annuler un brouillon' => [OcStatus::DRAFT, OcAction::CANCELLED, OcStatus::CANCELLED];
        yield 'annuler une soumise' => [OcStatus::SUBMITTED, OcAction::CANCELLED, OcStatus::CANCELLED];
        yield 'envoyer à X3' => [OcStatus::VALIDATED, OcAction::SENT_TO_X3, OcStatus::SENT_TO_X3];
    }

    #[DataProvider('allowedProvider')]
    public function testAllowedTransitions(OcStatus $from, OcAction $action, OcStatus $to): void
    {
        $w = new OfferWorkflow();
        self::assertTrue($w->can($from, $action));
        self::assertSame($to, $w->target($from, $action));
    }

    /** @return iterable<string, array{OcStatus, OcAction}> */
    public static function forbiddenProvider(): iterable
    {
        yield 'valider un brouillon' => [OcStatus::DRAFT, OcAction::VALIDATED];
        yield 'soumettre deux fois' => [OcStatus::SUBMITTED, OcAction::SUBMITTED];
        yield 'rejeter un brouillon' => [OcStatus::DRAFT, OcAction::REJECTED];
        yield 'rouvrir une rejetée' => [OcStatus::REJECTED, OcAction::SUBMITTED];
        yield 'annuler une validée' => [OcStatus::VALIDATED, OcAction::CANCELLED];
        yield 'annuler une rejetée' => [OcStatus::REJECTED, OcAction::CANCELLED];
        yield 'annuler une envoyée à X3' => [OcStatus::SENT_TO_X3, OcAction::CANCELLED];
        yield 'envoyer une soumise' => [OcStatus::SUBMITTED, OcAction::SENT_TO_X3];
        yield 'modifier le statut d\'une annulée' => [OcStatus::CANCELLED, OcAction::VALIDATED];
    }

    #[DataProvider('forbiddenProvider')]
    public function testForbiddenTransitions(OcStatus $from, OcAction $action): void
    {
        $w = new OfferWorkflow();
        self::assertFalse($w->can($from, $action));
        $this->expectException(\LogicException::class);
        $w->target($from, $action);
    }

    public function testActionsWithoutTransitionAreNotPossible(): void
    {
        // CREATED / UPDATED ne sont pas des changements de statut.
        self::assertFalse((new OfferWorkflow())->can(OcStatus::DRAFT, OcAction::UPDATED));
    }
}
