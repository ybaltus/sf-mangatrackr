<?php

namespace App\Repository;

use App\Entity\ReleaseMangaUpdatesAPI;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReleaseMangaUpdatesAPI>
 *
 * @method ReleaseMangaUpdatesAPI|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReleaseMangaUpdatesAPI|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReleaseMangaUpdatesAPI[]    findAll()
 * @method ReleaseMangaUpdatesAPI[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReleaseMangaUpdatesAPIRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReleaseMangaUpdatesAPI::class);
    }

    public function paginationQuery(): Query
    {
        $query = $this->createQueryBuilder('r')
            ->addSelect()
            ->join('r.manga', 'm')
            ->orderBy('r.releasedAt', 'DESC')
            ->addOrderBy('m.title', 'ASC')
        ;

        return $query->getQuery();
    }

    //    /**
    //     * @return ReleaseMangaUpdatesAPI[] Returns an array of ReleaseMangaUpdatesAPI objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ReleaseMangaUpdatesAPI
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
