<?php

namespace App\Repository;

use App\Entity\UserInvitationCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserInvitationCode>
 *
 * @method UserInvitationCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserInvitationCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserInvitationCode[]    findAll()
 * @method UserInvitationCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserInvitationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserInvitationCode::class);
    }

    //    /**
    //     * @return UserInvitationCode[] Returns an array of UserInvitationCode objects
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

    //    public function findOneBySomeField($value): ?UserInvitationCode
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
