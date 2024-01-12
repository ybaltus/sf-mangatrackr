<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CatalogControllerTest extends WebTestCase implements ControllerTestInterface
{
    public function testHTTPResponseSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');

        $this->assertResponseIsSuccessful();
    }

    public function testHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalogue');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testH1(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorTextContains('h1', 'Catalogue');
    }

    public function testNbSectionHtmlElements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorCount(4, 'section');
    }

    public function testInputSearchHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorExists('input#searchTerm');
    }

    public function testCheckBoxAdvancedHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorExists('input[name=advanced-input]');
    }

    public function testCheckBoxIsAdultHtmlElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorExists('input[name=adult-input]');
    }

    public function testInputWithHTMXAttributes(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorExists('form[hx-post="/manga/search"]');
        $this->assertSelectorExists('form[hx-trigger="submit delay:500ms"]');
        $this->assertSelectorExists('form[hx-target="#search-results"]');
        $this->assertSelectorExists('form[hx-indicator="#search-indicator"]');
    }

    public function testSearchCatalogStimulusController(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorExists('form[data-controller=search-catalog]');
    }

    public function testSubmitSearchyOnSubmitEventStimulusAction(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorExists('form[data-action="submit->search-catalog#submitSearch"]');
    }

    public function testHiddenGalleryOnKeyUpEventStimulusAction(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/catalog');
        $this->assertSelectorExists('input[data-action="keyup->search-catalog#hiddenGallery"]');
    }
}
