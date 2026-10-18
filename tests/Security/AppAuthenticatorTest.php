<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Repository\UserRepository;
use App\Security\Auth\AppAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class AppAuthenticatorTest extends TestCase
{
    /**
     * @return array{0: Passport, 1: Request}
     */
    private function authenticate(string $email, string $password = 'password'): array
    {
        // Values posted by the login form are strings (ADR 0001).
        $request = Request::create('/login', 'POST', [
            'email' => $email,
            'password' => $password,
            '_csrf_token' => 'token',
        ]);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $authenticator = new AppAuthenticator(
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(UserRepository::class)
        );

        return [$authenticator->authenticate($request), $request];
    }

    private function getIdentifier(Passport $passport): string
    {
        $badge = $passport->getBadge(UserBadge::class);
        $this->assertInstanceOf(UserBadge::class, $badge);

        return $badge->getUserIdentifier();
    }

    public function testBadgeIdentifierIsNormalized(): void
    {
        [$passport, $request] = $this->authenticate('  User@Default1.COM ');

        // The badge identifier is also the login throttling key (LoginThrottlingListener).
        $this->assertSame('user@default1.com', $this->getIdentifier($passport));
        $this->assertSame('user@default1.com', $request->getSession()->get(SecurityRequestAttributes::LAST_USERNAME));
    }

    public function testSpaceAndCaseVariantsShareTheSameIdentifier(): void
    {
        $identifiers = array_map(
            fn (string $email): string => $this->getIdentifier($this->authenticate($email)[0]),
            ['victim@x.fr', ' victim@x.fr', '  VICTIM@x.fr', "victim@X.FR\t"]
        );

        $this->assertSame(['victim@x.fr'], array_values(array_unique($identifiers)));
    }

    public function testPasswordIsNotTrimmed(): void
    {
        [$passport] = $this->authenticate('user@default1.com', ' pass word ');

        $credentials = $passport->getBadge(PasswordCredentials::class);
        $this->assertInstanceOf(PasswordCredentials::class, $credentials);
        $this->assertSame(' pass word ', $credentials->getPassword());
    }
}
