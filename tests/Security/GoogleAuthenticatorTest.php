<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\OAuth\GoogleAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

final class GoogleAuthenticatorTest extends TestCase
{
    private function getUser(string $allowedEmails, string $googleEmail, UserRepository $userRepository): ?User
    {
        $authenticator = new GoogleAuthenticator(
            $this->createMock(ClientRegistry::class),
            $this->createMock(RouterInterface::class),
            $userRepository,
            $allowedEmails
        );
        $resourceOwner = new GoogleUser(['sub' => '42', 'email' => $googleEmail, 'email_verified' => true]);

        $method = new \ReflectionMethod($authenticator, 'getUserFromRessourceOwner');
        $method->setAccessible(true);

        /** @var User|null $user */
        $user = $method->invoke($authenticator, $resourceOwner, $userRepository);

        return $user;
    }

    public function testAllowedEmailsAreCaseAndSpaceInsensitive(): void
    {
        $user = (new User())->setEmail('toto@example.com')->setIdGoogle('42');
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneByEmailInsensitive')
            ->with('Toto@Example.COM')
            ->willReturn($user);

        $this->assertSame($user, $this->getUser('other@example.com, TOTO@example.com ', 'Toto@Example.COM', $userRepository));
    }

    public function testEmailOutsideTheAllowedListIsRejected(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findOneByEmailInsensitive');

        $this->expectException(\RuntimeException::class);
        $this->getUser('other@example.com', 'toto@example.com', $userRepository);
    }
}
