<?php

namespace App\Controller;

use App\Entity\Community;
use App\Entity\CommunityMembers;
use App\Entity\JoinRequest;
use App\Entity\User;
use App\Enums\CategoryGrp;
use App\Form\CommunitySearchType;
use App\Form\GroupType;
use App\Repository\CommunityRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class GroupController extends AbstractController
{
    #[Route('backoffice/group', name: 'app_group')]
    public function index(CommunityRepository $communityRepository): Response
    {

        

        $groups = $communityRepository->findAllWithMemberCount();
    
        return $this->render('group/index.html.twig', [
            'groups' => $groups,
        ]);
    }

    
        #[Route('backoffice/group-ajouter', name: 'app_ajoutergroup')]
        public function ajouterGroup(Request $re, ManagerRegistry $m, SluggerInterface $slugger): Response
        {
            $em = $m->getManager();
            $grp = new Community();
            $grp->setCreationDate(new \DateTime());  

            $addGrpf = $this->createForm(GroupType::class, $grp);
            $addGrpf->handleRequest($re);
        
            if ($addGrpf->isSubmitted() && $addGrpf->isValid()) {
                $imageFile = $addGrpf->get('banner')->getData(); 
        
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
        
                    try {
                        $imageFile->move(
                            $this->getParameter('group_images_directory'), // Ensure this parameter is defined in services.yaml
                            $newFilename
                        );
                        $grp->setBanner('uploads/group_images/' . $newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image.');
                    }
                }
        
                $em->persist($grp);
                $em->flush();
        
                return $this->redirectToRoute("app_group");
            }
        
            return $this->render('group/ajoutergrp.html.twig', [
                'form' => $addGrpf->createView(),
            ]);
        }
    
    /////////////////////// --------------- update groupe ------------------ /////////////////
        #[Route('backoffice/group-modifier/{id}', name: 'app_modifier')]
    public function modifierGroup(int $id, Request $re, ManagerRegistry $m, CommunityRepository $communityRepository): Response
    {
        $em = $m->getManager();
        $grp = $communityRepository->findCommunityWithMembers($id);
    
        if (!$grp) {
            throw $this->createNotFoundException("Aucune communauté trouvée avec l'ID $id.");
        }
    
        $editForm = $this->createForm(GroupType::class, $grp);
        $editForm->handleRequest($re);
    
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            return $this->redirectToRoute("app_group");
        }
    
        return $this->render('group/modifiergrp.html.twig', [
            'form' => $editForm->createView(),
            'group' => $grp,
            'members' => $grp->getCommunityMembers()
        ]);
    }
    
    ////////////////////////// --- Suprimer groupe ------------//////////////////////////
        #[Route('backoffice/group-supprimer/{id}', name: 'app_supprimer')]
    public function supprimerGroup(ManagerRegistry $m, int $id): Response
    {
        $em = $m->getManager();
        $group = $em->getRepository(Community::class)->find($id);
    
        if ($group) {
            $em->remove($group);
            $em->flush();
        }
    
        return $this->redirectToRoute('app_group');
    }
   
    
    //////////////////////////----------  Supprimer member form grp -------------------- //////////////////
    #[Route('backoffice/group/{groupId}/remove-member/{memberId}', name: 'app_remove_member')]
    public function removeMember(int $groupId, int $memberId, ManagerRegistry $m): Response
{
    $em = $m->getManager();
    $community = $em->getRepository(Community::class)->find($groupId);
    $member = $em->getRepository(CommunityMembers::class)->findOneBy([
        'community' => $community,
        'user' => $memberId
    ]);

    if (!$community || !$member) {
        throw $this->createNotFoundException("Membre ou communauté introuvable.");
    }

    // Supprimer le membre de la communauté
    $em->remove($member);

    // Supprimer la demande d'adhésion associée si elle existe
    $joinRequest = $em->getRepository(JoinRequest::class)->findOneBy([
        'community' => $community,
        'user' => $memberId
    ]);

    if ($joinRequest) {
        $em->remove($joinRequest);
    }

    $em->flush();

    return $this->redirectToRoute('app_modifier', ['id' => $groupId]);
}
    
    
    /////////////////////////// ---------------- ajouter member to a grp ------------------ ///////////////////
    #[Route('backoffice/group/{groupId}/add-member', name: 'app_add_member')]
    public function addMember(int $groupId, Request $request, ManagerRegistry $m): Response
    {
        $em = $m->getManager();
        $community = $em->getRepository(Community::class)->find($groupId);
    
        if (!$community) {
            throw $this->createNotFoundException("Communauté introuvable.");
        }
    
        $email = $request->request->get('email');
    
        // Vérifier si l'utilisateur existe déjà
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
    
        if (!$user) {
            return new Response("Aucun utilisateur trouvé avec cet email.", Response::HTTP_NOT_FOUND);
        }
    
        // Vérifier si l'utilisateur est déjà membre de la communauté
        $existingMember = $em->getRepository(CommunityMembers::class)->findOneBy([
            'community' => $community,
            'user' => $user
        ]);
    
        if ($existingMember) {
            return new Response("Cet utilisateur est déjà membre de la communauté.", Response::HTTP_CONFLICT);
        }
    
        // Ajouter le nouvel utilisateur à la communauté
        $newMember = new CommunityMembers();
        $newMember->setCommunity($community);
        $newMember->setUser($user);
        $newMember->setJoinedAt(new \DateTime());
    
        $em->persist($newMember);
        $em->flush();
    
        return $this->redirectToRoute('app_modifier', ['id' => $groupId]);
    }
    


    #[Route('/accueil/group-list', name: 'app_groupList')]
