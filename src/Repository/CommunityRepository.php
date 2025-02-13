<?php

namespace App\Repository;

use App\Entity\Community;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Community>
 */
class CommunityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Community::class);
    }

//    /**
//     * @return Community[] Returns an array of Community objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Community
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function findAllWithMemberCount(): array
{
    return $this->createQueryBuilder('c')
        ->select('c, COUNT(cm.id) AS memberCount')
        ->leftJoin('c.communityMembers', 'cm')
        ->groupBy('c.id')
        ->getQuery()
        ->getResult();
}

// src/Repository/CommunityRepository.php
public function findCommunityWithMembers(int $id): ?Community
{
    return $this->createQueryBuilder('c')
        ->leftJoin('c.communityMembers', 'cm')
        ->leftJoin('cm.user', 'u')
        ->addSelect('cm', 'u')
        ->where('c.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}

}
