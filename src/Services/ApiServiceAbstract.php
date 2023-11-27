<?php

namespace App\Services;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class ApiServiceAbstract
{
    /**
     * @var array<string>|string[]
     */
    protected array $headers = ['Accept' => 'application/json'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Extract the necessary data's.
     */
    abstract public function extractDatas(array $result): array;

    /**
     * Execute a GET http request.
     *
     * @throws TransportExceptionInterface
     */
    protected function getRequest(
        string $url,
        array $queryParams,
        array $headers = null,
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
     * Simple handle http status code.
     */
    protected function handleHttpStatusCode(int $status): bool
    {
        return match ($status) {
            200, 201, 204 => true,
            400, 401, 403, 404, 500 => false
        };
    }
}
