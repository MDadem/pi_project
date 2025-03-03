<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\User;
use App\Entity\Vote;
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
            $basePrice = $product->getProductPrice() ?? ($product->getUseDynamicPricing() ? 10.0 : 0.0);

            if ($product->getUseDynamicPricing()) {
                $stock = $product->getProductStock();
                $discount = $product->getDiscount() ?? 0;
                $createdAt = $product->getCreatedAt();
                $category = $product->getProductCategory()->getName();

                $adjustedPrice = $basePrice;

                // Stock-based adjustment
                $idealStock = 30;
                $stockDelta = $stock - $idealStock;
                if ($stockDelta > 0) {
                    $stockMultiplier = 1 - ($stockDelta / 10) * 0.01;
                } else {
                    $stockMultiplier = 1 + (-$stockDelta / 5) * 0.02;
                }
                $adjustedPrice *= max(0.5, min(1.5, $stockMultiplier));

                // Discount-based adjustment
                if ($discount > 30) {
                    $adjustedPrice *= 0.90;
                } elseif ($discount > 20) {
                    $adjustedPrice *= 0.95;
                } elseif ($discount > 10) {
                    $adjustedPrice *= 0.98;
                }

                // Age-based adjustment
                $ageInDays = (new \DateTime())->diff($createdAt)->days;
                if ($ageInDays > 30) {
                    $daysOver = $ageInDays - 30;
                    $ageDiscount = min(0.5, $daysOver * 0.005);
                    $adjustedPrice *= (1 - $ageDiscount);
                }

                // Category-based adjustment
                $categoryMultipliers = [
                    'Premium' => 1.10,
                    'Budget' => 0.95,
                    'Seasonal' => (date('m') >= 6 && date('m') <= 8) ? 1.05 : 0.95 // Fixed syntax
                ];
                if (isset($categoryMultipliers[$category])) {
                    $adjustedPrice *= $categoryMultipliers[$category];
                }

                // Price boundaries
                $minPrice = $basePrice * 0.5;
                $maxPrice = $basePrice * 1.5;
                $adjustedPrice = max($minPrice, min($maxPrice, $adjustedPrice));

                $product->setDynamicPrice($adjustedPrice);
            } else {
                $product->setDynamicPrice($basePrice);
            }
        }

        return $products;
    }

    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to create a product.');
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

        $products = $this->applyDynamicPricing($products);

        usort($products, function ($a, $b) {
            $aVotes = $a->getVoteScore();
            $bVotes = $b->getVoteScore();
            $aDiscount = $a->getDiscount() ?? 0;
            $bDiscount = $b->getDiscount() ?? 0;

            if ($aVotes > 0 && $bVotes > 0) {
                return $bVotes <=> $aVotes;
            }
            if ($aVotes > 0 && $bVotes == 0) {
                return -1;
            }
            if ($bVotes > 0 && $aVotes == 0) {
                return 1;
            }
            if ($aVotes == 0 && $bVotes == 0 && $aDiscount > 0 && $bDiscount > 0) {
                return $bDiscount <=> $aDiscount;
            }
            if ($aVotes == 0 && $bVotes == 0 && $aDiscount > 0 && $bDiscount == 0) {
                return -1;
            }
            if ($aVotes == 0 && $bVotes == 0 && $bDiscount > 0 && $aDiscount == 0) {
                return 1;
            }
            return 0;
        });

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

        $products = $this->applyDynamicPricing($products);

        usort($products, function ($a, $b) {
            $aVotes = $a->getVoteScore();
            $bVotes = $b->getVoteScore();
            $aDiscount = $a->getDiscount() ?? 0;
            $bDiscount = $b->getDiscount() ?? 0;

            if ($aVotes > 0 && $bVotes > 0) {
                return $bVotes <=> $aVotes;
            }
            if ($aVotes > 0 && $bVotes == 0) {
                return -1;
            }
            if ($bVotes > 0 && $aVotes == 0) {
                return 1;
            }
            if ($aVotes == 0 && $bVotes == 0 && $aDiscount > 0 && $bDiscount > 0) {
                return $bDiscount <=> $aDiscount;
            }
            if ($aVotes == 0 && $bVotes == 0 && $aDiscount > 0 && $bDiscount == 0) {
                return -1;
            }
            if ($aVotes == 0 && $bVotes == 0 && $bDiscount > 0 && $aDiscount == 0) {
                return 1;
            }
            return 0;
        });

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

    #[Route('/product/{id}/vote/{type}', name: 'product_vote', methods: ['POST'])]
    public function vote(Request $request, Product $product, EntityManagerInterface $entityManager, string $type): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to vote.');
        }

        $voteValue = ($type === 'up') ? 1 : -1;

        $existingVote = $entityManager->getRepository(Vote::class)->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);

        if ($existingVote) {
            if ($existingVote->getValue() !== $voteValue) {
                $oldValue = $existingVote->getValue();
                $existingVote->setValue($voteValue);
                $newScore = $product->getVoteScore() + $voteValue - $oldValue;
                $product->setVoteScore(max(0, $newScore));
            }
        } else {
            $vote = new Vote();
            $vote->setUser($user);
            $vote->setProduct($product);
            $vote->setValue($voteValue);
            $entityManager->persist($vote);
            $newScore = $product->getVoteScore() + $voteValue;
            $product->setVoteScore(max(0, $newScore));
        }

        $entityManager->flush();

        return $this->redirectToRoute('product_list');
    }
}