<?php

namespace App\Services\Api;

use App\Entity\Editor;
use App\Entity\Manga;
use App\Entity\MangaJikanAPI;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiJikanService extends AbstractApiService
{
    public string $baseUrl;
    public const LIMIT_SEARCH = 15;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
        private readonly string $apiJikanUrl,
    ) {
        parent::__construct($this->httpClient, $this->em, $this->slugger);
        $this->baseUrl = $this->apiJikanUrl;
    }

    /**
     * Fetch manga datas by title.
     *
     * @return array<mixed>
     */
    public function fetchMangaByTitle(string $searchTerm, bool $isAdult = false, int $limit = self::LIMIT_SEARCH): array
    {
        $queryParams = [
            'q' => $searchTerm,
            'limit' => $limit,
        ];

        if (!$isAdult) {
            $queryParams['genres_exclude'] = '12,9,49,28,26';
        }

        $response = $this->getRequest($this->baseUrl.'/manga', $queryParams);

        if (!$this->handleHttpStatusCode($response->getStatusCode())) {
            return ["Error http response : {$response->getStatusCode()}"];
        }

        return $response->toArray()['data'];
    }

    /**
     * Fetch the top of mangas.
     *
     * @return array<mixed>
     */
    public function fetchTopManga(int $limit = self::LIMIT_SEARCH): array
    {
        // $limit max = 25 for Jikan
        $limit = $limit <= 25 ? $limit : 25;

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
     * Fetch the latest mangas.
     *
     * @return array<mixed>
     */
    public function fetchLastestManga(int $limit = self::LIMIT_SEARCH): array
    {
        // $limit max = 25 for Jikan
        $limit = $limit <= 25 ? $limit : 25;

        $queryParams = [
            'order_by' => 'start_date',
            'sort' => 'desc',
            'limit' => $limit,
        ];

        $response = $this->getRequest($this->baseUrl.'/manga', $queryParams);
        if (!$this->handleHttpStatusCode($response->getStatusCode())) {
            return ["Error http response : {$response->getStatusCode()}"];
        }

        return $response->toArray()['data'];
    }

    /**
     * Persist manga datas in database.
     *
     * @param array<string> $mangaDatas
     */
    public function saveMangaDatasInDb(array $mangaDatas): Manga
    {
        $result = $this->extractDatas($mangaDatas);

        $startPublishedAt = $result['malStartPublishedAt'] ?
            new \DateTimeImmutable($result['malStartPublishedAt']) : null;
        $endPublishedAt = $result['malEndPublishedAt'] ?
            new \DateTimeImmutable($result['malEndPublishedAt']) : null;

        // Check if the manga already exists by title and type
        $manga = $this->verifyIfMangaExistInDb(
            $result['malTitle'],
            $result['malCategory']
        );

        /**
         * @var Manga|bool $manga
         */
        if (!$manga) {
            $manga = $this->verifyByTitleAndMalId($result['malTitle'], $result['malId'], false, true);
        } elseif (
            !in_array($manga->getAuthor(), $result['malAuthors'])
            && ($manga->getPublishedAt()->format('Y') !== $startPublishedAt->format('Y'))
        ) {
            // Because there can be two mangas with the same title and category but not the same author and date.
            // So we check with the malId
            $manga = $this->verifyByTitleAndMalId($result['malTitle'], $result['malId'], true);
        }

        // Set manga datas
        /**
         * @var Manga $manga
         */
        $manga->setTitle($result['malTitle'])
            ->setTitleAlternative($result['malTitleAlternative'])
            ->setTitleEnglish($result['malTitleEnglish'])
            ->setTitleSynonym($result['malTitleSynonym'])
            ->setDescription($result['malDescription'])
            ->setCategory($this->getMangaCategory($result['malCategory']))
            ->setAuthor($result['malAuthors'][0] ?? 'Inconnu')
            ->setPublishedAt($startPublishedAt)
            ->setIsAdult($this->checkIfAdult($result['malGenres']))
        ;

        // Set max chapter
        $newMaxChapter = $result['malChapters'] ? floatval($result['malChapters']) : 1;
        if ($manga->getNbChapters() < $newMaxChapter) {
            $manga->setNbChapters($newMaxChapter);
        }

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
            ->setMalStartPublishedAt($startPublishedAt)
            ->setMalEndPublishedAt($endPublishedAt)
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
            'malTitleEnglish' => $result['title_english'] ?? null,
            'malTitleSynonym' => $result['title_synonyms'][0] ?? null,
            'malCategory' => $result['type'] ?? null,
            'malStatus' => $result['status'],
            'malDescription' => $result['synopsis'],
            'malUrl' => $result['url'],
            'malImgJpg' => $result['images']['jpg']['image_url'] ?? null,
            'malImgJpgLarge' => $result['images']['jpg']['large_image_url'] ?? null,
            'malImgWebp' => $result['images']['webp']['image_url'] ?? null,
            'malImgWebpLarge' => $result['images']['webp']['large_image_url'] ?? null,
            'malChapters' => $result['chapters'],
            'malVolumes' => $result['volumes'],
            'malStartPublishedAt' => $result['published']['from'],
            'malEndPublishedAt' => $result['published']['to'],
            'malDemographics' => $this->extractDatasFromArray($result['demographics'], 'name'), // array
            'malGenres' => $this->extractDatasFromArray($result['genres'], 'name'), // array
            'malSerializations' => $this->extractDatasFromArray($result['serializations'], 'name'), // array
            'malAuthors' => $this->extractDatasFromArray($result['authors'], 'name'), // array
            'malScored' => $result['scored'] ?? null,
            'malScoredBy' => $result['scored_by'] ?? null,
            'malRank' => $result['rank'] ?? null,
        ];
    }

    private function verifyByTitleAndMalId(
        string $title,
        string $malId,
        bool $isSetTitleSlug = false,
        bool $isCheckWithOnlyTitle = false
    ): Manga {
        $titleSlug = $this->slugger->slug($title)->lower()->slice(0, 10).'-'.$malId;
        $manga = $this->em->getRepository(Manga::class)->findOneByTitleSlug($titleSlug);

        if (!$manga) {
            if ($isCheckWithOnlyTitle) {
                $manga = $this->verifyIfExistInDb(Manga::class, $title, true);

                if ($manga) {
                    $manga = null;
                    $isSetTitleSlug = true;
                }
            }

            if ($isSetTitleSlug) {
                $manga = (new Manga())
                    ->setTitleSlug($titleSlug);
            } else {
                $manga = new Manga();
            }
        }

        return $manga;
    }
}
