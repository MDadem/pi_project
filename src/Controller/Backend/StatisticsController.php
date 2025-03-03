<?php

namespace App\Controller\Backend;

use App\Entity\Event;
use App\Entity\Category;
use App\Repository\EventRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard')]
class StatisticsController extends AbstractController
{
    #[Route('/statistics', name: 'app_dashboard_statistics')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $eventRepo = $entityManager->getRepository(Event::class);
        $categoryRepo = $entityManager->getRepository(Category::class);

        // Get total counts
        $totalEvents = $eventRepo->count([]);
        $totalCategories = $categoryRepo->count([]);
        $totalPlaces = $eventRepo->createQueryBuilder('e')
            ->select('SUM(e.numberOfPlaces)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Get events per category for the pie chart
        $eventsPerCategory = $categoryRepo->createQueryBuilder('c')
            ->select('c.name', 'COUNT(e.id) as eventCount')
            ->leftJoin('c.events', 'e')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();

        return $this->render('backend/statistics/dashboard.html.twig', [
            'totalEvents' => $totalEvents,
            'totalCategories' => $totalCategories,
            'totalPlaces' => $totalPlaces,
            'eventsPerCategory' => $eventsPerCategory
        ]);
    }

    #[Route('/statistics/charts', name: 'app_dashboard_statistics_charts')]
    public function charts(EntityManagerInterface $entityManager): Response
    {
        $eventRepo = $entityManager->getRepository(Event::class);

        // Get events with their dates
        $events = $eventRepo->createQueryBuilder('e')
            ->select('e.eventDate')
            ->getQuery()
            ->getResult();

        // Process monthly distribution in PHP
        $monthlyCount = array_fill(1, 12, 0); // Initialize counts for all months
        foreach ($events as $event) {
            $month = (int)$event['eventDate']->format('n');
            $monthlyCount[$month]++;
        }

        // Format data for the chart
        $monthlyEvents = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyEvents[] = [
                'month' => $i,
                'count' => $monthlyCount[$i]
            ];
        }

        // Get category distribution
        $categoryDistribution = $eventRepo->createQueryBuilder('e')
            ->select('c.name', 'COUNT(e.id) as count')
            ->join('e.category', 'c')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();

        return $this->render('backend/statistics/charts.html.twig', [
            'monthlyEvents' => $monthlyEvents,
            'categoryDistribution' => $categoryDistribution
        ]);
    }
}