public function GrpList(Request $request, CommunityRepository $communityRepository): Response
{
    // Création du formulaire de recherche
    $form = $this->createForm(CommunitySearchType::class);
    $form->handleRequest($request);

    $groups = [];

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
            
            $category = $data['category'] instanceof CategoryGrp ? $data['category'] : CategoryGrp::tryFrom($data['category']);
        
        $groups = $communityRepository->searchCommunities(
            $data['community'] ? $data['community']->getName() : null,
            $data['startDate'] ?? null,
            $data['endDate'] ?? null,
            category: $category
        );
      
       
        
    } else {
        $groups = $communityRepository->findAll();
        
    }

    return $this->render('accueil/index.html.twig', [
        'groups' => $groups,
        'form' => $form->createView(), // Envoi du formulaire à la vue
    ]);
}

    
    // ------------------- send request ------------------------------
    #[Route('/community/join/{id}', name: 'join_community', methods: ['POST'])]
    public function joinCommunity(Community $community, ManagerRegistry $doctrine, Request $request): Response
    {
        $user = $this->getUser();
        $entityManager = $doctrine->getManager();

        // Vérifier si l'utilisateur a déjà fait une demande
        $existingRequest = $doctrine->getRepository(JoinRequest::class)
            ->findOneBy(['user' => $user, 'community' => $community]);

        if ($existingRequest) {
            $this->addFlash('warning', 'Vous avez déjà demandé à rejoindre ce groupe.');
            return $this->redirectToRoute('app_groupList');
        }

        // Créer une nouvelle demande d'adhésion
        $joinRequest = new JoinRequest();
        $joinRequest->setUser($user);
        $joinRequest->setCommunity($community);
        $joinRequest->setStatus('pending'); // En attente
        $joinRequest->setJoinDate(new \DateTime());

        $entityManager->persist($joinRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande d\'adhésion a été envoyée.');

        return $this->redirectToRoute('app_groupList');
    }
    

    #[Route('/accueil/group-list/{id}', name: 'joined_community')]
    public function getUserCommunities(CommunityRepository $communityRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
    
        // Vérifier si l'utilisateur est authentifié
        if (!$user) {
            return $this->redirectToRoute('dashboard_signin');
        }
    
        // Récupérer les communautés auxquelles l'utilisateur a adhéré
        $communities = $communityRepository->findByUserParticipation($user);
    
        return $this->render('accueil/user-community.html.twig', [
            'communities' => $communities,
        ]);
    }




    
    }
    