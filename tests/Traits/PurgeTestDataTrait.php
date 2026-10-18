<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Entity\User;
use App\Entity\UserInvitationCode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Remove the rows a test created so that the suite stays replayable (ADR 0007).
 *
 * Must be used by a test case that booted the kernel.
 */
trait PurgeTestDataTrait
{
    /**
     * Delete the invitation codes then the users (and their cascaded track list / news) by email.
     *
     * @param list<string> $emails
     */
    private function purgeUsersAndInvitationsByEmail(array $emails): void
    {
        if ([] === $emails) {
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        // The invitation code holds the foreign key towards the user: remove it first.
        foreach ($em->getRepository(UserInvitationCode::class)->findBy(['email' => $emails]) as $code) {
            $em->remove($code);
        }
        $em->flush();

        foreach ($em->getRepository(User::class)->findBy(['email' => array_map('strtolower', $emails)]) as $user) {
            $em->remove($user);
        }
        $em->flush();
        $em->clear();
    }
}
