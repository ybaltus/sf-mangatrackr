<?php

namespace App\Repository;

use App\Entity\StatusTrack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StatusTrack>
 *
 * @method StatusTrack|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatusTrack|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatusTrack[]    findAll()
 * @method StatusTrack[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusTrackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatusTrack::class);
    }

    //    /**
    //     * @return StatusTrack[] Returns an array of StatusTrack objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?StatusTrack
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
