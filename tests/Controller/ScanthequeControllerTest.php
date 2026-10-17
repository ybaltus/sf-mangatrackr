<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\MangaUserTrack;
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
        return $userRepository->findOneByEmail(self::TEST_USER_EMAIL);
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
        $this->assertSelectorTextContains('h1', "Hello, {$testUser->getUsername()}");
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

    /**
     * The front sends the value of an input, so the number of chapters is a string.
     */
    public function testUpdateNbChaptersSentAsString(): void
    {
        $client = static::createClient();
        $testUser = $this->getTestUser();
        $client->loginUser($testUser);

        $mangaUserTrack = $testUser->getUserTrackList()->getMangaUserTracks()->first();
        $this->assertNotFalse($mangaUserTrack, 'The test user needs at least one manga in his scantheque.');
        $mutId = $mangaUserTrack->getId();
        $initialNbChapters = $mangaUserTrack->getNbChapters();

        $client->request(
            'PUT',
            "/scantheque/umut_datas/$mutId",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['mut' => $mutId, 'nbChaptersTrack' => '12', 'isUpdateStatusTrack' => false])
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_decode((string) $client->getInternalResponse()->getContent(), true));

        $em = static::getContainer()->get('doctrine')->getManager();
        $updated = $em->find(MangaUserTrack::class, $mutId);
        $this->assertSame(12.0, $updated->getNbChapters());

        // Restore the shared fixture
        $updated->setNbChapters($initialNbChapters);
        $em->flush();
    }

    public function testUpdateNbChaptersOfAnotherUserIsDenied(): void
    {
        $client = static::createClient();
        $testUser = $this->getTestUser();
        $mutId = $testUser->getUserTrackList()->getMangaUserTracks()->first()->getId();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepository->findOneByEmail('user@default2.com'));

        $client->request(
            'PUT',
            "/scantheque/umut_datas/$mutId",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['mut' => $mutId, 'nbChaptersTrack' => '12', 'isUpdateStatusTrack' => false])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
