<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Role;
use App\Form\RegistrationFormType;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Address;

class RegistrationController extends AbstractController
{
    #[Route('/dashboard/signup', name: 'app_dashboard_signup')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        // Redirect logged-in users to another page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_users'); // Redirect to users page if already logged in
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if an admin with the same email already exists
            $existingAdmin = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $user->getEmail(),
            ]);

            if ($existingAdmin && in_array(Role::Admin->value, $existingAdmin->getRoles())) {
                $this->addFlash('error', 'An admin with this email already exists.');
                return $this->redirectToRoute('app_dashboard_signup');
            }

            // Hash the password
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // Assign the ROLE_ADMIN role to the user
            $user->setRoles([Role::Admin->value]);

            // Save the user
            $entityManager->persist($user);
            $entityManager->flush();

            // Generate the login URL
            $loginUrl = $urlGenerator->generate('app_dashboard_signin', [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Send the confirmation email
            try {
                $email = (new Email())
                    ->from(new Address('no-reply@culturespace.com', 'CultureSpace'))
                    ->to($user->getEmail())
                    ->subject('Welcome to Our Platform!')
                    ->html($this->renderView('emails/signup_confirmation.html.twig', [
                        'user' => $user,
                        'login_url' => $loginUrl,
                    ]));

                $mailer->send($email);
                $this->addFlash('success', 'Your account has been created successfully. A Welcome email has been sent.');
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('warning', 'Your account has been created, but the confirmation email could not be sent.');
            }

            // Redirect to the sign-in page with flash message
            return $this->redirectToRoute('app_dashboard_signin');
        }

        return $this->render('backend/signup/signup.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/home/signup', name: 'app_home_signup')]
    public function registerStudent(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        // Redirect logged-in users
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if a user with the same email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $user->getEmail(),
            ]);

            if ($existingUser) {
                $this->addFlash('error', 'A user with this email already exists.');
                return $this->redirectToRoute('app_home_signup');
            }

            // Hash the password
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // Assign the ROLE_STUDENT role
            $user->setRoles([Role::Student->value]);

            // Save the user
            $entityManager->persist($user);
            $entityManager->flush();

            // Generate the login URL
            $loginUrl = $urlGenerator->generate('app_home_signin', [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Send the confirmation email
            try {
                $email = (new Email())
                    ->from(new Address('no-reply@culturespace.com', 'CultureSpace'))
                    ->to($user->getEmail())
                    ->subject('Welcome to Our Platform!')
                    ->html($this->renderView('emails/signup_user_confirmation.html.twig', [
                        'user' => $user,
                        'login_url' => $loginUrl,
                    ]));

                $mailer->send($email);
                $this->addFlash('success', 'Your account has been created successfully. A Welcome email has been sent.');
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('warning', 'Your account has been created, but the confirmation email could not be sent.');
            }

            return $this->redirectToRoute('app_home_signin');
        }

        return $this->render('frontend/auth/signup.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
