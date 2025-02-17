<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'panier_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $cartItems = $em->getRepository(Cart::class)->findAll();

        $validatedCart = $session->get('validated_cart', []);
        $totalGeneral = array_reduce($cartItems, function ($total, $item) {
            return $total + $item->getTotal();
        }, 0);

        return $this->render('panier/index.html.twig', [
            'cartItems' => $cartItems,
            'validatedCart' => $validatedCart,
            'totalGeneral' => $totalGeneral,
        ]);
    }

    #[Route('/panier/add/{id}', name: 'panier_add')]
    public function add(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $product = $em->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Le produit demandé n\'existe pas.');
        }

        $quantity = $request->query->getInt('quantity', 1);
        $existingCartItem = $em->getRepository(Cart::class)->findOneBy(['product' => $product]);

        if ($existingCartItem) {
            $existingCartItem->setProductQuantity($existingCartItem->getProductQuantity() + $quantity);
            $existingCartItem->setTotal($existingCartItem->getProduct()->getPrix() * $existingCartItem->getProductQuantity());
        } else {
            $cartItem = new Cart();
            $cartItem->setProduct($product);
            $cartItem->setProductQuantity($quantity);
            $cartItem->setTotal($product->getPrix() * $quantity);
            $cartItem->setPrice($product->getPrix());
            $em->persist($cartItem);
        }

        $em->flush();

        $this->addFlash('success', 'Le produit a été ajouté au panier.');
        return $this->redirectToRoute('panier_index');
    }

    #[Route('/panier/update/{id}', name: 'panier_update')]
    public function update(Cart $cartItem, Request $request, EntityManagerInterface $em): Response
    {
        $newQuantity = $request->query->getInt('quantity');
        if ($newQuantity > 0) {
            $cartItem->setProductQuantity($newQuantity);
            $cartItem->setTotal($cartItem->getProduct()->getPrix() * $newQuantity);
            $em->flush();
            $this->addFlash('success', 'Quantité mise à jour.');
        } else {
            $this->addFlash('error', 'La quantité doit être supérieure à zéro.');
        }

        return $this->redirectToRoute('panier_index');
    }

    #[Route('/panier/clear', name: 'panier_clear')]
    public function clear(Request $request, EntityManagerInterface $em): Response
    {
        $cartItems = $em->getRepository(Cart::class)->findAll();
        foreach ($cartItems as $cartItem) {
            $em->remove($cartItem);
        }
        $em->flush();

        $session = $request->getSession();
        $session->remove('validated_cart');

        $this->addFlash('success', 'Le panier a été vidé.');
        return $this->redirectToRoute('panier_index');
    }

    #[Route('/panier/remove/{id}', name: 'panier_remove')]
    public function remove(Cart $cartItem, EntityManagerInterface $em): Response
    {
        $em->remove($cartItem);
        $em->flush();

        $this->addFlash('success', 'Le produit a été supprimé du panier.');
        return $this->redirectToRoute('panier_index');
    }

    #[Route('/order/validate', name: 'order_validate')]
public function validateOrder(Request $request, EntityManagerInterface $em): Response
{
    $cartItems = $em->getRepository(Cart::class)->findAll();

    if (empty($cartItems)) {
        $this->addFlash('error', 'Votre panier est vide.');
        return $this->redirectToRoute('panier_index');
    }

    $totalGeneral = array_reduce($cartItems, function ($total, $item) {
        return $total + $item->getTotal();
    }, 0);

    // Création de la commande
    $order = new Order();
    $order->setCreationDate(new \DateTime());
    $order->setStatus('En cours');
    $order->setTotalPrice($totalGeneral);
    $order->setUser($this->getUser());

    // Copier les articles du panier dans la commande avant suppression
    foreach ($cartItems as $cartItem) {
        $orderItem = new OrderItem(); // Crée une nouvelle entité OrderItem (si elle existe)
        $orderItem->setOrder($order);
        $orderItem->setProduct($cartItem->getProduct());
        $orderItem->setPrice($cartItem->getPrice());
        $orderItem->setQuantity($cartItem->getProductQuantity());
        $orderItem->setTotal($cartItem->getTotal());

        $em->persist($orderItem);
    }

    $em->persist($order);
    $em->flush();

    // Supprimer les articles du panier après copie
    foreach ($cartItems as $cartItem) {
        $em->remove($cartItem);
    }
    $em->flush();

    $this->addFlash('success', 'Votre commande a été validée avec succès.');
    return $this->redirectToRoute('order_confirmation');
}


    #[Route('/order/confirmation', name: 'order_confirmation')]
    public function confirmation(Request $request): Response
    {
        $session = $request->getSession();
        $validatedCart = $session->get('validated_cart', []);

        $session->remove('validated_cart');

        return $this->render('panier/confirmation.html.twig', [
            'message' => 'Votre commande a été validée avec succès !',
            'validatedCart' => $validatedCart,
        ]);
    }
}
