<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AboutControllerTest extends WebTestCase implements ControllerTestInterface
{
    public function testHTTPResponseSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/about');

        $this->assertResponseIsSuccessful();
    }

    public function testHTTPResponseFailed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/aboutt');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testH1(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/about');
        $this->assertSelectorTextContains('h1', 'Lisez. Suivez. Répétez.');
    }

    public function testNbSectionHtmlElements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/about');
        $this->assertSelectorCount(2, 'section');
    }

    public function testNbH2HtmlElements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/about');
        $this->assertSelectorCount(1, 'h2');
    }

    public function testNbH3HtmlElements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/about');
        $this->assertSelectorCount(2, 'h3');
    }
}
