<?php
 



chdir(dirname(__DIR__, 4));
require_once 'init.php';

require_once dirname(__DIR__) . '/lib/wls_client_auth.php';

header('Content-Type: application/json');

use WHMCS\Database\Capsule;

try {
    $serviceId = (int) ($_GET['serviceid'] ?? $_POST['serviceid'] ?? 0);
    if (!$serviceId) {
        throw new Exception('Invalid service ID');
    }

    $vpsDetails = wls_assert_client_owns_wls_service($serviceId);
    if (!$vpsDetails || !$vpsDetails->wls_service_id) {
        throw new Exception('VM not found');
    }

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
        'serviceid' => $serviceId,
    ];

    if (!$vpsDetails->wls_vm_id) {
        $resolvedVmId = WhiteLabelServices_EnsureWlsVmId($params, $vpsDetails);
        if ($resolvedVmId) {
            $vpsDetails->wls_vm_id = $resolvedVmId;
        }
    }

    if (!$vpsDetails->wls_vm_id) {
        throw new Exception('VM not found');
    }

    $token = WhiteLabelServices_getToken($params);
    if (!$token) {
        throw new Exception('Failed to get API token');
    }

    $vmDetails = WhiteLabelServices_getVMDetails($serviceId, $vpsDetails->wls_vm_id, $token);
    if (!$vmDetails || !isset($vmDetails['vm'])) {
        throw new Exception('Failed to get VM details');
    }

    $vmData = $vmDetails['vm'];
    $vmReady = ($vmData['status'] ?? '') === 'running' && !empty($vmData['built']);

    $updateData = [
        'vm_status' => $vmData['status'] ?? '',
        'vm_built' => !empty($vmData['built']),
        'vm_power' => !empty($vmData['power']),
        'vm_ip' => $vmData['ipv4'] ?? '',
        'vm_username' => $vmData['username'] ?? '',
        'vm_password' => $vmData['password'] ?? '',
        'vm_memory' => $vmData['memory'] ?? '',
        'vm_disk' => $vmData['disk'] ?? '',
        'vm_cores' => $vmData['cores'] ?? '',
        'vm_template' => $vmData['template_name'] ?? '',
        'vm_mac' => $vmData['mac'] ?? '',
        'vm_uptime' => $vmData['uptime'] ?? 0,
        'last_check' => date('Y-m-d H:i:s'),
    ];

    if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
        $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
    }

    if (isset($vmData['ip']) && is_array($vmData['ip'])) {
        $allIps = [];
        foreach ($vmData['ip'] as $ipId => $ipInfo) {
            $allIps[] = [
                'id' => $ipId,
                'ip' => $ipInfo['ipaddress'],
                'main' => $ipInfo['main'],
            ];
        }
        $updateData['vm_all_ips'] = json_encode($allIps);
    }

    $storage = WhiteLabelServices_BuildStorageFromVm($vmData);
    if (!empty($storage)) {
        $updateData['vm_storage'] = json_encode($storage);
    }

    $updateData['vm_resources'] = json_encode([
        'memory' => $vmData['memory'] ?? '',
        'disk' => $vmData['disk'] ?? '',
        'cores' => $vmData['cores'] ?? '',
        'sockets' => $vmData['sockets'] ?? '',
        'cpus' => $vmData['cpus'] ?? '',
        'uptime' => $vmData['uptime'] ?? 0,
        'last_check' => time(),
    ]);

    WLSTokenManager::updateVPSDetails($serviceId, $updateData);

    if ($vmReady) {
        $hostingData = [];
        if (!empty($vmData['username'])) {
            $hostingData['username'] = $vmData['username'];
        }
        if (!empty($vmData['password'])) {
            $hostingData['password'] = $vmData['password'];
        }
        if (!empty($vmData['ipv4'])) {
            $hostingData['dedicatedip'] = $vmData['ipv4'];
            $hostingData['assignedips'] = $vmData['ipv4'];
        }
        if (!empty($hostingData)) {
            Capsule::table('tblhosting')->where('id', $serviceId)->update($hostingData);
        }
    }

    $interfaces = [];
    if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
        foreach ($vmData['interfaces'] as $ifaceName => $iface) {
            $interfaces[$ifaceName] = [
                'name' => $ifaceName,
                'mac' => $iface['mac'] ?? '',
                'bridge' => $iface['bridge'] ?? '',
                'ip' => $iface['ip'] ?? [],
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'vm_ready' => $vmReady,
        'vm_data' => [
            'status' => $vmData['status'] ?? '',
            'power' => $vmData['power'] ?? false,
            'built' => $vmData['built'] ?? false,
            'uptime' => $vmData['uptime'] ?? 0,
            'memory' => $vmData['memory'] ?? '',
            'disk' => $vmData['disk'] ?? '',
            'cores' => $vmData['cores'] ?? '',
            'template' => $vmData['template_name'] ?? '',
            'ip' => $vmData['ipv4'] ?? '',
            'username' => $vmData['username'] ?? '',
            'password' => $vmData['password'] ?? '',
            'mac' => $vmData['mac'] ?? '',
            'interfaces' => $interfaces,
            'bandwidth' => $vmData['bandwidth'] ?? null,
        ],
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
