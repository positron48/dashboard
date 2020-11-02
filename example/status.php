<?
$path = $_SERVER['DOCUMENT_ROOT'];
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$host = COption::GetOptionString('intaro.retailcrm', 'api_host');

switch ($_REQUEST['type']){
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
    'diff' => getDiff($diff),
    'additional' => [
        [
            'title' => 'crm',
            'type' => 'hint',
            'hint' => $host,
            'text' => getCrmNumber($host)
        ]
    ]
];

echo json_encode($result, JSON_UNESCAPED_UNICODE);

function getDiff($diff)
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

function getCrmNumber($host)
{
    preg_match('/^\D*(\d+)/', $host, $matches);
    return isset($matches[1]) ? $matches[1] : '-';
}