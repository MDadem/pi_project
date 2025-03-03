<?php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Order;

class MailService {
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer) {
        $this->mailer = $mailer;
    }

    public function sendOrderConfirmation(string $toEmail, Order $order): void {
        $email = (new Email())
            ->from('noreply@yourwebsite.com')
            ->to($toEmail)
            ->subject('Votre commande a été validée !')
            ->html('<p>Votre commande n°' . $order->getId() . ' a été payée avec succès.</p>');

        $this->mailer->send($email);
    }
}
