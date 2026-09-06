<?php
/**
 * WhiteLabelServices VPS Module - Get Network Interfaces AJAX Endpoint
 */

chdir(dirname(__DIR__, 4));
require_once 'init.php';

require_once dirname(__DIR__) . '/lib/wls_client_auth.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $serviceId = (int) ($input['service_id'] ?? $_GET['service_id'] ?? 0);

    if (!$serviceId) {
        throw new Exception('Service ID is required');
    }

    $vpsDetails = wls_assert_client_owns_wls_service($serviceId);
    if (!$vpsDetails) {
        throw new Exception('Service not found');
    }

    $interfaces = [];
    if (!empty($vpsDetails->vm_interfaces)) {
        $decoded = json_decode($vpsDetails->vm_interfaces, true);
        if (is_array($decoded)) {
            $interfaces = $decoded;
        }
    }

    echo json_encode([
        'success' => true,
        'interfaces' => $interfaces,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
