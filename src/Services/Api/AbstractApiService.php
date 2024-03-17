<?php

namespace App\Services\Api;

use App\Repository\EditorRepository;
use App\Repository\MangaRepository;
use App\Repository\MangaStatusRepository;
use App\Repository\MangaTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractApiService
{
    /**
     * @var array<string>|string[]
     */
    protected array $adultsGenres = [
        'Erotica',
        'Hentai',
        'Ecchi',
        'Adult',
        'Gender Bender',
        'Lolicon',
        'Shotacon',
    ];

    /**
     * @var array<string>|string[]
     */
    protected array $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em
    ) {
    }

    /**
     * Extract the necessary data's.
     *
     * @param array<string>|string[] $result
     *
     * @return array<string>|string[]
     */
    abstract public function extractDatas(array $result): array;

    /**
     * Execute a GET http request.
     *
     * @param array<string, mixed>        $queryParams
     * @param array<string>|string[]|null $headers
     *
     * @throws TransportExceptionInterface
     */
    protected function getRequest(
        string $url,
        array $queryParams,
        ?array $headers = null,
    ): ResponseInterface {
        return $this->httpClient->request(
            'GET',
            $url,
            [
                'headers' => $headers ?? $this->headers,
                'query' => $queryParams,
            ]
        );
    }

    /**
     * Execute a POST http request.
     *
     * @param array<string, mixed>        $apiParams
     * @param array<string>|string[]|null $headers
     *
     * @throws TransportExceptionInterface
     */
    protected function postRequest(
        string $url,
        array $apiParams,
        ?array $headers = null,
    ): ResponseInterface {
        $requestParams = [
            'headers' => $headers ?: $this->headers,
        ];

        if ($headers) {
            $requestParams['body'] = $apiParams;
        } else {
            $requestParams['json'] = $apiParams;
        }

        return $this->httpClient->request(
            'POST',
            $url,
            $requestParams
        );
    }

    /**
     * Simple handle http status code.
     */
    protected function handleHttpStatusCode(int $status): bool
    {
        return match ($status) {
            200, 201, 204 => true,
            400, 401, 403, 404, 500 => false,
            default => false
        };
    }

    /**
     * Check if an entity already exists by name or title.
     */
    protected function verifyIfExistInDb(string $className, string $value, bool $isTitle = false): bool|object
    {
        $slugger = new AsciiSlugger();
        $valSlug = $slugger->slug($value)->lower();
        /**
         * @var MangaRepository|MangaTypeRepository|EditorRepository|MangaStatusRepository $repository
         */
        $repository = $this->em->getRepository($className); /** @phpstan-ignore-line */
        $entity = $isTitle ?
            $repository->findOneByTitleSlug($valSlug) :
            $repository->findOneByNameSlug($valSlug)
        ;
        if ($entity) {
            return $entity;
        }

        return false;
    }

    /**
     * Extract the entries from an array.
     *
     * @param array<mixed> $datas
     *
     * @return array<string>|string[]
     */
    protected function extractDatasFromArray(array $datas, string $key): array
    {
        $results = [];
        foreach ($datas as $value) {
            if (is_array($value)) {
                $results[] = $value[$key];
            }
        }

        return $results;
    }

    /**
     * @param array<string> $genres
     */
    protected function checkIfAdult(array $genres): bool
    {
        foreach ($genres as $genre) {
            if (in_array($genre, $this->adultsGenres)) {
                return true;
            }
        }

        return false;
    }
}