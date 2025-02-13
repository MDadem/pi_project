<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;


final class UserController extends AbstractController {

    #[Route('/dashboard/edit-profile/{id}', name: 'app_edit_profile')]
    public function edit(User $user, Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->getUser() !== $user) {
            return $this->redirectToRoute('dashboard');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('profileIMG')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('profile_images_directory'),
                        $newFilename
                    );
                    $user->setProfileIMG('uploads/profile_images/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_edit_profile', ['id' => $user->getId()]);
        }

        return $this->render('backend/user/edit_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
