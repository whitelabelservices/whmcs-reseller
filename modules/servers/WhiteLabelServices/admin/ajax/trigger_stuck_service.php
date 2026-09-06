<?php
 





require_once dirname(__DIR__) . '/includes/wls_bootstrap.php';

$whmcsRoot = wls_find_whmcs_root_dir(__DIR__);
$initPath = $whmcsRoot ? ($whmcsRoot . DIRECTORY_SEPARATOR . 'init.php') : '';

if ($whmcsRoot === null || !is_file($initPath)) {
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['success' => false, 'message' => 'WHMCS init not found']));
}

require $initPath;

use WHMCS\Database\Capsule;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['adminid'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized — sign in to WHMCS admin (same browser); if using an iframe, open the panel in a new tab.']));
}

require_once dirname(dirname(__DIR__)) . '/WhiteLabelServices.php';

$serviceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
$action = isset($_POST['action']) ? (string) $_POST['action'] : 'vm_check';

if ($serviceId < 1) {
    die(json_encode(['success' => false, 'message' => 'Invalid service ID']));
}

try {
    $params = WhiteLabelServices_BuildModuleParamsFromHostingId($serviceId);
    if (!$params) {
        die(json_encode(['success' => false, 'message' => 'Not a WLS hosting service']));
    }

    if ($action === 'sync_vm') {
        $out = WhiteLabelServices_SyncVMFromAPI($params);
        if (!empty($out['error'])) {
            logActivity('WLS Bağlı Hizmetler — SyncVM API hata SID ' . $serviceId . ': ' . $out['error']);
            die(json_encode(['success' => false, 'message' => $out['error']]));
        }
        $msg = isset($out['reconciled']) ? ('Senkron tamam (reconcile: ' . $out['reconciled'] . ')') : 'VM verileri API ile güncellendi';
        logActivity('WLS Bağlı Hizmetler — SyncVM SID ' . $serviceId);
        die(json_encode(['success' => true, 'message' => $msg]));
    }

    if ($action === 'vm_check') {
        $result = WhiteLabelServices_check_vm_status(['serviceid' => $serviceId]);
        $ok = ($result === 'success');
        logActivity('WLS Bağlı Hizmetler — check_vm_status SID ' . $serviceId . ' → ' . (string) $result);
        die(json_encode([
            'success' => $ok,
            'message' => $ok ? 'Kuyruk/cron ile aynı kontrol çalıştırıldı (başarılı).' : (string) $result,
        ]));
    }

    die(json_encode(['success' => false, 'message' => 'Unknown action']));
} catch (Throwable $e) {
    logActivity('WLS Bağlı Hizmetler hata SID ' . $serviceId . ': ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}
