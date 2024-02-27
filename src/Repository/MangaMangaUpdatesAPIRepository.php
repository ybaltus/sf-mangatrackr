<?php

namespace App\Repository;

use App\Entity\MangaMangaUpdatesAPI;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MangaMangaUpdatesAPI>
 *
 * @method MangaMangaUpdatesAPI|null find($id, $lockMode = null, $lockVersion = null)
 * @method MangaMangaUpdatesAPI|null findOneBy(array $criteria, array $orderBy = null)
 * @method MangaMangaUpdatesAPI[]    findAll()
 * @method MangaMangaUpdatesAPI[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MangaMangaUpdatesAPIRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MangaMangaUpdatesAPI::class);
    }

    //    /**
    //     * @return MangaMangaUpdatesAPI[] Returns an array of MangaMangaUpdatesAPI objects
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

    //    public function findOneBySomeField($value): ?MangaMangaUpdatesAPI
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
