<?php

namespace App\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ProjectController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
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
            return new JsonResponse(['error' => 'You are not the author of this challenge'], 403);
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
        $project->setStatus($status);

        $this->em->persist($project);
        $this->em->flush();

        return new JsonResponse(['success' => 'Project status updated successfully']);
    }
}
