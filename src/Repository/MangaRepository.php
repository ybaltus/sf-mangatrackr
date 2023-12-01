<?php

namespace App\Repository;

use App\Entity\Manga;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function getTopMangas(int $quantity = 4): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.mangaJikanAPI', 'a')
            ->where('m.isActivated != FALSE')
            ->orderBy('a.malScored', 'DESC')
            ->setMaxResults($quantity)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Manga>
     */
    public function getLatestMangas(int $quantity = 4): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isActivated != FALSE')
            ->orderBy('m.publishedAt', 'DESC')
            ->setMaxResults($quantity)
            ->getQuery()
            ->getResult()
        ;
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

    //    /**
    //     * @return Manga[] Returns an array of Manga objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Manga
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
