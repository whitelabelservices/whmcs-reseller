<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/lib/wls_client_auth.php';

use WHMCS\Database\Capsule;

$serviceId = 0;

try {
    $serviceId = (int) ($_GET['serviceid'] ?? 0);
    if (!$serviceId) {
        throw new Exception('Invalid service ID');
    }

    $vpsDetails = wls_assert_client_owns_wls_service($serviceId);
    if (!$vpsDetails || !$vpsDetails->wls_service_id) {
        throw new Exception('Service not found');
    }

    $server = Capsule::table('tblservers')
        ->where('type', 'WhiteLabelServices')
        ->where('active', '1')
        ->first();

    if (!$server) {
        throw new Exception('No active WLS server found');
    }

    $apiParams = [
        'serverid' => $server->id,
        'serverusername' => $server->username,
        'serverpassword' => decrypt($server->password),
    ];

    $wlsServiceId = $vpsDetails->wls_service_id;

    $serviceStatus = wls_api_call_with_token_refresh($apiParams, function ($token) use ($wlsServiceId) {
        return WhiteLabelServices_CheckServiceStatus($wlsServiceId, $token);
    });

    if (!$serviceStatus || !isset($serviceStatus['service'])) {
        throw new Exception('Failed to get service status from WLS API');
    }

    $updateData = [
        'status' => $vpsDetails->status,
        'service_status' => $serviceStatus['service']['status'],
        'wls_service_id' => $wlsServiceId,
        'last_check' => date('Y-m-d H:i:s'),
    ];

    $syncMessage = 'Service synchronized successfully';
    $vmUpdated = false;

    if ($serviceStatus['service']['status'] === 'Active') {
        $vmList = wls_api_call_with_token_refresh($apiParams, function ($token) use ($wlsServiceId) {
            return WhiteLabelServices_getVMList($wlsServiceId, $token);
        });

        if ($vmList && isset($vmList['vms']) && !empty($vmList['vms'])) {
            foreach ($vmList['vms'] as $vmId => $vm) {
                $updateData['wls_vm_id'] = $vmId;
                $updateData['vm_status'] = $vm['status'] ?? '';
                $updateData['vm_built'] = !empty($vm['built']);
                $updateData['vm_powered'] = !empty($vm['power']);

                if (($vm['status'] ?? '') === 'running' && !empty($vm['built'])) {
                    $vmDetails = wls_api_call_with_token_refresh($apiParams, function ($token) use ($serviceId, $vmId) {
                        return WhiteLabelServices_getVMDetails($serviceId, $vmId, $token);
                    });

                    if ($vmDetails && isset($vmDetails['vm'])) {
                        $vmData = $vmDetails['vm'];

                        $updateData['vm_ip'] = $vmData['ipv4'] ?? '';
                        $updateData['vm_username'] = $vmData['username'] ?? '';
                        $updateData['vm_password'] = $vmData['password'] ?? '';
                        $updateData['vm_memory'] = $vmData['memory'] ?? '';
                        $updateData['vm_disk'] = $vmData['disk'] ?? '';
                        $updateData['vm_cores'] = $vmData['cores'] ?? '';
                        $updateData['vm_template'] = $vmData['template_name'] ?? '';
                        $updateData['vm_mac'] = $vmData['mac'] ?? '';
                        $updateData['vm_uptime'] = $vmData['uptime'] ?? 0;

                        if (isset($vmData['storage']) && is_array($vmData['storage'])) {
                            $updateData['vm_storage'] = json_encode($vmData['storage']);
                        }
                        if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
                            $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
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

                        $vmUpdated = true;
                        $syncMessage = 'Service and VM details synchronized successfully. VM is running and active.';
                    }
                } else {
                    $syncMessage = 'Service synchronized. VM status: ' . ($vm['status'] ?? 'unknown')
                        . ', Built: ' . (!empty($vm['built']) ? 'Yes' : 'No');
                }
                break;
            }
        } else {
            $syncMessage = 'Service synchronized. No VMs found for this service.';
        }
    } else {
        $syncMessage = 'Service synchronized. Service status: ' . $serviceStatus['service']['status'];
    }

    WLSTokenManager::updateVPSDetails($serviceId, $updateData);

    header('Location: clientarea.php?action=productdetails&id=' . $serviceId . '&success=1&message=' . urlencode($syncMessage));
    exit;
} catch (Exception $e) {
    logActivity('WLS Sync Error: ' . $e->getMessage());
    $redirectId = $serviceId > 0 ? $serviceId : (int) ($_GET['serviceid'] ?? 0);
    header('Location: clientarea.php?action=productdetails&id=' . $redirectId . '&error=1&message=' . urlencode($e->getMessage()));
    exit;
}
