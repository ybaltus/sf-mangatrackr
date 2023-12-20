<?php

namespace App\Security\Voter;

use App\Entity\MangaUserTrack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * TO avoid MangaUserTrackVoter extends generic class Voter but does not specify its types: TAttribute, TSubject.
 *
 * @phpstan-ignore-next-line
 */
class MangaUserTrackVoter extends Voter
{
    public const EDIT = 'mut_edit';
    public const DELETE = 'mut_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof MangaUserTrack;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::EDIT, self::DELETE => $this->canEdit($subject, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canEdit(MangaUserTrack $mut, UserInterface $user): bool
    {
        return $mut->getUserTrackList()->getUser() === $user;
    }
}
