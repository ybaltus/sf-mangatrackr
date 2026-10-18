<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Email lookups must be case-insensitive, although PostgreSQL compares strings case-sensitively.
 */
final class UserRepositoryTest extends KernelTestCase
{
    private const FIXTURE_EMAIL = 'user@default1.com';

    private function getRepository(): UserRepository
    {
        self::bootKernel();

        return static::getContainer()->get(UserRepository::class);
    }

    public function testFindOneByEmailInsensitiveIgnoresCaseAndSpaces(): void
    {
        $user = $this->getRepository()->findOneByEmailInsensitive('  USER@Default1.COM ');

        $this->assertNotNull($user);
        $this->assertSame(self::FIXTURE_EMAIL, $user->getEmail());
    }

    public function testFindOneByEmailInsensitiveReturnsNullForUnknownEmail(): void
    {
        $this->assertNull($this->getRepository()->findOneByEmailInsensitive('unknown-'.bin2hex(random_bytes(4)).'@default1.com'));
    }

    public function testLoadUserByIdentifierIgnoresCase(): void
    {
        $user = $this->getRepository()->loadUserByIdentifier('User@Default1.com');

        $this->assertNotNull($user);
        $this->assertSame(self::FIXTURE_EMAIL, $user->getUserIdentifier());
    }
}
