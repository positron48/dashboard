<?php
declare(strict_types=1);

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DevController
{
    #[Route('/api/dev/server-status', name: 'server-status', methods: ['GET'])]
    #[OA\Tag(name: 'dev')]
    public function getMenu(
        Request $request,
    ): Response {
        // check environment is dev
        if ($_ENV['APP_ENV'] !== 'prod') {
            return new JsonResponse([
                'status' => false,
                'message' => 'This method is only available in dev environment',
            ], 400);
        }

        try {
            $path = $_SERVER['DOCUMENT_ROOT'];
            $diff = $branch = '';
            $type = $request->get('type');

            switch ($type){
                case 'branch':
                    $ar = [];
                    exec("git rev-parse --abbrev-ref HEAD", $ar);
                    $branch = $ar[0];
                    break;
                case 'status':
                    exec("cd $path && git status -b -s", $ar);
                    foreach ($ar as $i => $str) {
                        if (!$i) {
                            $branch = str_replace('## ', '', $str);
                            $branch = preg_replace('/\.\.\..+$/', '', $branch);
                        } else {
                            $diff .= "$str\n";
                        }
                    }
                    break;
            }

            $result = [
                'branch' => $branch,
                'diff' => $this->getDiff($diff),
                'additional' => []
            ];

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param string $diff
     * @return array<string, mixed>
     */
    function getDiff(string $diff): array
    {
        $added = [];
        $modified = [];
        $untracked = [];
        $deleted = [];

        if (!empty($diff)) {
            preg_match_all('#M  (.*)#', $diff, $added);
            $added = $added[1];

            preg_match_all('# M (.*)#', $diff, $modified);
            $modified = $modified[1];

            preg_match_all('# D (.*)#', $diff, $deleted);
            $deleted = $deleted[1];

            preg_match_all('#\?\? (.*)#', $diff, $untracked);
            $untracked = $untracked[1];
        }

        return [
            'added' => $added,
            'modified' => $modified,
            'untracked' => $untracked,
            'deleted' => $deleted
        ];
    }
}