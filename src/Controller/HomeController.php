<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Event;

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

    #[Route('/event/{id}', name: 'app_event_details')]
    public function eventDetails(Event $event): Response
    {
        if ($event->getStatus() !== 'active') {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('frontend/event/details.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/event/{id}/register', name: 'app_event_register')]
    public function eventRegister(Event $event): Response
    {
        if ($event->getStatus() !== 'active') {
            throw $this->createNotFoundException('Event not found');
        }

        // TODO: Implement registration logic
        return $this->render('frontend/event/register.html.twig', [
            'event' => $event
        ]);
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
