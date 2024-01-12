<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ScanthequeControllerTest extends WebTestCase implements ControllerTestInterface
{
    private function getTestUser(): User
    {
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        return $userRepository->findOneByEmail('user@default1.com');
    }

    private function assertSectionStatus(string $sectionName): void
    {
        // Test section exist
        $this->assertSelectorExists("section#scantheque-section-$sectionName");

        // Test h2 exist
        $this->assertSelectorExists("h2#title-$sectionName");

        // Test div list
        $this->assertSelectorExists("div#scantheque-list-$sectionName");
        $this->assertSelectorExists("div[data-scantheque-target=$sectionName]");

    }

    public function testHTTPResponseSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');

        $this->assertResponseIsSuccessful();
    }

    public function testHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scanthequee');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testH1WithUserNotConnected(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');
        $this->assertSelectorTextContains('h1', 'Scanthèque');
    }

    public function testH1WithUserConnected(): void
    {
        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/scantheque');
        $this->assertSelectorTextContains('h1', 'Hello, user_0');
    }

    public function testNbSectionHtmlElements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');
        $this->assertSelectorCount(5, 'section');
    }

    public function testPlaySectionHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');
        $this->assertSectionStatus('play');
    }

    public function testPausedSectionHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');

        $this->assertSectionStatus('pause');
    }

    public function testArchivedSectionHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');

        $this->assertSectionStatus('archived');
    }

    public function testTemplateMangaHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');

        // Test section exist
        $this->assertSelectorExists('template#mangaCardTemplate');
    }

    public function testScanthequeStimulusController(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');
        $this->assertSelectorExists('div[data-controller=scantheque]');
    }

    public function testStepStimulusTarget(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/scantheque');
        $this->assertSelectorExists('section[data-scantheque-target=step]');
    }
}
