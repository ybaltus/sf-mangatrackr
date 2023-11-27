<?php

namespace App\Services;

use App\Entity\Manga;
use App\Entity\MangaJikanAPI;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiJikanService extends ApiServiceAbstract
{
    public string $baseUrl;
    public const LIMIT_SEARCH = 15;

    public function __construct(
        HttpClientInterface $httpClient,
        private EntityManagerInterface $em,
        string $apiJikanUrl
    ) {
        parent::__construct($httpClient);
        $this->baseUrl = $apiJikanUrl;
    }

    public function fetchMangaByTitle(string $searchTerm, int $limit = self::LIMIT_SEARCH): array
    {
        $queryParams = [
            'q' => $searchTerm,
            'limit' => $limit,
        ];

        $response = $this->getRequest($this->baseUrl, $queryParams);

        if (!$this->handleHttpStatusCode($response->getStatusCode())) {
            return ["Error http response : {$response->getStatusCode()}"];
        }

        return $response->toArray()['data'];
    }

    public function saveMangaDatasInDb(array $mangaDatas): bool
    {
        // TODO vérifier si le manga existe déjà
        // TODO Récupérer uniquement le nom pour les datas array
        // TODO Gérer les éditeurs (exist or not)
        // TODO  Gérer les types de manga (exist or not)

        $result = $this->extractDatas($mangaDatas);

        // Manga entity
        $manga = (new Manga())
            ->setTitle($result['malTitle'])
            ->setTitleAlternative($result['malTitleAlternative'])
            ->setNbChapters($result['malChapters'] ?? 1)
            ->setAuthor()
            ->setIsAdult()
            ->setPublishedAt(new \DateTimeImmutable($result['malStartPublishedAt']))
        ;

        // MangaJianAPI entity
        $mangaJikanApi = (new MangaJikanAPI())
            ->setMalId($result['malId'])
            ->setMalDescription($result['malDescription'])
            ->setMalUrl($result['malUrl'])
            ->setMalImgJpg($result['malImgJpg'])
            ->setMalImgJpgLarge($result['malImgJpgLarge'])
            ->setMalImgWebp($result['malImgWebp'])
            ->setMalImgWebpLarge($result['malImgWebpLarge'])
            ->setMalChapters($result['malChapters'])
            ->setMalVolume($result['malVolumes'])
            ->setMalStartPublishedAt($result['malStartPublishedAt'])
            ->setMalEndPublishedAt($result['malEndPublishedAt'])
            ->setMalDemographics($result['malDemographics'])
            ->setMalGenres($result['malGenres'])
            ->setMalSerializations($result['malSerializations'])
            ->setMalAuthors($result['malAuthors'])
            ->setMalScored($result['malScored'])
            ->setMalScroredBy($result['malScroredBy'])
            ->setMalRank($result['malRank'])
        ;

        // TODO Persister les données
        return true;
    }

    public function extractDatas(array $result): array
    {
        return [
            'malId' => $result['mal_id'],
            'malTitle' => $result['title'],
            'malTitleAlternative' => $result['title_japanese'],
            'malStatus' => $result['status'],
            'malDescription' => $result['synopsis'],
            'malUrl' => $result['mal_url'],
            'malImgJpg' => $result['images']['jpg']['image_url'] ?? null,
            'malImgJpgLarge' => $result['images']['jpg']['large_image_url'] ?? null,
            'malImgWebp' => $result['images']['webp']['image_url'] ?? null,
            'malImgWebpLarge' => $result['images']['webp']['large_image_url'] ?? null,
            'malChapters' => $result['chapters'],
            'malVolumes' => $result['volumes'],
            'malStartPublishedAt' => $result['published']['from'] ?? null,
            'malEndPublishedAt' => $result['published']['to'] ?? null,
            'malDemographics' => $result['demographics'], // array
            'malGenres' => $result['genres'], // array
            'malSerializations' => $result['serializations'], // array
            'malAuthors' => $result['authors'], // array
            'malScored ' => $result['scored'] ?? null,
            'malScroredBy' => $result['scored_by'] ?? null,
            'malRank' => $result['rank'] ?? null,
        ];
    }
}
