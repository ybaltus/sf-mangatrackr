<?php

declare(strict_types=1);

namespace App\Tests\Services\Common;

use App\Entity\User;
use App\Entity\UserInvitationCode;
use App\Services\Common\EmailNormalizer;
use PHPUnit\Framework\TestCase;

final class EmailNormalizerTest extends TestCase
{
    public function testNormalizeLowersAndTrims(): void
    {
        $this->assertSame('john.doe@example.com', EmailNormalizer::normalize(' John.DOE@Example.COM  '));
    }

    public function testNormalizeHandlesMultibyteCharacters(): void
    {
        $this->assertSame('élodie@exemple.fr', EmailNormalizer::normalize('ÉLODIE@Exemple.fr'));
    }

    public function testUserEmailIsNormalizedOnWrite(): void
    {
        $user = (new User())->setEmail('Toto@Example.COM');

        $this->assertSame('toto@example.com', $user->getEmail());
        $this->assertSame('toto@example.com', $user->getUserIdentifier());
    }

    public function testInvitationEmailIsNormalizedOnWrite(): void
    {
        $invitation = (new UserInvitationCode())->setEmail('Toto@Example.COM ');

        $this->assertSame('toto@example.com', $invitation->getEmail());
    }
}
