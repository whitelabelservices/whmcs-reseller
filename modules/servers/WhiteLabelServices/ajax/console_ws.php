<?php
/**
 * WebSocket relay: browser (noVNC) -> WHMCS -> Proxmox VNC.
 * Requires the web server to pass WebSocket upgrades to PHP (nginx: fastcgi_buffering off).
 */

chdir(dirname(__DIR__, 4));
require_once 'init.php';

require_once dirname(__DIR__) . '/lib/wls_client_auth.php';
require_once dirname(__DIR__) . '/lib/console_ws_proxy.php';

try {
    $serviceId = (int) ($_GET['serviceid'] ?? 0);
    if ($serviceId <= 0) {
        throw new RuntimeException('Service ID is required');
    }

    wls_assert_client_owns_wls_service($serviceId);

    $params = WhiteLabelServices_BuildModuleParamsFromHostingId($serviceId);
    if (!$params) {
        throw new RuntimeException('Service not found');
    }

    $session = WhiteLabelServices_prepareVmConsole($params);
    if (isset($session['error'])) {
        throw new RuntimeException($session['error']);
    }

    wls_ws_proxy_console($session);
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $e->getMessage();
}
