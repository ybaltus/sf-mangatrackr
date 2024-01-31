<?php

namespace App\Tests\Controller;

use App\Entity\UserInvitationCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class InvitationControllerTest extends WebTestCase implements ControllerTestInterface
{
    private function generateUserInvitationCode(): UserInvitationCode
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Set userEmail
        $userEmail = sprintf('user-%d@invitation.com', mt_rand(1, 1000));

        // Set userCodeInvitation
        $userCodeInvitation = (new UserInvitationCode())
            ->setEmail($userEmail)
            ->setCodeInvitation(Uuid::v4())
        ;
        $em->persist($userCodeInvitation);
        $em->flush();

        return $userCodeInvitation;
    }

    public function testHTTPResponseSuccess(): void
    {
        $client = static::createClient();

        // Create UserCodeInvitation
        $userCodeInvitation = $this->generateUserInvitationCode();

        $crawler = $client->request('GET', "/invitation/register/{$userCodeInvitation->getCodeInvitation()}");

        $this->assertResponseIsSuccessful();
    }

    public function testHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/invitation/register/12345678');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testInvitationFormSubmitAndRedirect(): void
    {
        $client = static::createClient();

        // Create UserCodeInvitation
        $userCodeInvitation = $this->generateUserInvitationCode();

        $crawler = $client->request('GET', "/invitation/register/{$userCodeInvitation->getCodeInvitation()}");

        // Test Form Submit for edit password
        $crawler = $client->submitForm('S\'enregistrer', [
            'invitation_form[username]' => 'userInvit',
            'invitation_form[email]' => $userCodeInvitation->getEmail(),
            'invitation_form[plainPassword][first]' => 'password',
            'invitation_form[plainPassword][second]' => 'password',
        ]);
        $this->assertResponseRedirects('/scantheque', 302);

        // Follow redirect
        $client->followRedirect();
        $this->assertSelectorTextContains('h1', "Hello, userInvit");
    }
}
