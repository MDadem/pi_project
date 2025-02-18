<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\JoinRequest;


final class JoinRequestController extends AbstractController
{
    #[Route('backoffice/join_request', name: 'join_request_list')]
    public function listRequests(ManagerRegistry $doctrine): Response
    {
        $requests = $doctrine->getRepository(JoinRequest::class)->findBy(['status' => 'pending']);

        return $this->render('join_request/index.html.twig', [
            'requests' => $requests,
        ]);
    }


    #[Route('/respond/{id}/{decision}', name: 'join_request_respond', methods: ['POST'])]
    public function respondToRequest(JoinRequest $joinRequest, string $decision, ManagerRegistry $doctrine, Request $request): Response
    {
        if (!in_array($decision, ['accepted', 'rejected'])) {
            throw $this->createNotFoundException('Décision invalide.');
        }

        $entityManager = $doctrine->getManager();
        $joinRequest->setStatus($decision);

        // Si accepté, ajouter l'utilisateur à la communauté
        if ($decision === 'accepted') {
            $community = $joinRequest->getCommunity();
            $user = $joinRequest->getUser();
          //  $community->addMember($user); // Assurez-vous que cette méthode existe dans Community.php
        }

        $entityManager->flush();

        $this->addFlash('success', 'Demande mise à jour avec succès.');

        return $this->redirectToRoute('join_request_list');
    }

}
