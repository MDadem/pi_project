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

    public function findAllOrderedByVoteScore()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.voteScore', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchProducts(
        ?string $name,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $category,
        ?float $priceMin,
        ?float $priceMax,
        ?string $availability,
        ?string $discountFilter,
        ?string $sortField = 'voteScore',
        ?string $sortDirection = 'DESC'
    ) {
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
        if ($availability && $availability !== 'all') {
            if ($availability === 'available') {
                $qb->andWhere('p.status = :status')
                   ->setParameter('status', true);
            } elseif ($availability === 'unavailable') {
                $qb->andWhere('p.status = :status')
                   ->setParameter('status', false);
            }
        }
        if ($discountFilter && $discountFilter !== 'all') {
            if ($discountFilter === 'discounted') {
                $qb->andWhere('p.discount > 0');
            } elseif ($discountFilter === 'non_discounted') {
                $qb->andWhere('p.discount IS NULL OR p.discount = 0');
            }
        }

        if ($sortField) {
            $qb->orderBy('p.' . $sortField, $sortDirection);
        } else {
            $qb->orderBy('p.voteScore', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}