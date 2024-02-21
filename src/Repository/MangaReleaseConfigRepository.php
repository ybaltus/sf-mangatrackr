<?php

namespace App\Repository;

use App\Entity\MangaReleaseConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MangaReleaseConfig>
 *
 * @method MangaReleaseConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method MangaReleaseConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method MangaReleaseConfig[]    findAll()
 * @method MangaReleaseConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MangaReleaseConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MangaReleaseConfig::class);
    }

    //    /**
    //     * @return MangaReleaseConfig[] Returns an array of MangaReleaseConfig objects
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

    //    public function findOneBySomeField($value): ?MangaReleaseConfig
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
