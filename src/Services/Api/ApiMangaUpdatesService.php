<?php

namespace App\Services\Api;

use App\Entity\Manga;
use App\Entity\MangaMangaUpdatesAPI;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
use App\Entity\ReleaseMangaUpdatesAPI;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiMangaUpdatesService extends AbstractApiService
{
    public string $baseUrl;
    public const LIMIT_SEARCH = 15;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
        private readonly string $apiMangaUpdatesUrl
    ) {
        parent::__construct($this->httpClient, $this->em, $this->slugger);
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
            'include_metadata' => true,
            'per_page' => $limit,
        ];

        // Execute post request
        $response = $this->postRequest($this->baseUrl . '/releases/search', $apiParams);

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
                    $this->baseUrl . '/releases/search',
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
     * Persist calendar datas in database.
     *
     * @param array<mixed> $releases
     */
    public function saveReleaseDataInDb(array $releases): ?ReleaseMangaUpdatesAPI
    {
        // Extract record and metadata
        $releaseMetaDatas = $releases['metadata'];
        $releaseDatas = $releases['record'];
        $titleHtmlEntityDecode = html_entity_decode($releaseDatas['title']);
        $manga = null;

        /*
         * Check if the manga already exists by metadata series_id.
         * Because $releaseDatas['title'] can be different to metadata title.
         */
        if (
            !$manga
            && $releaseMetaDatas
            && array_key_exists('series', $releaseMetaDatas)
            && $releaseMetaDatas['series']['series_id']
        ) {
            $manga = $this->verifyIfExistInDbWithReleaseMetadataSerieId(
                $releaseMetaDatas['series']['series_id']
            );
        }

        // Check if the manga already exists by title.
        if (!$manga) {
            $manga = $this->verifyIfExistInDb(
                Manga::class,
                $titleHtmlEntityDecode,
                true
            );
        }

        /**
         * Check if the manga already exists by other titles.
         *
         * @var Manga|bool $manga
         */
        if (!$manga) {
            $manga = $this->verifyIfExistWithOtherTitles($titleHtmlEntityDecode);
        }

        // Release management
        $releaseEntity = null;

        /**
         * @var Manga|null $manga
         */
        if ($manga) {
            $releaseDate = new \DateTimeImmutable($releaseDatas['release_date']);

            // Check if the calendar already exists
            $releaseEntity = $this->ifReleaseAlreadyExist(
                $manga,
                $releaseDate,
            ) ?: new ReleaseMangaUpdatesAPI();

            // Get chapter
            $chapter = $releaseDatas['chapter'];
            if (str_contains($chapter, 'a') || str_contains($chapter, 'b') || str_contains($chapter, 'c')) {
                $chapter = str_replace(['a', 'b', 'c'], '.5', $chapter);
            }

            // Set calendar data
            $releaseEntity
                ->setManga($manga)
                ->setVolumeVal($releaseDatas['volume'])
                ->setChapterVal($chapter)
                ->setReleasedAt($releaseDate)
            ;

            // Edit nbChapters, nbVolumes and lastReleasedAt for the manga
            $multipleChapter = explode('-', $chapter);
            if (count($multipleChapter) > 1) {
                $newMaxChapter = floatval($multipleChapter[1]);
            } else {
                $newMaxChapter = floatval($chapter);
            }

            if ($manga->getNbChapters() < $newMaxChapter) {
                $manga->setNbChapters($newMaxChapter);
            }

            // Edit nbVolumes for the manga
            if (
                !$manga->getNbVolumes()
                || ($releaseDatas['volume'] && $manga->getNbVolumes() < (int) $releaseDatas['volume'])
            ) {
                $manga->setNbVolumes($releaseDatas['volume']);
            }

            // Edit lastReleasedAt for the manga
            if (!$manga->getLastReleasedAt() || $manga->getLastReleasedAt() < $releaseDate) {
                $manga->setLastReleasedAt($releaseDate);
            }

            // Save in DB
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
                'Doujinshi',
                'Ecchi',
                'Hentai',
                'Gender Bender',
                'Lolicon',
                'Shotacon',
                'Shounen Ai',
                'Smut',
                'Yaoi',
                'Yuri',
            ];
        }

        // Execute post request
        $response = $this->postRequest($this->baseUrl . '/series/search', $apiParams);

        if (!$this->handleHttpStatusCode($response->getStatusCode())) {
            return ["Error http response : {$response->getStatusCode()}"];
        }

        return $response->toArray()['results'];
    }

    /**
     * Persist manga datas in database.
     *
     * Must be executed after ApiJikanService->saveMangaDatasInDb
     *
     * @param array<mixed> $mangaDatas
     */
    public function saveMangaDatasInDb(array $mangaDatas): ?Manga
    {
        $result = $this->extractDatas($mangaDatas['record']);
        $avoidDoubleTitleSlug = null;

        // Check 1: Check if the manga already exists
        $manga = $this->verifyIfMangaExistInDb(
            $result['muTitle'],
            $result['muCategory']
        );

        // Check 2: Check if the manga already exists by other titles
        if (!$manga) {
            $manga = $this->verifyIfExistWithOtherTitles($result['muTitle']);
            if ($manga && $manga->getCategory() !== $this->getMangaCategory($result['muCategory'])) {
                $manga = null;
            }
        }

        // Check 3: Check if the year is the same in order to skip some wrong results
        if ($manga && $manga->getPublishedAt() && intval($result['muYear']) !== intval($manga->getPublishedAt()->format('Y'))) {
                return null;
        }

        // Check 4: Check if a manga already exists with the simple slug (only title)
        if (!$manga) {
            $avoidDoubleTitleSlug = $this->verifyByTitleAndSerieId($result['muTitle'], $result['muSeriesId']);
            if (!is_string($avoidDoubleTitleSlug)) {
                $manga = $avoidDoubleTitleSlug;
            }
        }

        // Set manga datas
        if (!$manga) {
            $manga = new Manga();

            if (is_string($avoidDoubleTitleSlug)) {
                $manga->setTitleSlug($avoidDoubleTitleSlug);
            }

            $manga->setTitle($result['muTitle'])
                ->setTitleAlternative($result['muTitle'])
                ->setDescription($result['muDescription'])
                ->setCategory($this->getMangaCategory($result['muCategory']))
                ->setAuthor('Inconnu')
                ->setNbChapters(1)
                ->setPublishedAt(new \DateTimeImmutable($result['muYear'] . '-01-01'))
                ->setIsAdult($this->checkIfAdult($result['muGenres']))
            ;
        } else {
            /**
             * Update some datas.
             *
             * @var Manga $manga
             */
            $manga
                ->setDescription($manga->getDescription() ?? $result['muDescription'])
                ->setCategory($manga->getCategory() && 'Unknown' !== $manga->getCategory()->value ?
                    $manga->getCategory() : $this->getMangaCategory($result['muCategory']))
                ->setUpdatedAt(new \DateTimeImmutable())
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
            'muTitle' => html_entity_decode($result['title']),
            'muUrl' => $result['url'],
            'muDescription' => $result['description'],
            'muCategory' => $result['type'],
            'muImgJpg' => $muImgJpg,
            'muThumbJpg' => $muThumbJpg,
            'muYear' => '' !== $result['year'] ? $result['year'] : null,
            'muGenres' => $result['genres'] ?? [],
        ];
    }

    private function ifReleaseAlreadyExist(Manga $manga, \DateTimeImmutable $releaseDate): ?ReleaseMangaUpdatesAPI
    {
        return $this->em->getRepository(ReleaseMangaUpdatesAPI::class)->findOneBy([
            'manga' => $manga,
            'releasedAt' => $releaseDate,
        ]);
    }

    /**
     * Check if an manga already exists metadata[series][series_id].
     */
    private function verifyIfExistInDbWithReleaseMetadataSerieId(string $metadataSeriesId): bool|object
    {
        $repository = $this->em->getRepository(MangaMangaUpdatesAPI::class);
        $releaseEntity = $repository->findOneByMuSeriesId($metadataSeriesId);
        if ($releaseEntity) {
            return $releaseEntity->getManga();
        }

        return false;
    }

    private function verifyIfExistWithOtherTitles(string $title): bool|Manga
    {
        $mangaRepository = $this->em->getRepository(Manga::class);

        $results = $mangaRepository->searchByTitles($title);

        if ($results) {
            return $results[0];
        }

        return false;
    }

    private function verifyByTitleAndSerieId(string $title, string $seriesId): Manga|string|null
    {
        $repository = $this->em->getRepository(Manga::class);
        $titleSlug = $this->slugger->slug($title)->lower();
        $manga = $repository->findOneByTitleSlug($titleSlug);

        if ($manga) {
            $titleSlugWithSerieId = $this->slugger->slug($title)->lower()->slice(0, 10) . '-' . $seriesId;
            $mangaSlugWithSerieId = $repository->findOneByTitleSlug($titleSlugWithSerieId);

            if (!$mangaSlugWithSerieId) {
                return $titleSlugWithSerieId;
            } else {
                return $mangaSlugWithSerieId;
            }
        }

        return null;
    }
}
