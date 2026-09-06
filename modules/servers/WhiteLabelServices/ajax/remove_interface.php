<?php
/**
 * WhiteLabelServices VPS Module - Remove Network Interface AJAX Endpoint
 */

chdir(dirname(__DIR__, 4));
require_once 'init.php';

require_once dirname(__DIR__) . '/lib/wls_client_auth.php';

header('Content-Type: application/json');

use WHMCS\Database\Capsule;

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $serviceId = (int) ($input['service_id'] ?? 0);
    $interface = trim($input['interface'] ?? '');

    if (!$serviceId) {
        throw new Exception('Service ID is required');
    }
    if (!$interface) {
        throw new Exception('Interface name is required');
    }
    if ($interface === 'net0') {
        throw new Exception('Cannot remove primary network interface (net0)');
    }

    $vpsDetails = wls_assert_client_owns_wls_service($serviceId);
    if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
        throw new Exception('VM not provisioned yet');
    }

    $wlsServiceId = $vpsDetails->wls_service_id;
    $vmId = $vpsDetails->wls_vm_id;

    $server = Capsule::table('tblservers')
        ->where('type', 'WhiteLabelServices')
        ->where('active', '1')
        ->first();

    if (!$server) {
        throw new Exception('No active WLS server found');
    }

    $params = [
        'serverid' => $server->id,
        'serverusername' => $server->username,
        'serverpassword' => decrypt($server->password),
    ];

    $token = WhiteLabelServices_getToken($params);
    if (!$token) {
        throw new Exception('Failed to get API token');
    }

    $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
    $apiUrl = $apiBaseUrl . "/api/service/{$wlsServiceId}/vms/{$vmId}/interfaces/{$interface}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('API Error: ' . $error);
    }
    curl_close($ch);

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['message'] ?? 'Unknown API error';
        throw new Exception("API Error (HTTP {$httpCode}): {$errorMessage}");
    }

    logActivity("WLS Network - Interface {$interface} removed for service {$serviceId} (VM: {$vmId})");

    echo json_encode([
        'success' => true,
        'message' => "Network interface {$interface} removed successfully",
    ]);
} catch (Exception $e) {
    logActivity('WLS Network Error - Failed to remove interface: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
