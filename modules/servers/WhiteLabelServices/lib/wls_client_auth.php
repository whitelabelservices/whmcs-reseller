<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

 


function wls_require_module() {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $moduleFile = dirname(__DIR__) . '/WhiteLabelServices.php';
    if (!is_file($moduleFile)) {
        throw new Exception('WhiteLabelServices module not found');
    }
    require_once $moduleFile;
    $loaded = true;
}

 




function wls_assert_client_owns_wls_service($serviceId) {
    wls_require_module();
    require_once __DIR__ . '/TokenManager.php';

    $serviceId = (int) $serviceId;
    if ($serviceId <= 0) {
        throw new Exception('Invalid service ID');
    }

    if (empty($_SESSION['uid'])) {
        throw new Exception('Authentication required');
    }

    $clientId = (int) $_SESSION['uid'];
    $service = Capsule::table('tblhosting')
        ->where('id', $serviceId)
        ->where('userid', $clientId)
        ->first();

    if (!$service) {
        throw new Exception('Service not found or access denied');
    }

    $product = Capsule::table('tblproducts')
        ->where('id', $service->packageid)
        ->first();

    if (!$product || $product->servertype !== 'WhiteLabelServices') {
        throw new Exception('This is not a WhiteLabelServices service');
    }

    return WLSTokenManager::getVPSDetails($serviceId);
}

 


function wls_is_api_auth_error_response($data) {
    if (!function_exists('WhiteLabelServices_IsApiAuthErrorResponse')) {
        wls_require_module();
    }
    return WhiteLabelServices_IsApiAuthErrorResponse($data);
}

 






function wls_api_call_with_token_refresh(array $apiParams, callable $fetcher) {
    wls_require_module();
    $token = WhiteLabelServices_getToken($apiParams);
    if (!$token) {
        throw new Exception('Failed to get API token');
    }

    $result = $fetcher($token);
    if (wls_is_api_auth_error_response($result)) {
        $token = WhiteLabelServices_getToken($apiParams, true);
        if (!$token) {
            throw new Exception('Failed to refresh API token');
        }
        $result = $fetcher($token);
    }

    return $result;
}
