<?php

declare(strict_types=1);

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
        $this->assertSelectorCount(5, 'div[role=tabpanel]');
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

    public function testTabExportsUserContent(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user');

        // Test link scantheque export (stimulus action)
        $this->assertSelectorExists('a[data-action="account#exportScantheque"]');
    }

    public function testExportScantheque(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getTestUser());

        $client->request('GET', '/user/export/scantheque');

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith('text/csv', (string) $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'attachment;filename="mangasync-scantheque-',
            (string) $client->getResponse()->headers->get('Content-Disposition')
        );
        $this->assertStringStartsWith(
            '"Title";"LastChapter";"MaxChapter";"Status"',
            $client->getInternalResponse()->getContent()
        );
    }

    public function testExportScanthequeNotConnected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user/export/scantheque');

        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);
    }

    public function testDeleteAccount(): void
    {
        $client = static::createClient();

        // Use a dedicated user to keep the shared fixtures untouched
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = (new User())
            ->setEmail('user-to-delete@default.com')
            ->setUsername('user_to_delete')
            ->setPlainPassword('password')
        ;
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        $client->request('DELETE', '/user/delete-account');

        $this->assertResponseIsSuccessful();
        $result = json_decode((string) $client->getInternalResponse()->getContent(), true);
        $this->assertSame('success', $result[0]);
        $this->assertNull(static::getContainer()->get(UserRepository::class)->findOneByEmail('user-to-delete@default.com'));
    }

    public function testDeleteAccountNotConnected(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/user/delete-account');

        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);
    }
}
