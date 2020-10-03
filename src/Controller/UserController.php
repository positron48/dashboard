<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Redmine\Client;
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
        $url = $this->getParameter('redmine_url');

        // todo: refactor with DTO
        $username = $request->request->get('login');
        $password = $request->request->get('password');

        // todo: move to service
        $redmineClient = new Client($url, $username, $password);

        $currentUser = $redmineClient->user->getCurrentUser();
        if($currentUser === false){
            return $this->json([
                'success' => false,
                'message' => 'Auth error'
            ]);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]); //todo: move to repository
        if($user === null) {
            $user = new User();
            $user->setUsername($currentUser['user']['login']);
        }

        $user->setApiKey($currentUser['user']['api_key']); // на всякий обновляем апи ключ
        $entityManager->persist($user);
        $entityManager->flush();

        //todo: поиск проектов в системе, которые есть у пользователя и их привязка пользователю
        $projects = array_column($currentUser['user']['memberships'], 'project');
        $projectIds = array_column($projects, 'id');

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'image' => 'https://www.gravatar.com/avatar/' . $user->getGravatarHash(),
                'name' => $user->getUsername(),
            ],
            'token' => $user->getApiKey(),
        ]);
    }
}
