<?php

declare(strict_types=1);

namespace App\Tests\Services\Api;

use App\Services\Api\ApiMangaUpdatesService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiMangaUpdatesServiceTest extends TestCase
{
    private const API_URL = 'https://api.mangaupdates.test/v1';

    private function createService(HttpClientInterface $httpClient): ApiMangaUpdatesService
    {
        return new ApiMangaUpdatesService(
            $httpClient,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(SluggerInterface::class),
            self::API_URL
        );
    }

    /**
     * @param array<mixed> $body
     */
    private function jsonResponse(array $body, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($body, JSON_THROW_ON_ERROR), [
            'http_code' => $status,
            'response_headers' => ['Content-Type: application/json'],
        ]);
    }

    public function testFetchMangaReleasesWithOnePage(): void
    {
        $httpClient = new MockHttpClient([
            $this->jsonResponse(['results' => [['record' => 'a'], ['record' => 'b']], 'total_hits' => 2]),
        ]);

        $results = $this->createService($httpClient)->fetchMangaReleases(10);

        $this->assertSame([['record' => 'a'], ['record' => 'b']], $results);
        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testFetchMangaReleasesMergesAllThePages(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = [$method, $url, json_decode($options['body'], true)];

            return 1 === count($requests)
                ? $this->jsonResponse(['results' => [['record' => 'a'], ['record' => 'b']], 'total_hits' => 3])
                : $this->jsonResponse(['results' => [['record' => 'c']], 'total_hits' => 3]);
        });

        $results = $this->createService($httpClient)->fetchMangaReleases(
            2,
            new \DateTimeImmutable('2030-01-01'),
            new \DateTimeImmutable('2030-01-07')
        );

        $this->assertSame([['record' => 'a'], ['record' => 'b'], ['record' => 'c']], $results);
        $this->assertCount(2, $requests);
        $this->assertSame(['POST', self::API_URL.'/releases/search'], [$requests[0][0], $requests[0][1]]);
        $this->assertSame('2030-01-01', $requests[0][2]['start_date']);
        $this->assertSame('2030-01-07', $requests[0][2]['end_date']);
        $this->assertSame(2, $requests[1][2]['page']);
    }

    public function testFetchMangaReleasesWithAnHttpError(): void
    {
        $httpClient = new MockHttpClient([new MockResponse('', ['http_code' => 500])]);

        $this->assertSame(
            ['Error http response : 500'],
            $this->createService($httpClient)->fetchMangaReleases()
        );
    }

    public function testFetchMangaByTitleExcludesAdultGenresByDefault(): void
    {
        $body = null;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$body) {
            $body = json_decode($options['body'], true);

            return $this->jsonResponse(['results' => [['record' => 'berserk']]]);
        });

        $results = $this->createService($httpClient)->fetchMangaByTitle('Berserk');

        $this->assertSame([['record' => 'berserk']], $results);
        $this->assertSame('Berserk', $body['search']);
        $this->assertSame(ApiMangaUpdatesService::LIMIT_SEARCH, $body['perpage']);
        $this->assertContains('Hentai', $body['exclude_genre']);
    }

    public function testFetchMangaByTitleForAnAdultAudience(): void
    {
        $body = null;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$body) {
            $body = json_decode($options['body'], true);

            return $this->jsonResponse(['results' => []]);
        });

        $this->createService($httpClient)->fetchMangaByTitle('Berserk', true);

        $this->assertArrayNotHasKey('exclude_genre', $body);
    }

    public function testFetchMangaByTitleWithAnHttpError(): void
    {
        $httpClient = new MockHttpClient([new MockResponse('', ['http_code' => 404])]);

        $this->assertSame(
            ['Error http response : 404'],
            $this->createService($httpClient)->fetchMangaByTitle('Unknown')
        );
    }

    public function testExtractDatas(): void
    {
        $datas = $this->createService(new MockHttpClient())->extractDatas([
            'series_id' => 123,
            'title' => 'Fullmetal &amp; Alchemist',
            'url' => 'https://www.mangaupdates.test/series/123',
            'description' => 'A description',
            'type' => 'Manga',
            'image' => ['url' => ['original' => 'https://img.test/o.jpg', 'thumb' => 'https://img.test/t.jpg']],
            'year' => '',
            'genres' => [['genre' => 'Action']],
        ]);

        $this->assertSame(123, $datas['muSeriesId']);
        $this->assertSame('Fullmetal & Alchemist', $datas['muTitle']);
        $this->assertSame('https://img.test/o.jpg', $datas['muImgJpg']);
        $this->assertSame('https://img.test/t.jpg', $datas['muThumbJpg']);
        $this->assertNull($datas['muYear']);
        $this->assertSame([['genre' => 'Action']], $datas['muGenres']);
    }

    public function testExtractDatasWithoutImageNorGenres(): void
    {
        $datas = $this->createService(new MockHttpClient())->extractDatas([
            'series_id' => 1,
            'title' => 'Title',
            'url' => 'https://www.mangaupdates.test/series/1',
            'description' => null,
            'type' => 'Manga',
            'year' => '1997',
        ]);

        $this->assertNull($datas['muImgJpg']);
        $this->assertNull($datas['muThumbJpg']);
        $this->assertSame('1997', $datas['muYear']);
        $this->assertSame([], $datas['muGenres']);
    }
}
