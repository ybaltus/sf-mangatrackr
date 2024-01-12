<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase implements ControllerTestInterface
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

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');

        $this->assertResponseIsSuccessful();
    }

    public function testHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/users');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUserNotConnected(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/user');
        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);
    }

    public function testH1(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');
        $this->assertSelectorTextContains('h1', 'Mon compte');
    }

    public function testDivAccountStimulusController(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');

        $this->assertSelectorExists('div[data-controller=account]');
    }

    public function testIfTabsExist(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');
        $this->assertSelectorCount(4, 'div[role=tabpanel]');
    }

    public function testTabInfosContent(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');
        $this->assertSelectorExists('div#tab-content-1');
    }

    public function testTabEditUserInfosFormSubmit(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');

        // Test Form submit
        $crawler = $client->submitForm('Valider', [
            'user[username]' => 'user_edit',
            'user[email]' => $testUser->getEmail(),
        ]);

        // Test redirect
        $this->assertResponseRedirects('/user?tabSelected=2', 302);

        // Follow redirect
        $client->followRedirect();
        $this->assertSelectorTextContains('div.div-flash-message', 'Vos informations ont été éditées avec succès !');
    }

    public function testTabEditUserPasswordFormSubmit(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');

        // Test Form submit
        $crawler = $client->submitForm('Modifier', [
            'user_password[plainPassword]' => 'password',
            'user_password[newPassword][first]' => 'password',
            'user_password[newPassword][second]' => 'password',
        ]);

        $this->assertResponseRedirects('/user', 302);

        // Follow redirect
        $client->followRedirect();
        $this->assertSelectorTextContains('div.div-flash-message', 'Le mot de passe a été édité avec success !');
    }

    public function testTabDeleteUserContent(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');

        // Test link delete (stimulus target)
        $this->assertSelectorExists('p#counter-delete');
                $this->assertSelectorExists('a[data-action="account#deleteUserAccount"]');
        $this->assertSelectorExists('a[data-account-target=btnDelete]');
     }
}
