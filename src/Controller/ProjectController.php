<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Redmine;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    /**
     * @Route("/api/projects", name="projects", methods="GET")
     */
    public function projects()
    {
        /** @var User $user */
        $user = $this->getUser();
        $redmine = new \App\Service\Redmine($this->getParameter('redmine_url'), $user->getApiKey());
        try {
            $projectsFormatted = $redmine->getUserProjects();
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        return $this->json([
            'success' => true,
            'projects' => array_values($projectsFormatted),
        ]);
    }

    /**
     * @Route("/api/project", name="createProject", methods="PUT")
     */
    public function createProject(Request $request, EntityManagerInterface $entityManager)
    {
        /** @var User $user */
        $user = $this->getUser();

        $redmine = new \App\Service\Redmine($this->getParameter('redmine_url'), $user->getApiKey());
        try {
            $projectsFormatted = $redmine->getUserProjects();
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        // todo: refactor DTO
        $externalId = $request->request->get('id');
        $regexp = $request->request->get('regexp');

        if(!isset($projectsFormatted[$externalId])){
            return $this->json([
                'success' => false,
                'message' => 'Project is not allowed'
            ]);
        }

        //todo: move to repository
        $existedProject = $entityManager->getRepository(Project::class)->findOneBy(['externalId' => $externalId]);
        if($existedProject === null) {
            $project = new Project();
            $project
                ->setExternalId($externalId)
                ->setBranchRegexp($regexp)
                ->setMaintainer($user)
                ->setName($projectsFormatted[$externalId]['text']);

            $entityManager->persist($project);

            $redmine = new Redmine();
            $redmine
                ->setUrl($this->getParameter('redmine_url'))
                ->setApiKey($user->getApiKey())
                ->setProject($project);

            $entityManager->persist($redmine);
            $entityManager->flush();
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Project already exists'
            ]);
        }

        return $this->json([
            'success' => true
        ]);
    }
}
