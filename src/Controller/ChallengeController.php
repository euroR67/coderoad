<?php

namespace App\Controller;

use App\Entity\Challenge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ChallengeController extends AbstractController
{
    private function __construct(private EntityManagerInterface $em)
    {
    }


    #[Route('/challenge/{id}/update', name: 'challenge_update', methods: ['PUT'])]
    public function updateChallenge(
        Request $request,
        Challenge $challenge,
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
    public function editChallengeGithub(Challenge $challenge, Request $request): JsonResponse
    {
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
        $data = json_decode($request->getContent(), true);

        // check if the status key exists
        if (!array_key_exists('status', $data)) {
            return new JsonResponse(['error' => 'No status provided'], 400);
        }

        $status = $data['status'];
        $challenge->setStatus($status);

        $this->em->persist($challenge);
        $this->em->flush();

        return new JsonResponse(['success' => 'Challenge status updated successfully']);
    }
}
