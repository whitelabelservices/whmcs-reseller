<?php
/**
 * WhiteLabelServices VPS Module - VM Power Management AJAX Endpoint
 */

chdir(dirname(__DIR__, 4));
require_once 'init.php';

require_once dirname(__DIR__) . '/lib/wls_client_auth.php';

header('Content-Type: application/json');

use WHMCS\Database\Capsule;

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $serviceId = (int) ($input['service_id'] ?? 0);
    $action = trim($input['action'] ?? '');

    if (!$serviceId) {
        throw new Exception('Service ID is required');
    }
    if (!$action) {
        throw new Exception('Action is required');
    }

    $validActions = ['start', 'stop', 'shutdown', 'reboot', 'reset'];
    if (!in_array($action, $validActions, true)) {
        throw new Exception('Invalid action');
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
    $apiUrl = $apiBaseUrl . "/api/service/{$wlsServiceId}/vms/{$vmId}/{$action}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
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

    $result = json_decode($response, true);
    if (!$result || empty($result['status'])) {
        throw new Exception('VM action failed');
    }

    logActivity("WLS VM Action - {$action} executed for service {$serviceId} (VM: {$vmId})");

    echo json_encode([
        'success' => true,
        'message' => "VM {$action} command executed successfully",
    ]);
} catch (Exception $e) {
    if (!empty($serviceId) && !empty($action)) {
        logActivity("WLS VM Action Error - {$action} failed for service {$serviceId}: " . $e->getMessage());
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
