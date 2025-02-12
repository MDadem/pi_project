<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Role;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/dashboard/signup', name: 'app_dashboard_signup')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // Redirect logged-in users to another page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard'); // Change 'home' to your main route
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if an admin with the same firstName, lastName, and email already exists
            $existingAdmin = $entityManager->getRepository(User::class)->findOneBy([
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'roles' => [Role::Admin->value], // Ensure the existing user has the ROLE_ADMIN role
            ]);

            if ($existingAdmin) {
                // Display a danger flash message and redirect back to the registration form
                $this->addFlash('error', 'Admin Already Exists');
                return $this->redirectToRoute('app_dashboard_signup');
            }

            // Hash the plain password
            $user->setPwd(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Assign the ROLE_ADMIN role to the user
            $user->setRoles([Role::Admin->value]);

            // Save the user
            $entityManager->persist($user);
            $entityManager->flush();

            // Display a success flash message and redirect to the login page
            $this->addFlash('success', 'Admin Added Successfully');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('backend/signup/signup.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
