<?php

namespace App\Repository;

use App\Entity\MangaType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MangaType>
 *
 * @method MangaType|null find($id, $lockMode = null, $lockVersion = null)
 * @method MangaType|null findOneBy(array $criteria, array $orderBy = null)
 * @method MangaType[]    findAll()
 * @method MangaType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MangaTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MangaType::class);
    }

//    /**
//     * @return MangaType[] Returns an array of MangaType objects
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

//    public function findOneBySomeField($value): ?MangaType
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
