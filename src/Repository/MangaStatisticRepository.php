<?php

namespace App\Repository;

use App\Entity\MangaStatistic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MangaStatistic>
 *
 * @method MangaStatistic|null find($id, $lockMode = null, $lockVersion = null)
 * @method MangaStatistic|null findOneBy(array $criteria, array $orderBy = null)
 * @method MangaStatistic[]    findAll()
 * @method MangaStatistic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MangaStatisticRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MangaStatistic::class);
    }

    //    /**
    //     * @return MangaStatistic[] Returns an array of MangaStatistic objects
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

    //    public function findOneBySomeField($value): ?MangaStatistic
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
