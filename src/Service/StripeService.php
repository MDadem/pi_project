<?php
namespace App\Service;

use Stripe\Stripe;

class StripeService {
    private string $stripeSecretKey;

    public function __construct(string $stripeSecretKey) {
        $this->stripeSecretKey = $stripeSecretKey;
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function createCheckoutSession(array $items): string {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => array_map(fn($item) => [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => $item['name']],
                    'unit_amount' => $item['price'] * 100,
                ],
                'quantity' => $item['quantity'],
            ], $items),
            'mode' => 'payment',
            'success_url' => 'http://localhost:8000/payment/success',
            'cancel_url' => 'http://localhost:8000/payment/cancel',
        ]);

        return $session->url;
    }
}
