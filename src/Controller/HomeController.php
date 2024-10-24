<?php

namespace App\Controller;

use App\Repository\ChallengeRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(UserRepository $userRepo, ChallengeRepository $challengeRepo, ProjectRepository $projectRepo): Response
    {
        $users = $userRepo->findAll();
        $challenges = $challengeRepo->findAll();
        $projects = $projectRepo->findAll();

        $roadmap = [];

        foreach ($users as $user) {
            // Ajouter les challenges et projets attribués à chaque utilisateur
            $roadmap[$user->getId()] = [
                'user' => $user,
                'items' => [],
            ];

            // Ajouter tous les challenges avec un indicateur de type 'challenge'
            foreach ($challenges as $challenge) {
                if ($challenge->getUser()->getId() === $user->getId()) {
                    $roadmap[$user->getId()]['items'][] = [
                        'type' => 'challenge',
                        'data' => $challenge,
                    ];
                }
            }

            foreach ($projects as $project) {
                if ($project->getUser()->getId() === $user->getId()) {
                    $roadmap[$user->getId()]['items'][] = [
                        'type' => 'project',
                        'data' => $project,
                    ];
                }
            }
        }
        return $this->render('home/index.html.twig', [
            'roadmap' => $roadmap,
        ]);
    }
}
