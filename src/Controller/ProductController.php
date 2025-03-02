<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\User;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    private function applyDynamicPricing(array $products): array
    {
        foreach ($products as $product) {
            // Set a default base price if productPrice is null and dynamic pricing is used
            $basePrice = $product->getProductPrice() ?? ($product->getUseDynamicPricing() ? 10.0 : 0.0);

            if ($product->getUseDynamicPricing()) {
                $stock = $product->getProductStock();
                $discount = $product->getDiscount() ?? 0;
                $createdAt = $product->getCreatedAt();
                $category = $product->getProductCategory()->getName();

                $adjustedPrice = $basePrice;

                // Stock-based adjustment
                if ($stock > 50) {
                    $adjustedPrice *= 0.90; // -10%
                } elseif ($stock < 10) {
                    $adjustedPrice *= 1.15; // +15%
                }

                // Discount-based adjustment
                if ($discount > 20) {
                    $adjustedPrice *= 0.95; // Additional 5% off
                }

                // Age-based adjustment
                $ageInDays = (new \DateTime())->diff($createdAt)->days;
                if ($ageInDays > 30) {
                    $adjustedPrice *= 0.85; // -15% for old products
                }

                // Category-based adjustment
                if ($category === 'Premium') {
                    $adjustedPrice *= 1.10; // +10% for premium
                }

                $product->setDynamicPrice($adjustedPrice);
            } else {
                $product->setDynamicPrice($basePrice); // Use base price or 0.0 if null
            }
        }

        return $products;
    }

    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->find(1);
            if (!$user) {
                throw new \Exception("Test user not found. Please insert a test user.");
            }
        }

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, [
            'attr' => ['enctype' => 'multipart/form-data'],
            'require_image' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $product->getCreatedAt()) {
                $product->setCreatedAt(new \DateTime());
            }

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
                    $product->setImageUrl('/uploads/products/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
                    return $this->render('frontend/product/add_product.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
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
        $name = $request->query->get('name');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $category = $request->query->get('category');
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        $availability = $request->query->get('availability');
        $discountFilter = $request->query->get('discount');

        $priceMin = ($priceMin !== null && $priceMin !== '') ? (float)$priceMin : null;
        $priceMax = ($priceMax !== null && $priceMax !== '') ? (float)$priceMax : null;

        $products = $entityManager->getRepository(Product::class)
            ->searchProducts($name, $dateFrom, $dateTo, $category, $priceMin, $priceMax, $availability, $discountFilter);

        usort($products, function ($a, $b) {
            $discountA = $a->getDiscount() ?? 0;
            $discountB = $b->getDiscount() ?? 0;
            return $discountB <=> $discountA;
        });

        $products = $this->applyDynamicPricing($products);

        $categories = $entityManager->getRepository(ProductCategory::class)->findAll();

        if ($request->isXmlHttpRequest()) {
            return $this->render('frontend/product/_list.html.twig', [
                'products' => $products,
                'categories' => $categories,
            ]);
        }

        return $this->render('frontend/product/list_product.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    #[Route('/product/{id}/edit', name: 'product_edit')]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product, [
            'attr' => ['enctype' => 'multipart/form-data'],
            'require_image' => false,
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
                    $product->setImageUrl('/uploads/products/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
                    return $this->render('frontend/product/edit_product.html.twig', [
                        'form' => $form->createView(),
                        'product' => $product,
                    ]);
                }
            }
            $entityManager->flush();
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('product_list');
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'There was an error updating the product. Please check your form.');
        }

        return $this->render('frontend/product/edit_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/product/{id}', name: 'product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully!');
        }
        return $this->redirectToRoute('product_list');
    }

    #[Route('/product/list/pdf', name: 'product_list_pdf')]
    public function generateProductListPdf(Request $request, EntityManagerInterface $entityManager, Pdf $snappy): Response
    {
        $snappy->setTimeout(300);

        $name = $request->query->get('name');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $category = $request->query->get('category');
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        $availability = $request->query->get('availability');
        $discountFilter = $request->query->get('discount');

        $priceMin = ($priceMin !== null && $priceMin !== '') ? (float)$priceMin : null;
        $priceMax = ($priceMax !== null && $priceMax !== '') ? (float)$priceMax : null;

        $products = $entityManager->getRepository(Product::class)
            ->searchProducts($name, $dateFrom, $dateTo, $category, $priceMin, $priceMax, $availability, $discountFilter);

        usort($products, function ($a, $b) {
            $discountA = $a->getDiscount() ?? 0;
            $discountB = $b->getDiscount() ?? 0;
            return $discountB <=> $discountA;
        });

        $products = $this->applyDynamicPricing($products);

        $categories = $entityManager->getRepository(ProductCategory::class)->findAll();

        $html = $this->renderView('frontend/product/pdf_product_list.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);

        $pdf = $snappy->getOutputFromHtml($html);

        $response = new Response($pdf);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'product_list.pdf'
        );
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}