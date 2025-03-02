<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
final class CommandeController extends AbstractController
{
    #[Route('/commande/{id<\d+>}', name: 'app_commande_show')]
    public function show(OrderRepository $orderRepository, int $id): Response
    {
        $commande = $orderRepository->find($id);

        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
            'articles' => $commande->getOrderItems(), // Vérifie si cette méthode retourne bien les articles
        ]);
    }

    
    #[Route('/commande/valider', name: 'commande_valider')]
    public function validerCommande(Request $request, ManagerRegistry $doctrine, MailService $mailService): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        $email = $user->getEmail();

        // Simuler la création d'une commande
        $commande = new Order();
        $commande->setUser($user);
        $commande->setTotalPrice(34.450);

        // Sauvegarde en base de données
        $entityManager = $doctrine->getManager();
        $entityManager->persist($commande);
        $entityManager->flush();

        // Préparer les données pour l'email
        $context = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'order' => $commande
        ];

        // Envoyer l'email avec le template dynamique
        $mailService->sendEmail(
            $email, 
            'Confirmation de votre commande', 
            'commande/order_mail.html.twig', 
            $context
        );

        // Ajouter un message flash et rediriger
        $this->addFlash('success', 'Votre commande a été validée et un email de confirmation vous a été envoyé !');
        return $this->redirectToRoute('commande_success');
    }
 
}

