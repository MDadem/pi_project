<?php

namespace App\Controller;

use App\Entity\Community;
use App\Entity\Post;
use App\Entity\PostComment;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\CommunitNameType;
use App\Form\PostType;
use App\Repository\CommunityRepository;
use App\Repository\PostCommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Cast\Array_;
use Doctrine\Persistence\ManagerRegistry;
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
    public function managePosts(int $id, Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('dashboard_signin');
        }
    
        $community = $entityManager->getRepository(Community::class)->find($id);
        if (!$community) {
            throw $this->createNotFoundException('Community not found');
        }
    
        $posts = $entityManager->getRepository(Post::class)->findBy(['community' => $community]);
    
        // Formulaire de création de post
        $post = new Post();
        $post->setCommunity($community);
        $post->setCreationDate(new \DateTime());
        $post->setModificationDate(new \DateTime());
        $post->setUser($user);
    
        $postForm = $this->createForm(PostType::class, $post);
        $postForm->handleRequest($request);
    
        if ($postForm->isSubmitted() && $postForm->isValid()) {
            $imageFile = $postForm->get('postImg')->getData();
    
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
    
            $entityManager->persist($post);
            $entityManager->flush();
    
            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('community_posts_manage', ['id' => $id]);
        }
    
        // Gestion des commentaires : créer un formulaire pour chaque post
        $commentForms = [];
        $commentSubmitted = false;
    
        foreach ($posts as $post) {
            $comment = new PostComment();
            $commentForm = $this->createForm(CommentType::class, $comment);
            $commentForm->handleRequest($request);
    
            if ($commentForm->isSubmitted() && $commentForm->isValid() && $request->request->has('comment_submit_' . $post->getId())) {
                // Associez ce commentaire au bon post
                $comment->setUser($user);
                $comment->setCreationDate(new \DateTime());
                $comment->setPost($post);
                $entityManager->persist($comment);
                $entityManager->flush();
    
                $this->addFlash('success', 'Comment added successfully!');
                $commentSubmitted = true;
                break; // Exit loop after processing the submitted comment
            }
    
            $commentForms[$post->getId()] = $commentForm->createView();
        }
    
        if ($commentSubmitted) {
            return $this->redirectToRoute('community_posts_manage', ['id' => $id]);
        }
    
        return $this->render('accueil/community-post.html.twig', [
            'community' => $community,
            'posts' => $posts,
            'postForm' => $postForm->createView(),
            'commentForms' => $commentForms,
        ]);
    }
    

    #[Route('/dashboard/posts', name: 'community_post_list')]
    public function allPosts(Request $request, PostRepository $postRepository): Response
    {
        // Create the form
        $form = $this->createForm(CommunitNameType::class);
        $form->handleRequest($request);

        // Check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            $community = $form->get('community')->getData(); 
            $communityName = $community ? $community->getName() : null;// Get the selected community
            $posts = $postRepository->findByCommunityName($communityName);
        } else {
            $posts = $postRepository->findAll(); // Default: show all posts
        }

        return $this->render('post/posts-list.html.twig', [
            'form' => $form->createView(),
            'posts' => $posts,
        ]);
    }



    #[Route('/delete/{postId}', name: 'community_post_delete', methods: ['POST'])]
public function deletePost(int $postId, Request $request, ManagerRegistry $m, PostRepository $rep, PostCommentRepository $commentRep): Response
{
    $em = $m->getManager();
    $post = $rep->find($postId);
    

    if (!$post) {
        throw $this->createNotFoundException('Post not found');
    }

    // Supprimer les commentaires liés au post
    $comments = $commentRep->findBy(['post' => $post]);
    foreach ($comments as $comment) {
        $em->remove($comment);
    }

    $em->remove($post);
    $em->flush();

    return $this->redirectToRoute('community_post_list');
}

}
