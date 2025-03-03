<?php

namespace App\Controller\Backend;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Endroid\QrCode\QrCode;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/dashboard')]
class EventController extends AbstractController
{
    #[Route('/events', name: 'app_dashboard_events')]
    public function index(Request $request, EventRepository $eventRepository, CategoryRepository $categoryRepository): Response
    {
        $filters = [
            'search' => $request->query->get('search'),
            'category' => $request->query->get('category'),
            'status' => $request->query->get('status'),
            'dateFrom' => $request->query->get('dateFrom'),
            'dateTo' => $request->query->get('dateTo'),
            'sort' => $request->query->get('sortField', 'eventDate'),
            'order' => $request->query->get('sortOrder', 'ASC'),
        ];

        $events = $eventRepository->findByFilters($filters);
        $categories = $categoryRepository->findAll();

        if ($request->isXmlHttpRequest()) {
            return $this->render('backend/event/_table.html.twig', [
                'events' => $events
            ]);
        }

        return $this->render('backend/event/index.html.twig', [
            'events' => $events,
            'categories' => $categories,
            'currentCategory' => $filters['category'],
            'currentSearch' => $filters['search'],
            'currentDateFrom' => $filters['dateFrom'],
            'currentDateTo' => $filters['dateTo'],
            'currentStatus' => $filters['status'],
            'currentSort' => $filters['sort'],
            'currentOrder' => $filters['order'],
        ]);
    }

    #[Route('/event/new', name: 'app_dashboard_event_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('app_dashboard_event_new');
                }

                $event->setImageFilename($newFilename);
            }

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès');
            return $this->redirectToRoute('app_dashboard_events');
        }

        return $this->render('backend/event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/event/{id}/edit', name: 'app_dashboard_event_edit')]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

                    // Delete old image if exists
                    if ($event->getImageFilename()) {
                        $oldImagePath = $this->getParameter('event_images_directory').'/'.$event->getImageFilename();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('app_dashboard_event_edit', ['id' => $event->getId()]);
                }

                $event->setImageFilename($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Événement mis à jour avec succès');
            return $this->redirectToRoute('app_dashboard_events');
        }

        return $this->render('backend/event/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/event/{id}/delete', name: 'app_dashboard_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            // Delete image if exists
            if ($event->getImageFilename()) {
                $imagePath = $this->getParameter('event_images_directory').'/'.$event->getImageFilename();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($event);
            $entityManager->flush();

            $this->addFlash('success', 'Événement supprimé avec succès');
        }

        return $this->redirectToRoute('app_dashboard_events');
    }


}
