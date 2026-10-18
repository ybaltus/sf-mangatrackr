<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\UserResetPassword;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordControllerTest extends WebTestCase implements ControllerTestInterface
{
    public function testHTTPResponseSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/reset_password');

        $this->assertResponseIsSuccessful();
    }

    public function testHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/reset_passwordd');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testH1(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/reset_password');
        $this->assertSelectorTextContains('h1', 'Mot de passe oublié');
    }

    public function testFormHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/reset_password');
        $this->assertSelectorExists('form[name=reset_password_email]');
    }

    public function testRedirectUserLoggedForResetPassword(): void
    {
        $client = static::createClient();

        // Login User
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail(self::TEST_USER_EMAIL);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/reset_password');
        $this->assertResponseRedirects('/user', 302);
    }

    public function testResetPasswordFormSubmit(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset_password');

        // Form Submit
        $crawler = $client->submitForm('Envoyer', [
            'reset_password_email[email]' => self::TEST_USER_EMAIL,
        ]);

        // Test email send
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Modification de mot de passe');

        // Test status of the response
        $this->assertResponseRedirects('/reset_password', 302);
    }

    public function testResetPasswordFormSubmitIsCaseInsensitiveOnEmail(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset_password');

        // Email typed with upper case letters: the user must still be found
        $client->submitForm('Envoyer', [
            'reset_password_email[email]' => strtoupper(self::TEST_USER_EMAIL),
        ]);

        $this->assertEmailCount(1);
        $this->assertResponseRedirects('/reset_password', 302);
    }

    public function testEditPasswordHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/reset_password/edit_password');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testEditPasswordWithWrongCode(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/reset_password/edit_password/12345678');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testEditPasswordHTTPResponseSuccess(): void
    {
        $client = static::createClient();

        // Get userResetPassword
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail(self::TEST_USER_EMAIL);
        $userPasswordCode = $testUser->getUserResetPasswords()[0]->getResetCode();

        // Test Route
        $crawler = $client->request('GET', "/reset_password/edit_password/$userPasswordCode");
        $this->assertResponseIsSuccessful();

        // Test h1
        $this->assertSelectorTextContains('h1', 'Réinitialiser mon mot de passe');

        // Test Form Submit for edit password
        $crawler = $client->submitForm('Modifier', [
            'reset_password[plainPassword][first]' => 'password',
            'reset_password[plainPassword][second]' => 'password',
        ]);
        $this->assertResponseRedirects('/login', 302);
    }

    public function testEditPasswordWithAnExpiredCode(): void
    {
        $client = static::createClient();

        // Use a dedicated user to keep the shared fixtures untouched
        $em = static::getContainer()->get('doctrine')->getManager();
        $uniqueId = uniqid();
        $user = (new User())
            ->setEmail("user-expired-$uniqueId@default.com")
            ->setUsername("user_expired_$uniqueId")
            ->setPlainPassword('password')
        ;
        $userResetPassword = (new UserResetPassword())
            ->setUser($user)
            ->setResetCode("expired-code-$uniqueId")
            ->setExpiredAt(new \DateTimeImmutable('-1 day'))
        ;
        $em->persist($user);
        $em->persist($userResetPassword);
        $em->flush();

        $client->request('GET', "/reset_password/edit_password/expired-code-$uniqueId");

        $this->assertResponseStatusCodeSame(Response::HTTP_GONE);

        // Clean the dedicated datas
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->remove($em->find(UserResetPassword::class, $userResetPassword->getId()));
        $em->remove($em->find(User::class, $user->getId()));
        $em->flush();
    }
}
