<?php

namespace App\Repository;

use App\Entity\MangaJikanAPI;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MangaJikanAPI>
 *
 * @method MangaJikanAPI|null find($id, $lockMode = null, $lockVersion = null)
 * @method MangaJikanAPI|null findOneBy(array $criteria, array $orderBy = null)
 * @method MangaJikanAPI[]    findAll()
 * @method MangaJikanAPI[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MangaJikanAPIRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MangaJikanAPI::class);
    }

//    /**
//     * @return MangaJikanAPI[] Returns an array of MangaJikanAPI objects
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

//    public function findOneBySomeField($value): ?MangaJikanAPI
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
