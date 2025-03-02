<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminCommandeController extends AbstractController
{
    #[Route('/admin/commande', name: 'admin_commande')]
    public function index(EntityManagerInterface $em): Response
    {
        $commandes = $em->getRepository(Order::class)->findAll();

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

    #[Route('/admin/commande/{id}/delete', name: 'admin_commande_delete')]
    public function delete(Order $order, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('success', 'La commande a été supprimée avec succès.');
        return $this->redirectToRoute('admin_commande');
    }
    #[Route('/admin/commande/{id}', name: 'admin_commande_detail')]
    public function show(Order $order): Response
    {
        return $this->render('commande/order_detail.html.twig', [
            'order' => $order,
        ]);
    }
}