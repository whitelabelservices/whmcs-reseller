<?php
/**
 * WLS Dashboard - Get Invoices
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

try {
    $server = Capsule::table('tblservers')
        ->where('type', 'WhiteLabelServices')
        ->where('active', 1)
        ->first();

    if (!$server) {
        throw new Exception("Aktif WLS sunucusu bulunamadı.");
    }

    $params = [
        'serverhostname' => $server->hostname,
        'serverusername' => $server->username,
        'serverpassword' => decrypt($server->password),
        'serverid' => $server->id
    ];

    $token = WLSTokenManager::getToken($params);
    $apiBaseUrl = WLSTokenManager::getApiBaseUrl();

    $invoicesData = wls_api_call($apiBaseUrl . '/api/invoice', $token);
    
    $invoices = [];
    if (isset($invoicesData['invoices'])) {
        $count = 0;
        foreach ($invoicesData['invoices'] as $inv) {
            if ($count >= 10) break;
            $invoices[] = [
                'id' => $inv['id'] ?? $inv['invoice_id'] ?? '-',
                'total' => ($inv['total'] ?? '0') . ' ' . ($inv['currency'] ?? 'USD'),
                'status' => $inv['status'] ?? 'Unknown',
                'date' => $inv['date'] ?? $inv['created_at'] ?? '-'
            ];
            $count++;
        }
    }

    $endpointUrl = 'https://' . $server->hostname;
    echo json_encode(['status' => 'success', 'invoices' => $invoices, 'endpoint_url' => $endpointUrl]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function wls_api_call($url, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) throw new Exception("API Error ($httpCode)");

    return json_decode($response, true) ?: [];
}
