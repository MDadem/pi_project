<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthenticatorService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameters)
    {
        $this->entityManager = $em;
        $this->params = $parameters;
    }

    /**
     * Generates a QR code URI and secret for the user.
     */
    public function getQrCodeUri(User $user): array
    {
        $totp = TOTP::generate();
        $totp->setIssuer($this->params->get('app.issuer')); // Configure issuer in `services.yaml`
        $totp->setLabel($user->getLastName());

        // Generate QR code URI
        $qrCodeUri = $totp->getProvisioningUri();

        return [$qrCodeUri, $totp->getSecret()];
    }

    /**
     * Validates the pairing and saves the secret to the user.
     */
    public function validatePairing(User $user, string $secret): void
    {
        if (!$secret) {
            throw new \InvalidArgumentException('Secret cannot be empty.');
        }

        $user->setGoogleAuthenticatorSecret($secret);
        $this->entityManager->flush();
    }

    /**
     * Verifies the TOTP code.
     */
    public function verifyCode(User $user, string $code): bool
    {
        $secret = $user->getGoogleAuthenticatorSecret();
        if (!$secret) {
            throw new \RuntimeException('User has no secret configured.');
        }

        $totp = TOTP::create($secret);
        return $totp->verify($code);
    }
}