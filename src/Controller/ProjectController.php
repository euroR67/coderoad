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
    private function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/project/{id}/update', name: 'project_update', methods: ['PUT'])]
    public function updateProject(
        Request $request,
        Project $project,
    ): JsonResponse {
        // Récupérer les données envoyées dans la requête (JSON)
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        // Parcourir les données pour modifier les propriétés correspondantes
        foreach ($data as $field => $value) {
            // Vérifier que la méthode "set" correspondante existe
            $setter = 'set' . ucfirst($field);
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
        $data = json_decode($request->getContent(), true);

        // check if the status key exists
        if (!array_key_exists('status', $data)) {
            return new JsonResponse(['error' => 'No status provided'], 400);
        }

        $status = $data['status'];
        $project->setStatus($status);

        $this->em->persist($project);
        $this->em->flush();

        return new JsonResponse(['success' => 'Project status updated successfully']);
    }
}
