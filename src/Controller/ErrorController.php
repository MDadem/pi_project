<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{
    #[Route('/error/{slug}', name: 'error_page')]
    public function show($slug): Response
    {
        // render the 404 error page template
        return $this->render('bundles/TwigBundle/Exception/error404.html.twig');
    }

    // Handle 404 errors explicitly (if needed)
    public function notFound(): Response
    {
        return $this->render('bundles/TwigBundle/Exception/error404.html.twig');
    }
}
