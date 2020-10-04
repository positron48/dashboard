<?php


namespace App\Service;


use Redmine\Client;

class Redmine
{
    protected $client;

    public function __construct($redmineUrl, $token=null, $username=null, $password=null)
    {
        if($token) {
            $this->client = new Client($redmineUrl, $token);
        } else {
            $this->client = new Client($redmineUrl, $username, $password);
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getUser()
    {
        $user = $this->client->user->getCurrentUser();

        if($user === false){
            throw new \Exception('Auth error');
        }

        return $user['user'];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getUserProjects()
    {
        $currentUser = $this->getUser();

        // todo: не отдавать проекты, которые уже существуют у пользователя (либо вешать disable)
        $projects = array_column($currentUser['memberships'], 'project');
        $projectsFormatted = [];
        foreach ($projects as $project) {
            $projectsFormatted[$project['id']] = [
                'value' => $project['id'],
                'text' => $project['name'],
            ];
        }

        return $projectsFormatted;
    }
}