<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        return $this->render('backend/404/404error.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }


    #[Route('/dashboard/blank', name: 'app_dashboard_blank')]
    public function dashboard_blank(): Response
    {
        return $this->render('backend/blankpage/blank.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }


}
