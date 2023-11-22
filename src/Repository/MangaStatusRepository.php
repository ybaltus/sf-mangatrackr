<?php

namespace App\Repository;

use App\Entity\MangaStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MangaStatus>
 *
 * @method MangaStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MangaStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MangaStatus[]    findAll()
 * @method MangaStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MangaStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MangaStatus::class);
    }

    //    /**
    //     * @return MangaStatus[] Returns an array of MangaStatus objects
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

    //    public function findOneBySomeField($value): ?MangaStatus
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
