<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }
    public function searchProducts(?string $name, ?string $dateFrom, ?string $dateTo, ?string $category, ?float $priceMin, ?float $priceMax)
    {
        $qb = $this->createQueryBuilder('p');

        if ($name) {
            $qb->andWhere('p.productName LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($dateFrom) {
            $qb->andWhere('p.createdAt >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        if ($dateTo) {
            $qb->andWhere('p.createdAt <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo));
        }
        if ($category) {
            $qb->join('p.productCategory', 'pc')
                ->andWhere('pc.id = :category')
                ->setParameter('category', $category);
        }
        if ($priceMin !== null) {
            $qb->andWhere('p.productPrice >= :priceMin')
                ->setParameter('priceMin', $priceMin);
        }
        if ($priceMax !== null) {
            $qb->andWhere('p.productPrice <= :priceMax')
                ->setParameter('priceMax', $priceMax);
        }

        return $qb->getQuery()->getResult();
    }



    //    /**
    //     * @return Product[] Returns an array of Product objects
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

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
