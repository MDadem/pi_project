<?php

namespace App\Controller\Frontend;

use App\Entity\Event;
use App\Entity\EventRegistration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Security\Core\Security;
use App\Service\TicketService;

class EventRegistrationController extends AbstractController
{
    private $entityManager;
    private $ticketService;

    public function __construct(EntityManagerInterface $entityManager, TicketService $ticketService)
    {
        $this->entityManager = $entityManager;
        $this->ticketService = $ticketService;
    }

    #[Route('/events/register/{id}', name: 'app_event_register', methods: ['POST'])]
    public function register(Event $event): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'Please login to register for events.');
                return $this->redirectToRoute('app_login');
            }

            // Check if user is already registered
            if ($event->getEventRegistrations()->exists(function($key, $registration) use ($user) {
                return $registration->getUser() === $user;
            })) {
                $this->addFlash('error', 'You are already registered for this event.');
                return $this->redirectToRoute('app_events');
            }

            // Check if there are available places
            if (!$event->hasAvailablePlaces()) {
                $this->addFlash('error', 'Sorry, this event is fully booked.');
                return $this->redirectToRoute('app_events');
            }

            // Generate ticket number
            $ticketNumber = 'TKT-' . strtoupper(bin2hex(random_bytes(4)));

            // Create new registration
            $registration = new EventRegistration();
            $registration->setUser($user)
                        ->setEvent($event)
                        ->setRegistrationDate(new \DateTime())
                        ->setTicketNumber($ticketNumber);

            // Generate QR code
            try {
                $qrCode = $this->ticketService->generateQrCode($registration);
                $registration->setQrCode($qrCode);

                // Decrease available places
                $event->decrementPlaces();
                
                $this->entityManager->persist($registration);
                $this->entityManager->persist($event);
                $this->entityManager->flush();

                // Generate and save PDF ticket
                $pdfPath = $this->ticketService->generateTicketPdf($registration);
                
                // Set response headers for PDF download
                $response = new Response(file_get_contents($this->getParameter('kernel.project_dir') . '/public/' . $pdfPath));
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set(
                    'Content-Disposition',
                    'attachment; filename="ticket-' . $registration->getTicketNumber() . '.pdf"'
                );

                return $response;
            } catch (\Exception $e) {
                $this->entityManager->persist($registration);
                $this->entityManager->persist($event);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Successfully registered for the event! However, there was an issue generating your ticket. You can download it later from your profile.');
                return $this->redirectToRoute('app_events');
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while processing your registration. Please try again.');
            return $this->redirectToRoute('app_events');
        }
    }

    #[Route('/events/ticket/{id}', name: 'app_event_download_ticket', methods: ['GET'])]
    public function downloadTicket(EventRegistration $registration): Response
    {
        if ($registration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Access denied');
        }

        try {
            // Generate and save PDF ticket
            $pdfPath = $this->ticketService->generateTicketPdf($registration);
            
            // Set response headers for PDF download
            $response = new Response(file_get_contents($this->getParameter('kernel.project_dir') . '/public/' . $pdfPath));
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set(
                'Content-Disposition',
                'attachment; filename="ticket-' . $registration->getTicketNumber() . '.pdf"'
            );

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to generate ticket. Please try again later.');
            return $this->redirectToRoute('app_events');
        }
    }

    #[Route('/events/ticket/{eventId}', name: 'app_event_ticket', methods: ['GET'])]
    public function showTicket(int $eventId, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        $registration = $entityManager->getRepository(EventRegistration::class)->findOneBy(['event' => $eventId, 'user' => $user]);
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }

        return $this->render('frontend/event/ticket.html.twig', [
            'event' => $registration->getEvent(),
            'user' => $user,
            'qrCodeUrl' => '/' . $registration->getQrCode(),
            'registration' => $registration
        ]);
    }

    #[Route('/events/{eventId}/info', name: 'app_event_registration_info')]
    public function eventInfo(Event $event): Response
    {
        return $this->render('frontend/event/event_info.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/events/info/{eventId}', name: 'app_event_info', methods: ['GET'])]
    public function showEventInfo(int $eventId, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('frontend/event/event_info.html.twig', [
            'event' => $event
        ]);
    }
}
