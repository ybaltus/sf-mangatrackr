<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Manga;
use App\Entity\MangaTenraiAPI;
use App\Repository\MangaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Non-regression tests for the queries made portable for PostgreSQL
 * (NULL ordering and case-insensitive comparisons, chantier migration-postgresql T4.7).
 */
final class MangaRepositoryTest extends KernelTestCase
{
    private const FAR_FUTURE = '2126-01-01 00:00:00';

    private EntityManagerInterface $em;

    private MangaRepository $repository;

    private string $uniqueId;

    /**
     * @var Manga[]
     */
    private array $createdMangas = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Manga::class);
        $this->uniqueId = bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        foreach ($this->createdMangas as $manga) {
            $managed = $this->em->find(Manga::class, $manga->getId());
            if ($managed) {
                $this->em->remove($managed);
            }
        }
        $this->em->flush();
        $this->createdMangas = [];

        parent::tearDown();
    }

    private function createManga(
        string $title,
        ?float $malScored = null,
        ?\DateTimeImmutable $publishedAt = new \DateTimeImmutable(),
    ): Manga {
        $manga = (new Manga())
            ->setTitle($title)
            ->setAuthor('Auteur test')
            ->setPublishedAt($publishedAt)
        ;
        $manga->setMangaTenraiAPI(
            (new MangaTenraiAPI())
                ->setManga($manga)
                ->setMalScored($malScored)
        );

        $this->em->persist($manga);
        $this->em->flush();
        $this->createdMangas[] = $manga;

        return $manga;
    }

    public function testTopMangasPutNullScoresLast(): void
    {
        $scored = $this->createManga('Top score '.$this->uniqueId, 9.99);
        $unscored = $this->createManga('Top sans score '.$this->uniqueId);

        $results = $this->repository->getTopMangas(1000, true);
        $ids = array_map(static fn (Manga $m): ?int => $m->getId(), $results);

        $this->assertContains($scored->getId(), $ids);
        $this->assertContains($unscored->getId(), $ids);
        $this->assertNotNull($results[0]->getMangaTenraiAPI()?->getMalScored(), 'A NULL score must never come first.');
        $this->assertLessThan(
            array_search($unscored->getId(), $ids, true),
            array_search($scored->getId(), $ids, true)
        );

        // Non-NULL scores first, sorted DESC, then only NULL scores.
        $seenNull = false;
        $previous = null;
        foreach ($results as $manga) {
            $score = $manga->getMangaTenraiAPI()?->getMalScored();
            if (null === $score) {
                $seenNull = true;
                continue;
            }
            $this->assertFalse($seenNull, 'A non-NULL score appears after a NULL score.');
            if (null !== $previous) {
                $this->assertLessThanOrEqual($previous, $score);
            }
            $previous = $score;
        }
    }

    public function testTopMangasWithQuantityOneReturnsAScoredManga(): void
    {
        $this->createManga('Top unique score '.$this->uniqueId, 9.99);
        $this->createManga('Top unique sans score '.$this->uniqueId);

        $results = $this->repository->getTopMangas(1, true);

        $this->assertCount(1, $results);
        $this->assertNotNull($results[0]->getMangaTenraiAPI()?->getMalScored());
    }

    public function testLatestMangasPutNullPublicationDatesLast(): void
    {
        // Far future date: no fixture nor API manga (network tests) can be more recent.
        $dated = $this->createManga('Recent date '.$this->uniqueId, null, new \DateTimeImmutable(self::FAR_FUTURE));
        $undated = $this->createManga('Recent sans date '.$this->uniqueId, null, null);

        $results = $this->repository->getLatestMangas(1000, true);
        $ids = array_map(static fn (Manga $m): ?int => $m->getId(), $results);

        $this->assertContains($undated->getId(), $ids);
        $this->assertSame($dated->getId(), $results[0]->getId(), 'The most recent dated manga must come first.');

        $seenNull = false;
        $previous = null;
        foreach ($results as $manga) {
            $publishedAt = $manga->getPublishedAt();
            if (null === $publishedAt) {
                $seenNull = true;
                continue;
            }
            $this->assertFalse($seenNull, 'A dated manga appears after an undated one.');
            if (null !== $previous) {
                $this->assertLessThanOrEqual($previous, $publishedAt);
            }
            $previous = $publishedAt;
        }
    }

    public function testTopMangasTieBreakIsStable(): void
    {
        $older = $this->createManga('Top egalite A '.$this->uniqueId, 10.0);
        $newer = $this->createManga('Top egalite B '.$this->uniqueId, 10.0);

        $ids = array_map(static fn (Manga $m): ?int => $m->getId(), $this->repository->getTopMangas(1000, true));

        // Same score: highest id first, on every engine.
        $this->assertLessThan(
            array_search($older->getId(), $ids, true),
            array_search($newer->getId(), $ids, true)
        );
    }

    public function testLatestMangasTieBreakIsStable(): void
    {
        $sameDate = new \DateTimeImmutable(self::FAR_FUTURE);
        $older = $this->createManga('Recent egalite A '.$this->uniqueId, null, $sameDate);
        $newer = $this->createManga('Recent egalite B '.$this->uniqueId, null, $sameDate);

        $results = $this->repository->getLatestMangas(2, true);

        $this->assertSame(
            [$newer->getId(), $older->getId()],
            array_map(static fn (Manga $m): ?int => $m->getId(), $results)
        );
    }

    public function testSearchMangaIsCaseInsensitive(): void
    {
        $manga = $this->createManga('Recherche Casse '.$this->uniqueId);

        // Search term as typed by the user (string from the front, ADR 0001).
        $results = $this->repository->searchManga('rECHERCHE cASSE '.strtoupper($this->uniqueId), true);

        $this->assertSame([$manga->getId()], array_map(static fn (Manga $m): ?int => $m->getId(), $results));
    }

    public function testSearchMangaIsAccentInsensitive(): void
    {
        $accented = $this->createManga('Évangélion Ça '.$this->uniqueId);
        $plain = $this->createManga('Evangelion Ca bis '.$this->uniqueId);

        $fromPlain = $this->repository->searchManga('evangelion ca', true, 100);
        $fromAccented = $this->repository->searchManga('ÉVANGÉLION ÇA', true, 100);
        $ids = static fn (array $mangas): array => array_map(static fn (Manga $m): ?int => $m->getId(), $mangas);

        foreach ([$ids($fromPlain), $ids($fromAccented)] as $found) {
            $this->assertContains($accented->getId(), $found);
            $this->assertContains($plain->getId(), $found);
        }
        $this->assertSame([], $this->repository->searchManga('evangelion zzz'.$this->uniqueId, true));
    }

    public function testSearchMangaTreatsUnderscoreAsLiteral(): void
    {
        $literal = $this->createManga('Joker a_c '.$this->uniqueId);
        $other = $this->createManga('Joker abc '.$this->uniqueId);

        $results = $this->repository->searchManga('joker a_c '.$this->uniqueId, true);

        $this->assertSame([$literal->getId()], array_map(static fn (Manga $m): ?int => $m->getId(), $results));
        $this->assertNotContains($other->getId(), array_map(static fn (Manga $m): ?int => $m->getId(), $results));
    }

    public function testSearchByTitlesIsCaseInsensitiveEquality(): void
    {
        $bySynonym = $this->createManga('Synonyme '.$this->uniqueId)
            ->setTitleSynonym('Synonym Title '.$this->uniqueId);
        $byEnglish = $this->createManga('Anglais '.$this->uniqueId)
            ->setTitleEnglish('English Title '.$this->uniqueId);
        $this->em->flush();

        $synonymResults = $this->repository->searchByTitles('SYNONYM title '.strtoupper($this->uniqueId));
        $englishResults = $this->repository->searchByTitles('english TITLE '.$this->uniqueId);
        $partialResults = $this->repository->searchByTitles('English Title');

        $this->assertSame([$bySynonym->getId()], array_map(static fn (Manga $m): ?int => $m->getId(), $synonymResults));
        $this->assertSame([$byEnglish->getId()], array_map(static fn (Manga $m): ?int => $m->getId(), $englishResults));
        $this->assertNotContains($byEnglish->getId(), array_map(static fn (Manga $m): ?int => $m->getId(), $partialResults));
    }

    public function testSearchByTitlesIsOrderedById(): void
    {
        $first = $this->createManga('Doublon A '.$this->uniqueId)
            ->setTitleEnglish('Same Title '.$this->uniqueId);
        $second = $this->createManga('Doublon B '.$this->uniqueId)
            ->setTitleSynonym('same title '.$this->uniqueId);
        $this->em->flush();

        $results = $this->repository->searchByTitles('Same Title '.$this->uniqueId);

        $this->assertSame(
            [$first->getId(), $second->getId()],
            array_map(static fn (Manga $m): ?int => $m->getId(), $results)
        );
    }
}
