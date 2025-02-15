<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminCommandeController extends AbstractController
{
    #[Route('/admin/commande', name: 'admin_commande')]
    public function index(OrderRepository $orderRepository): Response
    {
        $commandes = $orderRepository->findAll();
        return $this->render('commande/order_list.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/admin/commande/{id}/change-status', name: 'admin_commande_change_status', methods: ['POST'])]
    public function changeStatus(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('status');
        if ($newStatus) {
            $order->setStatus($newStatus);
            $entityManager->flush();
            $this->addFlash('success', 'Le statut de la commande a été modifié avec succès.');
        } else {
            $this->addFlash('error', 'Le statut est invalide.');
        }
        return $this->redirectToRoute('admin_commande');
    }
}
