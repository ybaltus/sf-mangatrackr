<?php

namespace App\Repository;

use App\Entity\TextContentPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TextContentPage>
 *
 * @method TextContentPage|null find($id, $lockMode = null, $lockVersion = null)
 * @method TextContentPage|null findOneBy(array $criteria, array $orderBy = null)
 * @method TextContentPage[]    findAll()
 * @method TextContentPage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextContentPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TextContentPage::class);
    }

    //    /**
    //     * @return TextContentPage[] Returns an array of TextContentPage objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TextContentPage
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
