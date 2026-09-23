<?php

namespace App\Tests\Services\Api;

use App\Services\Api\ApiJikanService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ApiJikanServiceTest extends TestCase
{
    public function testFetchTopMangaSuccess(): void
    {
        $mockData = [
            'data' => [
                ['mal_id' => 1, 'title' => 'Berserk'],
            ],
        ];

        $mockResponse = new MockResponse(json_encode($mockData), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $httpClient = new MockHttpClient([$mockResponse]);
        $em = $this->createMock(EntityManagerInterface::class);
        $slugger = $this->createMock(SluggerInterface::class);

        $service = new ApiJikanService($httpClient, $em, $slugger, 'https://api.jikan.moe/v4');
        $result = $service->fetchTopManga(10);

        $this->assertIsArray($result);
        $this->assertSame($mockData['data'], $result);
    }

    public function testFetchTopMangaFailureReturnsFalse(): void
    {
        $mockResponse = new MockResponse('Not Found', [
            'http_code' => 404,
        ]);

        $httpClient = new MockHttpClient([$mockResponse]);
        $em = $this->createMock(EntityManagerInterface::class);
        $slugger = $this->createMock(SluggerInterface::class);

        $service = new ApiJikanService($httpClient, $em, $slugger, 'https://api.jikan.moe/v4');
        $result = $service->fetchTopManga(10);

        $this->assertIsBool($result);
        $this->assertFalse($result);
    }
}
