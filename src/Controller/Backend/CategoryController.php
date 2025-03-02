<?php

namespace App\Controller\Backend;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/category')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_dashboard_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('backend/category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Category created successfully');
            return $this->redirectToRoute('app_dashboard_category_index');
        }

        return $this->render('backend/category/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Category updated successfully');
            return $this->redirectToRoute('app_dashboard_category_index');
        }

        return $this->render('backend/category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_category_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            // Check if category has events
            if (!$category->getEvents()->isEmpty()) {
                $this->addFlash('error', 'Cannot delete category that has events');
                return $this->redirectToRoute('app_dashboard_category_index');
            }

            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Category deleted successfully');
        }

        return $this->redirectToRoute('app_dashboard_category_index');
    }

    #[Route('/{id}/ajax-edit', name: 'app_dashboard_category_ajax_edit', methods: ['POST'])]
    public function ajaxEdit(Request $request, Category $category, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) {
            $category->setName($data['name']);
            if (isset($data['description'])) {
                $category->setDescription($data['description']);
            }
            $entityManager->flush();
            return new JsonResponse(['success' => true]);
        }
        return new JsonResponse(['error' => 'Invalid data'], 400);
    }
}
