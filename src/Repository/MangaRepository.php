<?php

namespace App\Repository;

use App\Entity\Enum\MangaCategoryEnum;
use App\Entity\Manga;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Manga>
 *
 * @method Manga|null find($id, $lockMode = null, $lockVersion = null)
 * @method Manga|null findOneBy(array $criteria, array $orderBy = null)
 * @method Manga[]    findAll()
 * @method Manga[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MangaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manga::class);
    }

    /**
     * @return array<Manga>
     */
    public function getTopMangas(int $quantity = 4, bool $isAdult = false, ?string $durationDateInterval = null): array
    {
        $query = $this->createQueryBuilder('m')
            ->join('m.mangaJikanAPI', 'a')
            ->where('m.isActivated != FALSE')
            ->orderBy('a.malScored', 'DESC')
            ->setMaxResults($quantity)
        ;

        if (!$isAdult) {
            $query
                ->andWhere('m.isAdult = FALSE')
            ;
        }

        if ($durationDateInterval) {
            $dateLimitPast = (new \DateTimeImmutable())->sub(new \DateInterval($durationDateInterval));
            $query->andWhere('m.publishedAt >= :dateLimit')
                ->setParameter('dateLimit', $dateLimitPast);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return array<Manga>
     */
    public function getLatestMangas(int $quantity = 4, bool $isAdult = false): array
    {
        $query = $this->createQueryBuilder('m')
            ->where('m.isActivated != FALSE')
            ->orderBy('m.publishedAt', 'DESC')
            ->setMaxResults($quantity)
        ;

        if (!$isAdult) {
            $query
                ->andWhere('m.isAdult = FALSE')
            ;
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return array<Manga>
     */
    public function getMangas(int $quantity = 16): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isActivated != FALSE')
            ->orderBy('m.titleSlug', 'ASC')
            ->setMaxResults($quantity)
            ->getQuery()
            ->getResult()
        ;
    }

    public function paginationQuery(bool $isAdult = false): Query
    {
        $query = $this->createQueryBuilder('m')
            ->where('m.isActivated != FALSE')
            ->orderBy('m.titleSlug', 'ASC')
        ;

        if (!$isAdult) {
            $query
                ->andWhere('m.isAdult = FALSE')
            ;
        }

        return $query->getQuery();
    }

    /**
     * @return array<Manga>
     */
    public function searchManga(string $searchTerm, bool $isAdult = false, int $maxResults = 16): array
    {
        $query = $this->createQueryBuilder('m')
            ->where('m.isActivated != FALSE')
            ->andWhere('m.title LIKE :searchTerm')
            ->orderBy('m.titleSlug', 'ASC')
            ->setParameter('searchTerm', '%'.$searchTerm.'%')
        ;

        if (!$isAdult) {
            $query
                ->andWhere('m.isAdult = FALSE')
            ;
        }

        return $query->getQuery()
            ->setMaxResults($maxResults)
            ->getResult();
    }

    /**
     * @return Manga[]
     */
    public function searchByTitles(string $title): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.titleSynonym LIKE :title')
            ->orWhere('m.titleEnglish LIKE :title')
            ->setParameter('title', $title)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getBySlugTitleAndCategory(string $slugTitle, string $category): ?Manga
    {
        $unknownCategory = MangaCategoryEnum::UNKNOWN->value;

        return $this->createQueryBuilder('m')
            ->andWhere('m.titleSlug = :slugTitle')
            ->andWhere('m.category = :category OR m.category = :unknownCategory')
            ->setParameter('slugTitle', $slugTitle)
            ->setParameter('category', $category)
            ->setParameter('unknownCategory', $unknownCategory)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
