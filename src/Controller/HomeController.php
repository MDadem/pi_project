<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use App\Service\TicketService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\File;


final class HomeController extends AbstractController{

    #[Route('/home', name: 'app_home')]

    public function index(): Response
    {
        return $this->render('frontend/home/base.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }


    #[Route('/auth', name: 'app_auth')]
    public function auth(): Response
    {
        return $this->render('frontend/auth/auth.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('backend/home/home.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }


    #[Route('/dashboard/sign_in', name: 'app_dashboard_sign_in')]
    public function dashboard_sign_in(): Response
    {
        return $this->render('backend/signin/signin.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/dashboard/sign_up', name: 'app_dashboard_sign_up')]
    public function dashboard_sign_up(): Response
    {
        return $this->render('backend/signup/signup.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/dashboard/404', name: 'app_dashboard_404')]
    public function dashboard_404(): Response 
    {
        return $this->render('backend/404/404.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/events', name: 'app_events')]
    public function events(EntityManagerInterface $entityManager): Response
    {
        $events = $entityManager->getRepository(Event::class)->findBy(
            ['status' => 'active'],
            ['eventDate' => 'ASC']
        );

        return $this->render('frontend/event/events.html.twig', [
            'events' => $events
        ]);
    }

    #[Route('/event/{id}', name: 'app_event_details', methods: ['GET'])]
    public function eventDetails(Event $event): Response
    {
        return $this->render('frontend/event/show.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/event/{id}/register', name: 'app_event_register', methods: ['POST'])]
    public function registerForEvent(
        Event $event,
        Request $request,
        EntityManagerInterface $entityManager,
        EventRegistrationRepository $registrationRepository,
        TicketService $ticketService
    ): JsonResponse {
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('register', $request->headers->get('X-CSRF-Token'))) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ], 403);
        }

        // Check if user is logged in
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'You must be logged in to register for events.',
                'redirect' => $this->generateUrl('app_login')
            ]);
        }

        try {
            // Check if user is already registered
            $existingRegistration = $registrationRepository->findOneBy([
                'user' => $user,
                'event' => $event
            ]);

            if ($existingRegistration) {
                return $this->json([
                    'success' => false,
                    'message' => 'You are already registered for this event.'
                ]);
            }

            // Check if event has available places
            if ($event->getNumberOfPlaces() <= 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'Sorry, this event is fully booked.'
                ]);
            }

            // Create registration
            $registration = new EventRegistration();
            $registration->setUser($user);
            $registration->setEvent($event);
            $registration->setRegistrationDate(new \DateTime());

            // Generate QR code
            $qrCodePath = $ticketService->generateQrCode($registration);
            $registration->setQrCode($qrCodePath);

            // Decrease available places
            $event->setNumberOfPlaces($event->getNumberOfPlaces() - 1);

            // Save registration
            $entityManager->persist($registration);
            $entityManager->persist($event);
            $entityManager->flush();

            // Generate ticket PDF
            $pdfPath = $ticketService->generateTicketPdf($registration);

            return $this->json([
                'success' => true,
                'message' => 'Successfully registered for the event!',
                'ticketUrl' => $this->generateUrl('app_download_ticket', ['id' => $registration->getId()])
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred while processing your registration: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/ticket/{id}/download', name: 'app_download_ticket')]
    public function downloadTicket(EventRegistration $registration): Response
    {
        // Security check
        if ($registration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this ticket.');
        }

        $pdfPath = 'uploads/tickets/' . $registration->getTicketNumber() . '.pdf';
        
        return $this->file($pdfPath, 'ticket.pdf');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('frontend/about/about.html.twig');
    }

    #[Route('/service', name: 'app_service')]
    public function service(): Response
    {
        return $this->render('frontend/service/service.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('frontend/contact/contact.html.twig');
    }

    #[Route('/feature', name: 'app_feature')]
    public function feature(): Response
    {
        return $this->render('frontend/feature/feature.html.twig');
    }

    #[Route('/team', name: 'app_team')]
    public function team(): Response
    {
        return $this->render('frontend/team/team.html.twig');
    }


    #[Route('/testimonial', name: 'app_testimonial')]
    public function testimonial(): Response
    {
        return $this->render('frontend/testimonial/testimonial.html.twig');
    }

    #[Route('/offer', name: 'app_offer')]
    public function offer(): Response
    {
        return $this->render('frontend/offer/offer.html.twig');
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('frontend/faq/faq.html.twig');
    }

    #[Route('/blog', name: 'app_blog')]
    public function blog(): Response
    {
        return $this->render('frontend/blog/blog.html.twig');
    }

//     #[Route('/404', name: 'app_404')]
//     public function error404(): Response
//     {
//         return $this->render('frontend/error/404.html.twig');
//     }

}
