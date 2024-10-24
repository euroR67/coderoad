<?php

namespace App\Controller;

use App\Repository\ChallengeRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function index(ChallengeRepository $challengeRepo, ProjectRepository $projectRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $challenges = $challengeRepo->findAll();
        $projects = $projectRepo->findAll();

        $roadmap = [];

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

        return $this->render('profil/index.html.twig', [
            'roadmap' => $roadmap,
        ]);
    }
}
