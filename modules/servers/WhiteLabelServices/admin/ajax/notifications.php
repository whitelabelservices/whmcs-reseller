<?php
/**
 * WLS Notifications AJAX Handler
 * AJAX Only
 */

// AJAX güvenlik kontrolü
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

// Session kontrolü - WHMCS zaten session başlatmış olabilir
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
            $data = wls_api_call($apiBaseUrl . '/api/notifications', $token);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'new':
            $data = wls_api_call($apiBaseUrl . '/api/notifications/new', $token);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'ack':
            $id = $_POST['id'] ?? '';
            if (!$id) throw new Exception("Notification ID gerekli.");
            $data = wls_api_call($apiBaseUrl . '/api/notifications/' . $id . '/ack', $token, 'PUT');
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'ack_all':
            $list = wls_api_call($apiBaseUrl . '/api/notifications', $token);
            $notifications = $list['notifications'] ?? (is_array($list) ? $list : []);
            if (!is_array($notifications)) {
                $notifications = [];
            }
            $marked = 0;
            foreach ($notifications as $notification) {
                if (!is_array($notification) || empty($notification['id'])) {
                    continue;
                }
                $seen = $notification['seen'] ?? null;
                if ($seen === '0' || $seen === 0 || $seen === null || $seen === false) {
                    wls_api_call($apiBaseUrl . '/api/notifications/' . $notification['id'] . '/ack', $token, 'PUT');
                    $marked++;
                }
            }
            echo json_encode(['status' => 'success', 'marked' => $marked]);
            break;

        case 'news':
            $data = wls_api_call($apiBaseUrl . '/api/news', $token);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'news_detail':
            $id = $_POST['id'] ?? $_GET['id'] ?? '';
            if (!$id) throw new Exception("News ID gerekli.");
            $data = wls_api_call($apiBaseUrl . '/api/news/' . $id, $token);
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
    
    if ($method == 'PUT') {
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
    curl_close($ch);

    if ($httpCode >= 400) throw new Exception("API Error ($httpCode): $response");

    return json_decode($response, true) ?: [];
}
