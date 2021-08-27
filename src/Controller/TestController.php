<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Test;
use App\Entity\TestDomain;
use App\Entity\User;
use App\Repository\TestRepository;
use App\Service\Redmine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
            ->setComment($data['comment'])
            ->setSort((int) $data['sort']);

        if(!empty($data['links'])){
            foreach ($data['links'] as $code => $link) {
                $domain = new TestDomain();
                $domain
                    ->setCode($code)
                    ->setDomain($link)
                    ->setTest($test);

                $entityManager->persist($domain);
                $test->addTestDomain($domain);
            }
        }

        $entityManager->persist($test);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'id' => $test->getId()
        ]);
    }

    /**
     * @Route("/api/test/{id}", name="editTest", methods="PATCH")
     */
    public function editTest($id, Request $request, EntityManagerInterface $entityManager)
    {
        $data = $request->request->all();

        /** @var TestRepository $testRepository */
        $testRepository = $entityManager->getRepository(Test::class);
        $test = $testRepository->find($id);
        if($test === null){
            return $this->json([
                'success' => false,
                'message' => 'Test not found'
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $userProjects = $user->getProjects();

        /** @var Project $project */
        $project = $test->getProject();
        if($userProjects === null || !in_array($project->getExternalId(), $userProjects)){
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


        if(!empty($data['links'])){
            $domains = $test->getTestDomains();
            foreach ($domains as $domain) {
                if(isset($data['links'][$domain->getCode()])){
                    if($domain->getDomain() !== $data['links'][$domain->getCode()]){
                        $domain->setDomain($data['links'][$domain->getCode()]);
                    }
                    unset($data['links'][$domain->getCode()]);
                } else {
                    $test->removeTestDomain($domain);
                    $entityManager->remove($domain);
                }
            }

            foreach ($data['links'] as $code => $link) {
                $domain = new TestDomain();
                $domain
                    ->setCode($code)
                    ->setDomain($link)
                    ->setTest($test);
                $entityManager->persist($domain);
                $test->addTestDomain($domain);
            }
        }

        $test
            ->setName($data['name'])
            ->setScriptUrl($data['script'])
            ->setComment($data['comment'])
            ->setSort((int) $data['sort']);

        $entityManager->persist($test);
        $entityManager->flush();

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/api/test/{id}", name="deleteTest", methods="DELETE")
     */
    public function deleteTest($id, EntityManagerInterface $entityManager)
    {
        /** @var TestRepository $testRepository */
        $testRepository = $entityManager->getRepository(Test::class);
        $test = $testRepository->find($id);
        if($test === null){
            return $this->json([
                'success' => false,
                'message' => 'Test not found'
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $userProjects = $user->getProjects();

        /** @var Project $project */
        $project = $test->getProject();
        if($userProjects === null || !in_array($project->getExternalId(), $userProjects)){
            return $this->json([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        $entityManager->remove($test);
        $entityManager->flush();

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/api/test/{id}", name="getTest", methods="GET")
     */
    public function getTest($id, Request $request, EntityManagerInterface $entityManager)
    {
        /** @var TestRepository $testRepository */
        $testRepository = $entityManager->getRepository(Test::class);
        $test = $testRepository->find($id);
        if($test === null){
            return $this->json([
                'success' => false,
                'message' => 'Test not found'
            ]);
        }

        $client = new CurlHttpClient();

        try {
            $response = $client->request(
                'GET',
                $test->getScriptUrl(),
                [
                    'query' => [
                        'type' => $request->query->get('type') ? $request->query->get('type') : 'branch'
                    ],
                    'verify_peer' => false,
                    'verify_host' => false,
                ]
            );
            $testData = json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'curl error (' . $e->getCode() . '): ' . $e->getMessage()
            ]);
        }

        $testData['links'] = [];
        foreach ($test->getTestDomains() as $testDomain) {
            $testData['links'][] = [
                'title' => $testDomain->getCode(),
                'link' => $testDomain->getDomain()
            ];
        }

        $testData['redmineData'] = [];

        $project = $test->getProject();
        $regexp = $project->getBranchRegexp();

        if(!empty($regexp) && isset($testData['branch'])) {
            preg_match('/' . $regexp . '/', $testData['branch'], $matches);
            if(isset($matches[1])){
                $redmine = $project->getRedmines()[0];
                $redmineClient = new Redmine($redmine->getUrl(), $redmine->getApiKey());

                $task = $redmineClient->getTask($matches[1]);
                if(!empty($task)){
                    $testData['redmineData'] = [
                        'project' => isset($task['project']) ? $task['project']['name'] : '',
                        'status' => isset($task['status']) ? $task['status']['name'] : '',
                        'tracker' => isset($task['tracker']) ? $task['tracker']['name'] : '',
                        'assignedTo' => isset($task['assigned_to']) ? $task['assigned_to']['name'] : '',
                        'subject' => $task['subject'],
                        'link' => $redmine->getUrl() . '/issues/' . $task['id']
                    ];
                }
            }
        }

        return $this->json([
            'success' => true,
            'test' => $testData
        ]);
    }
}
