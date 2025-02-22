<?php

namespace App\Controller;

use App\Entity\Community;
use App\Entity\CommunityMembers;
use App\Entity\JoinRequest;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/join/{communityId}', name: 'join_request_send', methods: ['POST'])]
    public function sendJoinRequest(int $communityId, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login'); // Redirection si l'utilisateur n'est pas connecté
        }

        $entityManager = $doctrine->getManager();
        $community = $entityManager->getRepository(Community::class)->find($communityId);

        if (!$community) {
            throw $this->createNotFoundException('Communauté non trouvée.');
        }

        // Vérifier si une demande existe déjà pour cet utilisateur et cette communauté
        $existingRequest = $entityManager->getRepository(JoinRequest::class)->findOneBy([
            'user' => $user,
            'community' => $community,
            'status' => 'pending'
        ]);

        if ($existingRequest) {
            $this->addFlash('warning', 'Vous avez déjà envoyé une demande.');
            return $this->redirectToRoute('community_list'); // Rediriger vers la liste des communautés
        }

        // Créer une nouvelle demande d'adhésion
        $joinRequest = new JoinRequest();
        $joinRequest->setUser($user);
        $joinRequest->setCommunity($community);
        $joinRequest->setJoinDate(new \DateTime());
        $joinRequest->setStatus('pending');

        $entityManager->persist($joinRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande a été envoyée.');

        return $this->redirectToRoute('community_list');
    }

    #[Route('/respond/{id}/{decision}', name: 'join_request_respond', methods: ['POST'])]
    public function respondToRequest(JoinRequest $joinRequest, string $decision, ManagerRegistry $doctrine): Response
    {
        if (!in_array($decision, ['accepted', 'rejected'])) {
            throw $this->createNotFoundException('Décision invalide.');
        }
    
        $entityManager = $doctrine->getManager();
        $joinRequest->setStatus($decision);
    
        // Si accepté, ajouter l'utilisateur à la communauté via CommunityMembers
        if ($decision === 'accepted') {
            $community = $joinRequest->getCommunity();
            $user = $joinRequest->getUser();
    
            // Création explicite de CommunityMembers
            $communityMember = new CommunityMembers();
            $communityMember->setCommunity($community);
            $communityMember->setUser($user);
            $communityMember->setJoinedAt(new \DateTime());
    
            $entityManager->persist($communityMember); // 🔥 Nécessaire pour éviter l'erreur
        }
    
        $entityManager->flush();
    
        $this->addFlash('success', 'Demande mise à jour avec succès.');
    
        return $this->redirectToRoute('join_request_list');
    }
    }
