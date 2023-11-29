<?php

namespace App\Services\Api;

use App\Entity\Editor;
use App\Entity\Manga;
use App\Entity\MangaJikanAPI;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
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
        parent::__construct($httpClient, $em);
        $this->baseUrl = $apiJikanUrl;
    }

    /**
     * Fetch manga datas by title.
     *
     * @return array<mixed>
     */
    public function fetchMangaByTitle(string $searchTerm, int $limit = self::LIMIT_SEARCH): array
    {
        $queryParams = [
            'q' => $searchTerm,
            'limit' => $limit,
        ];

        $response = $this->getRequest($this->baseUrl.'/manga', $queryParams);

        if (!$this->handleHttpStatusCode($response->getStatusCode())) {
            return ["Error http response : {$response->getStatusCode()}"];
        }

        return $response->toArray()['data'];
    }

    /**
     * Fetch the top of mangas.
     *
     * @return string[]
     */
    public function fetchTopManga(int $limit = self::LIMIT_SEARCH): array
    {
        $queryParams = [
            'limit' => $limit,
        ];

        $response = $this->getRequest($this->baseUrl.'/top/manga', $queryParams);

        if (!$this->handleHttpStatusCode($response->getStatusCode())) {
            return ["Error http response : {$response->getStatusCode()}"];
        }

        return $response->toArray()['data'];
    }

    /**
     * Persist manga datas in database.
     *
     * @param array<mixed> $mangaDatas
     */
    public function saveMangaDatasInDb(array $mangaDatas): Manga
    {
        $result = $this->extractDatas($mangaDatas);

        // Check if the manga already exists
        $manga = $this->verifyIfExistInDb(
            Manga::class,
            $result['malTitle'],
            true
        );

        if (!$manga) {
            $manga = new Manga();
        }

        // Set manga datas
        /**
         * @var Manga $manga
         */
        $manga->setTitle($result['malTitle'])
            ->setTitleAlternative($result['malTitleAlternative'])
            ->setNbChapters($result['malChapters'] ?? 1)
            ->setDescription($result['malDescription'])
            ->setAuthor($result['malAuthors'][0])
            ->setPublishedAt(new \DateTimeImmutable($result['malStartPublishedAt']))
        ;

        // Set MangaType for manga entity
        foreach ($result['malGenres'] as $genreName) {
            $mangaType = $this->verifyIfExistInDb(
                MangaType::class,
                $genreName
            );

            if (!$mangaType) {
                $mangaType = (new MangaType())
                    ->setName($genreName)
                ;
            }
            if (!$manga->getMangaType()->contains($mangaType)) {
                $manga->addMangaType($mangaType);
            }
        }

        // Set MangaStatus for manga entity
        $mangaStatus = $this->verifyIfExistInDb(
            MangaStatus::class,
            $result['malStatus'],
            true
        );
        if (!$mangaStatus) {
            $mangaStatus = (new MangaStatus())
                ->setTitle($result['malStatus'])
            ;
        }
        $manga->setMangaStatus($mangaStatus);

        // Set Editor for manga entity
        foreach ($result['malSerializations'] as $editorName) {
            $editor = $this->verifyIfExistInDb(
                Editor::class,
                $editorName
            );

            if (!$editor) {
                $editor = (new Editor())
                    ->setName($editorName)
                ;
            }
            if (!$manga->getEditor()->contains($editor)) {
                $manga->addEditor($editor);
            }
        }

        // MangaJianAPI entity
        $mangaJikanApi = $manga->getMangaJikanAPI();
        if (!$mangaJikanApi) {
            $mangaJikanApi = new MangaJikanAPI();
        }
        $mangaJikanApi
            ->setManga($manga)
            ->setMalId($result['malId'])
            ->setMalDescription($result['malDescription'])
            ->setMalUrl($result['malUrl'])
            ->setMalImgJpg($result['malImgJpg'])
            ->setMalImgJpgLarge($result['malImgJpgLarge'])
            ->setMalImgWebp($result['malImgWebp'])
            ->setMalImgWebpLarge($result['malImgWebpLarge'])
            ->setMalChapters($result['malChapters'])
            ->setMalVolume($result['malVolumes'])
            ->setMalStartPublishedAt(new \DateTimeImmutable($result['malStartPublishedAt']))
            ->setMalEndPublishedAt(new \DateTimeImmutable($result['malEndPublishedAt']))
            ->setMalDemographics($result['malDemographics'])
            ->setMalGenres($result['malGenres'])
            ->setMalSerializations($result['malSerializations'])
            ->setMalAuthors($result['malAuthors'])
            ->setMalScored($result['malScored'])
            ->setMalScroredBy($result['malScoredBy'])
            ->setMalRank($result['malRank'])
        ;
        $manga->setMangaJikanAPI($mangaJikanApi);

        // Persist manga in db
        $this->em->persist($manga);
        $this->em->flush();

        return $manga;
    }

    /**
     * @param array<mixed> $result
     *
     * @return array<mixed>
     */
    public function extractDatas(array $result): array
    {
        return [
            'malId' => $result['mal_id'],
            'malTitle' => $result['title'],
            'malTitleAlternative' => $result['title_japanese'],
            'malStatus' => $result['status'],
            'malDescription' => $result['synopsis'],
            'malUrl' => $result['url'],
            'malImgJpg' => $result['images']['jpg']['image_url'] ?? null,
            'malImgJpgLarge' => $result['images']['jpg']['large_image_url'] ?? null,
            'malImgWebp' => $result['images']['webp']['image_url'] ?? null,
            'malImgWebpLarge' => $result['images']['webp']['large_image_url'] ?? null,
            'malChapters' => $result['chapters'],
            'malVolumes' => $result['volumes'],
            'malStartPublishedAt' => $result['published']['from'] ?? null,
            'malEndPublishedAt' => $result['published']['to'] ?? null,
            'malDemographics' => $this->extractDatasFromArray($result['demographics'], 'name'), // array
            'malGenres' => $this->extractDatasFromArray($result['genres'], 'name'), // array
            'malSerializations' => $this->extractDatasFromArray($result['serializations'], 'name'), // array
            'malAuthors' => $this->extractDatasFromArray($result['authors'], 'name'), // array
            'malScored' => $result['scored'] ?? null,
            'malScoredBy' => $result['scored_by'] ?? null,
            'malRank' => $result['rank'] ?? null,
        ];
    }
}