<?php

namespace App\Services\Api;

use App\Entity\Manga;
use App\Entity\ReleaseMangaUpdatesAPI;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiMangaUpdatesService extends ApiServiceAbstract
{
    public string $baseUrl;
    public const LIMIT_SEARCH = 15;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly string $apiMangaUpdatesUrl
    ) {
        parent::__construct($this->httpClient, $this->em);
        $this->baseUrl = $this->apiMangaUpdatesUrl;
    }

    /**
     * @return array<mixed>
     */
    public function fetchMangaReleases(
        int $limit = 40,
        ?\DateTimeImmutable $startedAt = null,
        ?\DateTimeImmutable $endedAt = null,
    ): array {
        // Set api params
        $currentDate = (new \DateTimeImmutable())->format('Y-m-d');

        $apiParams = [
            'start_date' => $startedAt ? $startedAt->format('Y-m-d') : $currentDate,
            'end_date' => $endedAt ? $endedAt->format('Y-m-d') : $currentDate,
            'per_page' => $limit,
        ];

        // Execute post request
        $response = $this->postRequest($this->baseUrl.'/releases/search', $apiParams);

        if (!$this->handleHttpStatusCode($response->getStatusCode())) {
            return ["Error http response : {$response->getStatusCode()}"];
        }

        // Retrieve results by page
        $resultArray = $response->toArray();
        $resultsByPage = $resultArray['results'];
        $totalHits = intval($resultArray['total_hits']);
        $nbPage = intval($totalHits / $limit) + 1;

        if ($totalHits > $limit) {
            for ($i = 2; $i <= $nbPage; ++$i) {
                $apiParams['page'] = $i;
                $response = $this->postRequest(
                    $this->baseUrl.'/releases/search',
                    $apiParams
                );
                $results = $response->toArray()['results'];
                if (!empty($results)) {
                    $resultsByPage = array_merge($resultsByPage, $results);
                }
            }
        }

        return $resultsByPage;
    }

    /**
     * @param array<string> $releaseDatas
     */
    public function saveReleaseDataInDb(array $releaseDatas): ?ReleaseMangaUpdatesAPI
    {
        // Check if the manga already exists
        $manga = $this->verifyIfExistInDb(
            Manga::class,
            $releaseDatas['title'],
            true
        );

        $releaseEntity = null;
        if ($manga) {
            // TODO Check if exist

            $releaseEntity = (new ReleaseMangaUpdatesAPI())
                ->setManga($manga)
                ->setVolumeVal($releaseDatas['volume'])
                ->setChapterVal($releaseDatas['chapter'])
                ->setReleasedAt(new \DateTimeImmutable($releaseDatas['release_date']))
            ;
            $this->em->persist($releaseEntity);
            $this->em->flush();
        }

        return $releaseEntity;
    }

    public function fetchMangaByTitle(string $searchTerm, bool $isAdult = false, int $limit = self::LIMIT_SEARCH): array
    {
        return ['TODO'];
    }

    public function extractDatas(array $result): array
    {
        return [
        ];
    }
}
