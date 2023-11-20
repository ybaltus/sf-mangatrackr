<?php

namespace App\Repository;

use App\Entity\UserNews;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNews>
 *
 * @method UserNews|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserNews|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserNews[]    findAll()
 * @method UserNews[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserNewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNews::class);
    }

//    /**
//     * @return UserNews[] Returns an array of UserNews objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserNews
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
