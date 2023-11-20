<?php

namespace App\Repository;

use App\Entity\Fantrad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fantrad>
 *
 * @method Fantrad|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fantrad|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fantrad[]    findAll()
 * @method Fantrad[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FantradRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fantrad::class);
    }

//    /**
//     * @return Fantrad[] Returns an array of Fantrad objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Fantrad
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
