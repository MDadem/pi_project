<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Category;
use App\Form\EventType;
use App\Form\CategoryType;
use App\Repository\EventRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventController extends AbstractController
{
    #[Route('/blank', name: 'app_dashboard_blank')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create query builder for events
        $queryBuilder = $entityManager->getRepository(Event::class)->createQueryBuilder('e')
            ->leftJoin('e.category', 'c');

        // Apply filters
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('e.title LIKE :search OR e.eventLocation LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($category = $request->query->get('category')) {
            $queryBuilder->andWhere('c.id = :category')
                ->setParameter('category', $category);
        }

        if ($status = $request->query->get('status')) {
            $queryBuilder->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }

        if ($dateFrom = $request->query->get('dateFrom')) {
            $queryBuilder->andWhere('e.eventDate >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo = $request->query->get('dateTo')) {
            $queryBuilder->andWhere('e.eventDate <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        // Apply sorting
        $sortField = $request->query->get('sort', 'eventDate');
        $sortOrder = $request->query->get('order', 'ASC');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['title', 'eventDate', 'numberOfPlaces', 'status'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'eventDate';
        }
        
        $queryBuilder->orderBy('e.' . $sortField, $sortOrder === 'DESC' ? 'DESC' : 'ASC');

        // Get results
        $events = $queryBuilder->getQuery()->getResult();
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Create category form
        $category = new Category();
        $categoryForm = $this->createForm(CategoryType::class, $category);
        $categoryForm->handleRequest($request);

        if ($categoryForm->isSubmitted() && $categoryForm->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_blank');
        }

        return $this->render('backend/blankpage/blank.html.twig', [
            'events' => $events,
            'categories' => $categories,
            'categoryForm' => $categoryForm->createView(),
            'currentCategory' => $category,
            'currentSearch' => $search,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
            'currentStatus' => $status,
            'currentSort' => $sortField,
            'currentOrder' => $sortOrder,
        ]);
    }

    #[Route('/category/{id}/edit', name: 'app_dashboard_category_edit', methods: ['POST'])]
    public function editCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) {
            $category->setName($data['name']);
        }
        if (isset($data['description'])) {
            $category->setDescription($data['description']);
        }

        $entityManager->flush();
        return $this->json(['success' => true]);
    }
//
//     #[Route('/category/{id}/delete', name: 'app_dashboard_category_delete', methods: ['POST'])]
//     public function deleteCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
//     {
//         if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
//             // Check if category is being used by any events
//             $events = $entityManager->getRepository(Event::class)->findBy(['category' => $category]);
//             if (count($events) > 0) {
//                 $this->addFlash('error', 'Cannot delete category that is being used by events');
//                 return $this->redirectToRoute('app_dashboard_blank');
//             }
//
//             $entityManager->remove($category);
//             $entityManager->flush();
//             $this->addFlash('success', 'Category deleted successfully');
//         }
//
//         return $this->redirectToRoute('app_dashboard_blank');
//     }

    #[Route('/new', name: 'app_dashboard_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('event_images_directory'),
                        $newFilename
                    );
                    $event->setImageFilename($newFilename);
                } catch (FileException $e) {
                    // Handle file upload error
                    $this->addFlash('error', 'There was an error uploading your file.');
                }
            }

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('app_dashboard_blank');
        }

        return $this->render('backend/event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
//
//    #[Route('/{id}/edit', name: 'app_dashboard_event_edit', methods: ['GET', 'POST'])]
//    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
//    {
//        $form = $this->createForm(EventType::class, $event);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            // Handle file upload
//            $imageFile = $form->get('imageFile')->getData();
//            if ($imageFile) {
//                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
//                $safeFilename = $slugger->slug($originalFilename);
//                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
//
//                try {
//                    // Delete old image if exists
//                    if ($event->getImageFilename()) {
//                        $oldImagePath = $this->getParameter('event_images_directory').'/'.$event->getImageFilename();
//                        if (file_exists($oldImagePath)) {
//                            unlink($oldImagePath);
//                        }
//                    }
//
//                    $imageFile->move(
//                        $this->getParameter('event_images_directory'),
//                        $newFilename
//                    );
//                    $event->setImageFilename($newFilename);
//                } catch (FileException $e) {
//                    $this->addFlash('error', 'There was an error uploading the image');
//                }
//            }
//
//            $entityManager->flush();
//
//            $this->addFlash('success', 'Event updated successfully');
//            return $this->redirectToRoute('app_dashboard_blank');
//        }
//
//        return $this->render('backend/event/edit.html.twig', [
//            'event' => $event,
//            'form' => $form->createView(),
//        ]);
//    }
//
//    #[Route('/{id}', name: 'app_dashboard_event_delete', methods: ['POST'])]
//    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
//    {
//        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
//            // Delete image file if exists
//            if ($event->getImageFilename()) {
//                $imagePath = $this->getParameter('event_images_directory').'/'.$event->getImageFilename();
//                if (file_exists($imagePath)) {
//                    unlink($imagePath);
//                }
//            }
//
//            $entityManager->remove($event);
//            $entityManager->flush();
//            $this->addFlash('success', 'Event deleted successfully');
//        }
//
//        return $this->redirectToRoute('app_dashboard_blank');
//    }
}
