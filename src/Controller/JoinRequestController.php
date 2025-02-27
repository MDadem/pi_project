<?php

namespace App\Controller;

use App\Entity\Community;
use App\Entity\CommunityMembers;
use App\Entity\JoinRequest;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
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
    public function respondToRequest(JoinRequest $joinRequest, string $decision, ManagerRegistry $doctrine, MailerInterface $mailer): Response
    {
        if (!in_array($decision, ['accepted', 'rejected'])) {
            throw $this->createNotFoundException('Décision invalide.');
        }
    
        $entityManager = $doctrine->getManager();
        $joinRequest->setStatus($decision);
    
        $user = $joinRequest->getUser();
        $community = $joinRequest->getCommunity();
        
        if ($decision === 'accepted') {
            $communityMember = new CommunityMembers();
            $communityMember->setCommunity($community);
            $communityMember->setUser($user);
            $communityMember->setJoinedAt(new \DateTime());
            $entityManager->persist($communityMember);
        }
    
        $entityManager->flush();
    
        // Envoi d'un email à l'utilisateur
        try {
            $email = (new Email())
                ->from(new Address('no-reply@culturespace.com', 'CultureSpace'))
                ->to($user->getEmail())
                ->subject($decision === 'accepted' ? 'Votre adhésion a été acceptée' : 'Votre demande a été refusée')
                ->html($this->renderView('emails/join_request_response.html.twig', [
                    'user' => $user,
                    'community' => $community,
                    'decision' => $decision,
                ]));

            $mailer->send($email);
            $this->addFlash('success', 'Demande mise à jour avec succès. Un email a été envoyé à lutilisateur.');
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->addFlash('warning', 'Demande mise à jour, mais l email de notification n a pas pu être envoyé.');
        }
    
        return $this->redirectToRoute('join_request_list');
    }
 }
