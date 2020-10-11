<?
$path = $_SERVER['DOCUMENT_ROOT'];
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$host = COption::GetOptionString('intaro.retailcrm', 'api_host');

$added = [];
$modified = [];
$untracked = [];
$deleted = [];

if($_REQUEST['type'] === 'branch'){

    $cmd = "git rev-parse --abbrev-ref HEAD";
    $ar = [];
    exec($cmd, $ar);
    $branch = $ar[0];
    
} else {

    $cmd = "cd $path && git status -b -s";
    $branch = '';
    $diff = '';
    $ar = [];

    exec($cmd, $ar);

    foreach ($ar as $i => $str) {
        if (!$i) {
            $branch = str_replace('## ', '', $str);
            $branch = preg_replace('/\.\.\..+$/', '', $branch);
        } else {
            $diff .= "$str\n";
        }
    }

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
}

$status = [
    'branch' => $branch,
    'diff' => [
        'added' => $added,
        'modified' => $modified,
        'untracked' => $untracked,
        'deleted' => $deleted
    ],
    'additional' => [
    	'crm' => [
    		'title' => 'crm',
    		'show' => true,
    		'value' => $host
    	]
    ]
];

echo json_encode($status, JSON_UNESCAPED_UNICODE);