<?php

namespace App\Controller;

use App\Entity\ProductCategory;
use App\Form\ProductCategoryType;
use App\Repository\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductCategoryController extends AbstractController
{
    #[Route('/product-category', name: 'product_category_index', methods: ['GET'])]
    public function index(ProductCategoryRepository $productCategoryRepository): Response
    {
        return $this->render('product-category/show.html.twig', [
            'productCategories' => $productCategoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'product_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $productCategory = new ProductCategory();
        $form = $this->createForm(ProductCategoryType::class, $productCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($productCategory);
            $entityManager->flush();

            return $this->redirectToRoute('product_category_index');
        }

        return $this->render('product-category/new.html.twig', [
            'productCategory' => $productCategory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product-category/{id}', name: 'product_category_show', methods: ['GET'])]
    public function show(ProductCategory $productCategory): Response
    {
        return $this->render('product-category/show.html.twig', [
            'productCategory' => $productCategory,
            
        ]);
    }

    #[Route('/product-category/{id}/edit', name: 'product_category_edit')]
    public function edit(Request $request, ProductCategory $productCategories, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductCategoryType::class, $productCategories);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('product_category_index');
        }

        return $this->render('product-category/edit.html.twig', [
            'productCategories' => $productCategories,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product-category-delete/{id}', name: 'product_category_delete', methods: ['POST'])]
    public function delete(Request $request, ProductCategory $productCategory, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $productCategory->getId(), $request->request->get('_token'))) {
            $entityManager->remove($productCategory);
            $entityManager->flush();
        }

        return $this->redirectToRoute('product_category_index');
    }
}
