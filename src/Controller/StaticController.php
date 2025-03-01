<?php

namespace App\Controller;

use App\Repository\CommunityRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StaticController extends AbstractController
{
    #[Route('/statistics/community-posts', name: 'app_static_community_posts')]
public function staticCommunityPost(CommunityRepository $communityRepository, PostRepository $postRepository): Response
{
    
    $communityPostStats = $communityRepository->findAllCommunityPostCounts(); 

    // Calculer le nombre total de communautés et le nombre total de posts
    $totalCommunities = count($communityPostStats);
    $totalPosts = array_sum(array_column($communityPostStats, 'postCount'));

    
    $userStats = $postRepository->getUserPostStatistics();
    return $this->render('static/index.html.twig', [
        'totalCommunities' => $totalCommunities,
        'totalPosts' => $totalPosts,
        'postsPerCommunity' => $communityPostStats, 
        'userStats' => $userStats,
    ]);
}
}
