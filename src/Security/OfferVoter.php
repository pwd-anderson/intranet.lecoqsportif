<?php

namespace App\Security;

use App\Entity\Oc\Offer;
use App\Entity\User;
use App\Service\Oc\OcStatus;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur une OC. Le valideur (head commercial, comme SOA) = ROLE_ADMIN ou ROLE_MANAGEMENT ; ROLE_CONTROLLING
 * peut consulter. Un commercial ne voit et ne modifie que ses propres OC.
 */
final class OfferVoter extends Voter
{
    public const VIEW = 'OC_VIEW';
    public const EDIT = 'OC_EDIT';
    public const SUBMIT = 'OC_SUBMIT';
    public const CANCEL = 'OC_CANCEL';
    public const DECIDE = 'OC_DECIDE';
    public const PDF = 'OC_PDF';
    public const RESEND_PDF = 'OC_RESEND_PDF';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Offer
            && in_array($attribute, [self::VIEW, self::EDIT, self::SUBMIT, self::CANCEL, self::DECIDE, self::PDF, self::RESEND_PDF], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Offer $offer */
        $offer = $subject;
        $roles = $token->getRoleNames();
        $isValidator = in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_MANAGEMENT', $roles, true);
        $isViewer = $isValidator || in_array('ROLE_CONTROLLING', $roles, true);
        $isOwner = $offer->getCreatedBy() !== null && $offer->getCreatedBy()->getId() === $user->getId();
        $status = $offer->getStatus();

        return match ($attribute) {
            self::VIEW => $isOwner || $isViewer,
            self::EDIT, self::SUBMIT => $isOwner && $status === OcStatus::DRAFT,
            self::CANCEL => ($isOwner && in_array($status, [OcStatus::DRAFT, OcStatus::SUBMITTED], true))
                || ($isValidator && in_array($status, [OcStatus::DRAFT, OcStatus::SUBMITTED, OcStatus::VALIDATED], true)),
            self::DECIDE => $isValidator && $status === OcStatus::SUBMITTED,
            // Le PDF n'existe qu'une fois l'OC validée ; qui peut voir l'OC peut le télécharger, la Direction peut le renvoyer.
            self::PDF => ($isOwner || $isViewer) && in_array($status, [OcStatus::VALIDATED, OcStatus::SENT_TO_X3], true),
            self::RESEND_PDF => $isValidator && in_array($status, [OcStatus::VALIDATED, OcStatus::SENT_TO_X3], true),
            default => false,
        };
    }
}
