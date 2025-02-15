<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'panier_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $cartItems = $em->getRepository(Cart::class)->findAll();
        $totalGeneral = array_reduce($cartItems, function ($total, $item) {
            return $total + $item->getTotal();
        }, 0);

        return $this->render('panier/index.html.twig', [
            'cartItems' => $cartItems,
            'totalGeneral' => $totalGeneral,
        ]);
    }

    #[Route('/panier/update/{id}', name: 'panier_update', methods: ['POST'])]
    public function update(Cart $cartItem, Request $request, EntityManagerInterface $em): Response
    {
        $newQuantity = $request->request->getInt('quantity');
        if ($newQuantity > 0) {
            $cartItem->setProductQuantity($newQuantity);
            $cartItem->setTotal($cartItem->getProduct()->getProductPrice() * $newQuantity);
            $em->flush();
        }
        return $this->redirectToRoute('panier_index');
    }

    #[Route('/panier/clear', name: 'panier_clear')]
    public function clear(EntityManagerInterface $em): Response
    {
        $cartItems = $em->getRepository(Cart::class)->findAll();
        foreach ($cartItems as $cartItem) {
            $em->remove($cartItem);
        }
        $em->flush();
        return $this->redirectToRoute('panier_index');
    }

    #[Route('/order/validate', name: 'order_validate')]
    public function validateOrder(EntityManagerInterface $em): Response
    {
        // Exemple logique pour valider la commande
        return $this->render('panier/confirmation.html.twig', [
            'message' => 'Votre commande a été validée avec succès !',
        ]);
    }
}
