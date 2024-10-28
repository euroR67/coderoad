<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\Image;
use App\Form\ChallengeFormType;
use App\Repository\ChallengeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

class ChallengeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/challenge/new', name: 'challenge_create', methods: ['GET', 'POST'])]
    public function newChallenge(Request $request, UserRepository $userRepository): Response
    {
        // formulaire de création de challenge
        $challenge = new Challenge();
        $form = $this->createForm(ChallengeFormType::class, $challenge, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $users = $userRepository->findAll();

            // Générer un UUID commun à tous les challenges créés
            $uuid = Uuid::v4();

            $images = $form->get('images')->getData();
            $imageFiles = [];

            // On boucle sur les images
            foreach ($images as $image) {
                // On génère un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                // On copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );

                $img = new Image();
                $img->setTitle($fichier);
                $imageFiles[] = $img;
            }

            foreach ($users as $user) {
                $newChallenge = new Challenge();
                // Copier les données du formulaire dans cette nouvelle instance
                $newChallenge->setTitle($challenge->getTitle());
                $newChallenge->setDescription($challenge->getDescription());
                $newChallenge->setType($challenge->getType());
                $newChallenge->setStatus($challenge->getStatus());
                $newChallenge->setGithub($challenge->getGithub());
                $newChallenge->setCreatedAt($challenge->getCreatedAt());
                $newChallenge->setUuid($uuid);

                // Associer l'utilisateur à ce nouveau challenge
                $newChallenge->setUser($user);

                // Associer les images à chaque nouvelle instance de Challenge
                foreach ($imageFiles as $img) {
                    $newImage = new Image();
                    $newImage->setTitle($img->getTitle());
                    $newChallenge->addImage($newImage);
                }

                $this->em->persist($newChallenge);
            }
            $this->em->flush();

            $this->addFlash('success', 'Challenge created successfully');
            //TODO : rediriger vers la page de détail du challenge
            return $this->redirectToRoute('app_home');
        }

        return $this->render('challenge/new.html.twig', [
            'challenge' => $challenge,
            'form' => $form
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/challenge/{id}/update', name: 'challenge_update')]
    public function updateChallenge(Challenge $challenge, Request $request, ChallengeRepository $challengeRepo): Response
    {
        $form = $this->createForm(ChallengeFormType::class, $challenge, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get all challenges with same uuid
            $challenges = $challengeRepo->findBy(['uuid' => $challenge->getUuid()]);

            foreach ($challenges as $challenge) {
                $challenge->setTitle($form->get('title')->getData());
                $challenge->setDescription($form->get('description')->getData());
                $challenge->setType($form->get('type')->getData());
                // $challenge->setStatus($form->get('status')->getData());
                $challenge->setGithub($form->get('github')->getData());
                $challenge->setCreatedAt($form->get('createdAt')->getData());

                $this->em->persist($challenge);
            }

            $this->em->flush();

            $this->addFlash('success', 'Challenge updated successfully');
            return $this->redirectToRoute('app_home');
        }


        return $this->render('challenge/edit.html.twig', [
            'challenge' => $challenge,
            'form' => $form
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/challenge/{id}', name: 'challenge_delete')]
    public function deleteChallenge(Challenge $challenge, ChallengeRepository $challengeRepo): Response
    {
        // Get all challenges with same uuid
        $challenges = $challengeRepo->findBy(['uuid' => $challenge->getUuid()]);

        foreach ($challenges as $challenge) {
            $this->em->remove($challenge);
        }

        $this->em->flush();

        $this->addFlash('success', 'Challenges deleted successfully');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/challenge/{id}/github', name: 'edit_challenge_github', methods: ['PUT'])]
    public function editChallengeGithub(Challenge $challenge, Request $request)
    {
        /** @var User $currentUser  */
        $currentUser = $this->getUser();

        if ($currentUser?->getId() !== $challenge->getUser()->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'You are not the author of this challenge'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // check if the github key exists
        if (!array_key_exists('github', $data)) {
            return new JsonResponse(['success' => false, 'message' => 'No github provided'], 400);
        }

        $github = $data['github'];
        $challenge->setGithub($github);

        $this->em->persist($challenge);
        $this->em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Challenge github updated successfully']);
    }

    #[Route('/challenge/{id}/status', name: 'edit_challenge_status', methods: ['PUT'])]
    public function editChallengeStatus(Challenge $challenge, Request $request): JsonResponse
    {
        /** @var $currentUser User */
        $currentUser = $this->getUser();

        if ($currentUser?->getId() !== $challenge->getUser()->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'You are not the author of this challenge'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // check if the status key exists
        if (!array_key_exists('status', $data)) {
            return new JsonResponse(['success' => false, 'message' => 'No status provided'], 400);
        }

        $status = $data['status'];
        // check if the status is valid (1 = todo, 2 = in progress, 3 = done)
        if (!in_array($status, [1, 2, 3])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
        }
        $challenge->setStatus($status);

        $this->em->persist($challenge);
        $this->em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Challenge status updated successfully']);
    }
}
