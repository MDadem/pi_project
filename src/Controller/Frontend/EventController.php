<?php

namespace App\Controller\Frontend;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_event_list')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy(
            ['status' => Event::STATUS_ACTIVE],
            ['eventDate' => 'ASC']
        );

        return $this->render('frontend/event/events.html.twig', [
            'events' => $events
        ]);
    }
}
