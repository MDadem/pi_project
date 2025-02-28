<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\User;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // For testing: if no real user is logged in, fetch a test user
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->find(1);
            if (!$user) {
                throw new \Exception("Test user not found. Please insert a test user.");
            }
        }

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, [
            'attr' => ['enctype' => 'multipart/form-data']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $product->getCreatedAt()) {
                $product->setCreatedAt(new \DateTime());
            }

            // Set the owner 
            $product->setOwner($user);

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image_url')->getData();
            if ($imageFile) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
                if (!is_dir($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);
                }
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadsDirectory, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed.');
                }
                $product->setImageUrl('/uploads/products/' . $newFilename);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('product_list');
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'There was an error creating the product. Please check your form.');
        }

        return $this->render('frontend/product/add_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product/list', name: 'product_list')]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Retrieve filter parameters from the query string
        $name = $request->query->get('name');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $category = $request->query->get('category');
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');

        // Convert price filters to float if provided, otherwise set to null
        $priceMin = ($priceMin !== null && $priceMin !== '') ? (float)$priceMin : null;
        $priceMax = ($priceMax !== null && $priceMax !== '') ? (float)$priceMax : null;

        // Use a custom repository method to fetch filtered products
        $products = $entityManager->getRepository(Product::class)
            ->searchProducts($name, $dateFrom, $dateTo, $category, $priceMin, $priceMax);

        // Fetch all categories for the filter dropdown
        $categories = $entityManager->getRepository(ProductCategory::class)->findAll();

        return $this->render('frontend/product/list_product.html.twig', [
            'products'   => $products,
            'categories' => $categories,
        ]);
    }



    #[Route('/product/{id}/edit', name: 'product_edit')]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // No user restriction – anyone can edit
        $form = $this->createForm(ProductType::class, $product, [
            'attr' => ['enctype' => 'multipart/form-data']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image_url')->getData();
            if ($imageFile) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
                if (!is_dir($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);
                }
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadsDirectory, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed.');
                }
                $product->setImageUrl('/uploads/products/' . $newFilename);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('product_list');
        }

        return $this->render('frontend/product/edit_product.html.twig', [
            'form'    => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/product/{id}', name: 'product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // No user restriction – anyone can delete
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully!');
        }
        return $this->redirectToRoute('product_list');
    }
}
