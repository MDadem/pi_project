<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $product->getCreatedAt()) {
                $product->setCreatedAt(new \DateTime());
            }

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image_url')->getData();
            if ($imageFile) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
                if (!is_dir($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);
                }
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($uploadsDirectory, $newFilename);
                $product->setImageUrl('/uploads/products/' . $newFilename);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('product_new', ['id' => $product->getId()]);
        }

        return $this->render('frontend/product/add_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}