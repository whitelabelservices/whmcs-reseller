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

require_once dirname(dirname(__DIR__)) . '/lib/TokenManager.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    $server = Capsule::table('tblservers')
        ->where('type', 'WhiteLabelServices')
        ->where('active', 1)
        ->first();

    if (!$server) throw new Exception("Aktif WLS sunucusu bulunamadı.");

    $params = [
        'serverhostname' => $server->hostname,
        'serverusername' => $server->username,
        'serverpassword' => decrypt($server->password),
        'serverid' => $server->id
    ];

    $token = WLSTokenManager::getToken($params);
    $apiBaseUrl = WLSTokenManager::getApiBaseUrl();

    switch ($action) {
        case 'list':
            $data = wls_api_call($apiBaseUrl . '/api/tickets', $token);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'view':
            $ticketId = $_POST['id'] ?? $_GET['id'] ?? '';
            if (!$ticketId) throw new Exception("Ticket ID gerekli.");
            $data = wls_api_call($apiBaseUrl . '/api/tickets/' . $ticketId, $token);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'reply':
            $ticketId = $_POST['id'] ?? '';
            $message = $_POST['message'] ?? '';
            if (!$ticketId || !$message) throw new Exception("Ticket ID ve mesaj gerekli.");
            $data = wls_api_call($apiBaseUrl . '/api/tickets/' . $ticketId, $token, 'POST', ['body' => $message]);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'create':
            $deptId = $_POST['dept_id'] ?? '';
            $subject = $_POST['subject'] ?? '';
            $message = $_POST['message'] ?? '';
            if (!$deptId || !$subject || !$message) throw new Exception("Tüm alanlar gerekli.");
            $data = wls_api_call($apiBaseUrl . '/api/tickets', $token, 'POST', [
                'dept_id' => $deptId, 'subject' => $subject, 'body' => $message
            ]);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'departments':
            $data = wls_api_call($apiBaseUrl . '/api/ticket/departments', $token);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'close':
            $ticketId = $_POST['id'] ?? '';
            if (!$ticketId) throw new Exception("Ticket ID gerekli.");
            $data = wls_api_call($apiBaseUrl . '/api/tickets/' . $ticketId . '/close', $token, 'PUT');
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function wls_api_call($url, $token, $method = 'GET', $data = []) {
    $ch = curl_init();
    
    if ($method == 'GET' && !empty($data)) $url .= '?' . http_build_query($data);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token, 
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
    } elseif ($method == 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token, 
            'Accept: application/json'
        ]);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token, 
            'Accept: application/json'
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 400) throw new Exception("API Error ($httpCode): $response");

    return json_decode($response, true) ?: [];
}
