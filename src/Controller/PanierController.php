<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;


class PanierController extends AbstractController
{
    #[Route('/panier', name: 'panier_index')]
public function index(Request $request, EntityManagerInterface $em, CartRepository $cartRepository): Response
{
    $user = $this->getUser();
    
    if (!$user) {
        throw $this->createAccessDeniedException('Vous devez être connecté pour voir votre panier.');
    }

    $session = $request->getSession();

    // Utilisation de la méthode du repository pour récupérer le panier par user ID
    $cartItems = $cartRepository->getPanierByUser($user);

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
    $user = $this->getUser(); // Get the currently logged-in user

    if (!$product) {
        throw $this->createNotFoundException('Le produit demandé n\'existe pas.');
    }

    if (!$user) {
        $this->addFlash('error', 'Vous devez être connecté pour ajouter un produit au panier.');
        return $this->redirectToRoute('app_login'); // Redirect to login if user is not authenticated
    }

    $quantity = $request->query->getInt('quantity', 1);

    // Check if the product is already in the user's cart
    $existingCartItem = $em->getRepository(Cart::class)->findOneBy([
        'product' => $product,
        'user' => $user
    ]);

    if ($existingCartItem) {
        // Update quantity and total price
        $existingCartItem->setProductQuantity($existingCartItem->getProductQuantity() + $quantity);
        $existingCartItem->setTotal($existingCartItem->getProduct()->getProductPrice() * $existingCartItem->getProductQuantity());
    } else {
        // Create a new cart item
        $cartItem = new Cart();
        $cartItem->setProduct($product);
        $cartItem->setUser($user); // Associate the cart item with the user
        $cartItem->setProductQuantity($quantity);
        $cartItem->setTotal($product->getProductPrice() * $quantity);
        $cartItem->setPrice($product->getProductPrice());
        $em->persist($cartItem);
    }

    $em->flush();

    $this->addFlash('success', 'Produit ajouté au panier avec succès.');
    return $this->redirectToRoute('panier_index');
}

    #[Route('/panier/update/{id}', name: 'panier_update')]
    public function update(Cart $cartItem, Request $request, EntityManagerInterface $em): Response
    {
        $newQuantity = $request->query->getInt('quantity');
        if ($newQuantity > 0) {
            $cartItem->setProductQuantity($newQuantity);
            $cartItem->setTotal($cartItem->getProduct()->getProductPrice() * $newQuantity);
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
public function validateOrder(
    Request $request, 
    EntityManagerInterface $em, 
    MailerInterface $mailer, 
    CartRepository $cartRepository
): Response {
    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'Vous devez être connecté pour valider une commande.');
        return $this->redirectToRoute('app_login');
    }

    if (!$user instanceof User) {
        throw new \LogicException('The logged-in user is not a valid User entity.');
    }

    $cartItems = $cartRepository->getPanierByUser($user);

    if (empty($cartItems)) {
        $this->addFlash('error', 'Votre panier est vide.');
        return $this->redirectToRoute('panier_index');
    }

    $totalGeneral = array_reduce($cartItems, fn($total, $item) => $total + $item->getTotal(), 0);

    // Création de la commande
    $order = new Order();
    $order->setCreationDate(new \DateTime());
    $order->setStatus('En cours');
    $order->setTotalPrice($totalGeneral);
    $order->setUser($user);

    $session = $request->getSession();
    $sessionCartItems = [];

    foreach ($cartItems as $cartItem) {
        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setProduct($cartItem->getProduct());
        $orderItem->setQuantity($cartItem->getProductQuantity());
        $orderItem->setPrice($cartItem->getPrice());
        $orderItem->setTotal($cartItem->getTotal());
    
        $order->addCartItem($orderItem); // Assure-toi que cette méthode existe dans Order.php
        $em->persist($orderItem);
    
        $sessionCartItems[] = [
            'product_name' => $cartItem->getProduct()->getProductName(),
            'quantity' => $cartItem->getProductQuantity(),
            'price' => $cartItem->getPrice(),
            'total' => $cartItem->getTotal(),
        ];
    }

    $em->persist($order);
    $em->flush();

    $session->set('validated_cart', $sessionCartItems);

    // **Envoi de l'email de confirmation**
    try {
        $email = (new Email())
            ->from(new Address('no-reply@culturespace.com', 'CultureSpace'))
            ->to($user->getEmail())
            ->subject('Confirmation de votre commande')
            ->html($this->renderView('emails/order_confirmation.html.twig', [
                'user' => $user,
                'order' => $order,
                'cartItems' => $sessionCartItems,
            ]));

        $mailer->send($email);

        $em->remove($cartItem);
        $em->flush();

        $this->addFlash('success', 'Votre commande a été validée et un email de confirmation vous a été envoyé !');

    } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
        $this->addFlash('warning', 'Commande validée, mais l’email de confirmation n’a pas pu être envoyé.');
    }

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
