<?php
/**
 * WLS Dashboard - Get Statistics
 * AJAX Only - Direct access blocked
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

// ADMINAREA tanımlamadan yükle
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
    if (!$token) {
        throw new Exception("API Token alınamadı.");
    }

    $apiBaseUrl = WLSTokenManager::getApiBaseUrl();

    // WHMCS Aktif Hizmet
    $whmcsActive = Capsule::table('tblhosting')
        ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
        ->where('tblproducts.servertype', 'WhiteLabelServices')
        ->where('tblhosting.domainstatus', 'Active')
        ->count();

    $wlsActive = 0;
    $credit = '0.00';
    $debt = '0.00';
    $currency = 'USD';
    $pendingTickets = 0;

    // Kredi ve Borç
    try {
        $balanceData = wls_api_call($apiBaseUrl . '/api/balance', $token);
        if (isset($balanceData['details'])) {
            $credit = number_format((float)($balanceData['details']['acc_credit'] ?? 0), 2);
            $debt = number_format((float)($balanceData['details']['acc_balance'] ?? 0), 2);
            $currency = $balanceData['details']['currency'] ?? 'USD';
        }
    } catch (Exception $e) {}

    // Servisler
    try {
        $servicesData = wls_api_call($apiBaseUrl . '/api/service', $token);
        if (isset($servicesData['services'])) {
            foreach ($servicesData['services'] as $svc) {
                if (isset($svc['status']) && $svc['status'] == 'Active') $wlsActive++;
            }
        }
    } catch (Exception $e) {}

    // Ticketlar
    try {
        $ticketsData = wls_api_call($apiBaseUrl . '/api/tickets', $token);
        if (isset($ticketsData['tickets'])) {
            foreach ($ticketsData['tickets'] as $ticket) {
                if (isset($ticket['status']) && $ticket['status'] != 'Closed') $pendingTickets++;
            }
        }
    } catch (Exception $e) {}

    // Endpoint URL'i hesapla (https://hostname/clientarea/ formatında)
    $endpointUrl = 'https://' . $server->hostname;

    echo json_encode([
        'status' => 'success',
        'wls_active' => $wlsActive,
        'whmcs_active' => $whmcsActive,
        'credit' => $credit . ' ' . $currency,
        'debt' => $debt . ' ' . $currency,
        'tickets' => $pendingTickets,
        'endpoint_url' => $endpointUrl
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function wls_api_call($url, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        throw new Exception("API Error ($httpCode)");
    }

    return json_decode($response, true) ?: [];
}
