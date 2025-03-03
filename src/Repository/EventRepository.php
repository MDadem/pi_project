<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c');

        if (!empty($filters['search'])) {
            $qb->andWhere('e.title LIKE :search OR e.eventLocation LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
                ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('e.eventDate >= :dateFrom')
                ->setParameter('dateFrom', new DateTime($filters['dateFrom']));
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('e.eventDate <= :dateTo')
                ->setParameter('dateTo', new DateTime($filters['dateTo'] . ' 23:59:59'));
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'eventDate';
        $sortOrder = $filters['order'] ?? 'ASC';
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['title', 'eventDate', 'numberOfPlaces', 'status'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'eventDate';
        }
        
        $qb->orderBy('e.' . $sortField, $sortOrder === 'DESC' ? 'DESC' : 'ASC');

        return $qb->getQuery()->getResult();
    }
}
