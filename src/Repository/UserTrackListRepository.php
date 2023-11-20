<?php

namespace App\Repository;

use App\Entity\UserTrackList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserTrackList>
 *
 * @method UserTrackList|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserTrackList|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserTrackList[]    findAll()
 * @method UserTrackList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserTrackListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTrackList::class);
    }

//    /**
//     * @return UserTrackList[] Returns an array of UserTrackList objects
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

//    public function findOneBySomeField($value): ?UserTrackList
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
