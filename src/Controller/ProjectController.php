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
        $data = $request->request->all();
        $externalId = $data['externalId'];
        $regexp = $data['regexp'];

        if(!isset($projectsFormatted[$externalId])){
            return $this->json([
                'success' => false,
                'message' => 'Project is not allowed'
            ]);
        }

        if(
            $data['redmineUrl'] && $data['redmineUser'] && $data['redminePassword'] &&
            $data['useDefaultRedmine'] !== 'true'
        ) {
            //todo: extract to service
            $redmine = new \App\Service\Redmine(
                $data['redmineUrl'],
                "",
                $data['redmineUser'],
                $data['redminePassword']
            );
            try {
                $currentUser = $redmine->getUser();
                $data['redmineApiKey'] = $currentUser['api_key'];
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ошибка авторизации в redmine: ' . $data['redmineUrl']
                ]);
            }
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
                ->setUrl(isset($data['redmineApiKey']) ? $data['redmineUrl'] : $this->getParameter('redmine_url'))
                ->setApiKey(isset($data['redmineApiKey']) ? $data['redmineApiKey'] : $user->getApiKey())
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
            'success' => true,
            'id' => $project->getId()
        ]);
    }

    /**
     * @Route("/api/project/{id}", name="editProject", methods="PATCH")
     */
    public function editProject($id, Request $request, EntityManagerInterface $entityManager)
    {
        $data = $request->request->all();

        /** @var User $user */
        $user = $this->getUser();
        $userProjects = $user->getProjects();

        if($userProjects === null || !in_array($data['externalId'], $userProjects)){
            return $this->json([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        /** @var Project $project */
        $project = $entityManager->getRepository(Project::class)->find($id);
        if($project === null){
            return $this->json([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        if(
            $data['redmineUrl'] && $data['redmineUser'] && $data['redminePassword'] &&
            $data['useDefaultRedmine'] !== 'true'
        ) {
            //todo: extract to service
            $redmine = new \App\Service\Redmine(
                $data['redmineUrl'],
                "",
                $data['redmineUser'],
                $data['redminePassword']
            );
            try {
                $currentUser = $redmine->getUser();
                $data['redmineApiKey'] = $currentUser['api_key'];
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ошибка авторизации в redmine: ' . $data['redmineUrl']
                ]);
            }
        }

        $redmine = new \App\Service\Redmine($this->getParameter('redmine_url'), $user->getApiKey());
        try {
            $projectsFormatted = $redmine->getUserProjects();
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        $project
            ->setExternalId($data['externalId'])
            ->setBranchRegexp($data['regexp'])
            ->setName($projectsFormatted[$data['externalId']]['text']);

        $entityManager->persist($project);

        if(isset($data['redmineApiKey'])) {
            $redmine = $project->getRedmines()[0];
            $redmine
                ->setUrl($data['redmineUrl'])
                ->setApiKey($data['redmineApiKey']);

            $entityManager->persist($redmine);
        } elseif($data['useDefaultRedmine']) {
            $redmine = $project->getRedmines()[0];
            $redmine
                ->setUrl($this->getParameter('redmine_url'))
                ->setApiKey($user->getApiKey());

            $entityManager->persist($redmine);
        }

        $entityManager->flush();

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/api/project/{id}", name="deleteProject", methods="DELETE")
     */
    public function deleteProject($id, EntityManagerInterface $entityManager)
    {
        /** @var User $user */
        $user = $this->getUser();
        $userProjects = $user->getProjects();

        /** @var Project $project */
        $project = $entityManager->getRepository(Project::class)->find($id);
        if($project === null){
            return $this->json([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        if($userProjects === null || !in_array($project->getExternalId(), $userProjects)){
            return $this->json([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        $entityManager->remove($project);
        $entityManager->flush();

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/api/project/{id}", name="getProject", methods="GET")
     */
    public function getProject($id, EntityManagerInterface $entityManager)
    {
        /** @var User $user */
        $user = $this->getUser();
        $userProjects = $user->getProjects();

        /** @var Project $project */
        $project = $entityManager->getRepository(Project::class)->findOneBy(['id' => $id]);
        if($project === null || $userProjects === null || !in_array($project->getExternalId(), $userProjects)){
            return $this->json([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        $testsData = [];
        foreach ($project->getTests() as $test) {

            $links = [];
            foreach ($test->getTestDomains() as $testDomain) {
                $links[] = [
                    'title' => $testDomain->getCode(),
                    'link' => $testDomain->getDomain()
                ];
            }

            $testsData[] = [
                'id' => $test->getId(),
                'name' => $test->getName(),
                'comment' => $test->getComment(),
                'script' => $test->getScriptUrl(),
                'sort' => $test->getSort(),
                'links' => $links
            ];
        }

        usort($testsData, function($a, $b) {
            if($a['sort'] != $b['sort']) {
                return $a['sort'] > $b['sort'] ? 1 : -1;
            }
            return $a['id'] > $b['id'] ? 1 : -1;;
        });

        return $this->json([
            'success' => true,
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'regexp' => $project->getBranchRegexp(),
                'externalId' => $project->getExternalId(),
                'tests' => array_values($testsData)
            ]
        ]);
    }
}
