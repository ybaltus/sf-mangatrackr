<?php

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

    public function testUserConnected(): void
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

    public function testInvitationFormSubmitAndRedirect(): void
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
        $this->assertSelectorTextContains('h1', sprintf("Hello, %s", $user->getUsername()));
    }

    public function testLogoutAndRedirect(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/logout');

        $this->assertResponseRedirects('/', 302);

        // Follow redirect
        $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Synchronisez votre passion pour les mangas !');
    }
}
