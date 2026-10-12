<?php

namespace App\Tests\Security;

use App\Entity\Oc\Offer;
use App\Entity\User;
use App\Security\OfferVoter;
use App\Service\Oc\OcStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class OfferVoterTest extends TestCase
{
    private function user(int $id): User
    {
        $u = $this->createStub(User::class);
        $u->method('getId')->willReturn($id);

        return $u;
    }

    private function offer(User $owner, OcStatus $status): Offer
    {
        return (new Offer())->setCreatedBy($owner)->setStatus($status);
    }

    private function token(User $user, array $roles): TokenInterface
    {
        $t = $this->createStub(TokenInterface::class);
        $t->method('getUser')->willReturn($user);
        $t->method('getRoleNames')->willReturn($roles);

        return $t;
    }

    private function vote(string $attr, Offer $offer, User $user, array $roles): int
    {
        return (new OfferVoter())->vote($this->token($user, $roles), $offer, [$attr]);
    }

    /** @return iterable<string, array{string, OcStatus, array<string>, bool, bool}> attr, statut, rôles, propriétaire ?, attendu */
    public static function matrix(): iterable
    {
        // Propriétaire commercial
        yield 'proprio voit son OC' => [OfferVoter::VIEW, OcStatus::SUBMITTED, ['ROLE_SALES'], true, true];
        yield 'proprio modifie son brouillon' => [OfferVoter::EDIT, OcStatus::DRAFT, ['ROLE_SALES'], true, true];
        yield 'proprio ne modifie pas une soumise' => [OfferVoter::EDIT, OcStatus::SUBMITTED, ['ROLE_SALES'], true, false];
        yield 'proprio soumet son brouillon' => [OfferVoter::SUBMIT, OcStatus::DRAFT, ['ROLE_SALES'], true, true];
        yield 'proprio annule sa soumise' => [OfferVoter::CANCEL, OcStatus::SUBMITTED, ['ROLE_SALES'], true, true];
        yield 'proprio n\'annule pas une validée' => [OfferVoter::CANCEL, OcStatus::VALIDATED, ['ROLE_SALES'], true, false];
        yield 'proprio ne valide pas' => [OfferVoter::DECIDE, OcStatus::SUBMITTED, ['ROLE_SALES'], true, false];
        // Autre commercial
        yield 'autre commercial ne voit pas' => [OfferVoter::VIEW, OcStatus::DRAFT, ['ROLE_SALES'], false, false];
        yield 'autre commercial ne modifie pas' => [OfferVoter::EDIT, OcStatus::DRAFT, ['ROLE_SALES'], false, false];
        yield 'autre commercial ne soumet pas' => [OfferVoter::SUBMIT, OcStatus::DRAFT, ['ROLE_SALES'], false, false];
        // Valideur
        yield 'management voit tout' => [OfferVoter::VIEW, OcStatus::DRAFT, ['ROLE_MANAGEMENT'], false, true];
        yield 'management valide une soumise' => [OfferVoter::DECIDE, OcStatus::SUBMITTED, ['ROLE_MANAGEMENT'], false, true];
        yield 'admin valide une soumise' => [OfferVoter::DECIDE, OcStatus::SUBMITTED, ['ROLE_ADMIN'], false, true];
        yield 'management ne valide pas un brouillon' => [OfferVoter::DECIDE, OcStatus::DRAFT, ['ROLE_MANAGEMENT'], false, false];
        yield 'management ne modifie pas le brouillon d\'un autre' => [OfferVoter::EDIT, OcStatus::DRAFT, ['ROLE_MANAGEMENT'], false, false];
        yield 'management annule une validée' => [OfferVoter::CANCEL, OcStatus::VALIDATED, ['ROLE_MANAGEMENT'], false, true];
        yield 'management n\'annule pas une envoyée à X3' => [OfferVoter::CANCEL, OcStatus::SENT_TO_X3, ['ROLE_MANAGEMENT'], false, false];
        // Contrôle de gestion : consultation seule
        yield 'controlling voit' => [OfferVoter::VIEW, OcStatus::SUBMITTED, ['ROLE_CONTROLLING'], false, true];
        // PDF de l'offre : seulement une fois validée
        yield 'proprio télécharge le PDF d\'une validée' => [OfferVoter::PDF, OcStatus::VALIDATED, ['ROLE_SALES'], true, true];
        yield 'proprio pas de PDF avant validation' => [OfferVoter::PDF, OcStatus::SUBMITTED, ['ROLE_SALES'], true, false];
        yield 'pas de PDF pour une rejetée' => [OfferVoter::PDF, OcStatus::REJECTED, ['ROLE_MANAGEMENT'], false, false];
        yield 'autre commercial pas de PDF' => [OfferVoter::PDF, OcStatus::VALIDATED, ['ROLE_SALES'], false, false];
        yield 'management télécharge le PDF' => [OfferVoter::PDF, OcStatus::SENT_TO_X3, ['ROLE_MANAGEMENT'], false, true];
        yield 'management renvoie le PDF' => [OfferVoter::RESEND_PDF, OcStatus::VALIDATED, ['ROLE_MANAGEMENT'], false, true];
        yield 'proprio ne renvoie pas le PDF' => [OfferVoter::RESEND_PDF, OcStatus::VALIDATED, ['ROLE_SALES'], true, false];
        yield 'pas de renvoi avant validation' => [OfferVoter::RESEND_PDF, OcStatus::SUBMITTED, ['ROLE_MANAGEMENT'], false, false];
        yield 'controlling ne valide pas' => [OfferVoter::DECIDE, OcStatus::SUBMITTED, ['ROLE_CONTROLLING'], false, false];
    }

    #[DataProvider('matrix')]
    public function testPermissions(string $attr, OcStatus $status, array $roles, bool $isOwner, bool $expected): void
    {
        $owner = $this->user(1);
        $actor = $isOwner ? $owner : $this->user(2);

        self::assertSame(
            $expected ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
            $this->vote($attr, $this->offer($owner, $status), $actor, $roles)
        );
    }

    public function testAnonymousIsDenied(): void
    {
        $t = $this->createStub(TokenInterface::class);
        $t->method('getUser')->willReturn(null);
        $t->method('getRoleNames')->willReturn([]);

        self::assertSame(VoterInterface::ACCESS_DENIED, (new OfferVoter())->vote($t, new Offer(), [OfferVoter::VIEW]));
    }
}
