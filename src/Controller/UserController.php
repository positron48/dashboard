<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Service\Redmine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/api/auth", name="auth", methods="POST")
     */
    public function auth(Request $request, EntityManagerInterface $entityManager)
    {
        // todo: refactor with DTO
        $username = $request->request->get('login');
        $password = $request->request->get('password');

        $redmine = new Redmine($this->getParameter('redmine_url'), "", $username, $password);
        try {
            $currentUser = $redmine->getUser();
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        //todo: move to repository
        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if($user === null) {
            $user = new User();
            $user->setUsername($currentUser['login']);
        }

        $user->setApiKey($currentUser['api_key']); // на всякий обновляем апи ключ

        $projects = $redmine->getUserProjects();
        $projectIds = array_keys($projects);

        $user->setProjects($projectIds);

        $entityManager->persist($user);
        $entityManager->flush();

        /** @var ProjectRepository $repository */
        $repository = $entityManager->getRepository(Project::class);
        $existedProjects = $repository->findBy(['externalId' => $projectIds]); //todo: move to repository

        $userProjects = [];
        foreach ($existedProjects as $existedProject) {
            $userProjects[] = [
                'id' => $existedProject->getId(),
                'name' => $existedProject->getName()
            ];
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'image' => 'https://www.gravatar.com/avatar/' . $user->getGravatarHash(),
                'name' => $user->getUsername(),
                'projects' => $userProjects
            ],
            'token' => $user->getApiKey(),
        ]);
    }

    /**
     * @Route("/api/user", name="user", methods="GET")
     */
    public function user(EntityManagerInterface $entityManager)
    {
        /** @var User $user */
        $user = $this->getUser();

        $redmine = new Redmine($this->getParameter('redmine_url'), $user->getApiKey());
        try {
            $projects = $redmine->getUserProjects();
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        $projectIds = array_keys($projects);

        $user->setProjects($projectIds);
        $entityManager->persist($user);
        $entityManager->flush();

        /** @var ProjectRepository $repository */
        $repository = $entityManager->getRepository(Project::class);
        $existedProjects = $repository->findBy(['externalId' => $projectIds]); //todo: move to repository

        $userProjects = [];
        foreach ($existedProjects as $existedProject) {
            $userProjects[] = [
                'id' => $existedProject->getId(),
                'name' => $existedProject->getName()
            ];
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'image' => 'https://www.gravatar.com/avatar/' . $user->getGravatarHash(),
                'name' => $user->getUsername(),
                'projects' => $userProjects
            ],
            'token' => $user->getApiKey(),
        ]);
    }
}
