<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MangaControllerTest extends WebTestCase implements ControllerTestInterface
{
    public function testHTTPResponseSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');

        $this->assertResponseIsSuccessful();
    }

    public function testHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testH1(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $this->assertSelectorTextContains('h1', 'One piece');
    }

    public function testNbSectionHtmlElements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $this->assertSelectorCount(1, 'section');
    }

    public function testImgMangaHtmlElements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $this->assertSelectorExists('img');
    }

    public function testScanthequeStimulusController(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $this->assertSelectorExists('div[data-controller=scantheque]');
    }

    public function testAddToScanthequeOnClickEventStimulusAction(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $this->assertSelectorCount(2, 'button[data-action="click->scantheque#addToScantheque"]');
    }

    public function testUlCharacteristicsHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $this->assertSelectorExists('ul');
    }

    public function testCatalogButtonNavigate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $this->assertSelectorExists('a[href="/catalog"]');
    }

    public function testCatalogClickLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $client->clickLink('Catalogue');
        $this->assertResponseIsSuccessful();
    }

    public function testScanthequeButtonNavigate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $this->assertSelectorExists('a[href="/scantheque"]');
    }

    public function testScanthequeClickLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/one-piece');
        $client->clickLink('Scanthèque');
        $this->assertResponseIsSuccessful();
    }
}
