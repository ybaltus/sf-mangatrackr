<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase implements ControllerTestInterface
{
    private function getTestUser(): User
    {
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        return $userRepository->findOneByEmail(self::TEST_USER_EMAIL);
    }

    public function testHTTPResponseSuccess(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    public function testHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login/ic15698');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUserSuccessfulLogin(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/login');

        // Test redirect
        $this->assertResponseRedirects('/', 302);

        // Follow redirect
        $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Synchronisez votre passion pour les mangas !');
    }

    public function testInvitationFormSubmittedAndRedirect(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        // Test Form Submit for edit password
        $crawler = $client->submitForm('Se connecter', [
            'email' => self::TEST_USER_EMAIL,
            'password' => 'password',
        ]);
        $this->assertResponseRedirects('/scantheque', 302);

        // retrieve the test user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail(self::TEST_USER_EMAIL);

        // Follow redirect
        $client->followRedirect();
        $this->assertSelectorTextContains('h1', sprintf('Hello, %s', $user->getUsername()));
    }

    public function testLoginIsCaseInsensitiveOnEmail(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        // Email typed with upper case letters: must match the stored lower case email
        $client->submitForm('Se connecter', [
            'email' => strtoupper(self::TEST_USER_EMAIL),
            'password' => 'password',
        ]);
        $this->assertResponseRedirects('/scantheque', 302);
    }

    public function testSucessfulLogoutAndRedirect(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/logout');

        $this->assertResponseRedirects('/login', 302);

        // Follow redirect
        $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Se connecter');
    }
}
