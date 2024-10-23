<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\Image;
use App\Form\ChallengeFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        $form = $this->createForm(ChallengeFormType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $users = $userRepository->findAll();

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

    #[Route('/challenge/{id}/update', name: 'challenge_update', methods: ['PUT'])]
    public function updateChallenge(
        Request $request,
        Challenge $challenge,
    ): JsonResponse {
        /** @var $currentUser User */
        $currentUser = $this->getUser();

        if ($currentUser == null) {
            return new JsonResponse(['error' => 'You are not logged in'], 403);
        }

        // Récupérer les données envoyées dans la requête (JSON)
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        // Parcourir les données pour modifier les propriétés correspondantes
        foreach ($data as $field => $value) {
            // Vérifier que la méthode "set" correspondante existe
            $setter = 'set' . ucfirst($field);
            // Traitement spécial pour les champs de type DateTimeImmutable
            if ($field === 'createdAt' || $field === 'updatedAt') {
                try {
                    $value = new \DateTimeImmutable($value);  // Conversion de la chaîne en DateTimeImmutable
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'Invalid date format'], 400);
                }
            }

            if (method_exists($challenge, $setter)) {
                $challenge->$setter($value);
            } else {
                return new JsonResponse(['error' => "Field '$field' does not exist"], 400);
            }
        }

        // Sauvegarder les modifications en base de données
        $this->em->persist($challenge);
        $this->em->flush();

        return new JsonResponse(['success' => 'Challenge updated successfully']);
    }


    #[Route('/challenge/{id}/github', name: 'edit_challenge_github', methods: ['PUT'])]
    public function editChallengeGithub(Challenge $challenge, Request $request)
    {
        /** @var $currentUser User */
        $currentUser = $this->getUser();

        if ($currentUser?->getId() !== $challenge->getUser()->getId()) {
            return new JsonResponse(['error' => 'You are not the author of this challenge'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // check if the github key exists
        if (!array_key_exists('github', $data)) {
            return new JsonResponse(['error' => 'No github provided'], 400);
        }

        $github = $data['github'];
        $challenge->setGithub($github);

        $this->em->persist($challenge);
        $this->em->flush();

        return new JsonResponse(['success' => 'Challenge github updated successfully']);
    }

    #[Route('/challenge/{id}/status', name: 'edit_challenge_status', methods: ['PUT'])]
    public function editChallengeStatus(Challenge $challenge, Request $request): JsonResponse
    {
        /** @var $currentUser User */
        $currentUser = $this->getUser();

        if ($currentUser?->getId() !== $challenge->getUser()->getId()) {
            return new JsonResponse(['error' => 'You are not the author of this challenge'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // check if the status key exists
        if (!array_key_exists('status', $data)) {
            return new JsonResponse(['error' => 'No status provided'], 400);
        }

        $status = $data['status'];
        // check if the status is valid (1 = todo, 2 = in progress, 3 = done)
        if (!in_array($status, [1, 2, 3])) {
            return new JsonResponse(['error' => 'Invalid status'], 400);
        }
        $challenge->setStatus($status);

        $this->em->persist($challenge);
        $this->em->flush();

        return new JsonResponse(['success' => 'Challenge status updated successfully']);
    }
}
