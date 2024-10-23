<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Project;
use App\Form\ProjectFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProjectController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/project/new', name: 'project_create', methods: ['GET', 'POST'])]
    public function newProject(Request $request, UserRepository $userRepository): Response
    {
        // formulaire de création de projet
        $project = new Project();
        $form = $this->createForm(ProjectFormType::class, $project);
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
                $newProject = new Project();
                // Copier les données du formulaire dans cette nouvelle instance
                $newProject->setTitle($project->getTitle());
                $newProject->setDescription($project->getDescription());
                $newProject->setStatus($project->getStatus());
                $newProject->setGithub($project->getGithub());
                $newProject->setCreatedAt($project->getCreatedAt());

                // Associer l'utilisateur à ce nouveau projet
                $newProject->setUser($user);

                // Associer les images à chaque nouvelle instance de Challenge
                foreach ($imageFiles as $img) {
                    $newImage = new Image();
                    $newImage->setTitle($img->getTitle());
                    $newProject->addImage($newImage);
                }

                $this->em->persist($newProject);
            }
            $this->em->flush();

            $this->addFlash('success', 'Project created successfully');
            //TODO : rediriger vers la page de détail du projet
            return $this->redirectToRoute('app_home');
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form
        ]);
    }

    #[Route('/project/{id}/update', name: 'project_update', methods: ['PUT'])]
    public function updateProject(
        Request $request,
        Project $project,
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
            if (method_exists($project, $setter)) {
                $project->$setter($value);
            } else {
                return new JsonResponse(['error' => "Field '$field' does not exist"], 400);
            }
        }

        // Sauvegarder les modifications en base de données
        $this->em->persist($project);
        $this->em->flush();

        return new JsonResponse(['success' => 'Project updated successfully']);
    }


    #[Route('/project/{id}/github', name: 'edit_project_github', methods: ['PUT'])]
    public function editProjectGithub(Project $project, Request $request): JsonResponse
    {
        /** @var $currentUser User */
        $currentUser = $this->getUser();

        if ($currentUser?->getId() !== $project->getUser()->getId()) {
            return new JsonResponse(['error' => 'You are not the author of this project'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // check if the github key exists
        if (!array_key_exists('github', $data)) {
            return new JsonResponse(['error' => 'No github provided'], 400);
        }

        $github = $data['github'];
        $project->setGithub($github);

        $this->em->persist($project);
        $this->em->flush();

        return new JsonResponse(['success' => 'Project github updated successfully']);
    }

    #[Route('/project/{id}/status', name: 'edit_project_status', methods: ['PUT'])]
    public function editProjectStatus(Project $project, Request $request): JsonResponse
    {
        /** @var $currentUser User */
        $currentUser = $this->getUser();

        if ($currentUser?->getId() !== $project->getUser()->getId()) {
            return new JsonResponse(['error' => 'You are not the author of this project'], 403);
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
        $project->setStatus($status);

        $this->em->persist($project);
        $this->em->flush();

        return new JsonResponse(['success' => 'Project status updated successfully']);
    }
}
