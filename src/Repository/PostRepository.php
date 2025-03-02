<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

//    /**
//     * @return Post[] Returns an array of Post objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Post
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function findByCommunityName(string $communityName): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.community', 'c') // Include posts without a community
            ->andWhere('c.name = :name')
            ->setParameter('name', $communityName)
            ->orderBy('p.creation_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUserPostStatistics(): array
{
    return $this->createQueryBuilder('p')
        ->select('u.id, u.firstName, u.lastName, COUNT(p.id) AS post_count')
        ->join('p.user', 'u')
        ->groupBy('u.id')
        ->orderBy('post_count', 'DESC')
        ->getQuery()
        ->getResult();
}

}
