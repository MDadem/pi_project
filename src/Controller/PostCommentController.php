<?php

namespace App\Controller;

use App\Entity\PostComment;
use App\Form\CommentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\PostRepository;



final class PostCommentController extends AbstractController
{
    #[Route('/post/comment', name: 'app_post_comment')]
    public function index(): Response
    {
        return $this->render('post_comment/index.html.twig', [
            'controller_name' => 'PostCommentController',
        ]);
    }

    #[Route("/comment/add/{postId}", name: 'comment_add')]
    public function add(Request $request, $postId, PostRepository $postRepository): Response
    {
        $post = $this->postRepository->find($postId);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        $comment = new PostComment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPost($post);
            $comment->setUser($this->getUser());
            $comment->setCreationDate(new \DateTime());

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->redirectToRoute('post_show', ['id' => $postId]);
        }

        return $this->render('comment/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
