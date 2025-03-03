<?php

namespace App\Repository;

use App\Entity\EventRegistration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventRegistration>
 *
 * @method EventRegistration|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventRegistration|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventRegistration[]    findAll()
 * @method EventRegistration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRegistration::class);
    }

    public function isUserRegisteredForEvent(int $userId, int $eventId): bool
    {
        return $this->createQueryBuilder('er')
            ->select('COUNT(er.id)')
            ->where('er.user = :userId')
            ->andWhere('er.event = :eventId')
            ->setParameter('userId', $userId)
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }


}
