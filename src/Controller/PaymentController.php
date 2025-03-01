<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AbstractController
{
    #[Route('/checkout', name: 'checkout')]
    public function checkout(): JsonResponse
    {
        Stripe::setApiKey($this->getParameter('stripe_public_key'));

        // Simulation d'un panier
        $lineItems = [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Commande Artspiration',
                    ],
                    'unit_amount' => 2000, // Prix en centimes (20€)
                ],
                'quantity' => 1,
            ],
        ];

        // Création de la session Stripe
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], Response::HTTP_SEE_OTHER),
            'cancel_url' => $this->generateUrl('payment_cancel', [], Response::HTTP_SEE_OTHER),
        ]);

        return new JsonResponse(['id' => $session->id]);
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function success(): Response
    {
        return $this->render('payment/success.html.twig');
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
