<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\Project;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\ChallengeRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, ChallengeRepository $challengeRepo, ProjectRepository $projectRepo): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $challenges = $challengeRepo->findUniqueChallengesByUUID();
            $projects = $projectRepo->findUniqueProjectsByUUID();

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            foreach ($challenges as $challenge) {
                $newChallenge = new Challenge();
                $newChallenge->setTitle($challenge->getTitle());
                $newChallenge->setDescription($challenge->getDescription());
                $newChallenge->setType($challenge->getType());
                $newChallenge->setCreatedAt($challenge->getCreatedAt());
                $newChallenge->setUuid($challenge->getUuid());
                $newChallenge->setUser($user);
                $entityManager->persist($newChallenge);
            }

            foreach ($projects as $project) {
                $newProject = new Project();
                $newProject->setTitle($project->getTitle());
                $newProject->setDescription($project->getDescription());
                $newProject->setCreatedAt($project->getCreatedAt());
                $newProject->setUuid($project->getUuid());
                $newProject->setUser($user);
                $entityManager->persist($newProject);
            }

            $entityManager->flush();

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
