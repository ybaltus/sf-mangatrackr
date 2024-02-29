<?php

namespace App\Services\Api;

use App\Entity\Manga;
use App\Entity\MangaMangaUpdatesAPI;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
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
     * Fetch the mangas released.
     *
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

        // Get results by page
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
     * Persist release datas in database.
     *
     * @param array<string> $releaseDatas
     */
    public function saveReleaseDataInDb(array $releaseDatas): ?ReleaseMangaUpdatesAPI
    {
        /**
         * Check if the manga already exists.
         *
         * @var Manga|bool $manga
         */
        $manga = $this->verifyIfExistInDb(
            Manga::class,
            $releaseDatas['title'],
            true
        );

        $releaseEntity = null;

        if ($manga) {
            $releaseDate = new \DateTimeImmutable($releaseDatas['release_date']);

            // Check if the release already exists
            $releaseEntity = $this->ifReleaseAlreadyExist(
                $manga,
                $releaseDate,
            ) ?: new ReleaseMangaUpdatesAPI();

            $releaseEntity
                ->setManga($manga)
                ->setVolumeVal($releaseDatas['volume'])
                ->setChapterVal($releaseDatas['chapter'])
                ->setReleasedAt($releaseDate)
            ;
            $this->em->persist($releaseEntity);
            $this->em->flush();
        }

        return $releaseEntity;
    }

    /**
     * Fetch manga datas by title.
     *
     * @return mixed[]
     */
    public function fetchMangaByTitle(string $searchTerm, bool $isAdult = false, int $limit = self::LIMIT_SEARCH): array
    {
        $apiParams = [
            'search' => $searchTerm,
            'perpage' => $limit,
        ];

        if (!$isAdult) {
            $apiParams['exclude_genre'] = [
                'Adult',
                'Ecchi',
                'Hentai',
                'Gender Bender',
                'Lolicon',
                'Shotacon',
            ];
        }

        // Execute post request
        $response = $this->postRequest($this->baseUrl.'/series/search', $apiParams);

        if (!$this->handleHttpStatusCode($response->getStatusCode())) {
            return ["Error http response : {$response->getStatusCode()}"];
        }

        return $response->toArray()['results'];
    }

    /**
     * Persist manga datas in database.
     *
     * @param array<mixed> $mangaDatas
     */
    public function saveMangaDatasInDb(array $mangaDatas): Manga
    {
        $result = $this->extractDatas($mangaDatas['record']);

        // Check if the manga already exists
        $manga = $this->verifyIfExistInDb(
            Manga::class,
            $result['muTitle'],
            true
        );

        // Set manga datas
        if (!$manga) {
            $manga = new Manga();
            $manga->setTitle($result['muTitle'])
                ->setTitleAlternative($result['muTitle'])
                ->setDescription($result['muDescription'])
                ->setAuthor('Inconnu')
                ->setNbChapters(1)
                ->setPublishedAt(new \DateTimeImmutable($result['muYear'].'-01-01'))
                ->setIsAdult($this->checkIfAdult($result['muGenres']))
            ;
        } else {
            /**
             * @var Manga $manga
             */
            $manga
                ->setDescription($manga->getDescription() ?? $result['muDescription'])
            ;
        }

        // Set MangaType for manga entity
        foreach ($result['muGenres'] as $genreName) {
            $mangaType = $this->verifyIfExistInDb(
                MangaType::class,
                $genreName['genre']
            );

            if (!$mangaType) {
                $mangaType = (new MangaType())
                    ->setName($genreName['genre'])
                ;
            }
            if (!$manga->getMangaType()->contains($mangaType)) {
                $manga->addMangaType($mangaType);
            }
        }

        // Set MangaStatus for manga entity
        $mangaStatus = $this->verifyIfExistInDb(
            MangaStatus::class,
            'Publishing', // Already Exist in DB
            true
        );

        $manga->setMangaStatus($mangaStatus);

        // MangaMangaUpdatesAPI entity
        $mangaMangaUpdatesAPI = $manga->getMangaMangaUpdatesAPI();

        if (!$mangaMangaUpdatesAPI) {
            $mangaMangaUpdatesAPI = new MangaMangaUpdatesAPI();
        }

        $mangaMangaUpdatesAPI
            ->setManga($manga)
            ->setMuSeriesId($result['muSeriesId'])
            ->setMuDescription($result['muDescription'])
            ->setMuUrl($result['muUrl'])
            ->setMuImgJpg($result['muImgJpg'])
            ->setMuThumbJpg($result['muThumbJpg'])
            ->setMuYear($result['muYear'])
            ->setMuGenres($result['muGenres'])
        ;

        $manga->setMangaMangaUpdatesAPI($mangaMangaUpdatesAPI);

        // Persist manga in db
        $this->em->persist($manga);
        $this->em->flush();

        return $manga;
    }

    /**
     * @return array<mixed>
     */
    public function extractDatas(array $result): array
    {
        $muImgJpg = null;
        $muThumbJpg = null;
        if (isset($result['image'])) {
            /**
             * @var array<mixed> $imageLinks
             */
            $imageLinks = $result['image'];
            if (array_key_exists('url', $imageLinks)) {
                $muImgJpg = $imageLinks['url']['original'];
                $muThumbJpg = $imageLinks['url']['thumb'];
            }
        }

        return [
            'muSeriesId' => $result['series_id'],
            'muTitle' => $result['title'],
            'muUrl' => $result['url'],
            'muDescription' => $result['description'],
            'muImgJpg' => $muImgJpg,
            'muThumbJpg' => $muThumbJpg,
            'muYear' => $result['year'],
            'muGenres' => $result['genres'],
        ];
    }

    private function ifReleaseAlreadyExist(Manga $manga, \DateTimeImmutable $releaseDate): ?ReleaseMangaUpdatesAPI
    {
        return $this->em->getRepository(ReleaseMangaUpdatesAPI::class)->findOneBy([
            'manga' => $manga,
            'releasedAt' => $releaseDate,
        ]);
    }
}
