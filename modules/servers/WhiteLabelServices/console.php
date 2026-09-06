<?php
 










$whmcsRoot = dirname(__DIR__, 3);
if (!is_file($whmcsRoot . DIRECTORY_SEPARATOR . 'init.php')) {
    http_response_code(500);
    echo 'WHMCS init.php not found';
    exit;
}

chdir($whmcsRoot);
require_once $whmcsRoot . DIRECTORY_SEPARATOR . 'init.php';
require_once __DIR__ . '/lib/wls_client_auth.php';

try {
    $serviceId = (int) ($_GET['serviceid'] ?? 0);
    if ($serviceId <= 0) {
        throw new RuntimeException('Invalid service ID');
    }

    wls_assert_client_owns_wls_service($serviceId);

    $params = WhiteLabelServices_BuildModuleParamsFromHostingId($serviceId);
    if (!$params) {
        throw new RuntimeException('Service not found');
    }

    WhiteLabelServices_renderConsolePage($params);
} catch (Throwable $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Console</title></head>'
        . '<body style="font-family:sans-serif;padding:2rem;background:#0f172a;color:#e2e8f0;">'
        . '<h2>Console unavailable</h2><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
}
