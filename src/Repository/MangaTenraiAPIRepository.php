<?php

namespace App\Repository;

use App\Entity\MangaTenraiAPI;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MangaTenraiAPI>
 *
 * @method MangaTenraiAPI|null find($id, $lockMode = null, $lockVersion = null)
 * @method MangaTenraiAPI|null findOneBy(array $criteria, array $orderBy = null)
 * @method MangaTenraiAPI[]    findAll()
 * @method MangaTenraiAPI[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MangaTenraiAPIRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MangaTenraiAPI::class);
    }

    //    /**
     //     * @return MangaTenraiAPI[] Returns an array of MangaTenraiAPI objects
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

     //    public function findOneBySomeField($value): ?MangaTenraiAPI
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
