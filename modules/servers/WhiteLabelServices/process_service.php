<?php

require_once('../../../init.php');
require_once('WhiteLabelServices.php');

use WHMCS\Database\Capsule;

header('Content-Type: application/json');

try {
    if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
        throw new Exception('Service ID is required');
    }
    
    $serviceId = intval($_POST['service_id']);
    
    // Service'i kontrol et
    $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
    if (!$service) {
        throw new Exception('Service not found');
    }
    
    // WLS ürünü mü kontrol et
    $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
    if (!$product || $product->servertype !== 'WhiteLabelServices') {
        throw new Exception('This is not a WLS service');
    }
    
    $params = WhiteLabelServices_ParamsMergeProductConfigOptions($product, array(
        'serviceid' => $serviceId,
        'domain' => $service->domain,
        'billingcycle' => $service->billingcycle,
    ));
    
    // Custom fields'ları al
    $customFields = [];
    $customFieldValues = Capsule::table('tblcustomfieldsvalues')
        ->join('tblcustomfields', 'tblcustomfieldsvalues.fieldid', '=', 'tblcustomfields.id')
        ->where('tblcustomfieldsvalues.relid', $serviceId)
        ->where('tblcustomfields.type', 'product')
        ->select('tblcustomfields.fieldname', 'tblcustomfieldsvalues.value')
        ->get();
        
    foreach ($customFieldValues as $field) {
        $customFields[$field->fieldname] = $field->value;
    }
    $params['customfields'] = $customFields;
    
    // Configurable options'ları al
    $configOptions = [];
    $configValues = Capsule::table('tblhostingconfigoptions')
        ->join('tblproductconfigoptions', 'tblhostingconfigoptions.configid', '=', 'tblproductconfigoptions.id')
        ->join('tblproductconfigoptionssub', 'tblhostingconfigoptions.optionid', '=', 'tblproductconfigoptionssub.id')
        ->where('tblhostingconfigoptions.relid', $serviceId)
        ->select('tblproductconfigoptions.optionname', 'tblproductconfigoptionssub.optionname as value')
        ->get();
        
    foreach ($configValues as $option) {
        $configOptions[$option->optionname] = $option->value;
    }
    $params['configoptions'] = $configOptions;
    
    // CreateAccount'u çağır
    $result = WhiteLabelServices_CreateAccount($params);
    
    if ($result === 'success') {
        logActivity("WLS Manual Processing - Service " . $serviceId . " processed successfully via admin panel");
        
        echo json_encode([
            'success' => true,
            'message' => 'Service processed successfully'
        ]);
    } else {
        throw new Exception($result);
    }
    
} catch (Exception $e) {
    logActivity("WLS Manual Processing Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 
