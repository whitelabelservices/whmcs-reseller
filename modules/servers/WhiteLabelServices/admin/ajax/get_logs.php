<?php
 




 
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Direct access not allowed']));
}

require_once dirname(__DIR__) . '/includes/wls_bootstrap.php';

$whmcsRoot = wls_find_whmcs_root_dir(__DIR__);
$initPath = $whmcsRoot ? ($whmcsRoot . DIRECTORY_SEPARATOR . 'init.php') : '';

if ($whmcsRoot === null || !is_file($initPath)) {
    die(json_encode(['status' => 'error', 'message' => 'WHMCS not found']));
}

require $initPath;

use WHMCS\Database\Capsule;

 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['adminid'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

header('Content-Type: application/json');

try {
    $logs = Capsule::table('tblactivitylog')
        ->where(function($query) {
            $query->where('description', 'like', '%WLS%')
                  ->orWhere('description', 'like', '%WhiteLabelServices%');
        })
        ->orderBy('date', 'desc')
        ->limit(15)
        ->get();

    $result = [];
    foreach ($logs as $log) {
        $result[] = [
            'date' => $log->date,
            'description' => $log->description
        ];
    }

    echo json_encode(['status' => 'success', 'logs' => $result]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
