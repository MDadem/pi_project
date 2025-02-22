<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\CommunityRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
{
    private CommunityRepository $communityRepository;
    private PostRepository $postRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CommunityRepository $communityRepository,
        PostRepository $postRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->communityRepository = $communityRepository;
        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/accueil/community/{id}/posts', name: 'community_posts_manage', methods: ['GET', 'POST'])]
public function managePosts(int $id, Request $request, SluggerInterface $slugger): Response
{
    $user = $this->getUser();

    // Vérifier si l'utilisateur est authentifié
    if (!$user) {
        return $this->redirectToRoute('dashboard_signin');
    }

    $community = $this->communityRepository->find($id);
    if (!$community) {
        throw $this->createNotFoundException('Community not found');
    }

    // Vérifier si l'utilisateur est membre de la communauté (optionnel)
    // if (!$community->getCommunityMembers()->contains($user)) {
    //     throw $this->createAccessDeniedException('You are not a member of this community.');
    // }

    $posts = $this->postRepository->findBy(['community' => $community]);

    $post = new Post();
    $post->setCommunity($community);
    $post->setCreationDate(new \DateTime());
    $post->setModificationDate(new \DateTime());
    $post->setUser($user); // Utilisation de l'utilisateur connecté

    $form = $this->createForm(PostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('postImg')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('post_images_directory'),
                    $newFilename
                );
                $post->setPostImg('uploads/post_images/' . $newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload image.');
            }
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $this->addFlash('success', 'Post created successfully!');

        return $this->redirectToRoute('community_posts_manage', ['id' => $id]);
    }


    return $this->render('accueil/community-post.html.twig', [
        'community' => $community,
        'posts' => $posts,
        'form' => $form->createView(),
    ]);
}


    #[Route('/delete/{postId}', name: 'community_post_delete', methods: ['POST'])]
    public function deletePost(int $id, int $postId, Request $request): Response
    {
        $post = $this->postRepository->find($postId);
        if (!$post || $post->getCommunity()->getId() !== $id) {
            throw $this->createNotFoundException('Post not found');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('community_posts_manage', ['id' => $id]);
    }
}
