<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Test;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * @Route("/api/test", name="createTest", methods="PUT")
     */
    public function createTest(Request $request, EntityManagerInterface $entityManager)
    {
        $data = $request->request->all();

        /** @var User $user */
        $user = $this->getUser();
        $userProjects = $user->getProjects();

        /** @var Project $project */
        $project = $entityManager->getRepository(Project::class)->findOneBy(['id' => $data['projectId']]);
        if($project === null || $userProjects === null || !in_array($project->getExternalId(), $userProjects)){
            return $this->json([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        if(empty($data['name'])){
            return $this->json([
                'success' => false,
                'message' => 'Name is required'
            ]);
        }

        $test = new Test();
        $test
            ->setProject($project)
            ->setName($data['name'])
            ->setScriptUrl($data['script'])
            ->setComment($data['comment']);

        $entityManager->persist($test);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'id' => $test->getId()
        ]);
    }
}
