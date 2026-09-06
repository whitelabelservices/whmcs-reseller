<?php

 
require_once '../../../init.php';

use WHMCS\Database\Capsule;

 
if (!isset($_SESSION['adminid'])) {
    header('Location: /admin/login.php');
    exit;
}

 
$serviceId = $_GET['service_id'] ?? null;
if (!$serviceId) {
    die('Service ID is required. Usage: admin_sync.php?service_id=12');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>WhiteLabelServices Synchronization</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #f0fff0; padding: 10px; border: 1px solid green; }
        .error { color: red; background: #fff0f0; padding: 10px; border: 1px solid red; }
        .info { color: blue; background: #f0f0ff; padding: 10px; border: 1px solid blue; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; }
        .button:hover { background: #005a87; }
    </style>
</head>
<body>";

echo "<h1>WhiteLabelServices Synchronization</h1>";
echo "<p><strong>Service ID:</strong> " . htmlspecialchars($serviceId) . "</p>";

try {
     
    require_once 'WhiteLabelServices.php';
    
     
    $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
    if (!$service) {
        throw new Exception('Service not found');
    }
    
     
    $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
    if (!$product || $product->servertype !== 'WhiteLabelServices') {
        throw new Exception('This is not a WhiteLabelServices service');
    }
    
    echo "<div class='info'>";
    echo "<strong>Service Details:</strong><br>";
    echo "Domain: " . htmlspecialchars($service->domain) . "<br>";
    echo "Status: " . htmlspecialchars($service->domainstatus) . "<br>";
    echo "Product: " . htmlspecialchars($product->name) . "<br>";
    echo "</div>";
    
     
    $action = $_GET['action'] ?? null;
    
    if ($action === 'sync') {
        echo "<h2>Synchronizing Service...</h2>";
        
         
        $params = [
            'serviceid' => $serviceId,
            'domain' => $service->domain,
            'username' => $service->username,
            'password' => $service->password,
            'notes' => $service->notes
        ];
        
         
        $result = WhiteLabelServices_synchronizeService($params);
        
        if ($result === 'success') {
            echo "<div class='success'>";
            echo "<strong>✅ Synchronization Successful!</strong><br>";
            echo "Service has been synchronized with WLS API.";
            echo "</div>";
            
             
            $updatedService = Capsule::table('tblhosting')->where('id', $serviceId)->first();
            $notes = json_decode($updatedService->notes, true);
            
            if ($notes && isset($notes['WLS_vm_ip'])) {
                echo "<div class='info'>";
                echo "<strong>Updated VM Information:</strong><br>";
                echo "VM ID: " . ($notes['wls_vm_id'] ?? 'N/A') . "<br>";
                echo "VM Status: " . ($notes['WLS_vm_status'] ?? 'N/A') . "<br>";
                echo "IP Address: " . ($notes['WLS_vm_ip'] ?? 'N/A') . "<br>";
                echo "Username: " . ($notes['WLS_vm_username'] ?? 'N/A') . "<br>";
                echo "Password: " . ($notes['WLS_vm_password'] ?? 'N/A') . "<br>";
                echo "Last Sync: " . ($notes['last_sync'] ?? 'N/A') . "<br>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<strong>❌ Synchronization Failed!</strong><br>";
            echo "Error: " . htmlspecialchars($result);
            echo "</div>";
        }
    } elseif ($action === 'check') {
        echo "<h2>Checking VM Status...</h2>";
        
         
        $params = [
            'serviceid' => $serviceId,
            'domain' => $service->domain,
            'username' => $service->username,
            'password' => $service->password,
            'notes' => $service->notes
        ];
        
         
        $result = WhiteLabelServices_checkVMStatusButton($params);
        
        if ($result === 'success') {
            echo "<div class='success'>";
            echo "<strong>✅ VM Status Check Successful!</strong><br>";
            echo "VM status has been updated.";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<strong>❌ VM Status Check Failed!</strong><br>";
            echo "Error: " . htmlspecialchars($result);
            echo "</div>";
        }
    }
    
     
    echo "<h2>Actions</h2>";
    echo "<a href='?service_id=" . $serviceId . "&action=sync' class='button'>🔄 Synchronize Service</a>";
    echo "<a href='?service_id=" . $serviceId . "&action=check' class='button'>🔍 Check VM Status</a>";
    echo "<a href='/admin/clientsservices.php?userid=" . $service->userid . "&id=" . $serviceId . "' class='button'>← Back to Service</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</body></html>"; 
