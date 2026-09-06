<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

// TokenManager sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±fÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± dahil et
if (!class_exists('WLSTokenManager')) {
    require_once __DIR__ . '/lib/TokenManager.php';
}


// Queue result sabitleri - check_vm_status iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lemi iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
define('WLS_RESULT_SUCCESS', 'wls_success');           // ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lem tamamen bitti
define('WLS_RESULT_RESCHEDULED', 'wls_rescheduled');   // Yeniden zamanlandÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±, task silmeyin
define('WLS_RESULT_FAILED', 'wls_failed');             // Hata oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tu

// NOT: hooks.php dosyasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± /includes/hooks/ dizininde olmalÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±, modÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼lden dahil edilmemeli

/**
 * WLS Debug Log Helper
 * Only logs when debug mode is enabled in WLS Admin Settings
 * @param string $message Log message
 * @param bool $force Force log even if debug mode is off (for critical errors)
 */
function WLS_debugLog($message, $force = false) {
    static $debugMode = null;
    
    // Cache debug mode setting
    if ($debugMode === null) {
        try {
            $setting = Capsule::table('mod_wls_settings')
                ->where('setting_key', 'debug_mode')
                ->first();
            $debugMode = $setting && intval($setting->setting_value) === 1;
        } catch (Exception $e) {
            $debugMode = false;
        }
    }
    
    // Only log if debug mode is enabled or force is true
    if ($debugMode || $force) {
        logActivity("WLS: " . $message);
    }
}

function WhiteLabelServices_MetaData()
{
    return array(
        'DisplayName' => 'WhiteLabelServices VPS Module',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
        'ServiceSingleSignOnLabel' => 'Login to Panel',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
        'NoEditNameservers' => true,
        'NoEditPassword' => true,
        'NoEditDomain' => true,
        'NoEditUsername' => true,
        'NoSSLOption' => true,
        'NoEditHostname' => false,
        'ServiceFeatures' => array(
            'changePassword' => false, // Disable Change Password button
        ),
        'Description' => 'WhiteLabelServices VPS Module provides complete integration for managing VPS services. <br><br><strong>Features:</strong><br>- Service Provisioning<br>- SSO Login<br>- Integrated Admin Dashboard (Statistics, Tickets, Pricing)<br>- API Driven',
        'Author' => 'WhiteLabelServices',
        'Language' => 'english',
        'LogoURL' => '../modules/servers/WhiteLabelServices/logo.png',
        'Homepage' => 'https://whitelabelservices.us',
        'Category' => 'Server',
    );
}

function WhiteLabelServices_TestConnection(array $params)
{
    try {
        if (empty($params['serverusername']) || empty($params['serverpassword'])) {
            return [
                'success' => false,
                'error' => 'API username and password are required',
            ];
        }

        if (empty($params['serverhostname'])) {
            return [
                'success' => false,
                'error' => 'Server hostname is required',
            ];
        }

        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $result = WLSTokenManager::validateClientLogin($params, $apiBaseUrl);

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Authentication failed',
            ];
        }

        if (!empty($params['serverid'])) {
            WLSTokenManager::clearToken((int) $params['serverid']);
            WLSTokenManager::loginWithPassword($params, $apiBaseUrl);
        }

        return [
            'success' => true,
            'error' => '',
        ];
    } catch (Exception $e) {
        WLS_debugLog('Connection Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Connection error: ' . $e->getMessage(),
        ];
    }
}

function WhiteLabelServices_getToken($params, $forceRefresh = false) {
    return WLSTokenManager::getToken($params, $forceRefresh);
}

/**
 * Build portal clientarea URL with JWT for reseller SSO (HostBill User API pattern).
 */
function WhiteLabelServices_buildPortalSsoRedirectUrl(array $params, $wlsServiceId = null) {
    $token = WLSTokenManager::getToken($params);
    if (!$token) {
        throw new Exception('Failed to obtain API token');
    }
    $base = rtrim(WhiteLabelServices_getApiBaseUrl($params), '/');
    $query = 'access_token=' . rawurlencode($token);
    if ($wlsServiceId) {
        return $base . '/clientarea/service/' . (int) $wlsServiceId . '/?' . $query;
    }
    return $base . '/clientarea/?' . $query;
}

function WhiteLabelServices_ServiceSingleSignOn(array $params) {
    try {
        $vps = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vps || !$vps->wls_service_id) {
            throw new Exception('Service not provisioned on portal yet');
        }
        return [
            'success' => true,
            'redirectTo' => WhiteLabelServices_buildPortalSsoRedirectUrl($params, $vps->wls_service_id),
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

function WhiteLabelServices_AdminSingleSignOn(array $params) {
    try {
        return [
            'success' => true,
            'redirectTo' => WhiteLabelServices_buildPortalSsoRedirectUrl($params),
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

// API base URL'ini sunucu yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rmasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
function WhiteLabelServices_getApiBaseUrl($params = null) {
    // params varsa ve hostname doluysa kullan
    if ($params && !empty($params['serverhostname'])) {
        return 'https://' . $params['serverhostname'];
    }
    
    // Fallback: veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan aktif sunucuyu al
    try {
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
        
        if ($server && !empty($server->hostname)) {
            return 'https://' . $server->hostname;
        }
    } catch (Exception $e) {
        WLS_debugLog("getApiBaseUrl Error: " . $e->getMessage());
    }
    
    // Son fallback
    return 'https://portal.WLS.com';
}

// ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n ID'sini seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ilen dropdown deÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸erinden ayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±kla
function WhiteLabelServices_ExtractProductId($selectedOption) {
    // BoÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ kontrolÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼
    if (empty($selectedOption)) {
        WLS_debugLog("Debug - ExtractProductId: Empty input");
        return false;
    }
    
    // Format 1: "ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n AdÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± (123)" formatÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±
    if (preg_match('/\((\d+)\)/', $selectedOption, $matches)) {
        WLS_debugLog("Debug - ExtractProductId found ID from parentheses: " . $matches[1]);
        return $matches[1];
    }
    
    // Format 2: Sadece sayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± (ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶rn: "12" veya 12)
    if (is_numeric($selectedOption)) {
        WLS_debugLog("Debug - ExtractProductId found numeric ID: " . $selectedOption);
        return intval($selectedOption);
    }
    
    // Format 3: "ID:123" veya "id:123" formatÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±
    if (preg_match('/id[:\s]*(\d+)/i', $selectedOption, $matches)) {
        WLS_debugLog("Debug - ExtractProductId found ID from prefix: " . $matches[1]);
        return $matches[1];
    }
    
    WLS_debugLog("Debug - ExtractProductId failed to extract ID from: " . $selectedOption);
    return false;
}

/**
 * Modul urun ayari semantik anahtarlari — WhiteLabelServices_ConfigOptions() donus dizisi ile ayni sira (configoption1 = ilk alan).
 */
function WhiteLabelServices_ModuleSemanticConfigKeys() {
    return array('wls_api_product', 'wls_promo_code', 'wls_form_config');
}

function WhiteLabelServices_SemanticKeyToConfigOptionIndex($semanticKey) {
    $keys = WhiteLabelServices_ModuleSemanticConfigKeys();
    $idx = array_search($semanticKey, $keys, true);
    return $idx === false ? null : ($idx + 1);
}

function WhiteLabelServices_ConfigOptionFieldName($index) {
    return 'configoption' . (int) $index;
}

function WhiteLabelServices_GetRawConfigOptionNFromSource($source, $n) {
    $field = WhiteLabelServices_ConfigOptionFieldName($n);
    if (is_array($source)) {
        return array_key_exists($field, $source) ? $source[$field] : null;
    }
    if (is_object($source) && isset($source->{$field})) {
        return $source->{$field};
    }
    return null;
}

function WhiteLabelServices_GetParamModuleOptionBySemantic($params, $semanticKey, $default = '') {
    if (!is_array($params)) {
        return $default;
    }
    $n = WhiteLabelServices_SemanticKeyToConfigOptionIndex($semanticKey);
    if ($n === null) {
        return $default;
    }
    $field = WhiteLabelServices_ConfigOptionFieldName($n);
    return array_key_exists($field, $params) ? $params[$field] : $default;
}

function WhiteLabelServices_GetProductModuleOptionBySemantic($product, $semanticKey, $default = '') {
    $n = WhiteLabelServices_SemanticKeyToConfigOptionIndex($semanticKey);
    if ($n === null) {
        return $default;
    }
    $field = WhiteLabelServices_ConfigOptionFieldName($n);
    if (is_object($product) && isset($product->{$field})) {
        return $product->{$field};
    }
    if (is_array($product) && array_key_exists($field, $product)) {
        return $product[$field];
    }
    return $default;
}

function WhiteLabelServices_IsValidStoredFormConfigJsonString($jsonString) {
    if (!is_string($jsonString) || trim($jsonString) === '') {
        return false;
    }
    $decoded = json_decode($jsonString, true);
    if (!is_array($decoded) || $decoded === array()) {
        return false;
    }
    $first = reset($decoded);
    if (!is_array($first)) {
        return false;
    }
    return isset($first['id'], $first['type'], $first['title']);
}

function WhiteLabelServices_ResolveWlsApiProductRaw($source) {
    $sem = WhiteLabelServices_GetProductModuleOptionBySemantic($source, 'wls_api_product', '');
    $sem = trim((string) $sem);
    if ($sem !== '' && WhiteLabelServices_ExtractProductId($sem)) {
        return $sem;
    }
    for ($i = 1; $i <= 24; $i++) {
        $v = WhiteLabelServices_GetRawConfigOptionNFromSource($source, $i);
        if ($v === null || $v === '') {
            continue;
        }
        $s = trim((string) $v);
        if ($s !== '' && WhiteLabelServices_ExtractProductId($s)) {
            return $s;
        }
    }
    return '';
}

function WhiteLabelServices_ResolveFormConfigJsonString($source) {
    $try = array();
    $sem = WhiteLabelServices_GetProductModuleOptionBySemantic($source, 'wls_form_config', '');
    $sem = is_string($sem) ? trim($sem) : '';
    if ($sem !== '') {
        $try[] = $sem;
    }
    $legacy8 = WhiteLabelServices_GetRawConfigOptionNFromSource($source, 8);
    if (is_string($legacy8) && trim($legacy8) !== '') {
        $try[] = trim($legacy8);
    }
    foreach ($try as $s) {
        if (WhiteLabelServices_IsValidStoredFormConfigJsonString($s)) {
            return $s;
        }
    }
    for ($i = 1; $i <= 24; $i++) {
        $s = WhiteLabelServices_GetRawConfigOptionNFromSource($source, $i);
        if (!is_string($s) || trim($s) === '') {
            continue;
        }
        $t = trim($s);
        if ($t === '' || $t[0] !== '{') {
            continue;
        }
        if (WhiteLabelServices_IsValidStoredFormConfigJsonString($t)) {
            return $t;
        }
    }
    return '';
}

function WhiteLabelServices_DecodeFormConfigFromSource($source) {
    $s = WhiteLabelServices_ResolveFormConfigJsonString($source);
    if ($s === '') {
        return null;
    }
    $d = json_decode($s, true);
    return is_array($d) ? $d : null;
}

function WhiteLabelServices_ResolvePromoCodeFromParams($params) {
    if (!is_array($params)) {
        return '';
    }
    $p = trim((string) WhiteLabelServices_GetParamModuleOptionBySemantic($params, 'wls_promo_code', ''));
    if ($p !== '') {
        return $p;
    }
    $productRaw = WhiteLabelServices_ResolveWlsApiProductRaw($params);
    $formStr = WhiteLabelServices_ResolveFormConfigJsonString($params);
    for ($i = 1; $i <= 24; $i++) {
        $v = WhiteLabelServices_GetRawConfigOptionNFromSource($params, $i);
        if ($v === null || $v === '') {
            continue;
        }
        $t = trim((string) $v);
        if ($t === '' || strcasecmp($t, 'on') === 0) {
            continue;
        }
        if ($productRaw !== '' && $t === $productRaw) {
            continue;
        }
        if ($formStr !== '' && $t === $formStr) {
            continue;
        }
        if (WhiteLabelServices_ExtractProductId($t)) {
            continue;
        }
        if (strlen($t) > 1 && $t[0] === '{') {
            continue;
        }
        if (strlen($t) <= 128 && strpos($t, "\n") === false) {
            return $t;
        }
    }
    return '';
}

function WhiteLabelServices_ProductHasWlsSetup($product) {
    $raw = WhiteLabelServices_ResolveWlsApiProductRaw($product);
    if ($raw === '' || !WhiteLabelServices_ExtractProductId($raw)) {
        return false;
    }
    return WhiteLabelServices_DecodeFormConfigFromSource($product) !== null;
}

function WhiteLabelServices_ParamsMergeProductConfigOptions($product, array $base = array()) {
    if (!$product || !is_object($product)) {
        return $base;
    }
    for ($i = 1; $i <= 24; $i++) {
        $f = WhiteLabelServices_ConfigOptionFieldName($i);
        if (isset($product->{$f})) {
            $base[$f] = $product->{$f};
        }
    }
    return $base;
}

/**
 * tblhosting ID için modül parametreleri (admin tetikleyicileri, SyncVMFromAPI, vb.).
 */
function WhiteLabelServices_BuildModuleParamsFromHostingId($serviceId) {
    $serviceId = (int) $serviceId;
    if ($serviceId < 1) {
        return null;
    }
    $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
    if (!$service) {
        return null;
    }
    $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
    if (!$product || $product->servertype !== 'WhiteLabelServices') {
        return null;
    }
    $params = WhiteLabelServices_ParamsMergeProductConfigOptions($product, [
        'serviceid' => $serviceId,
        'domain' => $service->domain,
        'billingcycle' => $service->billingcycle,
    ]);
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
    $server = Capsule::table('tblservers')->where('type', 'WhiteLabelServices')->where('active', '1')->first();
    if ($server) {
        $params['serverid'] = $server->id;
        $params['serverhostname'] = $server->hostname;
        $params['serverusername'] = $server->username;
        $params['serverpassword'] = decrypt($server->password);
    }
    return $params;
}

function WhiteLabelServices_FormConfigStorageColumnName() {
    $n = WhiteLabelServices_SemanticKeyToConfigOptionIndex('wls_form_config');
    return $n === null ? 'configoption3' : WhiteLabelServices_ConfigOptionFieldName($n);
}

// ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rma seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§enekleri - WHMCS Product Module Settings
function WhiteLabelServices_ConfigOptions() {
    try {
        // API'den ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n listesini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
        
        $options = [];
        
        if (!$server) {
            WLS_debugLog("ConfigOptions - No active server found");
        } else {
            WLS_debugLog("ConfigOptions - Server found: " . $server->hostname);
            
            $params = [
                'serverid' => $server->id,
                'serverhostname' => $server->hostname,
                'serverusername' => $server->username,
                'serverpassword' => decrypt($server->password)
            ];
            
            $token = WLSTokenManager::getToken($params);
            
            if (!$token) {
                WLS_debugLog("ConfigOptions - Token could not be retrieved");
            } else {
                WLS_debugLog("ConfigOptions - Token retrieved successfully");
                $apiBaseUrl = 'https://' . $server->hostname;
                
                // Kategorileri ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . '/api/category');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $response = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    WLS_debugLog("ConfigOptions - cURL Error: " . $curlError);
                }
                
                WLS_debugLog("ConfigOptions - Category API Response: " . substr($response, 0, 300));
                
                $categories = json_decode($response, true);
                
                if ($categories && isset($categories['categories'])) {
                    WLS_debugLog("ConfigOptions - Found " . count($categories['categories']) . " categories");
                    
                    // Sadece VPS/Cloud kategorilerini slug ile filtrele (dinamik)
                    $allowedSlugs = ['clouds', 'vps', 'cloud', 'cloud-server', 'cloud-vps'];
                    
                    foreach ($categories['categories'] as $cat) {
                        $catId = $cat['id'];
                        $catName = $cat['name'];
                        $catSlug = $cat['slug'] ?? '';
                        
                        // Sadece izin verilen slug'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
                        if (!in_array($catSlug, $allowedSlugs)) {
                            continue;
                        }
                        
                        // Her kategori iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nleri ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
                        $ch2 = curl_init();
                        curl_setopt($ch2, CURLOPT_URL, $apiBaseUrl . '/api/category/' . $catId . '/product');
                        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
                        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
                        $prodResponse = curl_exec($ch2);
                        curl_close($ch2);
                        
                        $prodData = json_decode($prodResponse, true);
                        
                        // FarklÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± API yanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±t formatlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± kontrol et
                        $products = [];
                        if ($prodData && isset($prodData['products'])) {
                            $products = $prodData['products'];
                        } elseif ($prodData && isset($prodData['category']['products'])) {
                            $products = $prodData['category']['products'];
                        } elseif ($prodData && isset($prodData['items'])) {
                            $products = $prodData['items'];
                        }
                        
                        foreach ($products as $prod) {
                            if (isset($prod['id']) && isset($prod['name'])) {
                                $options[$prod['id']] = $prod['name'] . ' (' . $prod['id'] . ')';
                            }
                        }
                    }
                } else {
                    WLS_debugLog("ConfigOptions - No categories found in response");
                }
            }
        }
        
        WLS_debugLog("ConfigOptions - Total products found: " . count($options));
        
        // Dil ayarlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± - WHMCS admin dili al
        $adminLang = $_SESSION['adminlang'] ?? 'english';
        
        // Multilingual metinler
        $lang = [
            'english' => [
                'select_product' => 'API Product',
                'select_placeholder' => '-- Select Product --',
                'no_server' => '-- Configure server connection first --',
                'product_desc' => 'Select the API product that this WHMCS product will provision',
                'promo_code' => 'Promo Code',
                'promo_desc' => 'Promotion code for this product (leave empty to use global setting)',
                'form_config' => 'Form Config',
                'form_desc' => 'API form configuration (auto-filled)',
            ],
            'turkish' => [
                'select_product' => 'API ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼',
                'select_placeholder' => '-- ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n SeÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in --',
                'no_server' => '-- ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“nce sunucu baÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸lantÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±n --',
                'product_desc' => 'Bu WHMCS ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸turacaÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± API ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in',
                'promo_code' => 'Promosyon Kodu',
                'promo_desc' => 'Bu ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in kullanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lacak promosyon kodu (boÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ bÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rakÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rsa genel ayar kullanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r)',
                'form_config' => 'Form YapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rmasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±',
                'form_desc' => 'API form yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rmasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± (otomatik doldurulur)',
            ],
        ];
        
        // VarsayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lan dil English
        $t = $lang['english'];
        if (isset($lang[$adminLang])) {
            $t = $lang[$adminLang];
        }
        
        // Options boÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸sa varsayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lan mesaj
        if (empty($options)) {
            $options = ['' => $t['no_server']];
        } else {
            $options = ['' => $t['select_placeholder']] + $options;
        }

        // UYARI: Asagidaki alan sirasi WhiteLabelServices_ModuleSemanticConfigKeys() ile ayni kalmali (configoption1,2,3).
        
        return [
            $t['select_product'] => [
                'Type' => 'dropdown',
                'Options' => $options,
                'Description' => $t['product_desc'],
            ],
            $t['promo_code'] => [
                'Type' => 'text',
                'Size' => 25,
                'Default' => '',
                'Description' => $t['promo_desc'],
            ],
            $t['form_config'] => [
                'Type' => 'textarea',
                'Rows' => 3,
                'Cols' => 50,
                'Default' => '',
                'Description' => $t['form_desc'],
            ],
        ];
        
    } catch (Exception $e) {
        WLS_debugLog("ConfigOptions Error: " . $e->getMessage());
        return [
            'ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n SeÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§imi' => [
                'Type' => 'dropdown',
                'Options' => ['' => 'Hata: ' . $e->getMessage()],
                'Description' => 'WLS API\'den gelen ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§imi',
            ],
        ];
    }
}

/**
 * GET /api/order/{wlsProductId} — ürün sipariş formu (OS Template items, custom alanları).
 */
function WhiteLabelServices_FetchOrderProductConfig($apiBaseUrl, $token, $wlsProductId) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($apiBaseUrl, '/') . '/api/order/' . $wlsProductId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $configResponse = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        WLS_debugLog('FetchOrderProductConfig HTTP ' . $httpCode . ' ' . substr((string) $configResponse, 0, 300));
        return null;
    }
    $decoded = json_decode($configResponse, true);
    return is_array($decoded) ? $decoded : null;
}

function WhiteLabelServices_IsOsTemplateOrderForm($form) {
    if (!is_array($form)) {
        return false;
    }
    $meta = isset($form['metadata']) && is_array($form['metadata']) ? $form['metadata'] : [];
    if (!empty($meta['key']) && strtolower((string) $meta['key']) === 'template') {
        return true;
    }
    if (!empty($meta['variable']) && strtolower((string) $meta['variable']) === 'os') {
        return true;
    }
    if (!empty($meta['category']) && strtolower((string) $meta['category']) === 'template') {
        return true;
    }
    $title = isset($form['title']) ? strtolower((string) $form['title']) : '';
    return ($title !== '' && strpos($title, 'os') !== false && strpos($title, 'template') !== false);
}

function WhiteLabelServices_NormalizeOsLabel($s) {
    return strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
}


function WhiteLabelServices_ResolveOsTemplateItemIdFromForm($form, $customerOsLabel) {
    $want = WhiteLabelServices_NormalizeOsLabel($customerOsLabel);
    if ($want === '') {
        return null;
    }
    if (!empty($form['items']) && is_array($form['items'])) {
        foreach ($form['items'] as $item) {
            if (!is_array($item) || !isset($item['id'])) {
                continue;
            }
            $t = WhiteLabelServices_NormalizeOsLabel(isset($item['title']) ? $item['title'] : '');
            if ($t !== '' && $t === $want) {
                return (int) $item['id'];
            }
        }
    }
    if (!empty($form['metadata']['items']) && is_array($form['metadata']['items'])) {
        foreach ($form['metadata']['items'] as $mid => $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = WhiteLabelServices_NormalizeOsLabel(isset($row['name']) ? $row['name'] : '');
            if ($name !== '' && $name === $want) {
                return (int) $mid;
            }
        }
    }
    if (!empty($form['textvalue']) && is_array($form['textvalue'])) {
        foreach ($form['textvalue'] as $tid => $label) {
            if (WhiteLabelServices_NormalizeOsLabel($label) === $want) {
                return (int) $tid;
            }
        }
    }
    if (!empty($form['items']) && is_array($form['items'])) {
        foreach ($form['items'] as $item) {
            if (!is_array($item) || !isset($item['id'], $item['title'])) {
                continue;
            }
            $t = WhiteLabelServices_NormalizeOsLabel($item['title']);
            if ($t !== '' && (strpos($want, $t) !== false || strpos($t, $want) !== false)) {
                return (int) $item['id'];
            }
        }
    }
    return null;
}


/**
 * GET /api/order form satirini generateFieldName() icin sarar (metadata / variable).
 */
function WhiteLabelServices_generateFieldNameFromApiForm($form) {
    if (!is_array($form)) {
        return '';
    }
    $sim = array(
        'id' => isset($form['id']) ? $form['id'] : 0,
        'type' => isset($form['type']) ? $form['type'] : '',
        'metadata' => array(),
    );
    if (isset($form['metadata']) && is_array($form['metadata'])) {
        $sim['metadata'] = $form['metadata'];
    } elseif (!empty($form['variable'])) {
        $sim['metadata'] = array('variable' => $form['variable']);
    }
    return WhiteLabelServices_generateFieldName($sim);
}

/**
 * tblproducts form JSON satirini (WhiteLabelServices_FormConfigStorageColumnName / legacy tarama) API formu ile ayni yapida sarar.
 */
function WhiteLabelServices_StoredFormConfigToApiFormShape($row) {
    if (!is_array($row)) {
        return array('id' => 0, 'type' => '', 'title' => '', 'metadata' => array());
    }
    $meta = isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : array();
    if (!empty($row['variable'])) {
        $meta['variable'] = $row['variable'];
    }
    return array(
        'id' => isset($row['id']) ? $row['id'] : 0,
        'type' => isset($row['type']) ? $row['type'] : '',
        'title' => isset($row['title']) ? $row['title'] : '',
        'metadata' => $meta,
    );
}

function WhiteLabelServices_ConfigOptionKeyExactMatchOrderForm($form, $optionName) {
    $opt = WhiteLabelServices_NormalizeOsLabel($optionName);
    $title = WhiteLabelServices_NormalizeOsLabel(isset($form['title']) ? $form['title'] : '');
    return ($opt !== '' && $title !== '' && $opt === $title);
}

/**
 * API basligi ile WHMCS optionname farkli olabilir (or. "Snapshots" vs "Additional Snapshots"); sync sonrasi kopya custom field da configurable ile tutarsiz olabilir.
 */
function WhiteLabelServices_ConfigOptionKeyFuzzyMatchOrderForm($form, $optionName) {
    if (WhiteLabelServices_ConfigOptionKeyExactMatchOrderForm($form, $optionName)) {
        return false;
    }
    $opt = WhiteLabelServices_NormalizeOsLabel($optionName);
    if ($opt === '') {
        return false;
    }
    $meta = isset($form['metadata']) && is_array($form['metadata']) ? $form['metadata'] : array();
    $variable = isset($meta['variable']) ? strtolower((string) $meta['variable']) : '';

    $varHints = array(
        'ipamlimit' => array('ip address', 'additional ip', ' ip '),
        'additional_storage' => array('storage'),
        'backuplimit' => array('backup'),
        'snapshot_limit' => array('snapshot'),
    );
    if ($variable !== '' && isset($varHints[$variable])) {
        foreach ($varHints[$variable] as $hint) {
            if (strpos($opt, $hint) !== false) {
                return true;
            }
        }
    }

    $title = WhiteLabelServices_NormalizeOsLabel(isset($form['title']) ? $form['title'] : '');
    if ($title !== '') {
        $short = strlen($opt) < strlen($title) ? $opt : $title;
        $long = strlen($opt) < strlen($title) ? $title : $opt;
        if (strlen($short) >= 5 && strpos($long, $short) !== false) {
            return true;
        }
    }
    return false;
}

function WhiteLabelServices_ConfigOptionKeyMatchesOrderForm($form, $optionName) {
    return WhiteLabelServices_ConfigOptionKeyExactMatchOrderForm($form, $optionName)
        || WhiteLabelServices_ConfigOptionKeyFuzzyMatchOrderForm($form, $optionName);
}

/**
 * Slider/qty: once configurable options (faturalanan deger), sonra custom fields. Tam baslik eslesmesi bulaniktan once.
 */
function WhiteLabelServices_CollectOrderFormRawValueCandidates($form, $params) {
    $out = array();
    if (!is_array($params)) {
        return $out;
    }
    $exactVals = array();
    $fuzzyVals = array();
    if (!empty($params['configoptions']) && is_array($params['configoptions'])) {
        foreach ($params['configoptions'] as $optName => $val) {
            if ($val === '' || $val === null || $val === 'None') {
                continue;
            }
            $optStr = (string) $optName;
            if (WhiteLabelServices_ConfigOptionKeyExactMatchOrderForm($form, $optStr)) {
                $exactVals[] = $val;
            } elseif (WhiteLabelServices_ConfigOptionKeyFuzzyMatchOrderForm($form, $optStr)) {
                $fuzzyVals[] = $val;
            }
        }
        $out = array_merge($exactVals, $fuzzyVals);
    }
    $fieldKey = WhiteLabelServices_generateFieldNameFromApiForm($form);
    if ($fieldKey !== '' && !empty($params['customfields']) && is_array($params['customfields'])) {
        if (isset($params['customfields'][$fieldKey]) && $params['customfields'][$fieldKey] !== '' && $params['customfields'][$fieldKey] !== null) {
            $out[] = $params['customfields'][$fieldKey];
        }
    }
    return $out;
}

function WhiteLabelServices_ParseQuantityFromWhmcsOption($raw) {
    $raw = trim((string) $raw);
    if ($raw === '' || strcasecmp($raw, 'None') === 0) {
        return null;
    }
    if (preg_match('/x\s*(\d+)/i', $raw, $m)) {
        return (int) $m[1];
    }
    if (preg_match('/^(\d+)$/', $raw, $m)) {
        return (int) $m[1];
    }
    if (preg_match('/(\d+)/', $raw, $m)) {
        return (int) $m[1];
    }
    return null;
}

/**
 * Slider/qty form icin API item id (firstItemId veya tek item).
 */
function WhiteLabelServices_GetOrderFormLineItemId($form) {
    if (!is_array($form)) {
        return '';
    }
    if (isset($form['firstItemId']) && $form['firstItemId'] !== '' && $form['firstItemId'] !== null) {
        return (string) $form['firstItemId'];
    }
    if (!empty($form['items']) && is_array($form['items'])) {
        $it = reset($form['items']);
        if (is_array($it) && isset($it['id'])) {
            return (string) $it['id'];
        }
    }
    return '';
}

function WhiteLabelServices_FormValueQtyForItem($form, $itemIdStr) {
    if ($itemIdStr === '' || empty($form['value']) || !is_array($form['value'])) {
        return null;
    }
    if (isset($form['value'][$itemIdStr])) {
        return (int) $form['value'][$itemIdStr];
    }
    $intKey = (int) $itemIdStr;
    if (isset($form['value'][$intKey])) {
        return (int) $form['value'][$intKey];
    }
    $first = reset($form['value']);
    return is_numeric($first) ? (int) $first : null;
}

function WhiteLabelServices_ClampOrderFormQty($form, $qty) {
    $cfg = isset($form['config']) && is_array($form['config']) ? $form['config'] : array();
    $min = isset($cfg['minvalue']) && $cfg['minvalue'] !== '' ? (int) $cfg['minvalue'] : null;
    $max = isset($cfg['maxvalue']) && $cfg['maxvalue'] !== '' ? (int) $cfg['maxvalue'] : null;
    if ($min !== null) {
        $qty = max($min, $qty);
    }
    if ($max !== null) {
        $qty = min($max, $qty);
    }
    return $qty;
}

/**
 * Slider/qty: degerler custom field ve configurable option isimleriyle bulunur (sabit configoption index kullanilmaz).
 */
function WhiteLabelServices_GetQtyForSliderOrQtyForm($form, $params) {
    foreach (WhiteLabelServices_CollectOrderFormRawValueCandidates($form, $params) as $raw) {
        $q = WhiteLabelServices_ParseQuantityFromWhmcsOption($raw);
        if ($q !== null) {
            return $q;
        }
    }
    return null;
}

/**
 * @param array|null $productConfig GET /api/order/{id} yaniti (OS form basligi ile configoptions eslemesi icin)
 */
function WhiteLabelServices_GetCustomerOsSelectionLabelForOrder($params, $productConfig = null) {
    if (!empty($params['customfields']) && is_array($params['customfields']) && isset($params['customfields']['os'])) {
        $v = trim((string) $params['customfields']['os']);
        if ($v !== '') {
            return $v;
        }
    }
    if ($productConfig && isset($productConfig['product']['config']['forms']) && is_array($productConfig['product']['config']['forms'])
        && !empty($params['configoptions']) && is_array($params['configoptions'])) {
        foreach ($productConfig['product']['config']['forms'] as $f) {
            if (!WhiteLabelServices_IsOsTemplateOrderForm($f)) {
                continue;
            }
            foreach ($params['configoptions'] as $optName => $optVal) {
                if ($optVal === '' || $optVal === null || $optVal === 'None') {
                    continue;
                }
                if (WhiteLabelServices_ConfigOptionKeyMatchesOrderForm($f, (string) $optName)) {
                    return is_string($optVal) ? trim($optVal) : (string) $optVal;
                }
            }
        }
    }
    $customerOs = '';
    if (!empty($params['customfields']) && is_array($params['customfields'])) {
        foreach ($params['customfields'] as $fname => $fval) {
            if ($fval === '' || $fval === null) {
                continue;
            }
            $fn = strtolower((string) $fname);
            if (strpos($fn, 'os') !== false || strpos($fn, 'template') !== false || strpos($fn, 'operating') !== false) {
                $customerOs = trim((string) $fval);
                break;
            }
        }
    }
    if ($customerOs === '' && !empty($params['configoptions']) && is_array($params['configoptions'])) {
        foreach ($params['configoptions'] as $optName => $optVal) {
            if ($optVal === '' || $optVal === null || $optVal === 'None') {
                continue;
            }
            $on = strtolower((string) $optName);
            if (strpos($on, 'os') !== false || strpos($on, 'template') !== false || strpos($on, 'operating') !== false) {
                $customerOs = is_string($optVal) ? trim($optVal) : (string) $optVal;
                break;
            }
        }
    }
    return $customerOs;
}


function WhiteLabelServices_MergeOrderCustomFromOrderConfig(&$orderPayload, $productConfig, $params) {
    if (!isset($productConfig['product']['config']['forms']) || !is_array($productConfig['product']['config']['forms'])) {
        return;
    }
    $customerOs = WhiteLabelServices_GetCustomerOsSelectionLabelForOrder($params, $productConfig);
    foreach ($productConfig['product']['config']['forms'] as $form) {
        $formId = isset($form['id']) ? (string) $form['id'] : '';
        if ($formId === '') {
            continue;
        }
        $formType = isset($form['type']) ? $form['type'] : '';

        if (WhiteLabelServices_IsOsTemplateOrderForm($form)) {
            $itemId = null;
            if ($customerOs !== '') {
                $itemId = WhiteLabelServices_ResolveOsTemplateItemIdFromForm($form, $customerOs);
            }
            if ($itemId === null && isset($form['firstItemId'])) {
                $itemId = (int) $form['firstItemId'];
            }
            if ($itemId === null && !empty($form['items']) && is_array($form['items'])) {
                $fi = reset($form['items']);
                if (is_array($fi) && isset($fi['id'])) {
                    $itemId = (int) $fi['id'];
                }
            }
            if ($itemId !== null) {
                $orderPayload['custom'][$formId] = array((string) $itemId => 1);
                WLS_debugLog('Order custom OS form ' . $formId . ' item ' . $itemId . ' (customer: "' . $customerOs . '")');
            }
            continue;
        }

        if ($formType === 'servicegroupselector') {
            if (isset($form['firstItemId'])) {
                $orderPayload['custom'][$formId] = array((string) $form['firstItemId'] => 'main');
                WLS_debugLog('CreateAccount - servicegroupselector: ' . $formId . '[' . $form['firstItemId'] . ']=main');
            }
        } elseif (in_array($formType, array('select', 'sshkeyselect'), true)) {
            if (isset($form['firstItemId'])) {
                $orderPayload['custom'][$formId] = array((string) $form['firstItemId'] => 1);
                WLS_debugLog('CreateAccount - select: ' . $formId . '[' . $form['firstItemId'] . ']=1');
            } elseif (!empty($form['items']) && is_array($form['items'])) {
                $firstItem = reset($form['items']);
                if (is_array($firstItem) && isset($firstItem['id'])) {
                    $orderPayload['custom'][$formId] = array((string) $firstItem['id'] => 1);
                    WLS_debugLog('CreateAccount - select from items: ' . $formId . '[' . $firstItem['id'] . ']=1');
                }
            }
        } elseif (in_array($formType, array('slider', 'qty'), true)) {
            $itemIdStr = WhiteLabelServices_GetOrderFormLineItemId($form);
            if ($itemIdStr === '') {
                continue;
            }
            $qty = WhiteLabelServices_GetQtyForSliderOrQtyForm($form, $params);
            if ($qty === null) {
                $qty = WhiteLabelServices_FormValueQtyForItem($form, $itemIdStr);
            }
            if ($qty === null) {
                continue;
            }
            $qty = WhiteLabelServices_ClampOrderFormQty($form, $qty);
            $orderPayload['custom'][$formId] = array($itemIdStr => $qty);
            WLS_debugLog('Order custom ' . $formType . ' form ' . $formId . ' [' . $itemIdStr . ']=' . $qty);
        }
    }
}

/**
 * Provision a new VPS service via the WLS API
 * Called when Create button is clicked in WHMCS admin
 * 
 * @param array $params WHMCS service parameters
 * @return string "success" or error message
 */
function WhiteLabelServices_CreateAccount(array $params) {
    try {
        WLS_debugLog("CreateAccount - Starting provisioning for service: " . $params['serviceid']);
        
        // Get API token
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            throw new Exception("Failed to obtain API token");
        }
        
        $wlsProductRaw = WhiteLabelServices_ResolveWlsApiProductRaw($params);
        $wlsProductId = WhiteLabelServices_ExtractProductId($wlsProductRaw);
        if (!$wlsProductId) {
            throw new Exception('Invalid WLS product selection (no API product in module options): ' . $wlsProductRaw);
        }
        
        WLS_debugLog("CreateAccount - WLS Product ID: " . $wlsProductId);
        
        // Build API base URL
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        
        // Get billing cycle in API format
        $cycleMap = [
            'Monthly' => 'm',
            'Quarterly' => 'q',
            'Semi-Annually' => 's',
            'Annually' => 'a',
            'Biennially' => 'b',
            'Triennially' => 't',
            'Free Account' => 'm',
            'One Time' => 'm',
        ];
        $cycle = $cycleMap[$params['billingcycle']] ?? 'm';
        
        // Build order payload (product_id + custom OS item id from GET /order response — updates.MD)
        $domainVal = $params['domain'] ?: 'vps-' . $params['serviceid'] . '-' . time();
        $orderPayload = [
            'product_id' => (int) $wlsProductId,
            'domain' => $domainVal,
            'hostname' => $domainVal,
            'cycle' => $cycle,
            'pay_method' => '120',
            'custom' => [],
        ];
        
        $promo = WhiteLabelServices_ResolvePromoCodeFromParams($params);
        if ($promo !== '') {
            $orderPayload['promocode'] = $promo;
        }
        
        $productConfig = WhiteLabelServices_FetchOrderProductConfig($apiBaseUrl, $token, $wlsProductId);
        if (!$productConfig) {
            throw new Exception('Could not load product order configuration from API (GET /api/order/' . $wlsProductId . ')');
        }
        if (isset($productConfig['product']['config']['forms'])) {
            WLS_debugLog('CreateAccount - Found ' . count($productConfig['product']['config']['forms']) . ' forms from API');
        }
        WhiteLabelServices_MergeOrderCustomFromOrderConfig($orderPayload, $productConfig, $params);
        
        WLS_debugLog("CreateAccount - Order payload: " . json_encode($orderPayload));
        
        // Make API request with JSON body (not form data)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . '/api/order/' . $wlsProductId);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderPayload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("cURL Error: " . $curlError);
        }
        
        WLS_debugLog("CreateAccount - API Response (HTTP $httpCode): " . substr($response, 0, 500));
        
        $result = json_decode($response, true);
        
        // Check for API error
        if (isset($result['error'])) {
            $errorMsg = is_array($result['error']) ? json_encode($result['error']) : $result['error'];
            throw new Exception("API Error: " . $errorMsg);
        }
        
        if (!$result || !isset($result['items'])) {
            throw new Exception("Invalid API response - missing items");
        }
        
        // Extract service ID from response - items can be array or object
        $wlsServiceId = null;
        $items = $result['items'];
        
        // If items is an object (single item), convert to array format
        if (isset($items['id'])) {
            $items = [$items];
        }
        
        // Find Hosting type item
        foreach ($items as $item) {
            if (isset($item['type']) && $item['type'] === 'Hosting') {
                $wlsServiceId = $item['id'];
                break;
            }
        }
        
        // Fallback to first item
        if (!$wlsServiceId && !empty($items)) {
            $firstItem = reset($items);
            $wlsServiceId = $firstItem['id'] ?? null;
        }
        
        if (!$wlsServiceId) {
            throw new Exception("Could not extract service ID from API response");
        }
        
        WLS_debugLog("CreateAccount - Order created! Order #" . ($result['order_num'] ?? 'N/A') . ", Service ID: " . $wlsServiceId);

        // Service label'i WHMCS HİZMET ID'si ile güncelle
        try {
            if (!empty($params['serviceid'])) {
                $labelValue = (string) $params['serviceid']; // WHMCS service ID
                $labelUrl   = rtrim($apiBaseUrl, '/') . '/api/service/' . $wlsServiceId . '/label?label=' . urlencode($labelValue);

                // Hem query, hem body'de label gönder
                $labelResponse = WhiteLabelServices_APIRequest($labelUrl, $token, 'POST', ['label' => $labelValue]);
                WLS_debugLog("CreateAccount - Service label set to WHMCS service ID: " . $labelValue);
            } else {
                WLS_debugLog("CreateAccount - serviceid not found in params, skipping label update");
            }
        } catch (Exception $e) {
            WLS_debugLog("CreateAccount - Failed to set service label: " . $e->getMessage());
        }
        
        // Save WLS details to mod_wls_vps table
        Capsule::table('mod_wls_vps')->updateOrInsert(
            ['id' => $params['serviceid']],
            [
                'wls_service_id' => $wlsServiceId,
                'order_number' => $result['order_num'] ?? '',
                'invoice_id' => $result['invoice_id'] ?? '',
                'status' => 'Pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        
        // Update WHMCS service with username
        Capsule::table('tblhosting')
            ->where('id', $params['serviceid'])
            ->update([
                'username' => 'wls_' . $wlsServiceId,
            ]);
        
        // Schedule VM status check
        WhiteLabelServices_ScheduleVMCheck($params, $wlsServiceId, $token, $apiBaseUrl);
        
        // Update order status to Active if order exists
        try {
            $service = Capsule::table('tblhosting')
                ->where('id', $params['serviceid'])
                ->first();
            
            if ($service && $service->orderid) {
                Capsule::table('tblorders')
                    ->where('id', $service->orderid)
                    ->update(['status' => 'Active']);
                WLS_debugLog("CreateAccount - Order #" . $service->orderid . " marked as Active");
            }
        } catch (Exception $e) {
            // Order gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncellemesi opsiyonel - hata logla ama iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lemi durma
            WLS_debugLog("CreateAccount - Order status update failed: " . $e->getMessage());
        }
        
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("CreateAccount Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

/**
 * Schedule VM status check - polls service until active, then gets VM details
 */
function WhiteLabelServices_ScheduleVMCheck($params, $wlsServiceId, $token, $apiBaseUrl) {
    // Add to queue for async processing (will be handled by cron)
    try {
        WhiteLabelServices_AddToQueue('check_vm_status', [
            'service_id' => $params['serviceid'],
            'wls_service_id' => $wlsServiceId,
            'api_base_url' => $apiBaseUrl,
        ], 1); // Priority 1 = high
        
        WLS_debugLog("- Scheduled VM status check for service: " . $wlsServiceId);
    } catch (Exception $e) {
        WLS_debugLog("- Failed to schedule VM check: " . $e->getMessage());
    }
}

/**
 * Process queued VM status checks
 * Full workflow:
 * 1. Check service status - wait until Active
 * 2. Get VM list and VM ID
 * 3. Get VM details - wait until built=true and status=running
 * 4. Sync username, password, IP to WHMCS
 */
function WhiteLabelServices_ProcessVMStatusCheck($params, $queueData) {
    try {
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            throw new Exception("Failed to obtain API token");
        }
        
        $wlsServiceId = $queueData['wls_service_id'];
        $whmcsServiceId = $queueData['service_id'];
        $apiBaseUrl = $queueData['api_base_url'] ?? WhiteLabelServices_getApiBaseUrl($params);
        $retryCount = ($queueData['retry_count'] ?? 0) + 1;
        $stage = $queueData['stage'] ?? 'check_service';
        
        WLS_debugLog("VMCheck - Stage: $stage, Retry: $retryCount, WLS Service: $wlsServiceId");
        
        // Max 60 retries (approx 1 hour if run every minute)
        if ($retryCount >= 60) {
            WLS_debugLog("VMCheck - FAILED after 60 attempts for service: $wlsServiceId", true);
            sendAdminNotification('system', 'WLS Service Provisioning Failed', 
                "Service ID $wlsServiceId did not complete provisioning after 60 attempts.\n" .
                "WHMCS Service ID: $whmcsServiceId\n" .
                "Stage: $stage"
            );
            return "Provisioning failed after $retryCount attempts. Please check provider panel.";
        }
        
        // STAGE 1: Check service status
        if ($stage === 'check_service') {
            $serviceData = WhiteLabelServices_ApiRequestWithTokenRefresh($apiBaseUrl . '/api/service/' . $wlsServiceId, $token, $params);
            
            if (!$serviceData || !isset($serviceData['service'])) {
                throw new \Exception("Invalid service response from API");
            }
            
            $service = $serviceData['service'];
            $status = $service['status'] ?? 'Unknown';
            
            WLS_debugLog("VMCheck - Service status: $status");
            
            if ($status !== 'Active') {
                // Update queue data for next attempt
                if (isset($params['queueid'])) {
                    WLSTokenManager::addQueueData($params['queueid'], array_merge($queueData, [
                        'retry_count' => $retryCount,
                        'stage' => 'check_service'
                    ]));
                }
                return WLS_RESULT_RESCHEDULED;
            }
            
            // Service is Active - activate in WHMCS and move to next stage
            Capsule::table('tblhosting')
                ->where('id', $whmcsServiceId)
                ->update([
                    'domainstatus' => 'Active',
                    'domain' => $service['domain'] ?? '',
                    'nextduedate' => $service['next_due'] ?? null,
                ]);
            
            WLS_debugLog("VMCheck - Service Active, WHMCS service $whmcsServiceId activated");
            
            // Move to next stage immediately
            $queueData['stage'] = 'get_vm_id';
            $queueData['retry_count'] = 0;
        }
        
        // STAGE 2: Get VM ID from VMs list
        if ($stage === 'get_vm_id' || $queueData['stage'] === 'get_vm_id') {
            $vmsData = WhiteLabelServices_ApiRequestWithTokenRefresh($apiBaseUrl . '/api/service/' . $wlsServiceId . '/vms', $token, $params);
            
            if (!$vmsData || !isset($vmsData['vms']) || empty($vmsData['vms'])) {
                // VM not yet created, update queue and exit
                if (isset($params['queueid'])) {
                    WLSTokenManager::addQueueData($params['queueid'], array_merge($queueData, [
                        'retry_count' => $retryCount,
                        'stage' => 'get_vm_id'
                    ]));
                }
                return WLS_RESULT_RESCHEDULED;
            }
            
            // Get first VM's ID
            $firstVm = reset($vmsData['vms']);
            $vmId = $firstVm['vmid'] ?? $firstVm['id'] ?? null;
            
            if (!$vmId) {
                throw new Exception("Could not extract VM ID from VMs response");
            }
            
            WLS_debugLog("VMCheck - Found VM ID: $vmId");
            
            // Move to next stage
            $queueData['wls_vm_id'] = $vmId;
            $queueData['stage'] = 'check_vm_details';
            $queueData['retry_count'] = 0;
        }
        
        // STAGE 3: Get VM details and wait for built + running
        if ($queueData['stage'] === 'check_vm_details') {
            $vmId = $queueData['wls_vm_id'] ?? null;
            
            // If VM ID is missing, try to discover it from VMs list
            if (!$vmId) {
                WLS_debugLog("VMCheck - No VM ID in queue, fetching from VMs API...");
                
                $vmsData = WhiteLabelServices_ApiRequestWithTokenRefresh($apiBaseUrl . '/api/service/' . $wlsServiceId . '/vms', $token, $params);
                
                if ($vmsData && isset($vmsData['vms']) && !empty($vmsData['vms'])) {
                    // Get first VM's ID
                    $firstVmKey = array_key_first($vmsData['vms']);
                    $firstVm = $vmsData['vms'][$firstVmKey];
                    $vmId = $firstVm['id'] ?? $firstVmKey ?? null;
                    
                    if ($vmId) {
                        WLS_debugLog("VMCheck - Discovered VM ID: $vmId, saving to database");
                        
                        // Save VM ID to database
                        Capsule::table('mod_wls_vps')
                            ->where('id', $whmcsServiceId)
                            ->update(['wls_vm_id' => $vmId]);
                        
                        // Update queue data
                        $queueData['wls_vm_id'] = $vmId;
                    }
                }
                
                if (!$vmId) {
                    WLS_debugLog("VMCheck - Could not discover VM ID, waiting...");
                    if (isset($params['queueid'])) {
                        WLSTokenManager::addQueueData($params['queueid'], array_merge($queueData, [
                            'retry_count' => $retryCount,
                            'stage' => 'get_vm_id'
                        ]));
                    }
                    return WLS_RESULT_RESCHEDULED;
                }
            }
            
            $apiUrl = $apiBaseUrl . '/api/service/' . $wlsServiceId . '/vms/' . $vmId;
            WLS_debugLog("VMCheck - Calling API URL: " . $apiUrl);
            
            $vmData = WhiteLabelServices_ApiRequestWithTokenRefresh($apiUrl, $token, $params);
            
            WLS_debugLog("VMCheck - API Response: " . substr(json_encode($vmData), 0, 500));
            
            // API returns vms[vmId] not vm - handle both formats
            $vm = null;
            if (isset($vmData['vm'])) {
                $vm = $vmData['vm'];
            } elseif (isset($vmData['vms'][$vmId])) {
                $vm = $vmData['vms'][$vmId];
            } elseif (isset($vmData['vms']) && is_array($vmData['vms'])) {
                // Get first VM if exists
                $vm = reset($vmData['vms']);
            }
            
            if (!$vm) {
                if (is_array($vmData) && WhiteLabelServices_ApiPayloadIndicatesVmGone($vmData)) {
                    WLS_debugLog("VMCheck - VM gone on API; checking portal service /api/service/$wlsServiceId");
                    WhiteLabelServices_ReconcileWlsServiceFromPortal($whmcsServiceId, $wlsServiceId, $token, $apiBaseUrl);
                    $hosting = Capsule::table('tblhosting')->where('id', $whmcsServiceId)->first();
                    $ds = $hosting && isset($hosting->domainstatus) ? $hosting->domainstatus : '';
                    if ($ds === 'Terminated' || $ds === 'Cancelled') {
                        return WLS_RESULT_SUCCESS;
                    }
                    if (isset($params['queueid'])) {
                        WLSTokenManager::addQueueData($params['queueid'], array_merge($queueData, array(
                            'retry_count' => $retryCount,
                            'stage' => 'get_vm_id',
                            'wls_vm_id' => null,
                        )));
                    }
                    return WLS_RESULT_RESCHEDULED;
                }
                throw new Exception("Invalid VM details response");
            }
            
            $vmBuilt = $vm['built'] ?? false;
            $vmStatus = $vm['status'] ?? 'unknown';
            $vmLocked = $vm['locked'] ?? true;
            
            WLS_debugLog("VMCheck - VM $vmId: built=$vmBuilt, status=$vmStatus, locked=$vmLocked");
            
            // Check if VM is fully ready
            if (!$vmBuilt || $vmStatus !== 'running') {
                // VM not ready, update queue and exit
                if (isset($params['queueid'])) {
                    WLSTokenManager::addQueueData($params['queueid'], array_merge($queueData, [
                        'retry_count' => $retryCount,
                        'stage' => 'check_vm_details'
                    ]));
                }
                return WLS_RESULT_RESCHEDULED;
            }
            
            // VM is ready - extract all details
            $username = $vm['username'] ?? 'root';
            $password = $vm['password'] ?? '';
            $ipv4 = $vm['ipv4'] ?? '';
            $ipv6 = $vm['ipv6'] ?? '';
            $memory = $vm['memory'] ?? '';
            $cores = $vm['cores'] ?? '';
            $disk = $vm['disk'] ?? '';
            $templateName = $vm['template_name'] ?? '';
            $label = $vm['label'] ?? '';
            
            WLS_debugLog("VMCheck - VM Ready! IP: $ipv4, Username: $username");
            
            // Save VM details to mod_wls_vps table
            Capsule::table('mod_wls_vps')->updateOrInsert(
                ['id' => $whmcsServiceId],
                [
                    'wls_service_id' => $wlsServiceId,
                    'wls_vm_id' => $vmId,
                    'status' => 'Active',
                    'vm_status' => $vmStatus,
                    'vm_built' => true,
                    'vm_powered' => ($vmStatus === 'running'),
                    'ipv4' => $ipv4,
                    'ipv6' => $ipv6,
                    'all_ips' => json_encode(['ipv4' => $ipv4, 'ipv6' => $ipv6]),
                    'username' => $username,
                    'password' => $password,
                    'memory' => intval($memory),
                    'disk' => intval($disk),
                    'cores' => intval($cores),
                    'template' => $templateName,
                    'mac_address' => $vm['mac'] ?? '',
                    'vm_created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
            
            // Update WHMCS service with VM access info
            // Use localAPI to properly encrypt the password
            $updateResult = localAPI('UpdateClientProduct', [
                'serviceid' => $whmcsServiceId,
                'serviceusername' => $username,
                'servicepassword' => $password,
                'dedicatedip' => $ipv4,
                'assignedips' => $ipv6 ? "$ipv4\n$ipv6" : $ipv4,
            ]);
            
            if ($updateResult['result'] !== 'success') {
                WLS_debugLog("VMCheck - Failed to update service via localAPI: " . ($updateResult['message'] ?? 'Unknown error'));
                // Fallback to direct update (password won't be encrypted properly but at least data is saved)
                Capsule::table('tblhosting')
                    ->where('id', $whmcsServiceId)
                    ->update([
                        'username' => $username,
                        'dedicatedip' => $ipv4,
                        'assignedips' => $ipv6 ? "$ipv4\n$ipv6" : $ipv4,
                    ]);
            }
            
            WLS_debugLog("VMCheck - SUCCESS! WHMCS service $whmcsServiceId fully provisioned");
            
            return WLS_RESULT_SUCCESS;
        }
        
        return WLS_RESULT_SUCCESS;
        
    } catch (\Throwable $e) {
        WLS_debugLog("VMCheck ERROR (Service $whmcsServiceId): " . $e->getMessage(), true);
        
        // Save error reason to queue if possible
        if (isset($params['queueid'])) {
            Capsule::table('tblmodulequeue')
                ->where('id', $params['queueid'])
                ->update(['last_attempt_error' => $e->getMessage()]);
        }
        
        return "VM Check Error: " . $e->getMessage();
    }
}

/**
 * Ensure $params has serverid / credentials for token refresh (VMCheck cron may only pass serviceid).
 */
function WhiteLabelServices_WlsEnsureApiServerParams(&$params) {
    if (!empty($params['serverid']) && !empty($params['serverusername']) && !empty($params['serverpassword'])) {
        return true;
    }
    $server = Capsule::table('tblservers')
        ->where('type', 'WhiteLabelServices')
        ->where('active', '1')
        ->first();
    if (!$server) {
        return false;
    }
    $params['serverid'] = $server->id;
    $params['serverusername'] = $server->username;
    $params['serverpassword'] = decrypt($server->password);
    return true;
}

/**
 * Portal JSON: {"error":["unauthorized"]} or token_expired (HTTP 200 ile de gelebilir).
 */
function WhiteLabelServices_IsApiAuthErrorResponse($decoded) {
    if (!is_array($decoded) || empty($decoded['error'])) {
        return false;
    }
    $err = $decoded['error'];
    if (is_string($err)) {
        $e = strtolower($err);
        return ($e === 'unauthorized' || $e === 'token_expired');
    }
    if (!is_array($err)) {
        return false;
    }
    foreach ($err as $code) {
        if ($code === 'unauthorized' || $code === 'token_expired') {
            return true;
        }
    }
    return false;
}

/**
 * API istegi; unauthorized / bos yanit (or. HTTP 401) ise token silinir, login ile yenilenir, bir kez tekrarlanir.
 */
function WhiteLabelServices_ApiRequestWithTokenRefresh($url, &$token, &$params, $method = 'GET', $data = null) {
    $decoded = WhiteLabelServices_APIRequest($url, $token, $method, $data);
    if (WhiteLabelServices_IsApiAuthErrorResponse($decoded)) {
        WLS_debugLog('ApiRequestWithTokenRefresh - Auth error in JSON body, clearing cache and forcing new token');
        if (WhiteLabelServices_WlsEnsureApiServerParams($params) && !empty($params['serverid'])) {
            WLSTokenManager::clearToken($params['serverid']);
            $newToken = WLSTokenManager::getToken($params, true);
            if ($newToken) {
                $token = $newToken;
                return WhiteLabelServices_APIRequest($url, $token, $method, $data);
            }
        }
        return $decoded;
    }
    if ($decoded === null && WhiteLabelServices_WlsEnsureApiServerParams($params) && !empty($params['serverid'])) {
        WLS_debugLog('ApiRequestWithTokenRefresh - Empty response (e.g. HTTP 401), clearing token and retrying once');
        WLSTokenManager::clearToken($params['serverid']);
        $newToken = WLSTokenManager::getToken($params, true);
        if ($newToken) {
            $token = $newToken;
            return WhiteLabelServices_APIRequest($url, $token, $method, $data);
        }
    }
    return $decoded;
}

/**
 * Helper function for API requests
 */
function WhiteLabelServices_APIRequest($url, $token, $method = 'GET', $data = null, $returnErrorBody = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Debug log
    if ($curlError) {
        WLS_debugLog("API Request Error - CURL Error: " . $curlError);
    }
    WLS_debugLog("API Request - URL: " . $url . " HTTP Code: " . $httpCode);
    
    if ($httpCode !== 200) {
        WLS_debugLog("API Request Failed - HTTP " . $httpCode . " Response: " . substr($response, 0, 500));
        if ($returnErrorBody) {
            $decoded = is_string($response) ? json_decode($response, true) : null;
            return [
                '_error' => true,
                '_http_code' => $httpCode,
                'message' => is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded)) : substr($response, 0, 500),
            ];
        }
        return null;
    }
    
    // Log raw response for debugging
    WLS_debugLog("API Request Success - Raw Response (first 500 chars): " . substr($response, 0, 500));
    
    $decoded = json_decode($response, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        WLS_debugLog("API Request - JSON decode error: " . json_last_error_msg());
    }
    
    return $decoded;
}

// Ana fonksiyon - FormlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸turur
function WhiteLabelServices_ApiCall($url, $token, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $method = strtoupper($method);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
        }
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $decoded = is_string($response) ? json_decode($response, true) : null;
    $apiError = null;
    if (is_array($decoded)) {
        if (!empty($decoded['error'])) {
            $err = $decoded['error'];
            $apiError = is_array($err) ? implode(', ', array_map('strval', $err)) : (string) $err;
        } elseif (isset($decoded['status']) && strtolower((string) $decoded['status']) === 'error') {
            $apiError = (string) ($decoded['message'] ?? 'API error');
        }
    }

    return [
        'ok' => ($httpCode >= 200 && $httpCode <= 299) && !$curlError && !$apiError,
        'http_code' => $httpCode,
        'body' => $decoded,
        'raw' => $response,
        'curl_error' => $curlError ?: null,
        'api_error' => $apiError,
    ];
}

function WhiteLabelServices_getModuleSetting($key, $default = '') {
    try {
        if (!Capsule::schema()->hasTable('mod_wls_settings')) {
            return $default;
        }
        $row = Capsule::table('mod_wls_settings')->where('setting_key', $key)->first();
        return ($row && $row->setting_value !== null && $row->setting_value !== '') ? $row->setting_value : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function WhiteLabelServices_pveApiUrl($host, $port, $path) {
    $host = trim($host);
    $port = (int) $port;
    if ($port <= 0 || $port === 443) {
        return 'https://' . $host . $path;
    }
    return 'https://' . $host . ':' . $port . $path;
}

function WhiteLabelServices_pveApiRequest($host, $port, $method, $path, $options = []) {
    $url = WhiteLabelServices_pveApiUrl($host, $port, $path);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $headers = ['Accept: application/json'];
    if (!empty($options['cookie'])) {
        $headers[] = 'Cookie: PVEAuthCookie=' . $options['cookie'];
    }
    if (!empty($options['csrf'])) {
        $headers[] = 'CSRFPreventionToken: ' . $options['csrf'];
    }
    if (!empty($options['content_type'])) {
        $headers[] = 'Content-Type: ' . $options['content_type'];
    }

    $method = strtoupper($method);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body'] ?? '');
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (isset($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'ok' => ($httpCode >= 200 && $httpCode <= 299) && !$curlError,
        'http_code' => $httpCode,
        'body' => is_string($response) ? json_decode($response, true) : null,
        'raw' => $response,
        'curl_error' => $curlError ?: null,
    ];
}

/**
 * Proxmox console flow:
 * 0. GET portal /api/service/{id} → username + password (append @pve to username)
 * 1. POST /api2/json/access/ticket
 * 2. GET  /api2/json/cluster/resources?type=vm  (Cookie: PVEAuthCookie)
 * 3. Redirect to console proxy: ?ticket=&vmid=&node= (nginx sets cookie → Proxmox noVNC)
 */
function WhiteLabelServices_ensureConsoleParams(array $params) {
    $serviceId = (int) ($params['serviceid'] ?? 0);
    if ($serviceId > 0 && empty($params['serverpassword'])) {
        $built = WhiteLabelServices_BuildModuleParamsFromHostingId($serviceId);
        if ($built) {
            return array_merge($built, $params);
        }
    }
    return $params;
}

function WhiteLabelServices_fetchPortalPveCredentials(array $params, $wlsServiceId) {
    $params = WhiteLabelServices_ensureConsoleParams($params);
    $token = WLSTokenManager::getToken($params);
    if (!$token) {
        WLS_debugLog('Console portal creds - failed to get API token');
        return null;
    }

    $apiBaseUrl = rtrim(WhiteLabelServices_getApiBaseUrl($params), '/');
    $response = WhiteLabelServices_APIRequest($apiBaseUrl . '/api/service/' . (int) $wlsServiceId, $token, 'GET');
    if (!is_array($response)) {
        WLS_debugLog('Console portal creds - invalid API response for service ' . $wlsServiceId);
        return null;
    }

    $service = $response['service'] ?? null;
    if (!is_array($service)) {
        WLS_debugLog('Console portal creds - missing service object for ' . $wlsServiceId);
        return null;
    }

    $username = trim((string) ($service['username'] ?? ''));
    $password = (string) ($service['password'] ?? '');
    if ($username === '' || $password === '') {
        WLS_debugLog('Console portal creds - empty username/password for service ' . $wlsServiceId);
        return null;
    }

    if (stripos($username, '@pve') === false) {
        $username .= '@pve';
    }

    return [
        'username' => $username,
        'password' => $password,
    ];
}

function WhiteLabelServices_buildProxmoxConsoleUrl($pveHost, $pvePort, $vmid, $node, $vmName = '') {
    $url = WhiteLabelServices_pveApiUrl($pveHost, $pvePort, '/')
        . '?console=kvm&novnc=1&vmid=' . (int) $vmid;
    if ($vmName !== '') {
        $url .= '&vmname=' . rawurlencode($vmName);
    }
    return $url
        . '&node=' . rawurlencode($node)
        . '&resize=off&cmd=';
}

/**
 * Console proxy redirect (nginx sets PVEAuthCookie, 302 to Proxmox noVNC).
 * ticket must be rawurlencode()'d — ticket value may contain +, =, : etc.
 */
function WhiteLabelServices_buildConsoleRedirectUrl($ticket, $vmid, $node) {
    $base = trim(WhiteLabelServices_getModuleSetting(
        'pve_console_redirect_url',
        'https://console.whitelabelservices.us/console-redirect'
    ));
    if ($base === '') {
        return '';
    }
    $base = rtrim($base, '?&');

    return $base
        . '?ticket=' . rawurlencode((string) $ticket)
        . '&vmid=' . (int) $vmid
        . '&node=' . rawurlencode((string) $node);
}

function WhiteLabelServices_prepareVmConsole(array $params) {
    $params = WhiteLabelServices_ensureConsoleParams($params);

    $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
    if (!$vpsDetails) {
        return ['error' => 'VM not found'];
    }
    if (!$vpsDetails->wls_vm_id) {
        $resolvedVmId = WhiteLabelServices_EnsureWlsVmId($params, $vpsDetails);
        if ($resolvedVmId) {
            $vpsDetails->wls_vm_id = $resolvedVmId;
        }
    }
    if (!$vpsDetails->wls_vm_id) {
        return ['error' => 'VM not found'];
    }
    if (empty($vpsDetails->wls_service_id)) {
        return ['error' => 'WLS service not linked'];
    }

    $vmid = (int) $vpsDetails->wls_vm_id;
    $vmName = $params['domain'] ?? ('vm-' . $vmid);

    $pveHost = trim(WhiteLabelServices_getModuleSetting('pve_console_host', ''));
    $pvePort = (int) WhiteLabelServices_getModuleSetting('pve_console_port', '8006');
    if ($pvePort <= 0) {
        $pvePort = 8006;
    }

    if ($pveHost === '') {
        return ['error' => 'Console is not configured. Contact support.'];
    }

    $creds = WhiteLabelServices_fetchPortalPveCredentials($params, (int) $vpsDetails->wls_service_id);
    if (!$creds) {
        return ['error' => 'Could not load console credentials from portal API'];
    }

    $pveUser = $creds['username'];
    $pvePass = $creds['password'];

    // Step 1 — Login with VM portal credentials, get API ticket + CSRF
    $login = WhiteLabelServices_pveApiRequest($pveHost, $pvePort, 'POST', '/api2/json/access/ticket', [
        'body' => http_build_query(['username' => $pveUser, 'password' => $pvePass]),
        'content_type' => 'application/x-www-form-urlencoded',
    ]);

    if (!$login['ok'] || empty($login['body']['data']['ticket'])) {
        WLS_debugLog('Console step 1 failed: HTTP ' . $login['http_code']);
        return ['error' => 'Could not authenticate to console server'];
    }

    $ticket = $login['body']['data']['ticket'];
    $csrf = $login['body']['data']['CSRFPreventionToken'] ?? '';

    // Step 2 — Resolve node for VM (Cookie: PVEAuthCookie={ticket})
    $resources = WhiteLabelServices_pveApiRequest($pveHost, $pvePort, 'GET', '/api2/json/cluster/resources?type=vm', [
        'cookie' => $ticket,
        'csrf' => $csrf,
    ]);

    if (!$resources['ok'] || empty($resources['body']['data']) || !is_array($resources['body']['data'])) {
        WLS_debugLog('Console step 2 failed: HTTP ' . $resources['http_code']);
        return ['error' => 'Could not list cluster VMs'];
    }

    $node = null;
    $resolvedName = $vmName;
    foreach ($resources['body']['data'] as $item) {
        if (!is_array($item)) {
            continue;
        }
        if ((int) ($item['vmid'] ?? 0) === $vmid) {
            $node = $item['node'] ?? null;
            if (!empty($item['name'])) {
                $resolvedName = $item['name'];
            }
            break;
        }
    }

    if (!$node) {
        return ['error' => 'VM node not found on hypervisor'];
    }

    WLS_debugLog('Console OK: vmid=' . $vmid . ' node=' . $node . ' user=' . $pveUser);

    $consoleUrl = WhiteLabelServices_buildProxmoxConsoleUrl($pveHost, $pvePort, $vmid, $node, $resolvedName);
    $redirectUrl = WhiteLabelServices_buildConsoleRedirectUrl($ticket, $vmid, $node);

    return [
        'pve_host' => $pveHost,
        'pve_port' => $pvePort,
        'pve_user' => $pveUser,
        'node' => $node,
        'vmid' => $vmid,
        'vm_name' => $resolvedName,
        'pve_ticket' => $ticket,
        'pve_csrf' => $csrf,
        'console_url' => $consoleUrl,
        'redirect_url' => $redirectUrl,
    ];
}

function WhiteLabelServices_renderConsoleLaunchPage(array $params) {
    WhiteLabelServices_renderConsolePage($params);
}

function WhiteLabelServices_renderConsolePage(array $params) {
    while (ob_get_level()) {
        ob_end_clean();
    }

    $session = WhiteLabelServices_prepareVmConsole($params);
    header('Content-Type: text/html; charset=utf-8');

    if (isset($session['error'])) {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Console</title></head><body style="font-family:sans-serif;padding:2rem;background:#0f172a;color:#e2e8f0;">'
            . '<h2>Console unavailable</h2><p>' . htmlspecialchars($session['error']) . '</p></body></html>';
        exit;
    }

    if (empty($session['redirect_url'])) {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Console</title></head><body style="font-family:sans-serif;padding:2rem;background:#0f172a;color:#e2e8f0;">'
            . '<h2>Console unavailable</h2><p>Console redirect URL is not configured.</p></body></html>';
        exit;
    }

    header('Location: ' . $session['redirect_url'], true, 302);
    exit;
}

function WhiteLabelServices_getWhmcsHostname() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $host = '';
    try {
        if (class_exists('WHMCS\\Database\\Capsule')) {
            $systemUrl = Capsule::table('tblconfiguration')
                ->where('setting', 'SystemURL')
                ->value('value');
            if ($systemUrl) {
                $host = (string) parse_url($systemUrl, PHP_URL_HOST);
            }
        }
    } catch (Exception $e) {
        // fall through to request host
    }

    if ($host === '') {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    }

    $cached = strtolower(preg_replace('/:\d+$/', '', $host));
    return $cached;
}

function WhiteLabelServices_getConsoleCookieDomain($pveHost) {
    $manual = trim(WhiteLabelServices_getModuleSetting('pve_cookie_domain', ''));
    if ($manual !== '') {
        return ltrim($manual, '.');
    }

    return WhiteLabelServices_sharedCookieDomain(
        WhiteLabelServices_getWhmcsHostname(),
        strtolower($pveHost)
    );
}

function WhiteLabelServices_getEmbeddedConsoleUrl(array $params) {
    $serviceId = (int) ($params['serviceid'] ?? 0);

    return [
        'url' => 'modules/servers/WhiteLabelServices/console.php?serviceid=' . $serviceId,
        'mode' => 'embedded',
    ];
}

function WhiteLabelServices_getLauncherConsoleUrl(array $params) {
    $serviceId = (int) ($params['serviceid'] ?? 0);
    $token = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';

    return [
        'url' => 'clientarea.php?action=productdetails&id=' . $serviceId
            . '&customAction=launchConsole&token=' . rawurlencode($token),
        'mode' => 'launcher',
    ];
}

function WhiteLabelServices_getVmConsoleUrl(array $params) {
    $session = WhiteLabelServices_prepareVmConsole($params);
    if (isset($session['error'])) {
        return $session;
    }

    return WhiteLabelServices_getEmbeddedConsoleUrl($params);
}

/**
 * Longest common domain suffix of two hostnames (min. 2 labels),
 * usable as a cookie Domain attribute shared by both hosts.
 * Returns null when hosts don't share a registrable parent domain.
 */
function WhiteLabelServices_sharedCookieDomain($hostA, $hostB) {
    if ($hostA === '' || $hostB === ''
        || filter_var($hostA, FILTER_VALIDATE_IP) || filter_var($hostB, FILTER_VALIDATE_IP)) {
        return null;
    }

    $a = array_reverse(explode('.', $hostA));
    $b = array_reverse(explode('.', $hostB));
    $common = [];
    $max = min(count($a), count($b));
    for ($i = 0; $i < $max; $i++) {
        if ($a[$i] !== $b[$i]) {
            break;
        }
        $common[] = $a[$i];
    }

    if (count($common) < 2) {
        return null;
    }

    return implode('.', array_reverse($common));
}

function WhiteLabelServices_AdminProductConfigFieldsSave($vars) {
    try {
        WLS_debugLog("Debug - AdminProductConfigFieldsSave triggered with vars: " . print_r($vars, true));

        if (!isset($vars['pid']) || empty($vars['pid'])) {
            WLS_debugLog("Debug - No PID found in vars");
            return;
        }

        // ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n bilgilerini al
        $product = Capsule::table('tblproducts')
            ->where('id', $vars['pid'])
            ->first();

        if (!$product) {
            WLS_debugLog("Debug - Product not found: " . $vars['pid']);
            return;
        }

        // WLS modÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ mÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ kontrol et
        if ($product->servertype !== 'WhiteLabelServices') {
            WLS_debugLog("Debug - Product is not WLS type: " . $product->servertype);
            return;
        }

        $mergeOpts = array();
        for ($i = 1; $i <= 24; $i++) {
            $f = WhiteLabelServices_ConfigOptionFieldName($i);
            $mergeOpts[$f] = $vars[$f] ?? ($product->{$f} ?? '');
        }
        $selectedProduct = WhiteLabelServices_ResolveWlsApiProductRaw($mergeOpts);
        if ($selectedProduct === '') {
            WLS_debugLog("Debug - No product selected");
            return;
        }

        $productId = WhiteLabelServices_ExtractProductId($selectedProduct);
        if (!$productId) {
            WLS_debugLog("Debug - Could not extract product ID from: " . $selectedProduct);
            return;
        }
        WLS_debugLog("Debug - Extracted product ID: " . $productId);

        // Sunucu bilgilerini al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();

        if (!$server) {
            WLS_debugLog("Debug - No active WLS server found");
            return;
        }

        $params = array(
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password),
            'pid' => $vars['pid'],
        );
        for ($i = 1; $i <= 24; $i++) {
            $f = WhiteLabelServices_ConfigOptionFieldName($i);
            $params[$f] = $vars[$f] ?? ($product->{$f} ?? '');
        }

        // API'den token al
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            WLS_debugLog("Debug - Failed to get API token");
            return;
        }

        // API'den ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/order/" . $productId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            WLS_debugLog("Debug - API Error: " . curl_error($ch));
            curl_close($ch);
            return;
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            WLS_debugLog("Debug - API request failed with code " . $httpCode . " Response: " . $response);
            return;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['product']) || !isset($data['product']['config']['forms'])) {
            WLS_debugLog("Debug - Invalid API response structure");
            return;
        }

        $forms = $data['product']['config']['forms'];
        WLS_debugLog("Debug - Found " . count($forms) . " forms in API response");

        // ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“nce bu ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ne ait tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼m custom field'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± temizle
        $deletedCount = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $vars['pid'])
            ->delete();
        
        WLS_debugLog("Debug - Deleted " . $deletedCount . " existing custom fields");

        // Form yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rmasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± saklamak iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
        $formConfig = [];
        $createdFields = 0;

        // Her form iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in custom field oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
        foreach ($forms as $form) {
            WLS_debugLog("Debug - Processing form: " . $form['title'] . " (ID: " . $form['id'] . ", Type: " . $form['type'] . ")");

            // Form yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rmasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± sakla (ID'ler dahil) - TÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“M FORMLAR ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°N
            $formConfig[$form['id']] = [
                'id' => $form['id'],
                'type' => $form['type'],
                'title' => $form['title'],
                'variable' => $form['metadata']['variable'] ?? '',
                'required' => $form['required'] ?? false,
                'sort_order' => $form['metadata']['sort_order'] ?? 0,
                'items' => []
            ];

            // Form item'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± sakla
            if (isset($form['items']) && is_array($form['items'])) {
                foreach ($form['items'] as $item) {
                    $formConfig[$form['id']]['items'][$item['id']] = [
                        'id' => $item['id'],
                        'title' => $item['title'],
                        'value' => $item['value'] ?? null,
                        'price' => $item['price'] ?? 0,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'selected' => $item['selected'] ?? false
                    ];
                }
            }

            // Form tipine gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶re enable durumunu kontrol et - SADECE CUSTOM FIELD ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°N
            $isEnabled = WhiteLabelServices_isFormEnabled($form, $params);
            
            if (!$isEnabled) {
                WLS_debugLog("Debug - Form disabled for custom field, skipping: " . $form['title'] . " (variable: " . ($form['metadata']['variable'] ?? 'none') . ")");
                continue;
            }

            // WHMCS custom field oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur - SADECE ENABLE EDÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°LMÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â FORMLAR ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°N
            $fieldData = WhiteLabelServices_createCustomFieldData($form, $vars['pid']);
            
            if ($fieldData) {
                try {
                    $customFieldId = Capsule::table('tblcustomfields')->insertGetId($fieldData);
                    
                    if ($customFieldId) {
                        $createdFields++;
                        WLS_debugLog("Debug - Created custom field: " . $fieldData['fieldname'] . " (ID: " . $customFieldId . ")");
                    } else {
                        WLS_debugLog("Debug - Failed to create custom field: " . $fieldData['fieldname']);
                    }
                } catch (Exception $e) {
                    WLS_debugLog("Debug - Error creating custom field: " . $e->getMessage());
                }
            }
        }

        $formCol = WhiteLabelServices_FormConfigStorageColumnName();
        Capsule::table('tblproducts')
            ->where('id', $vars['pid'])
            ->update(array(
                $formCol => json_encode($formConfig),
            ));

        WLS_debugLog("Debug - Process completed. Created " . $createdFields . " custom fields. Form config saved.");

        // Configurable Options oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur (fiyatlandÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rma iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in)
        $configResult = WhiteLabelServices_CreateConfigurableOptions($vars['pid'], $formConfig);
        if ($configResult) {
            WLS_debugLog("Debug - Configurable options created successfully");
            
            // FiyatlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            WhiteLabelServices_UpdateConfigurableOptionsPricing($vars['pid'], $formConfig);
        } else {
            WLS_debugLog("Debug - Failed to create configurable options");
        }

    } catch (Exception $e) {
        WLS_debugLog("Error in AdminProductConfigFieldsSave: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}

// Form'un enable durumunu kontrol et
function WhiteLabelServices_isFormEnabled($form, $params) {
    $formType = $form['type'] ?? '';
    $formVariable = $form['metadata']['variable'] ?? '';

    if ($formVariable === 'os' || $formType === 'serverselector') {
        return true;
    }
    return false;
}

function WhiteLabelServices_generateFieldName($form) {
    $variable = $form['metadata']['variable'] ?? '';
    $type = $form['type'];
    
      $variableMap = [
        'os' => 'os',
        'ipamlimit' => 'ip_address',
        'additional_storage' => 'additional_storage',
        'backuplimit' => 'backups',
        'snapshot_limit' => 'snapshots',
    ];
    
    // Variable varsa ve map'te varsa onu kullan
    if (!empty($variable) && isset($variableMap[$variable])) {
        return $variableMap[$variable];
    }
    
    // Type'a gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶re ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶zel durumlar
    if ($type === 'serverselector') {
        return 'location';
    } elseif ($type === 'multicheckbox') {
        return 'addons';
    }
    
    // VarsayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lan olarak custom_formid
    return 'custom_' . $form['id'];
}

// Hook tanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±mlamasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± - DEVRE DIÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚ÂI (ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nler artÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±k pricing sayfasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸turuluyor)
// add_hook('AdminProductConfigFieldsSave', 1, function($vars) {
//     // Sadece ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n kaydetme iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lemlerinde ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±n
//     if (isset($vars['pid']) && !empty($vars['pid'])) {
//         WhiteLabelServices_AdminProductConfigFieldsSave($vars);
//     }
// });

/**
 * TicketOpen: Iliskili hizmet WLS ise mod_wls_ticket_tasks'a Pending ekler. API cagrisi cron (ProcessTicketTasks) ile yapilir.
 * Iliskili hizmet: tbltickets.service "S123" formatinda veya tbltickets.relid (hosting id).
 */
function WhiteLabelServices_CreatePortalTicketIfWLS($vars) {
    try {
        $ticketId = (int) (isset($vars['ticketid']) ? $vars['ticketid'] : 0);
        $subject = isset($vars['subject']) ? $vars['subject'] : '';
        $message = isset($vars['message']) ? $vars['message'] : '';
        if (!$ticketId || $subject === '') {
            return;
        }

        WLSTokenManager::ensureTablesExist();
        $ticket = Capsule::table('tbltickets')->where('id', $ticketId)->first();
        if (!$ticket) {
            return;
        }

        $hostingId = 0;
        if (isset($ticket->service) && is_string($ticket->service) && preg_match('/^S(\d+)$/', trim($ticket->service), $m)) {
            $hostingId = (int) $m[1];
        }
        if (!$hostingId && isset($ticket->relid)) {
            $hostingId = (int) $ticket->relid;
        }
        if (!$hostingId) {
            return;
        }

        $hosting = Capsule::table('tblhosting')->where('id', $hostingId)->first();
        if (!$hosting) {
            return;
        }
        $status = isset($hosting->domainstatus) ? trim((string) $hosting->domainstatus) : '';
        if (strtolower($status) !== 'active') {
            return;
        }
        $product = Capsule::table('tblproducts')->where('id', $hosting->packageid)->first();
        if (!$product || $product->servertype !== 'WhiteLabelServices') {
            return;
        }

        $added = WLSTokenManager::addTicketTask($ticketId, $hostingId, $subject, $message);
        if ($added) {
            WLS_debugLog("CreatePortalTicketIfWLS - Ticket task added Pending for WHMCS ticket #" . $ticketId . ", hosting #" . $hostingId);
        }
    } catch (Exception $e) {
        WLS_debugLog("CreatePortalTicketIfWLS error: " . $e->getMessage());
    }
}

/**
 * CancellationRequest hook: WLS hizmetine iptal talebi acildiysa mod_wls_cancel_tasks'a Pending ekler.
 * Cron (ProcessCancelTasks) ile portal API POST /service/@id/cancel cagrilir.
 */
function WhiteLabelServices_AddCancelTaskIfWLS($vars) {
    try {
        $relid = (int) (isset($vars['relid']) ? $vars['relid'] : 0);
        $reason = isset($vars['reason']) ? (string) $vars['reason'] : '';
        $type = isset($vars['type']) ? (string) $vars['type'] : '';
        if (!$relid) {
            return;
        }
        $hosting = Capsule::table('tblhosting')->where('id', $relid)->first();
        if (!$hosting) {
            return;
        }
        $product = Capsule::table('tblproducts')->where('id', $hosting->packageid)->first();
        if (!$product || $product->servertype !== 'WhiteLabelServices') {
            return;
        }
        WLSTokenManager::ensureTablesExist();
        $vps = Capsule::table('mod_wls_vps')->where('id', $relid)->first();
        if (!$vps || empty($vps->wls_service_id)) {
            return;
        }
        $immediate = (stripos($type, 'immediate') !== false || strtolower($type) === 'immediate');
        $added = WLSTokenManager::addCancelTask($relid, $vps->wls_service_id, $immediate, $reason);
        if ($added) {
            WLS_debugLog("AddCancelTaskIfWLS - Cancel task added Pending for service #" . $relid . ", wls_service_id " . $vps->wls_service_id);
        }
    } catch (Exception $e) {
        WLS_debugLog("AddCancelTaskIfWLS error: " . $e->getMessage());
    }
}

/**
 * Cron'dan cagrilir: Pending ticket task'lari portal API'ye gonderir.
 */
function WhiteLabelServices_ProcessTicketTasks() {
    $processed = 0;
    try {
        WLSTokenManager::ensureTablesExist();
        $tasks = WLSTokenManager::getPendingTicketTasks(50);
        if (empty($tasks)) {
            return 0;
        }

        $server = Capsule::table('tblservers')->where('type', 'WhiteLabelServices')->where('active', '1')->first();
        if (!$server) {
            if (function_exists('logActivity')) {
                logActivity('WLS: ProcessTicketTasks - No active WLS server found');
            }
            return 0;
        }
        $params = [
            'serverid' => $server->id,
            'serverhostname' => $server->hostname,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password),
        ];
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            if (function_exists('logActivity')) {
                logActivity('WLS: ProcessTicketTasks - Failed to get API token');
            }
            return 0;
        }

        $apiBaseUrl = rtrim(WhiteLabelServices_getApiBaseUrl($params), '/');
        $deptId = 1;
        try {
            $setting = Capsule::table('mod_wls_settings')->where('setting_key', 'portal_ticket_dept_id')->first();
            if ($setting && is_numeric(trim(isset($setting->setting_value) ? $setting->setting_value : ''))) {
                $deptId = (int) trim($setting->setting_value);
            }
        } catch (Exception $e) {}

        foreach ($tasks as $task) {
            $errMsg = '';
            try {
                $payload = [
                    'dept_id' => $deptId,
                    'subject' => isset($task->subject) ? $task->subject : '',
                    'body' => isset($task->message) ? $task->message : '',
                ];
                $url = $apiBaseUrl . '/api/tickets';
                $response = WhiteLabelServices_APIRequestTicket($url, $token, $payload);

                if (is_array($response) && !empty($response['_error'])) {
                    $rawMsg = isset($response['message']) ? $response['message'] : ('HTTP ' . (isset($response['_http_code']) ? $response['_http_code'] : ''));
                    $errMsg = is_array($rawMsg) ? json_encode($rawMsg) : (string) $rawMsg;
                    $errMsg = substr($errMsg, 0, 1000);
                    WLSTokenManager::setTicketTaskStatus($task->id, 'Failed', null, $errMsg);
                    if (function_exists('logActivity')) {
                        logActivity('WLS: ProcessTicketTasks - Task #' . $task->id . ' (WHMCS ticket #' . (isset($task->whmcs_ticket_id) ? $task->whmcs_ticket_id : '') . ') failed: ' . $errMsg);
                    }
                } elseif ($response === null) {
                    $errMsg = 'API request failed (CURL/connection or invalid response)';
                    WLSTokenManager::setTicketTaskStatus($task->id, 'Failed', null, $errMsg);
                    if (function_exists('logActivity')) {
                        logActivity('WLS: ProcessTicketTasks - Task #' . $task->id . ' failed: ' . $errMsg);
                    }
                } else {
                    $portalTicketId = null;
                    if (is_array($response)) {
                        if (isset($response['ticket'])) {
                            $portalTicketId = is_array($response['ticket'])
                                ? ($response['ticket']['ticket_number'] ?? $response['ticket']['id'] ?? null)
                                : $response['ticket'];
                        } elseif (isset($response['ticket_number'])) {
                            $portalTicketId = $response['ticket_number'];
                        }
                    }
                    if ($portalTicketId !== null && $portalTicketId !== '') {
                        WLSTokenManager::setTicketTaskStatus($task->id, 'Completed', (string) $portalTicketId, null);
                        $processed++;
                        continue;
                    }
                    $errMsg = 'Portal did not return ticket id (response: ' . (is_array($response) ? json_encode($response) : (string) $response) . ')';
                    $errMsg = is_string($errMsg) ? substr($errMsg, 0, 1000) : substr(json_encode($errMsg), 0, 1000);
                    WLSTokenManager::setTicketTaskStatus($task->id, 'Failed', null, $errMsg);
                    if (function_exists('logActivity')) {
                        logActivity('WLS: ProcessTicketTasks - Task #' . $task->id . ' failed: ' . $errMsg);
                    }
                }
            } catch (Exception $e) {
                $errMsg = $e->getMessage();
                WLSTokenManager::setTicketTaskStatus($task->id, 'Failed', null, $errMsg);
                if (function_exists('logActivity')) {
                    logActivity('WLS: ProcessTicketTasks - Task #' . $task->id . ' exception: ' . $errMsg);
                }
                WLS_debugLog('ProcessTicketTasks task #' . $task->id . ' exception: ' . $errMsg);
            }
        }
    } catch (Exception $e) {
        $msg = $e->getMessage();
        WLS_debugLog('ProcessTicketTasks error: ' . $msg);
        if (function_exists('logActivity')) {
            logActivity('WLS: ProcessTicketTasks fatal: ' . $msg);
        }
    }
    return $processed;
}

/**
 * Portal ticket API: JSON body. Cift encode onlenir, body elle birlikte tek JSON string yapilir.
 * Request body: {"dept_id":5,"subject":"...","body":"..."}
 */
function WhiteLabelServices_APIRequestTicket($url, $token, $data) {
    if (is_string($data)) {
        $data = json_decode($data, true);
        $data = is_array($data) ? $data : [];
    }
    $deptId = isset($data['dept_id']) ? (int) $data['dept_id'] : 5;
    $subject = isset($data['subject']) ? (string) $data['subject'] : '';
    $bodyContent = isset($data['body']) ? (string) $data['body'] : (isset($data['message']) ? (string) $data['message'] : '');
    $bodyString = '{"dept_id":' . $deptId . ',"subject":' . json_encode($subject) . ',"body":' . json_encode($bodyContent) . '}';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        WLS_debugLog('APIRequestTicket CURL Error: ' . $curlError);
        return ['_error' => true, '_http_code' => 0, 'message' => 'CURL: ' . $curlError];
    }
    if ($httpCode < 200 || $httpCode > 299) {
        $decoded = is_string($response) ? json_decode($response, true) : null;
        $msg = is_array($decoded) && (isset($decoded['message']) || isset($decoded['error'])) ? (isset($decoded['message']) ? $decoded['message'] : $decoded['error']) : substr($response, 0, 500);
        WLS_debugLog('APIRequestTicket HTTP ' . $httpCode . ': ' . $msg);
        return ['_error' => true, '_http_code' => $httpCode, 'message' => $msg ?: ('HTTP ' . $httpCode)];
    }
    $decoded = is_string($response) ? json_decode($response, true) : null;
    return is_array($decoded) ? $decoded : null;
}

/**
 * Cron'dan cagrilir: Pending iptal taleplerini portal API POST /service/@id/cancel ile gonderir.
 */
function WhiteLabelServices_ProcessCancelTasks() {
    $processed = 0;
    try {
        WLSTokenManager::ensureTablesExist();
        $tasks = WLSTokenManager::getPendingCancelTasks(50);
        if (empty($tasks)) {
            return 0;
        }
        $server = Capsule::table('tblservers')->where('type', 'WhiteLabelServices')->where('active', '1')->first();
        if (!$server) {
            return 0;
        }
        $params = [
            'serverid' => $server->id,
            'serverhostname' => $server->hostname,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password),
        ];
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            return 0;
        }
        $apiBaseUrl = rtrim(WhiteLabelServices_getApiBaseUrl($params), '/');
        foreach ($tasks as $task) {
            try {
                $wlsId = (int) $task->wls_service_id;
                $immediate = !empty($task->immediate);
                $reason = isset($task->reason) ? (string) $task->reason : '';
                $url = $apiBaseUrl . '/api/service/' . $wlsId . '/cancel';
                $payload = [
                    'immediate' => $immediate,
                    'reason' => $reason,
                ];
                $bodyString = '{"immediate":' . ($immediate ? 'true' : 'false') . ',"reason":' . json_encode($reason) . '}';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token,
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                if ($curlError) {
                    WLSTokenManager::setCancelTaskStatus($task->id, 'Failed', 'CURL: ' . $curlError);
                    continue;
                }
                if ($httpCode >= 200 && $httpCode <= 299) {
                    WLSTokenManager::setCancelTaskStatus($task->id, 'Completed', null);
                    $processed++;
                } else {
                    $errMsg = is_string($response) ? substr($response, 0, 500) : 'HTTP ' . $httpCode;
                    WLSTokenManager::setCancelTaskStatus($task->id, 'Failed', $errMsg);
                }
            } catch (Exception $e) {
                WLSTokenManager::setCancelTaskStatus($task->id, 'Failed', $e->getMessage());
            }
        }
    } catch (Exception $e) {
        WLS_debugLog('ProcessCancelTasks error: ' . $e->getMessage());
    }
    return $processed;
}

// ClientArea fonksiyonlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± - WHMCS standart formatÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nda
function WhiteLabelServices_ClientArea($params) {
    $requestedAction = isset($_REQUEST['customAction']) ? $_REQUEST['customAction'] : '';

    if ($requestedAction === 'launchConsole' || $requestedAction === 'showConsole') {
        WhiteLabelServices_renderConsolePage($params);
    }

    if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
        // AJAX iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in output buffering temizle
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        $response = ['success' => false, 'error' => 'Invalid action'];
        
        switch ($requestedAction) {
            case 'getVMData':
                $vpsDetails = Capsule::table('mod_wls_vps')
                    ->where('id', $params['serviceid'])
                    ->first();
                if ($vpsDetails) {
                    $response = ['success' => true, 'data' => $vpsDetails];
                } else {
                    $response = ['success' => false, 'error' => 'VM not found'];
                }
                break;
                
            case 'start':
            case 'stop':
            case 'shutdown':
            case 'reboot':
            case 'reset':
                // DoÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸rudan API'ye istek gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
                $result = WhiteLabelServices_handleVMAction($params, $requestedAction);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = ['success' => true, 'message' => $result['message'] ?? 'Action completed'];
                }
                break;
                
            case 'rebuild':
                $template = $_REQUEST['template'] ?? '';
                if (empty($template)) {
                    $response = ['success' => false, 'error' => 'Template is required'];
                    break;
                }
                // DoÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸rudan rebuild API ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§aÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸rÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±
                $result = WhiteLabelServices_rebuildVM($params, $template);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = ['success' => true, 'message' => 'Rebuild started'];
                }
                break;
                
            case 'getTemplates':
                $result = WhiteLabelServices_getTemplates($params);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = [
                        'success' => true, 
                        'templates' => $result['templates'] ?? [],
                        'linux' => $result['linux'] ?? [],
                        'windows' => $result['windows'] ?? []
                    ];
                }
                break;
                
            case 'updateRDNS':
                $rdns = $_REQUEST['rdns'] ?? '';
                $ip = $_REQUEST['ip'] ?? ''; // Specific IP to update
                $result = WhiteLabelServices_updateRDNS($params, $rdns, $ip);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = ['success' => true, 'message' => 'rDNS updated for ' . ($ip ?: 'primary IP')];
                }
                break;
                
            case 'updateLabel':
                $label = $_REQUEST['label'] ?? '';
                $result = WhiteLabelServices_updateLabel($params, $label);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = [
                        'success' => true,
                        'message' => 'Hostname updated',
                        'label' => $result['label'] ?? $label,
                        'vm_status' => $result['vm_status'] ?? null,
                    ];
                }
                break;

            case 'getConsole':
                $result = WhiteLabelServices_getVmConsoleUrl($params);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = ['success' => true, 'mode' => $result['mode'] ?? 'url', 'url' => $result['url']];
                }
                break;
                
            case 'addNetworkInterface':
                $result = WhiteLabelServices_addNetworkInterface($params);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = ['success' => true, 'message' => 'Network interface added'];
                }
                break;
                
            case 'deleteNetworkInterface':
                $interfaceName = $_REQUEST['interfaceName'] ?? '';
                if (empty($interfaceName)) {
                    $response = ['success' => false, 'error' => 'Interface name is required'];
                    break;
                }
                if ($interfaceName === 'net0') {
                    $response = ['success' => false, 'error' => 'Cannot delete primary interface (net0)'];
                    break;
                }
                $result = WhiteLabelServices_deleteNetworkInterface($params, $interfaceName);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = ['success' => true, 'message' => 'Network interface deleted'];
                }
                break;
                
            case 'syncVMData':
                // API'den gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncel VM verilerini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek ve DB'yi gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                $result = WhiteLabelServices_SyncVMFromAPI($params);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = ['success' => true, 'message' => 'VM data synchronized'];
                }
                break;
                
            case 'getVMStatus':
                // Always refresh from API (rebuild sonrası storage/interfaces dahil)
                WhiteLabelServices_SyncVMFromAPI($params);

                $vpsDetails = Capsule::table('mod_wls_vps')
                    ->where('id', $params['serviceid'])
                    ->first();
                
                $vmStatus = $vpsDetails->vm_status ?? 'unknown';
                
                $lockedStates = ['rebuild', 'rebuilding', 'creating', 'resetting', 'shutdown', 'stopping', 'starting', 'backup', 'snapshot', 'restore', 'updating'];
                $isLocked = in_array($vmStatus, $lockedStates);
                
                $response = [
                    'success' => true,
                    'vm_status' => $vmStatus,
                    'locked' => $isLocked,
                    'ip_address' => $vpsDetails->ipv4 ?? '',
                    'username' => $vpsDetails->username ?? '',
                    'password' => $vpsDetails->password ?? ''
                ];
                break;
        }
        
        echo json_encode($response);
        die();
    }
    
    // Normal sayfa yÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼klemesi - WHMCS standart return formatÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± kullan
    try {
        // Servis durumunu kontrol et
        $serviceStatus = $params['status'] ?? 'Active';
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± mod_wls_vps tablosundan al
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->first();
        
        // DeÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸kenleri hazÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rla
        $templateVars = [
            'serviceid' => $params['serviceid'],
            'domain' => $params['domain'] ?? '',
            'username' => $params['username'] ?? '',
            'password' => $params['password'] ?? '',
            'dedicatedip' => $params['dedicatedip'] ?? '',
            'serviceStatus' => $serviceStatus,
        ];
        
        if ($vpsDetails) {
            // Fetch rDNS value for all IPs
            $rdnsResult = WhiteLabelServices_getRDNS($params);
            $rdnsValue = ($rdnsResult['success'] ?? false) ? ($rdnsResult['rdns'] ?? '') : '';
            $allRdns = ($rdnsResult['success'] ?? false) ? ($rdnsResult['all_rdns'] ?? []) : [];
            
            // Parse vm_interfaces for detailed IP info (gateway, mask, etc.)
            $networkIps = [];
            $vmInterfaces = json_decode($vpsDetails->vm_interfaces ?? '{}', true);
            if (!empty($vmInterfaces)) {
                foreach ($vmInterfaces as $iface) {
                    if (isset($iface['ip']) && is_array($iface['ip'])) {
                        foreach ($iface['ip'] as $ipInfo) {
                            $networkIps[] = [
                                'ip' => $ipInfo['ipaddress'] ?? $ipInfo['ip'] ?? '',
                                'type' => $ipInfo['type'] ?? 'ipv4',
                                'mask' => $ipInfo['mask'] ?? '',
                                'gateway' => $ipInfo['gateway'] ?? '',
                                'network' => $ipInfo['network'] ?? '',
                                'main' => $ipInfo['main'] ?? 0,
                            ];
                        }
                    }
                }
            }
            
            $templateVars['vmInfo'] = [
                'vm_id' => $vpsDetails->wls_vm_id ?? '',
                'wls_service_id' => $vpsDetails->wls_service_id ?? '',
                'vm_status' => $vpsDetails->vm_status ?? 'unknown',
                'vm_built' => (bool)($vpsDetails->vm_built ?? false),
                'vm_power' => (bool)($vpsDetails->vm_powered ?? false),
                'ip_address' => $vpsDetails->ipv4 ?? $params['dedicatedip'] ?? '',
                'username' => $vpsDetails->username ?? 'root',
                'password' => $vpsDetails->password ?? '',
                'memory' => $vpsDetails->memory ?? '',
                'disk' => $vpsDetails->disk ?? '',
                'cores' => $vpsDetails->cores ?? '',
                'template' => $vpsDetails->template ?? '',
                'order_number' => $vpsDetails->order_number ?? '',
                'rdns' => $rdnsValue,
                'all_rdns' => $allRdns,
                'ipv6' => $vpsDetails->ipv6 ?? '',
                'all_ips' => json_decode($vpsDetails->all_ips ?? '{}', true),
                'network_ips' => $networkIps,
                'interfaces' => $vmInterfaces,
                'storage' => json_decode($vpsDetails->vm_storage ?? '{}', true),
                'label' => $params['domain'] ?? '',
            ];
            
            // WHMCS "Preparing" ekranı: vm_built bayrak bazen API ile senkron kalmaz; VM id + anlamlı durum varsa paneli göster
            $vmStatusStr = (string) ($vpsDetails->vm_status ?? '');
            $statusLower = strtolower($vmStatusStr);
            $transitional = $vmStatusStr === '' || $statusLower === 'unknown'
                || in_array($statusLower, ['creating', 'pending', 'provisioning', 'rebuilding'], true);
            $templateVars['showVMDetails'] = !empty($vmStatusStr) && !$transitional
                && (!empty($vpsDetails->vm_built) || !empty($vpsDetails->wls_vm_id));
            
            // Templates will be loaded via AJAX when rebuild tab is clicked (performance optimization)
            $templateVars['availableTemplates'] = [];
            $templateVars['osFamilies'] = [];
            $templateVars['linuxFamilies'] = [];
            $templateVars['windowsFamilies'] = [];
        } else {
            $templateVars['showVMDetails'] = false;
            $templateVars['availableTemplates'] = [];
            $templateVars['osFamilies'] = [];
            $templateVars['linuxFamilies'] = [];
            $templateVars['windowsFamilies'] = [];
        }
        
        // Iptal talebi bu hizmet icin acilmis mi (Pending veya Completed)
        $templateVars['hasCancelRequest'] = false;
        if (Capsule::schema()->hasTable('mod_wls_cancel_tasks')) {
            $hasCancel = Capsule::table('mod_wls_cancel_tasks')
                ->where('service_id', $params['serviceid'])
                ->whereIn('status', ['Pending', 'Completed'])
                ->exists();
            $templateVars['hasCancelRequest'] = (bool) $hasCancel;
        }
        
        return [
            'tabOverviewReplacementTemplate' => 'templates/clientarea.tpl',
            'templateVariables' => $templateVars,
        ];
        
    } catch (Exception $e) {
        return [
            'tabOverviewReplacementTemplate' => '',
            'templateVariables' => [
                'error' => $e->getMessage(),
            ],
        ];
    }
}

/**
 * Portal /vms yanitini normalize et (assoc id => vm veya numerik dizi).
 *
 * @return array<int, array>
 */
function WhiteLabelServices_ParseVmsList($vmsPayload) {
    if (!is_array($vmsPayload)) {
        return [];
    }

    $vms = $vmsPayload['vms'] ?? $vmsPayload;
    if (!is_array($vms) || empty($vms)) {
        return [];
    }

    $result = [];
    foreach ($vms as $key => $vm) {
        if (!is_array($vm)) {
            continue;
        }
        $id = (int) ($vm['id'] ?? $vm['vmid'] ?? $key);
        if ($id > 0) {
            $result[$id] = $vm;
        }
    }

    return $result;
}

/**
 * /vms listesinden VM id coz (tercihen mevcut kayit).
 */
function WhiteLabelServices_ResolveWlsVmId($vmsPayload, $preferredId = null) {
    $vms = WhiteLabelServices_ParseVmsList($vmsPayload);
    if (empty($vms)) {
        return null;
    }

    $preferredId = (int) $preferredId;
    if ($preferredId > 0 && isset($vms[$preferredId])) {
        return $preferredId;
    }

    return (int) array_key_first($vms);
}

/**
 * Detay endpoint storage donmezse disk / additional_storage alanlarindan UI formati uret.
 */
function WhiteLabelServices_BuildStorageFromVm(array $vm) {
    if (isset($vm['storage']) && is_array($vm['storage']) && count($vm['storage']) > 0) {
        return $vm['storage'];
    }

    $disks = [];
    if (isset($vm['disk']) && $vm['disk'] !== '' && $vm['disk'] !== null) {
        $sizeGb = is_numeric($vm['disk']) ? (int) $vm['disk'] : (int) preg_replace('/\D/', '', (string) $vm['disk']);
        if ($sizeGb > 0) {
            $disks['ide0'] = [
                'name' => 'ide0',
                'size_gb' => $sizeGb,
                'bootable' => true,
                'type' => 'disk',
                'format' => 'raw',
                'mp' => '/',
            ];
        }
    }

    if (!empty($vm['additional_storage']) && is_array($vm['additional_storage'])) {
        foreach ($vm['additional_storage'] as $idx => $extra) {
            if (!is_array($extra)) {
                continue;
            }
            $key = (string) ($extra['name'] ?? $extra['id'] ?? ('scsi' . ($idx + 1)));
            $sizeGb = (int) ($extra['size_gb'] ?? $extra['size'] ?? $extra['disk'] ?? 0);
            if ($sizeGb <= 0) {
                continue;
            }
            $disks[$key] = [
                'name' => $key,
                'size_gb' => $sizeGb,
                'bootable' => !empty($extra['bootable']),
                'type' => $extra['type'] ?? 'disk',
                'format' => $extra['format'] ?? 'raw',
            ];
        }
    }

    return $disks;
}

/**
 * wls_vm_id eksikse portal /vms uzerinden bul ve DB'ye yaz.
 */
function WhiteLabelServices_EnsureWlsVmId(array $params, $vpsDetails = null) {
    if (!$vpsDetails) {
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->first();
    }

    if (!$vpsDetails || empty($vpsDetails->wls_service_id)) {
        return null;
    }

    if (!empty($vpsDetails->wls_vm_id)) {
        return (int) $vpsDetails->wls_vm_id;
    }

    $token = WhiteLabelServices_getToken($params);
    if (!$token) {
        return null;
    }

    $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
    $vmsUrl = rtrim($apiBaseUrl, '/') . '/api/service/' . $vpsDetails->wls_service_id . '/vms';
    $vmsResponse = WhiteLabelServices_APIRequest($vmsUrl, $token, 'GET');
    $vmId = WhiteLabelServices_ResolveWlsVmId($vmsResponse);

    if ($vmId) {
        Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->update([
                'wls_vm_id' => $vmId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        WLS_debugLog('EnsureWlsVmId - resolved VM id ' . $vmId . ' for WHMCS #' . $params['serviceid']);
    }

    return $vmId;
}

/**
 * API VM detay yanitinda VM yok/ silinmis (or. {"error":["VM 30458 not found"]}).
 */
function WhiteLabelServices_ApiPayloadIndicatesVmGone($decoded) {
    if (!is_array($decoded) || empty($decoded['error'])) {
        return false;
    }
    $err = $decoded['error'];
    $parts = is_array($err) ? $err : array($err);
    foreach ($parts as $m) {
        $s = strtolower(trim((string) $m));
        if ($s === '') {
            continue;
        }
        if (strpos($s, 'not found') !== false && strpos($s, 'vm') !== false) {
            return true;
        }
    }
    return false;
}

/**
 * GET /api/service/{wlsServiceId} ile portal servis durumunu oku; VM yok/iptal ise WHMCS ile esitle.
 */
function WhiteLabelServices_ReconcileWlsServiceFromPortal($whmcsServiceId, $wlsServiceId, $token, $apiBaseUrl = null) {
    try {
        $base = $apiBaseUrl ? rtrim($apiBaseUrl, '/') : rtrim(WhiteLabelServices_getApiBaseUrl(), '/');
        $url = $base . '/api/service/' . $wlsServiceId;
        $data = WhiteLabelServices_APIRequest($url, $token, 'GET');
        if (!$data || empty($data['service']) || !is_array($data['service'])) {
            WLS_debugLog('ReconcileWlsService - no service payload for WLS ' . $wlsServiceId);
            Capsule::table('mod_wls_vps')->where('id', $whmcsServiceId)->update(array(
                'wls_vm_id' => null,
                'last_sync' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ));
            return false;
        }
        $svc = $data['service'];
        $st = isset($svc['status']) ? trim((string) $svc['status']) : '';
        $stLower = strtolower($st);
        $vpsUpdate = array(
            'service_status' => $st,
            'last_sync' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $hostingStatus = null;
        if ($stLower !== '' && (strpos($stLower, 'termin') !== false || strpos($stLower, 'cancel') !== false)) {
            $vpsUpdate['wls_vm_id'] = null;
            $vpsUpdate['vm_status'] = 'terminated';
            $vpsUpdate['vm_built'] = 0;
            $vpsUpdate['vm_powered'] = 0;
            $vpsUpdate['status'] = 'Terminated';
            $hostingStatus = 'Terminated';
        } elseif (strpos($stLower, 'suspend') !== false) {
            $vpsUpdate['vm_status'] = 'suspended';
            $hostingStatus = 'Suspended';
        } else {
            // Portal Active — once /vms ile VM id bul; yalnizca liste bos ise temizle
            $vmsUrl = $base . '/api/service/' . $wlsServiceId . '/vms';
            $vmsData = WhiteLabelServices_APIRequest($vmsUrl, $token, 'GET');
            $discoveredVmId = WhiteLabelServices_ResolveWlsVmId($vmsData);

            if ($discoveredVmId) {
                $vpsUpdate['wls_vm_id'] = $discoveredVmId;
                $parsedVms = WhiteLabelServices_ParseVmsList($vmsData);
                $vmEntry = $parsedVms[$discoveredVmId] ?? null;
                if (is_array($vmEntry)) {
                    if (isset($vmEntry['status'])) {
                        $vpsUpdate['vm_status'] = $vmEntry['status'];
                    }
                    if (isset($vmEntry['power'])) {
                        $vpsUpdate['vm_powered'] = $vmEntry['power'] ? 1 : 0;
                    }
                    if (isset($vmEntry['built'])) {
                        $vpsUpdate['vm_built'] = $vmEntry['built'] ? 1 : 0;
                    }
                    if (isset($vmEntry['ipv4'])) {
                        $vpsUpdate['ipv4'] = $vmEntry['ipv4'];
                    }
                    if (isset($vmEntry['disk'])) {
                        $vpsUpdate['disk'] = $vmEntry['disk'];
                    }
                    $storage = WhiteLabelServices_BuildStorageFromVm($vmEntry);
                    if (!empty($storage)) {
                        $vpsUpdate['vm_storage'] = json_encode($storage);
                    }
                }
            } else {
                $vpsUpdate['wls_vm_id'] = null;
                $vpsUpdate['vm_status'] = 'unknown';
            }
        }
        Capsule::table('mod_wls_vps')->where('id', $whmcsServiceId)->update($vpsUpdate);
        if ($hostingStatus !== null) {
            Capsule::table('tblhosting')->where('id', $whmcsServiceId)->update(array('domainstatus' => $hostingStatus));
            if (function_exists('logActivity')) {
                logActivity('WLS: Service #' . $whmcsServiceId . ' -> ' . $hostingStatus . ' (portal WLS ' . $wlsServiceId . ' status: ' . $st . ')');
            }
        }
        WLS_debugLog('ReconcileWlsService - WHMCS #' . $whmcsServiceId . ' portal status: ' . $st);
        return true;
    } catch (Exception $e) {
        WLS_debugLog('ReconcileWlsService Error: ' . $e->getMessage());
        return false;
    }
}

// Sync VM data from API - fetch latest VM info and update local database
function WhiteLabelServices_SyncVMFromAPI($params) {
    try {
        // Get token
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            return ['error' => 'Could not get API token'];
        }
        
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        
        // Get VPS details from local DB
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->first();
            
        if (!$vpsDetails) {
            return ['error' => 'VPS record not found'];
        }
        
        $wlsServiceId = $vpsDetails->wls_service_id;
        $wlsVmId = $vpsDetails->wls_vm_id;
        
        if (!$wlsServiceId) {
            return ['error' => 'WLS Service ID not found'];
        }
        
        if (!$wlsVmId) {
            $wlsVmId = WhiteLabelServices_EnsureWlsVmId($params, $vpsDetails);
        }
        
        if (!$wlsVmId) {
            WLS_debugLog('SyncVMFromAPI - No VM id (empty /vms list); reconciling portal service ' . $wlsServiceId);
            WhiteLabelServices_ReconcileWlsServiceFromPortal($params['serviceid'], $wlsServiceId, $token, $apiBaseUrl);
            return ['success' => true, 'reconciled' => 'no_vm_id'];
        }
        
        // Get VM details from API - Correct endpoint: /api/service/{id}/vms/{vmid}
        $vmUrl = rtrim($apiBaseUrl, '/') . '/api/service/' . $wlsServiceId . '/vms/' . $wlsVmId;
        WLS_debugLog("Sync Debug - API URL: " . $vmUrl);
        WLS_debugLog("Sync Debug - Token: " . substr($token, 0, 20) . "...");
        
        $vmData = WhiteLabelServices_APIRequest($vmUrl, $token, 'GET');
        WLS_debugLog("Sync Debug - API Response: " . json_encode($vmData));
        
        $vmGone = !$vmData || (is_array($vmData) && WhiteLabelServices_ApiPayloadIndicatesVmGone($vmData));
        
        if ($vmGone) {
            WLS_debugLog('SyncVMFromAPI - VM missing or error; reconciling via GET /api/service/' . $wlsServiceId);
            WhiteLabelServices_ReconcileWlsServiceFromPortal($params['serviceid'], $wlsServiceId, $token, $apiBaseUrl);
            return ['success' => true, 'reconciled' => 'service_endpoint'];
        }
        
        // Extract VM info - API returns {"vm":{...}} structure
        $vm = $vmData['vm'] ?? $vmData['data'] ?? $vmData;
        
        // Update local database
        $updateData = [
            'wls_vm_id' => $wlsVmId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        // Update fields if available
        if (isset($vm['status'])) {
            $updateData['vm_status'] = $vm['status'];
        }
        if (isset($vm['hostname'])) {
            $updateData['hostname'] = $vm['hostname'];
        }
        // API returns template_name for OS
        if (isset($vm['template_name'])) {
            $updateData['template'] = $vm['template_name'];
        }
        // API returns cores or cpus for CPU count
        if (isset($vm['cpus'])) {
            $updateData['cores'] = $vm['cpus'];
        } elseif (isset($vm['cores'])) {
            $updateData['cores'] = $vm['cores'];
        }
        if (isset($vm['memory'])) {
            $updateData['memory'] = $vm['memory'];
        }
        if (isset($vm['disk'])) {
            $updateData['disk'] = $vm['disk'];
        }
        if (isset($vm['username'])) {
            $updateData['username'] = $vm['username'];
        }
        if (isset($vm['password'])) {
            $updateData['password'] = $vm['password'];
        }
        if (isset($vm['ipv4'])) {
            $updateData['ipv4'] = $vm['ipv4'];
        } elseif (isset($vm['ip'])) {
            $updateData['ipv4'] = $vm['ip'];
        }
        if (isset($vm['ipv6'])) {
            $updateData['ipv6'] = $vm['ipv6'];
        }
        if (isset($vm['power'])) {
            $updateData['vm_powered'] = $vm['power'] ? 1 : 0;
        }
        if (isset($vm['mac'])) {
            $updateData['mac_address'] = $vm['mac'];
        }

        $storage = WhiteLabelServices_BuildStorageFromVm($vm);
        if (!empty($storage)) {
            $updateData['vm_storage'] = json_encode($storage);
        }
        if (isset($vm['interfaces']) && is_array($vm['interfaces']) && count($vm['interfaces']) > 0) {
            $updateData['vm_interfaces'] = json_encode($vm['interfaces']);
        }
        if (isset($vm['bandwidth']) && is_array($vm['bandwidth'])) {
            $updateData['vm_bandwidth'] = json_encode($vm['bandwidth']);
        }
        if (isset($vm['ip']) && is_array($vm['ip'])) {
            $allIps = [];
            foreach ($vm['ip'] as $ipId => $ipInfo) {
                if (!is_array($ipInfo)) {
                    continue;
                }
                $allIps[] = [
                    'id' => $ipId,
                    'ip' => $ipInfo['ipaddress'] ?? $ipInfo['ip'] ?? '',
                    'main' => $ipInfo['main'] ?? 0,
                ];
            }
            if (!empty($allIps)) {
                $updateData['all_ips'] = json_encode($allIps);
            }
        }

        $updateData['last_sync'] = date('Y-m-d H:i:s');
        
        // vm_built: API alanı varsa kullan; yoksa kararlı VM durumlarında WHMCS arayüzünün takılmaması için işaretle
        if (isset($vm['built'])) {
            $updateData['vm_built'] = $vm['built'] ? 1 : 0;
        } elseif (isset($updateData['vm_status'])) {
            $stable = ['running', 'stopped', 'shutoff', 'paused', 'active'];
            if (in_array(strtolower((string) $updateData['vm_status']), $stable, true)) {
                $updateData['vm_built'] = 1;
            }
        }

        // Update mod_wls_vps
        Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->update($updateData);
        
        // Also update tblhosting for WHMCS display
        $hostingUpdate = [];
        if (isset($updateData['ipv4'])) {
            $hostingUpdate['dedicatedip'] = $updateData['ipv4'];
        }
        if (isset($updateData['username'])) {
            $hostingUpdate['username'] = $updateData['username'];
        }
        if (isset($updateData['password'])) {
            $hostingUpdate['password'] = encrypt($updateData['password']);
        }
        // Sync label/hostname to domain field
        if (isset($vm['label']) && $vm['label'] !== '') {
            $hostingUpdate['domain'] = $vm['label'];
        } elseif (isset($vm['hostname']) && $vm['hostname'] !== '') {
            $hostingUpdate['domain'] = $vm['hostname'];
        }
        
        if (!empty($hostingUpdate)) {
            Capsule::table('tblhosting')
                ->where('id', $params['serviceid'])
                ->update($hostingUpdate);
        }
        
        WLS_debugLog("- Synced VM data for service " . $params['serviceid']);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        WLS_debugLog("- Sync VM Error: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rma sayfasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶zel alanlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± hazÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rla
function WhiteLabelServices_ConfigureProductAddons($params) {
    try {
        // Form yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rmasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $formConfig = WhiteLabelServices_DecodeFormConfigFromSource($params);
        if (!$formConfig) {
            return [];
        }

        // ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“zel alan deÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸erlerini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $customFields = [];
        $result = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $params['pid'])
            ->get();

        foreach ($result as $field) {
            $customFields[$field->fieldname] = [
                'id' => $field->id,
                'name' => $field->fieldname,
                'type' => $field->fieldtype,
                'description' => $field->description,
                'options' => explode(',', $field->fieldoptions)
            ];
        }

        return [
            'templatefile' => 'templates/configureproduct',
            'vars' => [
                'formConfig' => $formConfig,
                'customFields' => $customFields,
            ],
        ];
    } catch (Exception $e) {
        WLS_debugLog("Configure Error: " . $e->getMessage());
        return [];
    }
}

function WhiteLabelServices_SuspendAccount(array $params) {
    try {
        WLS_debugLog("Suspend - Suspending service: " . $params['serviceid']);
        
        // Token al
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            throw new Exception("API token alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±namadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            throw new Exception("Servis bulunamadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

        // Suspend API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
            $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/service/" . $vpsDetails->wls_service_id . "/vms/" . $vpsDetails->wls_vm_id . "/suspend");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
            }
            
            curl_close($ch);
            
        if ($httpCode !== 200) {
            throw new Exception("API Error: HTTP " . $httpCode);
        }

        // VeritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        WLSTokenManager::updateVPSDetails($params['serviceid'], [
            'status' => 'suspended',
            'vm_status' => 'suspended'
        ]);

        WLS_debugLog("Suspend - Service suspended successfully: " . $params['serviceid']);
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("Suspend Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

function WhiteLabelServices_UnsuspendAccount(array $params) {
    try {
        WLS_debugLog("Unsuspend - Unsuspending service: " . $params['serviceid']);
        
        // Token al
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            throw new Exception("API token alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±namadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            throw new Exception("Servis bulunamadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

        // Unsuspend API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/service/" . $vpsDetails->wls_service_id . "/vms/" . $vpsDetails->wls_vm_id . "/unsuspend");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API Error: HTTP " . $httpCode);
        }

        // VeritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        WLSTokenManager::updateVPSDetails($params['serviceid'], [
            'status' => 'active',
            'vm_status' => 'running'
        ]);

        WLS_debugLog("Unsuspend - Service unsuspended successfully: " . $params['serviceid']);
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("Unsuspend Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

function WhiteLabelServices_TerminateAccount(array $params) {
    try {
        WLS_debugLog("Terminate - Terminating service: " . $params['serviceid']);
        
        // Token al
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            throw new Exception("API token alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±namadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id) {
            // KayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±t yoksa zaten silinmiÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ demektir
            WLS_debugLog("Terminate - No VPS details found, assuming already terminated");
            return 'success';
        }

        // VM varsa ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nce onu sil
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        if ($vpsDetails->wls_vm_id) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/service/" . $vpsDetails->wls_service_id . "/vms/" . $vpsDetails->wls_vm_id);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // 404 hatasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± VM zaten silinmiÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ demektir
            if ($httpCode !== 200 && $httpCode !== 404) {
                WLS_debugLog("Terminate - VM deletion returned HTTP " . $httpCode);
            }
        }

        // Servisi iptal et
        $payload = [
            'immediate' => true,
            'reason' => 'Service terminated via WHMCS'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/service/" . $vpsDetails->wls_service_id . "/cancel");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        
        curl_close($ch);
        
        // 404 veya 200 kabul edilebilir
        if ($httpCode !== 200 && $httpCode !== 404) {
            throw new Exception("API Error: HTTP " . $httpCode);
        }

        // VeritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan VM kaydÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± sil
        WLSTokenManager::deleteVPSDetails($params['serviceid']);

        WLS_debugLog("Terminate - Service terminated successfully: " . $params['serviceid']);
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("Terminate Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

function WhiteLabelServices_ChangePassword(array $params) {
    try {
        WLS_debugLog("Debug - ChangePassword called for service: " . $params['serviceid']);
        
        // TODO: WLS API'sine ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ifre deÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tirme isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("ChangePassword Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

/**
 * Change Package / Upgrade - WHMCS paket degisikligi veya yukseltme istediginde cagrilir.
 * mod_wls_upgrade_tasks tablosuna kayit eklenir; cron ProcessUpgradeTasks ile portal API'ye gonderilir.
 */
function WhiteLabelServices_ChangePackage(array $params) {
    try {
        WLS_debugLog("ChangePackage - Called for service " . ($params['serviceid'] ?? '?'), true);
        $serviceId = (int) ($params['serviceid'] ?? 0);
        if (!$serviceId) {
            WLS_debugLog("ChangePackage - Missing serviceid");
            return 'Service ID is required';
        }

        WLSTokenManager::ensureTablesExist();
        $vps = Capsule::table('mod_wls_vps')->where('id', $serviceId)->first();
        if (!$vps || empty($vps->wls_service_id)) {
            WLS_debugLog("ChangePackage - No WLS service for WHMCS service " . $serviceId);
            return 'WLS service not found for this service';
        }

        $orderId = (int) ($params['orderid'] ?? 0);
        $newPid = (int) ($params['pid'] ?? $params['packageid'] ?? 0);
        if (!$newPid) {
            $hosting = Capsule::table('tblhosting')->where('id', $serviceId)->first();
            $newPid = $hosting ? (int) $hosting->packageid : 0;
        }

        $data = array(
            'service_id' => $serviceId,
            'package_id' => $newPid,
            'billingcycle' => $params['billingcycle'] ?? '',
        );
        for ($i = 1; $i <= 24; $i++) {
            $f = WhiteLabelServices_ConfigOptionFieldName($i);
            $data[$f] = $params[$f] ?? '';
        }

        $added = WLSTokenManager::addUpgradeTask($serviceId, $orderId, 'upgrade', $data);
        if (!$added) {
            WLS_debugLog("ChangePackage - Failed to add upgrade task for service " . $serviceId);
            return 'Failed to create upgrade task';
        }

        WLS_debugLog("ChangePackage - Upgrade task created for service " . $serviceId . ", order " . $orderId . ", package " . $newPid);
        return 'success';
    } catch (Exception $e) {
        WLS_debugLog("ChangePackage Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

/**
 * Sync VM Data from API - Admin modÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼l komutu
 * Bu fonksiyon admin panelinden "Yenile" butonuna basÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ldÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nda ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§aÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸rÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
 */
function WhiteLabelServices_SyncVMData(array $params) {
    try {
        $serviceId = $params['serviceid'];
        WLS_debugLog("Debug - SyncVMData called for service: " . $serviceId);
        
        // API'den gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncel VM verilerini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $result = WhiteLabelServices_SyncVMFromAPI($params);
        
        if (isset($result['error'])) {
            WLS_debugLog("SyncVMData Error: " . $result['error']);
            return $result['error'];
        }
        
        WLS_debugLog("SyncVMData Success - Service: " . $serviceId);
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("SyncVMData Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

function WhiteLabelServices_AdminServicesTabFields($params) {
    try {
        $serviceId = $params['serviceid'];
        
        // VM bilgilerini veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $vmInfo = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();

        if (!$vmInfo) {
            return [
                'VM Information' => '<div class="alert alert-warning">No VM information found.</div>'
            ];
        }

        // VM detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± JSON'dan decode et
        $vmInterfaces = json_decode($vmInfo->vm_interfaces, true) ?: [];
        $vmStorage = json_decode($vmInfo->vm_storage, true) ?: [];
        $vmResources = json_decode($vmInfo->vm_resources, true) ?: [];
        $vmBandwidth = json_decode($vmInfo->vm_bandwidth, true) ?: [];
        
        // Bandwidth hesapla
        $dataReceivedBytes = isset($vmBandwidth['data_received']) ? intval($vmBandwidth['data_received']) : 0;
        $dataSentBytes = isset($vmBandwidth['data_sent']) ? intval($vmBandwidth['data_sent']) : 0;
        $totalTrafficBytes = $dataReceivedBytes + $dataSentBytes;
        
        $dataReceived = formatBytes($dataReceivedBytes);
        $dataSent = formatBytes($dataSentBytes);
        $totalTraffic = formatBytes($totalTrafficBytes);
        
        // 4TB = 4 * 1024^4 bytes = 4398046511104 bytes
        $trafficLimitBytes = 4398046511104;
        $isOverLimit = $totalTrafficBytes > $trafficLimitBytes;
        $trafficWarning = '';

        // Power durumu iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±f belirle
                $statusClass = '';
        switch ($vmInfo->vm_status) {
            case 'running':
                $statusClass = 'success';
                                break;
            case 'stopped':
                $statusClass = 'danger';
                                break;
            case 'pending':
                    case 'provisioning':
                $statusClass = 'warning';
                        break;
                default:
                $statusClass = 'info';
        }

        // Uptime hesapla
        $uptime = '';
        if (isset($vmResources['uptime'])) {
            $uptimeSeconds = intval($vmResources['uptime']);
            $days = floor($uptimeSeconds / 86400);
            $hours = floor(($uptimeSeconds % 86400) / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);
            
            if ($days > 0) $uptime .= $days . ' days ';
            if ($hours > 0) $uptime .= $hours . ' hours ';
            if ($minutes > 0) $uptime .= $minutes . ' minutes';
            
            if (empty($uptime)) $uptime = 'Just started';
        } else {
            $uptime = 'N/A';
        }

        // Username'i iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸letim sistemine gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶re belirle
        $template = strtolower($vmInfo->template ?? '');
        if (strpos($template, 'windows') !== false) {
            $displayUsername = 'administrator';
        } elseif (strpos($template, 'mikrotik') !== false) {
            $displayUsername = 'admin';
        } else {
            $displayUsername = 'root';
        }

        // Unique ID'ler oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
        $pwdHiddenId = 'pwd-hidden-' . $serviceId;
        $pwdVisibleId = 'pwd-visible-' . $serviceId;
        
        // HTML ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ktÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
        $output = '
        <style>
            /* Hide Change Password button in Module Commands */
            #btnChange_Password { display: none !important; }
            
            .vm-box {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .vm-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            .vm-status {
                padding: 8px 15px;
                border-radius: 4px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .vm-controls {
                margin: 20px 0;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .vm-controls button {
                margin-right: 10px;
                margin-bottom: 10px;
            }
            .vm-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }
            .vm-info-item {
                padding: 10px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .vm-info-label {
                font-weight: bold;
                color: #666;
                margin-bottom: 5px;
            }
            .vm-info-value {
                color: #333;
            }
            .vm-refresh {
                margin-left: auto;
            }
            .password-toggle {
                background: none;
                border: none;
                color: #666;
                cursor: pointer;
                padding: 0 5px;
            }
            .password-toggle:hover {
                color: #333;
            }
        </style>
        
        <div class="vm-box">
            <div class="vm-header">
                <h3>
                    <i class="fas fa-server"></i> 
                    VM Information
                    <span class="label label-' . $statusClass . ' vm-status">' . ucfirst($vmInfo->vm_status) . '</span>
                </h3>
            </div>
        
            <div class="vm-info-grid">
                <div class="vm-info-item">
                    <div class="vm-info-label">WLS Product ID</div>
                    <div class="vm-info-value">' . $vmInfo->wls_service_id . '</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">Main IPv4</div>
                    <div class="vm-info-value">' . $vmInfo->ipv4 . '</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">Main IPv6</div>
                    <div class="vm-info-value">' . (!empty($vmInfo->ipv6) ? $vmInfo->ipv6 : 'Not Assigned') . '</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">MAC Address</div>
                    <div class="vm-info-value">' . $vmInfo->mac_address . '</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">Template</div>
                    <div class="vm-info-value">' . $vmInfo->template . '</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">Uptime</div>
                    <div class="vm-info-value">' . $uptime . '</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">Username</div>
                    <div class="vm-info-value">' . $displayUsername . '</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">Password</div>
                    <div class="vm-info-value">
                        <span id="' . $pwdHiddenId . '">••••••••</span>
                        <span id="' . $pwdVisibleId . '" style="display:none">' . $vmInfo->password . '</span>
                        <button type="button" class="password-toggle" onclick="togglePassword(\'' . $serviceId . '\', event)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">CPU Cores</div>
                    <div class="vm-info-value">' . $vmInfo->cores . '</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">Memory</div>
                    <div class="vm-info-value">' . $vmInfo->memory . ' MB</div>
                </div>
                <div class="vm-info-item">
                    <div class="vm-info-label">Disk</div>
                    <div class="vm-info-value">' . $vmInfo->disk . ' GB</div>
                </div>
                <div class="vm-info-item" style="grid-column: span 3;">
                    <div class="vm-info-label">Network Traffic</div>
                    <div class="vm-info-value" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                        <div>
                            <small style="color: #666;">Downloaded</small><br>
                            <strong style="color: #28a745;"><i class="fas fa-arrow-down"></i> ' . $dataReceived . '</strong>
                        </div>
                        <div>
                            <small style="color: #666;">Uploaded</small><br>
                            <strong style="color: #dc3545;"><i class="fas fa-arrow-up"></i> ' . $dataSent . '</strong>
                        </div>
                        <div>
                            <small style="color: #666;">Total Traffic</small><br>
                            <strong style="color: ' . ($isOverLimit ? '#dc3545' : '#007bff') . ';"><i class="fas fa-exchange-alt"></i> ' . $totalTraffic . ' ' . ($isOverLimit ? '<i class="fas fa-exclamation-triangle" style="color:#dc3545;" title="Traffic limit exceeded"></i>' : '') . '</strong>
                        </div>
                    </div>
                    ' . ($isOverLimit ? '
                    <div style="margin-top: 15px; padding: 12px 15px; background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%); border: 1px solid #feb2b2; border-radius: 6px; border-left: 4px solid #dc3545;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-exclamation-triangle" style="color: #dc3545; font-size: 1.2rem;"></i>
                            <div>
                                <strong style="color: #c53030;">AÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± Trafik UyarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±</strong>
                                <p style="margin: 5px 0 0 0; color: #742a2a; font-size: 0.9rem;">
                                    Bu VM\'in toplam trafik kullanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±mÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± 4 TB\'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± aÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸mÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r. Adil kullanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±m politikasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±na aykÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± trafik kullanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±mÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± tespit edilmesi durumunda, 
                                    VM\'in network portu rate limit uygulanarak sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rlandÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±labilir.
                                </p>
                            </div>
                        </div>
                    </div>' : '') . '
                </div>
            </div>
        </div>

        <script>
        function vmAction(serviceId, action) {
            if (!confirm("Are you sure you want to " + action + " this VM?")) {
                return;
            }
            
            // AJAX request
            WHMCS.http.jqClient.post("addonmodules.php?module=WLS", {
                action: action,
                service_id: serviceId,
                token: csrfToken
            }, function(data) {
                if (data.success) {
                    alert("Action " + action + " initiated successfully");
                    setTimeout(function() {
                        refreshVMInfo(serviceId);
                    }, 5000);
                } else {
                    alert("Error: " + (data.message || "Unknown error occurred"));
                }
            }, "json").fail(function() {
                alert("Connection error occurred");
            });
        }

        function refreshVMInfo(serviceId) {
            // Refresh button dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ndÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rmeye baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸la
            var refreshBtn = document.querySelector(".vm-refresh i");
            refreshBtn.className = "fas fa-sync-alt fa-spin";
            
            // AJAX request
            WHMCS.http.jqClient.post("addonmodules.php?module=WLS", {
                action: "refresh",
                service_id: serviceId,
                token: csrfToken
            }, function(data) {
                if (data.success) {
                    // SayfayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± yenile
                    window.location.reload();
                } else {
                    alert("Error: " + (data.message || "Unknown error occurred"));
                    refreshBtn.className = "fas fa-sync-alt";
                }
            }, "json").fail(function() {
                alert("Connection error occurred");
                refreshBtn.className = "fas fa-sync-alt";
            });
        }

        function togglePassword(serviceId, event) {
            event.preventDefault();
            event.stopPropagation();
            
            var hiddenPwd = document.getElementById("pwd-hidden-" + serviceId);
            var visiblePwd = document.getElementById("pwd-visible-" + serviceId);
            var icon = event.target.tagName === "I" ? event.target : event.target.querySelector("i");
            
            if (visiblePwd.style.display === "none") {
                // Show password
                hiddenPwd.style.display = "none";
                visiblePwd.style.display = "inline";
                icon.className = "fas fa-eye-slash";
            } else {
                // Hide password
                hiddenPwd.style.display = "inline";
                visiblePwd.style.display = "none";
                icon.className = "fas fa-eye";
            }
        }
        </script>';
        
        return [
            'VM Information' => $output
        ];
        
    } catch (Exception $e) {
        return [
            'VM Information' => '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>'
        ];
    }
}

// WHMCS Configurable Options oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
function WhiteLabelServices_CreateConfigurableOptions($productId, $formConfig) {
    try {
        WLS_debugLog("Debug - Creating configurable options for product: " . $productId);
        
        // ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“nce mevcut configurable options'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± temizle
        $existingLinks = Capsule::table('tblproductconfiglinks')
            ->where('pid', $productId)
            ->get();
            
        foreach ($existingLinks as $link) {
            // Options'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± sil
            $options = Capsule::table('tblproductconfigoptions')
                ->where('gid', $link->gid)
                ->get();
                
            foreach ($options as $option) {
                // Sub options'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± sil
                Capsule::table('tblproductconfigoptionssub')
                    ->where('configid', $option->id)
                    ->delete();
                    
                // Pricing'leri sil
                Capsule::table('tblpricing')
                    ->where('type', 'configoptions')
                    ->whereIn('relid', function($query) use ($option) {
                        $query->select('id')
                              ->from('tblproductconfigoptionssub')
                              ->where('configid', $option->id);
                    })
                    ->delete();
            }
            
            // Options'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± sil
            Capsule::table('tblproductconfigoptions')
                ->where('gid', $link->gid)
                ->delete();
        }
        
        // Links'leri sil
        Capsule::table('tblproductconfiglinks')
            ->where('pid', $productId)
            ->delete();
            
        // Groups'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± sil (eÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸er baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ka ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nle baÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸lantÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± yoksa)
        foreach ($existingLinks as $link) {
            $otherLinks = Capsule::table('tblproductconfiglinks')
                ->where('gid', $link->gid)
                ->count();
                
            if ($otherLinks == 0) {
                Capsule::table('tblproductconfiggroups')
                    ->where('id', $link->gid)
                    ->delete();
            }
        }
            
        WLS_debugLog("Debug - Cleared existing configurable options");
        
        $groupOrder = 1;
        
        foreach ($formConfig as $formId => $form) {
            // Sadece ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼cretli seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§enekleri olan formlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
            $hasPaidOptions = false;
            if (isset($form['items'])) {
                foreach ($form['items'] as $item) {
                    if ($item['unit_price'] > 0) {
                        $hasPaidOptions = true;
                    break;
                    }
                }
            }
            
            if (!$hasPaidOptions) {
                continue; // ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“cretsiz formlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± atla
            }
            
            WLS_debugLog("Debug - Processing paid form for configurable options: " . $form['title']);

            // Configurable Option Group oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
            $groupId = Capsule::table('tblproductconfiggroups')->insertGetId([
                'name' => $form['title'],
                'description' => $form['title']
            ]);
            
            // ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n ile grubu baÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸la
            Capsule::table('tblproductconfiglinks')->insert([
                'gid' => $groupId,
                'pid' => $productId
            ]);
            
            WLS_debugLog("Debug - Created config group: " . $form['title'] . " (ID: " . $groupId . ")");
            
            $formVariable = $form['variable'] ?? ($form['metadata']['variable'] ?? '');
            $qtyMin = 0;
            $qtyMax = 0;
            if (($form['type'] ?? '') === 'slider' && $formVariable === 'ipamlimit') {
                $qtyMin = 1;
            }

            // Configurable Option oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
            $optionId = Capsule::table('tblproductconfigoptions')->insertGetId([
                'gid' => $groupId,
                'optionname' => $form['title'],
                'optiontype' => 1, // Dropdown
                'qtyminimum' => $qtyMin,
                'qtymaximum' => $qtyMax,
                'order' => 1,
                'hidden' => 0
            ]);
            
            WLS_debugLog("Debug - Created config option: " . $form['title'] . " (ID: " . $optionId . ")");
            
            // Sub options oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
            $subOrder = 1;
            
            // Form tipine gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶re seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§enekleri oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
            switch ($form['type']) {
                case 'slider':
                    // IP, Storage, Backup iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
                    if (isset($form['items']) && count($form['items']) > 0) {
                        $item = reset($form['items']); // ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°lk item'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
                        $unitPrice = $item['unit_price'];
                        
                        // API'den min/max deÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸erlerini al (form config'den)
                        $min = 0;
                        $max = 10;
                        $step = 1;
                        
                        // Form variable'a gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶re deÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸erleri ayarla
                        switch ($form['variable']) {
                            case 'ipamlimit':
                                $min = 1; $max = 3; $unit = ' IP';
                                break;
                            case 'additional_storage':
                                $min = 0; $max = 250; $step = 25; $unit = ' GB';
                                break;
                            case 'backuplimit':
                                $min = 0; $max = 30; $unit = ' Backup';
                                break;
                        }
                        
                    for ($i = $min; $i <= $max; $i += $step) {
                            $price = ($i == 0 || ($form['variable'] === 'ipamlimit' && $i == 1)) ? 0 : $i * $unitPrice;
                            
                            $subId = Capsule::table('tblproductconfigoptionssub')->insertGetId([
                                'configid' => $optionId,
                                'optionname' => $i . $unit,
                                'sortorder' => $subOrder++,
                                'hidden' => 0
                            ]);
                            
                            // FiyatlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ekle (tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼m para birimleri iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in)
                            $currencies = Capsule::table('tblcurrencies')->get();
                            foreach ($currencies as $currency) {
                                Capsule::table('tblpricing')->insert([
                                    'type' => 'configoptions',
                                    'currency' => $currency->id,
                                    'relid' => $subId,
                                    'msetupfee' => 0,
                                    'qsetupfee' => 0,
                                    'ssetupfee' => 0,
                                    'asetupfee' => 0,
                                    'bsetupfee' => 0,
                                    'tsetupfee' => 0,
                                    'monthly' => $price,
                                    'quarterly' => $price * 3,
                                    'semiannually' => $price * 6,
                                    'annually' => $price * 12,
                                    'biennially' => $price * 24,
                                    'triennially' => $price * 36
                                ]);
                            }
                        }
                    }
                    break;
                    
                case 'multicheckbox':
                    // Server Licenses iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
                    if (isset($form['items'])) {
                        // None seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§eneÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i
                        $noneSubId = Capsule::table('tblproductconfigoptionssub')->insertGetId([
                            'configid' => $optionId,
                            'optionname' => 'None',
                            'sortorder' => $subOrder++,
                            'hidden' => 0
                        ]);
                        
                        // None iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼cretsiz fiyat
                        $currencies = Capsule::table('tblcurrencies')->get();
                        foreach ($currencies as $currency) {
                            Capsule::table('tblpricing')->insert([
                                'type' => 'configoptions',
                                'currency' => $currency->id,
                                'relid' => $noneSubId,
                                'msetupfee' => 0, 'qsetupfee' => 0, 'ssetupfee' => 0,
                                'asetupfee' => 0, 'bsetupfee' => 0, 'tsetupfee' => 0,
                                'monthly' => 0, 'quarterly' => 0, 'semiannually' => 0,
                                'annually' => 0, 'biennially' => 0, 'triennially' => 0
                            ]);
                        }
                        
                        // Lisans seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§enekleri
                    foreach ($form['items'] as $item) {
                            $subId = Capsule::table('tblproductconfigoptionssub')->insertGetId([
                                'configid' => $optionId,
                                'optionname' => $item['title'],
                                'sortorder' => $subOrder++,
                                'hidden' => 0
                            ]);
                            
                            // FiyatlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ekle
                            foreach ($currencies as $currency) {
                                $price = $item['unit_price'];
                                Capsule::table('tblpricing')->insert([
                                    'type' => 'configoptions',
                                    'currency' => $currency->id,
                                    'relid' => $subId,
                                    'msetupfee' => 0, 'qsetupfee' => 0, 'ssetupfee' => 0,
                                    'asetupfee' => 0, 'bsetupfee' => 0, 'tsetupfee' => 0,
                                    'monthly' => $price,
                                    'quarterly' => $price * 3,
                                    'semiannually' => $price * 6,
                                    'annually' => $price * 12,
                                    'biennially' => $price * 24,
                                    'triennially' => $price * 36
                                ]);
                            }
                        }
                    }
                    break;
            }
        }
        
        WLS_debugLog("Debug - Configurable options created successfully");
        return true;
        
    } catch (Exception $e) {
        WLS_debugLog("Error creating configurable options: " . $e->getMessage());
        return false;
    }
}

// Configurable Options fiyatlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
function WhiteLabelServices_UpdateConfigurableOptionsPricing($productId, $formConfig) {
    try {
        WLS_debugLog("Debug - Updating configurable options pricing for product: " . $productId);
        
        // Mevcut configurable options'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± bul
        $links = Capsule::table('tblproductconfiglinks')
            ->where('pid', $productId)
            ->get();
            
        foreach ($links as $link) {
            $group = Capsule::table('tblproductconfiggroups')
                ->where('id', $link->gid)
                ->first();
                
            if (!$group) continue;
            
            // Form config'den bu gruba ait form'u bul
            $matchingForm = null;
            foreach ($formConfig as $form) {
                if ($form['title'] === $group->name) {
                    $matchingForm = $form;
                    break;
                }
            }
            
            if (!$matchingForm) continue;
            
            WLS_debugLog("Debug - Updating pricing for group: " . $group->name);
            
            // Bu grubun options'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
            $options = Capsule::table('tblproductconfigoptions')
                ->where('gid', $link->gid)
                ->get();
                
            foreach ($options as $option) {
                // Sub options'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
                $subOptions = Capsule::table('tblproductconfigoptionssub')
                    ->where('configid', $option->id)
                    ->get();
                    
                foreach ($subOptions as $subOption) {
                    // API'den gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncel fiyatÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± hesapla
                    $newPrice = WhiteLabelServices_CalculateOptionPrice($matchingForm, $subOption->optionname);
                    
                    if ($newPrice !== null) {
                        // Mevcut fiyatlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                        $currencies = Capsule::table('tblcurrencies')->get();
                        foreach ($currencies as $currency) {
                            Capsule::table('tblpricing')
                                ->where('type', 'configoptions')
                                ->where('currency', $currency->id)
                                ->where('relid', $subOption->id)
                                ->update([
                                    'monthly' => $newPrice,
                                    'quarterly' => $newPrice * 3,
                                    'semiannually' => $newPrice * 6,
                                    'annually' => $newPrice * 12,
                                    'biennially' => $newPrice * 24,
                                    'triennially' => $newPrice * 36
                                ]);
                        }
                        
                        WLS_debugLog("Debug - Updated pricing for: " . $subOption->optionname . " to $" . $newPrice);
                    }
                }
            }
        }
        
        WLS_debugLog("Debug - Pricing update completed for product: " . $productId);
        return true;
        
    } catch (Exception $e) {
        WLS_debugLog("Error updating pricing: " . $e->getMessage());
        return false;
    }
}

// Option fiyatÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± hesapla
function WhiteLabelServices_CalculateOptionPrice($form, $optionName) {
    try {
        $formVariable = $form['variable'] ?? '';
        
        switch ($form['type']) {
                case 'slider':
                // IP, Storage, Backup iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
                if (isset($form['items']) && count($form['items']) > 0) {
                    $item = reset($form['items']); // ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°lk item'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
                    $unitPrice = $item['unit_price'];
                    
                    // Option name'den sayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±yÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±kar
                    if (preg_match('/(\d+)/', $optionName, $matches)) {
                        $quantity = intval($matches[1]);
                        
                        // ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“zel durumlar
                        switch ($formVariable) {
                            case 'ipamlimit':
                                return ($quantity == 1) ? 0 : $quantity * $unitPrice;
                            case 'additional_storage':
                            case 'backuplimit':
                                return ($quantity == 0) ? 0 : $quantity * $unitPrice;
                        }
                    }
                    }
                    break;
                    
                case 'multicheckbox':
                // Server Licenses iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
                if ($optionName === 'None') {
                    return 0;
                }
                
                // Form items'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan fiyatÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± bul
                if (isset($form['items'])) {
                    foreach ($form['items'] as $item) {
                        if (strpos($optionName, $item['title']) !== false) {
                            return $item['unit_price'];
                        }
                    }
                    }
                    break;
            }

        return null; // Fiyat bulunamadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±
        
    } catch (Exception $e) {
        WLS_debugLog("Error calculating price: " . $e->getMessage());
        return null;
    }
}

// Gunluk fiyat guncelleme fonksiyonu (DailyCronJob hook: hooks.php)
function WhiteLabelServices_DailyPriceUpdate() {
    try {
        WLS_debugLog("Debug - Daily price update started");
        
        // WLS ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nlerini bul
        $products = Capsule::table('tblproducts')
            ->where('servertype', 'WhiteLabelServices')
            ->get();

        $updatedCount = 0;

        foreach ($products as $product) {
            if (!WhiteLabelServices_ProductHasWlsSetup($product)) {
                continue;
            }
            if (WhiteLabelServices_UpdateSingleProductPricing($product)) {
                $updatedCount++;
            }
        }
        
        WLS_debugLog("Debug - Daily price update completed. Updated " . $updatedCount . " products.");
        return $updatedCount;
        
    } catch (Exception $e) {
        WLS_debugLog("Error in daily price update: " . $e->getMessage());
        return false;
    }
}

// Tek ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n fiyat gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelleme fonksiyonu (kod tekrarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nler)
function WhiteLabelServices_UpdateSingleProductPricing($product) {
    try {
        $formConfig = WhiteLabelServices_DecodeFormConfigFromSource($product);
        if (!$formConfig) {
            return false;
        }
        
        // API'den gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncel form verilerini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) return false;
        
        $params = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($params);
        if (!$token) return false;
        
        $apiProductId = WhiteLabelServices_ExtractProductId(WhiteLabelServices_ResolveWlsApiProductRaw($product));
        if (!$apiProductId) {
            return false;
        }
        
        // API'den gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncel ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n verilerini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $updatedFormConfig = WLS_FetchProductConfig($apiProductId, $token);
        if (!$updatedFormConfig) return false;
        
        $formCol = WhiteLabelServices_FormConfigStorageColumnName();
        Capsule::table('tblproducts')
            ->where('id', $product->id)
            ->update(array(
                $formCol => json_encode($updatedFormConfig),
            ));
            
        // FiyatlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        WhiteLabelServices_UpdateConfigurableOptionsPricing($product->id, $updatedFormConfig);
        
        WLS_debugLog("Debug - Updated pricing for product: " . $product->name . " (ID: " . $product->id . ")");
        return true;
        
    } catch (Exception $e) {
        WLS_debugLog("Error updating single product pricing: " . $e->getMessage());
        return false;
    }
}

// API'den ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼n config'i ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ekme fonksiyonu (kod tekrarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nler)
function WhiteLabelServices_FetchProductConfig($productId, $token) {
    try {
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/order/" . $productId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Standart timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) return false;
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['product']['config']['forms'])) return false;
        
        // Form config'i oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
        $formConfig = [];
        foreach ($data['product']['config']['forms'] as $form) {
            $formConfig[$form['id']] = [
                'id' => $form['id'],
                'type' => $form['type'],
                'title' => $form['title'],
                'variable' => $form['metadata']['variable'] ?? '',
                'required' => $form['required'] ?? false,
                'sort_order' => $form['metadata']['sort_order'] ?? 0,
                'items' => []
            ];
            
            if (isset($form['items']) && is_array($form['items'])) {
                foreach ($form['items'] as $item) {
                    $formConfig[$form['id']]['items'][$item['id']] = [
                        'id' => $item['id'],
                        'title' => $item['title'],
                        'value' => $item['value'] ?? null,
                        'price' => $item['price'] ?? 0,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'selected' => $item['selected'] ?? false
                    ];
                }
            }
        }
        
        return $formConfig;
        
    } catch (Exception $e) {
        WLS_debugLog("Error fetching product config: " . $e->getMessage());
        return false;
    }
}

/**
 * Map a form/item mapping into portal upgrade "resources" payload shape.
 */
function WhiteLabelServices_ApplyMappingToUpgradeResources(array &$resources, array $mapping, array $formConfig) {
    $formId = (string) ($mapping['form_id'] ?? '');
    $itemId = (string) ($mapping['item_id'] ?? '');
    if ($formId === '' || $itemId === '') {
        return;
    }

    $formType = '';
    foreach ($formConfig as $form) {
        if ((string) ($form['id'] ?? '') === $formId) {
            $formType = (string) ($form['type'] ?? '');
            break;
        }
    }

    if (in_array($formType, ['slider', 'qty'], true)) {
        $resources[$formId] = (string) (int) ($mapping['quantity'] ?? 1);
        return;
    }

    if ($formType === 'servicegroupselector') {
        $resources[$formId] = [$itemId => 'main'];
        return;
    }

    $qty = $mapping['quantity'] ?? 1;
    if (is_numeric($qty) && (int) $qty > 1) {
        $resources[$formId] = [$itemId => (string) (int) $qty];
        return;
    }

    $resources[$formId] = $itemId;
}

/**
 * Build portal POST /service/@id/upgrade "resources" from WHMCS hosting params.
 */
function WhiteLabelServices_BuildUpgradeResourcesFromParams(array $params, $packageProductId = null) {
    $source = $params;
    if ($packageProductId) {
        $product = Capsule::table('tblproducts')->where('id', (int) $packageProductId)->first();
        if ($product) {
            $source = WhiteLabelServices_ParamsMergeProductConfigOptions($product, $params);
        }
    }

    $formConfig = WhiteLabelServices_DecodeFormConfigFromSource($source);
    if (!$formConfig || !is_array($formConfig)) {
        WLS_debugLog('BuildUpgradeResources - No form config for service ' . ($params['serviceid'] ?? '?'));
        return [];
    }

    $resources = [];

    if (!empty($params['customfields']) && is_array($params['customfields'])) {
        foreach ($params['customfields'] as $fieldName => $fieldValue) {
            if ($fieldValue === '' || $fieldValue === null) {
                continue;
            }
            $mapping = WhiteLabelServices_MapCustomFieldToItemId($formConfig, $fieldName, $fieldValue);
            if ($mapping) {
                WhiteLabelServices_ApplyMappingToUpgradeResources($resources, $mapping, $formConfig);
            }
        }
    }

    if (!empty($params['configoptions']) && is_array($params['configoptions'])) {
        foreach ($params['configoptions'] as $optionName => $selectedValue) {
            if ($selectedValue === '' || $selectedValue === null || $selectedValue === 'None') {
                continue;
            }

            $quantity = $selectedValue;
            if (preg_match('/(\d+)/', (string) $selectedValue, $matches)) {
                $quantity = (int) $matches[1];
            }

            $mapping = WhiteLabelServices_MapConfigurableOptionToItemId($formConfig, $optionName, $quantity);
            if ($mapping) {
                WhiteLabelServices_ApplyMappingToUpgradeResources($resources, $mapping, $formConfig);
            }
        }
    }

    WLS_debugLog('BuildUpgradeResources - service ' . ($params['serviceid'] ?? '?') . ': ' . json_encode($resources));
    return $resources;
}

/**
 * Build POST body for portal /api/service/{id}/upgrade from WHMCS service state.
 */
function WhiteLabelServices_BuildServiceUpgradePostData($wlsServiceId, array $serviceParams, array $options = []) {
    $packageProductId = isset($options['package_product_id']) ? (int) $options['package_product_id'] : 0;
    $billingCycle = $options['billing_cycle'] ?? ($serviceParams['billingcycle'] ?? '');

    $resources = WhiteLabelServices_BuildUpgradeResourcesFromParams(
        $serviceParams,
        $packageProductId > 0 ? $packageProductId : null
    );

    $postData = [
        'id' => (int) $wlsServiceId,
        'resources' => empty($resources) ? (object) [] : $resources,
        'send' => true,
    ];

    if (isset($options['wls_package_id']) && $options['wls_package_id'] !== null && $options['wls_package_id'] !== false && $options['wls_package_id'] !== '') {
        $postData['package'] = (int) $options['wls_package_id'];
    } elseif ($packageProductId > 0) {
        $product = Capsule::table('tblproducts')->where('id', $packageProductId)->first();
        if ($product) {
            $mappedPackage = WhiteLabelServices_ExtractProductId(WhiteLabelServices_ResolveWlsApiProductRaw($product));
            if ($mappedPackage) {
                $postData['package'] = (int) $mappedPackage;
            }
        }
    }

    if ($billingCycle !== '') {
        $postData['cycle'] = WhiteLabelServices_ConvertBillingCycle($billingCycle);
    }

    return $postData;
}

// Manuel fiyat guncelleme fonksiyonu (AfterCronJob hook: hooks.php)
function WhiteLabelServices_ProcessUpdateTasks() {
    try {
        WLSTokenManager::ensureTablesExist();
        $tasks = WLSTokenManager::getPendingUpdateTasks(50);
        if (empty($tasks)) return 0;

        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
        if (!$server) {
            WLS_debugLog("ProcessUpdateTasks - No active WLS server found");
            return 0;
        }

        $params = [
            'serverid' => $server->id,
            'serverhostname' => $server->hostname,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password),
        ];
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            WLS_debugLog("ProcessUpdateTasks - Failed to get API token");
            return 0;
        }

        $apiBaseUrl = rtrim(WhiteLabelServices_getApiBaseUrl($params), '/');
        $processed = 0;

        foreach ($tasks as $task) {
            $whmcsServiceId = (int) $task->service_id;
            $vps = Capsule::table('mod_wls_vps')->where('id', $whmcsServiceId)->first();
            if (!$vps || empty($vps->wls_service_id)) {
                WLSTokenManager::setUpdateTaskStatus($task->id, 'failed', 'WLS service ID not found for WHMCS service ' . $whmcsServiceId);
                WLS_debugLog("ProcessUpdateTasks - Task #" . $task->id . " no wls_service_id for service " . $whmcsServiceId);
                continue;
            }

            $payload = $task->payload ? json_decode($task->payload, true) : [];
            if (!is_array($payload)) {
                $payload = [];
            }

            $serviceParams = WhiteLabelServices_BuildModuleParamsFromHostingId($whmcsServiceId);
            if (!$serviceParams) {
                WLSTokenManager::setUpdateTaskStatus($task->id, 'failed', 'Could not load WHMCS service params for service ' . $whmcsServiceId);
                WLS_debugLog('ProcessUpdateTasks - Task #' . $task->id . ' missing service params');
                continue;
            }

            $postData = WhiteLabelServices_BuildServiceUpgradePostData(
                (int) $vps->wls_service_id,
                $serviceParams,
                [
                    'billing_cycle' => $serviceParams['billingcycle'] ?? '',
                    'wls_package_id' => $payload['package'] ?? null,
                ]
            );

            $url = $apiBaseUrl . '/api/service/' . (int) $vps->wls_service_id . '/upgrade';
            $response = WhiteLabelServices_APIRequest($url, $token, 'POST', $postData);

            if ($response === null) {
                WLSTokenManager::setUpdateTaskStatus($task->id, 'paid', 'API request failed');
                WLS_debugLog("ProcessUpdateTasks - Task #" . $task->id . " API request failed");
                continue;
            }

            $responseData = is_array($response) ? $response : (is_string($response) ? json_decode($response, true) : []);
            if (is_array($responseData) && isset($responseData['success']) && $responseData['success'] === false) {
                $errMsg = $responseData['message'] ?? $responseData['error'] ?? $response;
                WLSTokenManager::setUpdateTaskStatus($task->id, 'paid', is_string($errMsg) ? $errMsg : json_encode($errMsg));
                WLS_debugLog("ProcessUpdateTasks - Task #" . $task->id . " API success=false: " . (is_string($errMsg) ? $errMsg : json_encode($errMsg)));
                continue;
            }

            WLSTokenManager::setUpdateTaskStatus($task->id, 'completed');
            $processed++;
            WLS_debugLog("ProcessUpdateTasks - Task #" . $task->id . " completed (invoice #" . $task->invoice_id . ", service #" . $whmcsServiceId . ")");
        }

        return $processed;
    } catch (Exception $e) {
        WLS_debugLog("ProcessUpdateTasks Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Pending mod_wls_upgrade_tasks kayitlarini isle: portal POST /api/service/@id/upgrade cagir.
 */
function WhiteLabelServices_ProcessUpgradeTasks() {
    try {
        WLSTokenManager::ensureTablesExist();
        $tasks = WLSTokenManager::getPendingUpgradeTasks(50);
        if (empty($tasks)) return 0;

        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
        if (!$server) {
            WLS_debugLog("ProcessUpgradeTasks - No active WLS server found");
            return 0;
        }

        $params = [
            'serverid' => $server->id,
            'serverhostname' => $server->hostname,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password),
        ];
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            WLS_debugLog("ProcessUpgradeTasks - Failed to get API token");
            return 0;
        }

        $apiBaseUrl = rtrim(WhiteLabelServices_getApiBaseUrl($params), '/');
        $processed = 0;

        foreach ($tasks as $task) {
            $whmcsServiceId = (int) $task->service_id;
            $vps = Capsule::table('mod_wls_vps')->where('id', $whmcsServiceId)->first();
            if (!$vps || empty($vps->wls_service_id)) {
                WLSTokenManager::setUpgradeTaskStatus($task->id, 'Failed', 'WLS service ID not found for service ' . $whmcsServiceId);
                WLS_debugLog("ProcessUpgradeTasks - Task #" . $task->id . " no wls_service_id");
                continue;
            }

            $data = $task->data ? json_decode($task->data, true) : [];
            if (!is_array($data)) {
                $data = [];
            }

            $packageId = (int) ($data['package_id'] ?? 0);
            $wlsPackageId = null;
            if ($packageId) {
                $product = Capsule::table('tblproducts')->where('id', $packageId)->first();
                if ($product) {
                    $wlsPackageId = WhiteLabelServices_ExtractProductId(WhiteLabelServices_ResolveWlsApiProductRaw($product));
                }
            }

            $serviceParams = WhiteLabelServices_BuildModuleParamsFromHostingId($whmcsServiceId);
            if (!$serviceParams) {
                WLSTokenManager::setUpgradeTaskStatus($task->id, 'Failed', 'Could not load WHMCS service params for service ' . $whmcsServiceId);
                WLS_debugLog('ProcessUpgradeTasks - Task #' . $task->id . ' missing service params');
                continue;
            }

            $postData = WhiteLabelServices_BuildServiceUpgradePostData(
                (int) $vps->wls_service_id,
                $serviceParams,
                [
                    'package_product_id' => $packageId,
                    'billing_cycle' => $data['billingcycle'] ?? ($serviceParams['billingcycle'] ?? ''),
                    'wls_package_id' => $wlsPackageId,
                ]
            );

            $url = $apiBaseUrl . '/api/service/' . (int) $vps->wls_service_id . '/upgrade';
            $response = WhiteLabelServices_APIRequest($url, $token, 'POST', $postData);

            if ($response === null) {
                WLSTokenManager::setUpgradeTaskStatus($task->id, 'Pending', 'API request failed');
                WLS_debugLog("ProcessUpgradeTasks - Task #" . $task->id . " API request failed");
                continue;
            }

            $responseData = is_array($response) ? $response : (is_string($response) ? json_decode($response, true) : []);
            if (is_array($responseData) && isset($responseData['success']) && $responseData['success'] === false) {
                $errMsg = $responseData['message'] ?? $responseData['error'] ?? json_encode($responseData);
                WLSTokenManager::setUpgradeTaskStatus($task->id, 'Failed', is_string($errMsg) ? $errMsg : json_encode($errMsg));
                WLS_debugLog("ProcessUpgradeTasks - Task #" . $task->id . " API success=false");
                continue;
            }

            WLSTokenManager::setUpgradeTaskStatus($task->id, 'Completed');
            $processed++;
            WLS_debugLog("ProcessUpgradeTasks - Task #" . $task->id . " completed (service #" . $whmcsServiceId . ")");
        }

        return $processed;
    } catch (Exception $e) {
        WLS_debugLog("ProcessUpgradeTasks Error: " . $e->getMessage());
        return 0;
    }
}

function WhiteLabelServices_ManualPriceUpdate($productId = null) {
    try {
        WLS_debugLog("Debug - Manual price update started" . ($productId ? " for product: " . $productId : ""));
        
        $query = Capsule::table('tblproducts')
            ->where('servertype', 'WhiteLabelServices');
            
        if ($productId) {
            $query->where('id', $productId);
        }
        
        $products = $query->get();
        $updatedCount = 0;
        
        foreach ($products as $product) {
            if (!WhiteLabelServices_ProductHasWlsSetup($product)) {
                continue;
            }
            if (WhiteLabelServices_UpdateSingleProductPricing($product)) {
                $updatedCount++;
            }
        }

        WLS_debugLog("Debug - Manual price update completed. Updated " . $updatedCount . " products.");
        return $updatedCount;
        
    } catch (Exception $e) {
        WLS_debugLog("Error in manual price update: " . $e->getMessage());
        return false;
    }
}

// Custom field mapping (ClientAreaHeadOutput hook: hooks.php)
function WhiteLabelServices_MapCustomFieldToItemId($formConfig, $fieldName, $fieldValue) {
    try {
        // Field name'e gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶re form'u bul
        $targetForm = null;
        foreach ($formConfig as $form) {
            // Form'u simÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼le et (metadata yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur)
            $simulatedForm = [
                'id' => $form['id'],
                'type' => $form['type'],
                'title' => $form['title'],
                'metadata' => [
                    'variable' => $form['variable'] ?? ''
                ]
            ];
            
            $expectedFieldName = WhiteLabelServices_generateFieldName($simulatedForm);
            if ($expectedFieldName === $fieldName) {
                $targetForm = $form;
                    break;
            }
        }
        
        if (!$targetForm) {
            WLS_debugLog("Debug - Form not found for field: " . $fieldName);
            return null;
        }
        
        // Text'i normalize et (boÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸luklarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± temizle)
        $normalizedValue = preg_replace('/[,\s]+/', ' ', trim($fieldValue));
        
        // Form items'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nda deÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸eri ara
        if (isset($targetForm['items'])) {
            foreach ($targetForm['items'] as $item) {
                $normalizedItemTitle = preg_replace('/[,\s]+/', ' ', trim($item['title']));
                if ($normalizedItemTitle === $normalizedValue) {
                    WLS_debugLog("Debug - Mapped field: " . $fieldName . " = " . $fieldValue . " ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€Â¢ Form:" . $targetForm['id'] . ", Item:" . $item['id']);
        return [
                        'form_id' => $targetForm['id'],
                        'item_id' => $item['id']
                    ];
                }
            }
        }
        
        WLS_debugLog("Debug - Item not found for value: " . $fieldValue . " in form: " . $targetForm['title']);
        return null;
        
    } catch (Exception $e) {
        WLS_debugLog("Error mapping custom field: " . $e->getMessage());
        return null;
    }
}

// Configurable option deÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸erini form item ID'sine dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r
function WhiteLabelServices_MapConfigurableOptionToItemId($formConfig, $optionName, $quantity) {
    try {
        $targetForm = null;
        foreach ($formConfig as $form) {
            $apiShape = WhiteLabelServices_StoredFormConfigToApiFormShape($form);
            if (WhiteLabelServices_ConfigOptionKeyExactMatchOrderForm($apiShape, $optionName)) {
                $targetForm = $form;
                break;
            }
        }
        if (!$targetForm) {
            foreach ($formConfig as $form) {
                $apiShape = WhiteLabelServices_StoredFormConfigToApiFormShape($form);
                if (WhiteLabelServices_ConfigOptionKeyFuzzyMatchOrderForm($apiShape, $optionName)) {
                    $targetForm = $form;
                    break;
                }
            }
        }

        if (!$targetForm) {
            WLS_debugLog("Debug - Form not found for configurable option: " . $optionName);
            return null;
        }
        
        $formVariable = $targetForm['variable'] ?? '';
        
        switch ($targetForm['type']) {
            case 'slider':
            case 'qty':
                if (isset($targetForm['items']) && count($targetForm['items']) > 0) {
                    $item = reset($targetForm['items']);
                    return [
                        'form_id' => $targetForm['id'],
                        'item_id' => $item['id'],
                        'quantity' => $quantity
                    ];
                }
                    break;
                
            case 'multicheckbox':
                // Server Licenses iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in - seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ilen lisansÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±n item ID'sini bul
                if ($quantity === 'None') {
                    // None seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§eneÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶zel durum - genelde ilk item
                    if (isset($targetForm['items']) && count($targetForm['items']) > 0) {
                        $firstItem = reset($targetForm['items']);
                        return [
                            'form_id' => $targetForm['id'],
                            'item_id' => $firstItem['id'],
                            'quantity' => 0
                        ];
                    }
                } else {
                    // Lisans adÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan item ID'sini bul
                    if (isset($targetForm['items'])) {
                        foreach ($targetForm['items'] as $item) {
                            if (strpos($quantity, $item['title']) !== false) {
                                return [
                                    'form_id' => $targetForm['id'],
                                    'item_id' => $item['id'],
                                    'quantity' => 1
                                ];
                            }
                        }
                    }
                }
                break;
        }
        
        WLS_debugLog("Debug - Could not map configurable option: " . $optionName . " = " . $quantity);
        return null;
        
    } catch (Exception $e) {
        WLS_debugLog("Error mapping configurable option: " . $e->getMessage());
        return null;
    }
}

// WHMCS billing cycle'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± WLS API cycle'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±na dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r
function WhiteLabelServices_ConvertBillingCycle($whmcsCycle) {
    $cycleMap = [
        'Monthly' => 'm',
        'Quarterly' => 'q', 
        'Semi-Annually' => 'q', // 6 aylÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±k iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in quarterly kullan
        'Annually' => 'a',
        'Biennially' => 'b',
        'Triennially' => 't',
        'Free Account' => 'm', // ÃƒÆ’Ã†â€™Ãƒâ€¦Ã¢â‚¬Å“cretsiz hesaplar iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in monthly
        'One Time' => 'm' // Tek seferlik iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in monthly
    ];
    
    return $cycleMap[$whmcsCycle] ?? 'm'; // VarsayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lan monthly
}

// SipariÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in API payload'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
function WhiteLabelServices_BuildOrderPayload($params) {
    try {
        WLS_debugLog("Debug - Building order payload for service: " . $params['serviceid']);
        
        $formConfig = WhiteLabelServices_DecodeFormConfigFromSource($params);

        $productId = WhiteLabelServices_ExtractProductId(WhiteLabelServices_ResolveWlsApiProductRaw($params));
        
        // Temel payload
        $domainVal = !empty($params['domain']) ? $params['domain'] : 'wls-vm-' . time() . '-' . $params['serviceid'];
        $payload = [
            'product_id' => $productId,
            'domain' => $domainVal,
            'hostname' => $domainVal,
            'cycle' => WhiteLabelServices_ConvertBillingCycle($params['billingcycle'] ?? 'monthly'),
            'pay_method' => '120', // Default payment method
            'custom' => []
        ];
        
        $promoCode = WhiteLabelServices_ResolvePromoCodeFromParams($params);
        if ($promoCode === '') {
            // Ayarlardan ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
            try {
                $promoSetting = Capsule::table('mod_wls_settings')
                    ->where('setting_key', 'promo_code')
                    ->first();
                if ($promoSetting && !empty($promoSetting->setting_value)) {
                    $promoCode = trim($promoSetting->setting_value);
                }
            } catch (Exception $e) {
                // Ayarlar tablosu yoksa sessizce devam et
            }
        }
        
        if (!empty($promoCode)) {
            $payload['promocode'] = $promoCode;
            WLS_debugLog("Debug - Promocode added: " . $promoCode);
        }
        
        // Form yapÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±landÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rmasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± yoksa sadece temel payload ile devam et
        if (!$formConfig) {
            WLS_debugLog("Debug - No form config found, using basic payload");
            return $payload;
        }
        
        // Custom field'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
        if (!empty($params['customfields'])) {
            foreach ($params['customfields'] as $fieldName => $fieldValue) {
                if (empty($fieldValue)) continue;
                
                $mapping = WhiteLabelServices_MapCustomFieldToItemId($formConfig, $fieldName, $fieldValue);
                if ($mapping) {
                    $payload['custom'][$mapping['form_id']] = [
                        $mapping['item_id'] => 1
                    ];
                    WLS_debugLog("Debug - Mapped custom field: " . $fieldName . " = " . $fieldValue . " ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€Â¢ Form:" . $mapping['form_id'] . ", Item:" . $mapping['item_id']);
                }
            }
        }
        
        // Configurable options'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
        if (!empty($params['configoptions'])) {
            foreach ($params['configoptions'] as $optionName => $selectedValue) {
                if (empty($selectedValue) || $selectedValue === 'None') {
                    continue; // None seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§eneklerini atla
                }
                
                // SeÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ilen deÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸erden quantity'yi ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±kar
                $quantity = $selectedValue;
                if (preg_match('/(\d+)/', $selectedValue, $matches)) {
                    $quantity = intval($matches[1]);
                } else {
                    $quantity = $selectedValue;
                }
                
                $mapping = WhiteLabelServices_MapConfigurableOptionToItemId($formConfig, $optionName, $quantity);
                if ($mapping) {
                    $payload['custom'][$mapping['form_id']] = [
                        $mapping['item_id'] => $mapping['quantity']
                    ];
                    WLS_debugLog("Debug - Mapped configurable option: " . $optionName . " = " . $selectedValue . " ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€Â¢ Form:" . $mapping['form_id'] . ", Item:" . $mapping['item_id'] . ", Qty:" . $mapping['quantity']);
                }
            }
        }
        
        WLS_debugLog("Debug - Order payload built: " . json_encode($payload));
        return $payload;
        
    } catch (Exception $e) {
        WLS_debugLog("Error building order payload: " . $e->getMessage());
        return null;
    }
}

// Arka planda pending sipariÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸leri iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
function WhiteLabelServices_ProcessPendingOrders() {
    try {
        WLS_debugLog("Debug - Processing pending orders");
        
        // Pending durumundaki WLS servislerini bul
        $pendingServices = Capsule::table('tblhosting')
            ->where('domainstatus', 'Active')
            ->whereIn('packageid', function($query) {
                $query->select('id')
                      ->from('tblproducts')
                      ->where('servertype', 'WhiteLabelServices');
            })
            ->get();
            
        WLS_debugLog("Debug - Found " . count($pendingServices) . " active WLS services to check");
        
        $processedCount = 0;
        
        foreach ($pendingServices as $service) {
            // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
            $vpsDetails = WLSTokenManager::getVPSDetails($service->id);
            
            // Sadece WLS service ID'si olan ama VM'i henÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼z hazÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r olmayan servisleri iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
            if ($vpsDetails && $vpsDetails->wls_service_id && 
                (!$vpsDetails->vm_status || $vpsDetails->vm_status !== 'running' || 
                 !$vpsDetails->vm_built)) {
                
                // Token al
                $server = Capsule::table('tblservers')
                    ->where('type', 'WhiteLabelServices')
                    ->where('active', '1')
                    ->first();
                    
                if (!$server) continue;
                
                $params = [
                    'serverid' => $server->id,
                    'serverusername' => $server->username,
                    'serverpassword' => decrypt($server->password)
                ];
                
                $token = WhiteLabelServices_getToken($params);
                if (!$token) continue;
                
                // VM status check task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§aÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
                $vmParams = ['serviceid' => $service->id];
                $vmResult = WhiteLabelServices_check_vm_status($vmParams);
                if ($vmResult === 'success' || $vmResult === true) {
                    $processedCount++;
                    WLS_debugLog("Debug - Processed VM status for service: " . $service->id);
                }
            }
        }
        
        WLS_debugLog("Debug - Finished processing pending orders. Processed: " . $processedCount);
        return $processedCount;
        
    } catch (Exception $e) {
        WLS_debugLog("ProcessPendingOrders Error: " . $e->getMessage());
        return 0;
    }
}

// WLS servis durumunu kontrol et
function WhiteLabelServices_CheckServiceStatus($serviceId, $token) {
    return WLSTokenManager::checkServiceStatus($serviceId, $token);
}

// WLS VM listesini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
function WhiteLabelServices_getVMList($serviceId, $token) {
    return WLSTokenManager::getVMList($serviceId, $token);
}

// WLS VM detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
function WhiteLabelServices_getVMDetails($whmcsServiceId, $vmId, $token) {
    try {
        // Debug log ekle
        WLS_debugLog("Debug - Getting VM details for WHMCS Service ID: " . $whmcsServiceId);
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
        $vpsDetails = WLSTokenManager::getVPSDetails($whmcsServiceId);
        if (!$vpsDetails) {
            throw new Exception("VPS kaydÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± bulunamadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± (WHMCS Service ID: " . $whmcsServiceId . ")");
        }

        // wls_service_id kontrolÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼
        if (!$vpsDetails->wls_service_id) {
            throw new Exception("WLS Service ID bulunamadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± (WHMCS Service ID: " . $whmcsServiceId . ")");
        }

        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/service/" . $vpsDetails->wls_service_id . "/vms/" . $vmId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception("CURL Error: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API Error: HTTP " . $httpCode);
        }
        
        return json_decode($response, true);
        
    } catch (Exception $e) {
        WLS_debugLog("getVMDetails Error: " . $e->getMessage());
        return null;
    }
}

// ============ WHMCS MODULE QUEUE SYSTEM ============

// WHMCS Module Queue'ya task ekle
function WhiteLabelServices_AddToQueue($action, $data, $priority = 1, $scheduledAt = null) {
    try {
        // check_vm_status için WHMCS module queue kullanma (kendi cron döngümüzle yöneteceğiz)
        if ($action === 'check_vm_status') {
            WLS_debugLog("Queue - Skipping WHMCS module queue for check_vm_status, will be handled by custom cron loop");
            return 0;
        }
        // EÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸er scheduledAt verilmiÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸se, last_attempt olarak kullan (WHMCS bu zamanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± geÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ene kadar bekler)
        $lastAttempt = null;
        if ($scheduledAt && $scheduledAt > time()) {
            $lastAttempt = date('Y-m-d H:i:s', $scheduledAt);
        }
        
        // WHMCS'nin gerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek module queue sistemini kullan
        $moduleQueueId = Capsule::table('tblmodulequeue')->insertGetId([
            'service_type' => 'hosting',
            'service_id' => $data['service_id'] ?? 0,
            'module_name' => 'WhiteLabelServices',
            'module_action' => $action,
            'last_attempt' => $lastAttempt,
            'last_attempt_error' => '',
            'num_retries' => 0,
            'completed' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Data'yÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ayrÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± tabloda sakla (tblhosting.notes yerine)
        WLSTokenManager::addQueueData($moduleQueueId, $data);
        
        WLS_debugLog("Queue - Added task to WHMCS module queue: " . $action . " (ID: " . $moduleQueueId . ")" . ($lastAttempt ? " scheduled for: " . $lastAttempt : ""));
        return $moduleQueueId;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue Error - Failed to add task to WHMCS queue: " . $e->getMessage());
        return false;
    }
}

/**
 * WHMCS cron'u üzerinden, module queue KULLANMADAN pending VM durumlarını kontrol et
 * check_vm_status için kendi döngümüz
 */
function WhiteLabelServices_ProcessPendingVMChecks() {
    try {
        WLSTokenManager::ensureTablesExist();

        // Sadece WLS ürünü + iptal edilmemiş hosting; aksi halde OR koşulu tüm vm_built=0 satırlarını (gürültü) çekerdi
        $pending = Capsule::table('mod_wls_vps as v')
            ->join('tblhosting as h', 'h.id', '=', 'v.id')
            ->join('tblproducts as p', 'p.id', '=', 'h.packageid')
            ->where('p.servertype', 'WhiteLabelServices')
            ->whereNotIn('h.domainstatus', ['Cancelled', 'Terminated'])
            ->where(function ($q) {
                $q->whereIn('v.status', ['Pending', 'provisioning'])
                    ->orWhereNull('v.vm_built')
                    ->orWhere('v.vm_built', '=', 0);
            })
            ->select('v.*')
            ->limit(50)
            ->get();

        if (!$pending || count($pending) === 0) {
            return 0;
        }

        $processed = 0;

        foreach ($pending as $row) {
            $serviceId = $row->id;
            if (!$serviceId) {
                continue;
            }

            WLS_debugLog("VMCheck Cron - Running check_vm_status for service: " . $serviceId);

            // Sadece serviceid ver, geri kalanını WhiteLabelServices_check_vm_status kendisi dolduruyor
            $result = WhiteLabelServices_check_vm_status(['serviceid' => $serviceId]);

            // Hata almıyorsak processed say
            if ($result === 'success' || stripos((string)$result, 'failed') === false) {
                $processed++;
            }
        }

        return $processed;

    } catch (Exception $e) {
        WLS_debugLog("Error in ProcessPendingVMChecks: " . $e->getMessage());
        return 0;
    }
}

/**
 * Queue Power Action (start/stop/reboot/shutdown/reset)
 * Client Area'dan gelen power eylemleri WHMCS queue'ya eklenir
 */
function WhiteLabelServices_QueuePowerAction($params, $action) {
    try {
        $serviceId = $params['serviceid'];
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± kontrol et
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();
            
        if (!$vpsDetails || empty($vpsDetails->wls_service_id) || empty($vpsDetails->wls_vm_id)) {
            return ['error' => 'VM not provisioned yet'];
        }
        
        // Queue data hazÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rla
        $queueData = [
            'service_id' => $serviceId,
            'wls_service_id' => $vpsDetails->wls_service_id,
            'wls_vm_id' => $vpsDetails->wls_vm_id,
            'action' => $action,
            'requested_at' => date('Y-m-d H:i:s'),
            'requested_by' => 'clientarea'
        ];
        
        // WHMCS Module Queue'ya ekle
        $queueId = WhiteLabelServices_AddToQueue('power_' . $action, $queueData);
        
        if (!$queueId) {
            // Queue baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸arÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±z olursa doÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸rudan ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
            WLS_debugLog("Queue - Failed to queue, executing directly: " . $action);
            return WhiteLabelServices_handleVMAction($params, $action);
        }
        
        // AyrÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ca anÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nda durum gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle (UI iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in)
        $statusMap = [
            'start' => 'starting',
            'stop' => 'stopping',
            'shutdown' => 'shutting_down',
            'reboot' => 'rebooting',
            'reset' => 'resetting'
        ];
        
        Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->update([
                'vm_status' => $statusMap[$action] ?? 'pending',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        
        WLS_debugLog("Queue - Power action queued: " . $action . " for service " . $serviceId . " (Queue ID: " . $queueId . ")");
        
        return [
            'success' => true,
            'message' => ucfirst($action) . ' command queued. Please wait...',
            'queue_id' => $queueId
        ];
        
    } catch (Exception $e) {
        WLS_debugLog("Queue Error - Power action: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Queue Rebuild Action
 * VM rebuild iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lemi WHMCS queue'ya eklenir
 */
function WhiteLabelServices_QueueRebuild($params, $template) {
    try {
        $serviceId = $params['serviceid'];
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± kontrol et
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();
            
        if (!$vpsDetails || empty($vpsDetails->wls_service_id) || empty($vpsDetails->wls_vm_id)) {
            return ['error' => 'VM not provisioned yet'];
        }
        
        // Queue data hazÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rla
        $queueData = [
            'service_id' => $serviceId,
            'wls_service_id' => $vpsDetails->wls_service_id,
            'wls_vm_id' => $vpsDetails->wls_vm_id,
            'template' => $template,
            'requested_at' => date('Y-m-d H:i:s'),
            'requested_by' => 'clientarea'
        ];
        
        // WHMCS Module Queue'ya ekle
        $queueId = WhiteLabelServices_AddToQueue('rebuild', $queueData);
        
        if (!$queueId) {
            // Queue baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸arÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±z olursa doÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸rudan ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
            WLS_debugLog("Queue - Failed to queue rebuild, executing directly");
            return WhiteLabelServices_rebuildVM($params, $template);
        }
        
        // Durumu hemen gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->update([
                'vm_status' => 'rebuilding',
                'vm_built' => false,
                'template' => $template,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        
        WLS_debugLog("Queue - Rebuild queued for service " . $serviceId . " with template: " . $template . " (Queue ID: " . $queueId . ")");
        
        return [
            'success' => true,
            'message' => 'Rebuild queued. This may take several minutes...',
            'queue_id' => $queueId
        ];
        
    } catch (Exception $e) {
        WLS_debugLog("Queue Error - Rebuild: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// WHMCS Module Queue'dan task'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
function WhiteLabelServices_ProcessQueue() {
    try {
        // Cron lock al - duplicate ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸mayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nle
        $cronLockToken = WLSTokenManager::acquireCronLock('wls_queue_process', 120);
        if (!$cronLockToken) {
            WLS_debugLog("Queue - Already running, skipping this execution");
            return 0;
        }
        
        WLS_debugLog("Queue - Processing WHMCS module queue tasks");
        
        // Pending durumundaki WLS task'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
        $tasks = Capsule::table('tblmodulequeue')
            ->where('module_name', 'WhiteLabelServices')
            ->where('completed', 0)
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();
            
        if (empty($tasks) || count($tasks) == 0) {
            WLS_debugLog("Queue - No pending tasks found in WHMCS module queue");
            WLSTokenManager::releaseCronLock('wls_queue_process', $cronLockToken);
            return 0;
        }
        
        WLS_debugLog("Queue - Found " . count($tasks) . " pending tasks in WHMCS module queue");
        $processedCount = 0;
        
        foreach ($tasks as $task) {
            try {
                // Task iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in lock almaya ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ (atomik)
                $taskLockToken = WLSTokenManager::lockQueueData($task->id);
                if (!$taskLockToken) {
                    WLS_debugLog("Queue - Task " . $task->id . " is locked by another process, skipping");
                    continue;
                }
                
                // Task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± processing olarak iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸aretle
                Capsule::table('tblmodulequeue')
                    ->where('id', $task->id)
                    ->update([
                        'last_attempt' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                // Data'yÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± yeni tablodan al
                $data = WLSTokenManager::getQueueData($task->id, $taskLockToken);
                
                if (!$data) {
                    WLS_debugLog("Queue - No data found for task: " . $task->id);
                    // Task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± failed olarak iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸aretle
                    Capsule::table('tblmodulequeue')
                        ->where('id', $task->id)
                        ->update([
                            'completed' => 1,
                            'last_attempt_error' => 'No data found for task',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    WLSTokenManager::deleteQueueData($task->id);
                    continue;
                }
                
                $result = false;
                
                // WHMCS Module Queue action'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
                switch ($task->module_action) {
                    case 'process_order':
                        $result = WhiteLabelServices_ProcessOrderTask($data);
                        break;
                    case 'check_vm_status':
                        // Get WHMCS service params for API calls
                        $whmcsServiceId = $data['service_id'] ?? 0;
                        if ($whmcsServiceId) {
                            $service = Capsule::table('tblhosting')->where('id', $whmcsServiceId)->first();
                            if ($service) {
                                $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
                                $serverParams = [];
                                if ($product && $product->servertype === 'WhiteLabelServices') {
                                    // Get active WLS server for credentials
                                    $server = Capsule::table('tblservers')
                                        ->where('type', 'WhiteLabelServices')
                                        ->where('active', '1')
                                        ->first();
                                    
                                    if ($server) {
                                        // Get server params with credentials for token retrieval
                                        $serverParams = WhiteLabelServices_ParamsMergeProductConfigOptions($product, array(
                                            'serviceid' => $whmcsServiceId,
                                            'serverid' => $server->id,
                                            'serverusername' => $server->username,
                                            'serverpassword' => decrypt($server->password),
                                        ));
                                    } else {
                                        WLS_debugLog("Queue - No active WLS server found for check_vm_status");
                                        $result = false;
                                        break;
                                    }
                                }
                                $result = WhiteLabelServices_ProcessVMStatusCheck($serverParams, $data);
                            } else {
                                WLS_debugLog("Queue - Service not found for check_vm_status: $whmcsServiceId");
                                $result = false;
                            }
                        } else {
                            WLS_debugLog("Queue - No service_id in check_vm_status data");
                            $result = false;
                        }
                        break;
                    case 'CreateAccount':
                        // WHMCS'nin kendi CreateAccount action'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
                        $result = WhiteLabelServices_ProcessOrderTask($data);
                        break;
                    
                    // Power Actions
                    case 'power_start':
                    case 'power_stop':
                    case 'power_shutdown':
                    case 'power_reboot':
                    case 'power_reset':
                        $result = WhiteLabelServices_ProcessPowerActionTask($data);
                        break;
                    
                    // Rebuild Action
                    case 'rebuild':
                        $result = WhiteLabelServices_ProcessRebuildTask($data);
                        break;
                        
                    default:
                        WLS_debugLog("Queue - Unknown action: " . $task->module_action);
                        // Unknown action'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± failed olarak iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸aretle
                        Capsule::table('tblmodulequeue')
                            ->where('id', $task->id)
                            ->update([
                                'completed' => 1,
                                'last_attempt_error' => 'Unknown action: ' . $task->module_action,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        WLSTokenManager::deleteQueueData($task->id);
                        break;
                }
                
                // WLS result sabitlerini kontrol et
                if ($result === WLS_RESULT_SUCCESS || $result === true) {
                    // GerÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ekten tamamlandÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± - task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± kapat
                    Capsule::table('tblmodulequeue')
                        ->where('id', $task->id)
                        ->update([
                            'completed' => 1,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    // Queue data'yÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± temizle
                    WLSTokenManager::deleteQueueData($task->id);
                    
                    $processedCount++;
                    WLS_debugLog("Queue - Task completed: " . $task->module_action . " (ID: " . $task->id . ")");
                } elseif ($result === WLS_RESULT_RESCHEDULED) {
                    // Yeniden zamanlandÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± - mevcut task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± kapat, yeni task zaten oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸turuldu
                    Capsule::table('tblmodulequeue')
                        ->where('id', $task->id)
                        ->update([
                            'completed' => 1,
                            'last_attempt_error' => null,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    // Queue data'yÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± temizle
                    WLSTokenManager::deleteQueueData($task->id);
                    
                    WLS_debugLog("Queue - Task rescheduled: " . $task->module_action . " (ID: " . $task->id . ")");
                } else {
                    // Hata - retry sayÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± artÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
                    $retryCount = ($task->num_retries ?? 0) + 1;
                    if ($retryCount >= 5) {
                        // 5 deneme sonrasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± completed olarak iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸aretle (failed)
                        Capsule::table('tblmodulequeue')
                            ->where('id', $task->id)
                            ->update([
                                'completed' => 1,
                                'num_retries' => $retryCount,
                                'last_attempt_error' => 'Failed after 5 retries',
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        WLSTokenManager::deleteQueueData($task->id);
                        WLS_debugLog("Queue - Task failed after 5 retries: " . $task->module_action . " (ID: " . $task->id . ")");
                    } else {
                        // Tekrar dene - lock'u serbest bÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rak
                        Capsule::table('mod_wls_queue_data')
                            ->where('queue_id', $task->id)
                            ->update([
                                'lock_token' => null,
                                'locked_at' => null
                            ]);
                        
                        Capsule::table('tblmodulequeue')
                            ->where('id', $task->id)
                            ->update([
                                'num_retries' => $retryCount,
                                'last_attempt_error' => 'Retry ' . $retryCount . '/5',
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        WLS_debugLog("Queue - Task retry " . $retryCount . "/5: " . $task->module_action . " (ID: " . $task->id . ")");
                    }
                }
                
            } catch (Exception $e) {
                WLS_debugLog("Queue Error processing task " . $task->id . ": " . $e->getMessage());
                
                // Hata durumunda task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± failed olarak iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸aretle
                Capsule::table('tblmodulequeue')
                    ->where('id', $task->id)
                    ->update([
                        'completed' => 1,
                        'last_attempt_error' => $e->getMessage(),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                WLSTokenManager::deleteQueueData($task->id);
            }
        }
        
        // Cron lock'u serbest bÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rak
        WLSTokenManager::releaseCronLock('wls_queue_process', $cronLockToken);
        
        WLS_debugLog("Queue - Processed " . $processedCount . " tasks successfully from WHMCS module queue");
        return $processedCount;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue Error: " . $e->getMessage());
        // Hata durumunda da lock'u serbest bÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rak
        WLSTokenManager::releaseCronLock('wls_queue_process');
        return false;
    }
}

// SipariÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸leme task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±
function WhiteLabelServices_ProcessOrderTask($data) {
    try {
        $serviceId = $data['service_id'];
        $wlsProductId = $data['WLS_product_id'];
        $payload = $data['payload'];
        
        WLS_debugLog("Queue - Processing order for service: " . $serviceId);
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();

        if (!$server) {
            WLS_debugLog("Queue - No active WLS server found");
            return false;
        }

        $params = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            WLS_debugLog("Queue - Failed to get API token");
            return false;
        }

        // API'ye sipariÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/order/" . $wlsProductId);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            WLS_debugLog("Queue - API Error: " . $error);
            
            // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            $updateData = [
                'status' => 'error',
                'error_message' => $error,
                'last_check' => date('Y-m-d H:i:s')
            ];
            WLSTokenManager::updateVPSDetails($serviceId, $updateData);
            
            return false;
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown API error';
            WLS_debugLog("Queue - API Error (HTTP " . $httpCode . "): " . $errorMessage);
            
            // Service notes'u gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
            if ($service) {
                $notes = json_decode($service->notes, true);
                $notes['WLS_status'] = 'error';
                $notes['error_message'] = "HTTP " . $httpCode . ": " . $errorMessage;
                $notes['last_attempt'] = date('Y-m-d H:i:s');
                $notes['queue_status'] = 'failed';
                
                Capsule::table('tblhosting')
                    ->where('id', $serviceId)
                    ->update(['notes' => json_encode($notes)]);
            }
            
            return false;
        }
        
        $orderData = json_decode($response, true);
        if (!$orderData || !isset($orderData['order_num']) || !isset($orderData['invoice_id'])) {
            WLS_debugLog("Queue - Invalid order response");
            return false;
        }
        
        // BaÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸arÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± sipariÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ - bilgileri gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        $updateData = [
            'status' => 'completed',
            'order_num' => $orderData['order_num'],
            'invoice_id' => $orderData['invoice_id'],
            'total' => $orderData['total'] ?? '0.00',
            'completed_date' => date('Y-m-d H:i:s'),
            'last_check' => date('Y-m-d H:i:s')
        ];
            
            // WLS service ID'sini kaydet
            if (isset($orderData['items']) && is_array($orderData['items'])) {
                foreach ($orderData['items'] as $item) {
                    if (isset($item['id'])) {
                    $updateData['wls_service_id'] = $item['id'];
                    $updateData['status'] = 'provisioning';
                
                // VM durumu kontrol task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± queue'ya ekle
                WhiteLabelServices_AddToQueue('check_vm_status', [
                    'service_id' => $serviceId,
                    'wls_service_id' => $item['id']
                ]);
                
                WLS_debugLog("Queue - Order completed, added VM status check to queue");
                    break;
            }
            }
        }
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        WLSTokenManager::updateVPSDetails($serviceId, $updateData);
        
        WLS_debugLog("Queue - Order processed successfully for service: " . $serviceId);
        
        // Admin bilgilendirme
        sendAdminNotification(
            'WLS VM Order Completed',
            'A WLS VM order has been completed successfully.<br>' .
            'Service ID: ' . $serviceId . '<br>' .
            'Order Number: ' . $orderData['order_num'] . '<br>' .
            'Invoice ID: ' . $orderData['invoice_id'] . '<br>' .
            'Total: $' . ($orderData['total'] ?? '0.00'),
            'system'
        );
        
        return true;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue - Error processing order: " . $e->getMessage());
        return false;
    }
}

// VM durumu kontrol task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±
function WhiteLabelServices_CheckVMStatusTask($data) {
    try {
        $serviceId = $data['service_id'];
        $wlsServiceId = $data['wls_service_id'];
        
        WLS_debugLog("Queue - Checking VM status for service: " . $serviceId);
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            return false;
        }
        
        $params = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            return false;
        }
        
        // Service durumunu kontrol et
        $serviceStatus = WhiteLabelServices_CheckServiceStatus($wlsServiceId, $token);
        if (!$serviceStatus || !isset($serviceStatus['service'])) {
            WLS_debugLog("Queue - Failed to get service status");
            return false;
        }
        
        // VeritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        $updateData = [
            'status' => $serviceStatus['service']['status'],
            'last_check' => date('Y-m-d H:i:s')
        ];
        
        // Service Active ise VM'leri kontrol et
        if ($serviceStatus['service']['status'] === 'Active') {
            $vmList = WhiteLabelServices_getVMList($wlsServiceId, $token);
            if ($vmList && isset($vmList['vms']) && !empty($vmList['vms'])) {
                foreach ($vmList['vms'] as $vmId => $vm) {
                    $updateData['wls_vm_id'] = $vmId;
                    $updateData['vm_status'] = $vm['status'];
                    $updateData['vm_built'] = $vm['built'] ? true : false;
                    $updateData['vm_power'] = $vm['power'] ? true : false;
                    
                    // VM running ve built ise detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
                    if ($vm['status'] === 'running' && $vm['built']) {
                        $vmDetails = WhiteLabelServices_getVMDetails($serviceId, $vmId, $token);
                        if ($vmDetails && isset($vmDetails['vm'])) {
                            $vmData = $vmDetails['vm'];
                            
                            // VM bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                            $updateData['vm_ip'] = $vmData['ipv4'] ?? '';
                            $updateData['vm_username'] = $vmData['username'] ?? '';
                            $updateData['vm_password'] = $vmData['password'] ?? '';
                            $updateData['vm_memory'] = $vmData['memory'] ?? '';
                            $updateData['vm_disk'] = $vmData['disk'] ?? '';
                            $updateData['vm_cores'] = $vmData['cores'] ?? '';
                            $updateData['vm_template'] = $vmData['template_name'] ?? '';
                            $updateData['vm_mac'] = $vmData['mac'] ?? '';
                            $updateData['vm_uptime'] = $vmData['uptime'] ?? 0;
                            
                            // Storage bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                            if (isset($vmData['storage']) && is_array($vmData['storage'])) {
                                $updateData['vm_storage'] = json_encode($vmData['storage']);
                            }
                            
                            // Network interfaces bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                            if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
                                $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
                            }
                            
                            // IP listesini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                            if (isset($vmData['ip']) && is_array($vmData['ip'])) {
                                $allIps = [];
                                foreach ($vmData['ip'] as $ipId => $ipInfo) {
                                    $allIps[] = [
                                        'id' => $ipId,
                                        'ip' => $ipInfo['ipaddress'],
                                        'main' => $ipInfo['main']
                                    ];
                                }
                                $updateData['vm_all_ips'] = json_encode($allIps);
                            }
                            
                            // WHMCS servisini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
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
        Capsule::table('tblhosting')
            ->where('id', $serviceId)
                                ->update($hostingData);
                        }
                    }
                    }
                    break; // ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°lk VM'i iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ledikten sonra dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ngÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼den ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±k
                }
            }
        }
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        WLSTokenManager::updateVPSDetails($serviceId, $updateData);
        
        return true;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue - Error checking VM status: " . $e->getMessage());
        return false;
    }
}

/**
 * Power Action Task Processor
 * Queue'dan power action iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lemlerini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
 */
function WhiteLabelServices_ProcessPowerActionTask($data) {
    try {
        $serviceId = $data['service_id'];
        $wlsServiceId = $data['wls_service_id'];
        $vmId = $data['wls_vm_id'];
        $action = $data['action'];
        
        WLS_debugLog("Queue - Processing power action: " . $action . " for service " . $serviceId);
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            WLS_debugLog("Queue - No active server found");
            return false;
        }
        
        $apiParams = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            WLS_debugLog("Queue - Failed to get API token");
            return false;
        }
        
        // API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($apiParams);
        $apiUrl = $apiBaseUrl . "/api/service/{$wlsServiceId}/vms/{$vmId}/{$action}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
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
            WLS_debugLog("Queue - Power action CURL error: " . $error);
            return false;
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode === 200 && isset($result['status']) && $result['status'] === true) {
            WLS_debugLog("Queue - Power action executed successfully: " . $action);
            
            // 2 saniye bekle ve durumu gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            sleep(2);
            
            // VM durumunu API'den al ve gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            WhiteLabelServices_CheckVMStatusTask([
                'service_id' => $serviceId,
                'wls_service_id' => $wlsServiceId
            ]);
            
            return true;
        }
        
        $errorMsg = isset($result['error']) ? implode(', ', (array)$result['error']) : 'Unknown error';
        WLS_debugLog("Queue - Power action failed: " . $errorMsg . " (HTTP " . $httpCode . ")");
        
        // Hata durumunda durumu gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->update([
                'vm_status' => 'error',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        
        return false;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue - Power action error: " . $e->getMessage());
        return false;
    }
}

/**
 * Rebuild Task Processor
 * Queue'dan rebuild iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lemini ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
 */
function WhiteLabelServices_ProcessRebuildTask($data) {
    try {
        $serviceId = $data['service_id'];
        $wlsServiceId = $data['wls_service_id'];
        $vmId = $data['wls_vm_id'];
        $template = $data['template'];
        
        WLS_debugLog("Queue - Processing rebuild for service " . $serviceId . " with template: " . $template);
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            WLS_debugLog("Queue - No active server found");
            return false;
        }
        
        $apiParams = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            WLS_debugLog("Queue - Failed to get API token");
            return false;
        }
        
        // API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($apiParams);
        $apiUrl = $apiBaseUrl . "/api/service/{$wlsServiceId}/vms/{$vmId}/rebuild?template=" . urlencode($template);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Rebuild uzun sÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rebilir
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            WLS_debugLog("Queue - Rebuild CURL error: " . $error);
            return false;
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode === 200 || $httpCode === 202) {
            WLS_debugLog("Queue - Rebuild started successfully");
            
            // Durumu gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            Capsule::table('mod_wls_vps')
                ->where('id', $serviceId)
                ->update([
                    'vm_status' => 'rebuilding',
                    'vm_built' => false,
                    'template' => $template,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            // VM durumu kontrolÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in yeni queue ekle
            WhiteLabelServices_AddToQueue('check_vm_status', [
                'service_id' => $serviceId,
                'wls_service_id' => $wlsServiceId
            ], 5); // Normal ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ncelik
            
            return true;
        }
        
        $errorMsg = isset($result['message']) ? $result['message'] : 'Unknown error';
        WLS_debugLog("Queue - Rebuild failed: " . $errorMsg . " (HTTP " . $httpCode . ")");
        
        // Hata durumunda durumu gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->update([
                'vm_status' => 'error',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        
        return false;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue - Rebuild error: " . $e->getMessage());
        return false;
    }
}

/**
 * WHMCS Queue Compatible Module Functions
 * WHMCS queue processor bu fonksiyonlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± doÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸rudan ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§aÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
 */

// Power Start - WHMCS Queue iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
function WhiteLabelServices_power_start(array $params) {
    WLS_debugLog("Queue - power_start called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'start');
}

// Power Stop - WHMCS Queue iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
function WhiteLabelServices_power_stop(array $params) {
    WLS_debugLog("Queue - power_stop called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'stop');
}

// Power Shutdown - WHMCS Queue iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
function WhiteLabelServices_power_shutdown(array $params) {
    WLS_debugLog("Queue - power_shutdown called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'shutdown');
}

// Power Reboot - WHMCS Queue iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
function WhiteLabelServices_power_reboot(array $params) {
    WLS_debugLog("Queue - power_reboot called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'reboot');
}

// Power Reset - WHMCS Queue iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
function WhiteLabelServices_power_reset(array $params) {
    WLS_debugLog("Queue - power_reset called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'reset');
}

// Rebuild - WHMCS Queue iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in
function WhiteLabelServices_rebuild(array $params) {
    WLS_debugLog("Queue - rebuild called for service: " . $params['serviceid']);
    
    // Queue data'dan template al
    $template = $params['template'] ?? '';
    
    if (empty($template)) {
        // Service notes'dan template'i al
        $service = Capsule::table('tblhosting')->where('id', $params['serviceid'])->first();
        if ($service) {
            $notes = json_decode($service->notes, true) ?: [];
            $template = $notes['rebuild_template'] ?? '';
        }
    }
    
    if (empty($template)) {
        return 'Template not specified';
    }
    
    $result = WhiteLabelServices_rebuildVM($params, $template);
    
    if (isset($result['error'])) {
        return $result['error'];
    }
    
    return 'success';
}

/**
 * Power Action Executor
 * TÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼m power action'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± bu fonksiyon ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼zerinden ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
 */
function WhiteLabelServices_executePowerAction($params, $action) {
    try {
        $serviceId = $params['serviceid'];
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();
            
        if (!$vpsDetails || empty($vpsDetails->wls_service_id) || empty($vpsDetails->wls_vm_id)) {
            return 'VM not provisioned yet';
        }
        
        $wlsServiceId = $vpsDetails->wls_service_id;
        $vmId = $vpsDetails->wls_vm_id;
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            return 'No active WLS server found';
        }
        
        $apiParams = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            return 'Failed to get API token';
        }
        
        // API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($apiParams);
        $apiUrl = $apiBaseUrl . "/api/service/{$wlsServiceId}/vms/{$vmId}/{$action}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return 'CURL error: ' . $error;
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode === 200 && isset($result['status']) && $result['status'] === true) {
            WLS_debugLog("Queue - Power action {$action} executed successfully for service {$serviceId}");
            
            // Durumu gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            $statusMap = [
                'start' => 'running',
                'stop' => 'stopped',
                'shutdown' => 'stopped',
                'reboot' => 'running',
                'reset' => 'running'
            ];
            
            Capsule::table('mod_wls_vps')
                ->where('id', $serviceId)
                ->update([
                    'vm_status' => $statusMap[$action] ?? 'unknown',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            return 'success';
        }
        
        $errorMsg = isset($result['error']) ? implode(', ', (array)$result['error']) : 'Unknown error';
        return "API error: {$errorMsg} (HTTP {$httpCode})";
        
    } catch (Exception $e) {
        WLS_debugLog("Queue - Power action error: " . $e->getMessage());
        return $e->getMessage();
    }
}

// WHMCS Admin Custom Buttons - Yeni sistem
function WhiteLabelServices_AdminCustomButtonArray() {
    return [
        "Sync VM Data" => "refresh",
        "Start VM" => "start",
        "Stop VM" => "stop",
        "Shutdown VM" => "shutdown",
        "Reboot VM" => "reboot",
        "Reset VM" => "reset"
    ];
}

// Yenile butonu fonksiyonu - action adÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ile aynÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± olmalÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±
function WhiteLabelServices_refresh(array $params) {
    try {
        WLS_debugLog("Refresh - Starting refresh for service: " . $params['serviceid']);
        
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
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
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            throw new Exception('Failed to get API token');
        }
        
        $currentVmId = $vpsDetails->wls_vm_id;
        
        $vmList = WhiteLabelServices_getVMList($vpsDetails->wls_service_id, $token);
        if (!$vmList || !isset($vmList['vms']) || empty($vmList['vms'])) {
            throw new Exception('No VMs found for this service');
        }
        
        if ($currentVmId && isset($vmList['vms'][$currentVmId])) {
            $vmId = $currentVmId;
            $vm = $vmList['vms'][$vmId];
        } else {
            reset($vmList['vms']);
            $vmId = key($vmList['vms']);
            $vm = current($vmList['vms']);
        }
        
        $updateData = [
            'wls_vm_id' => $vmId,
            'vm_status' => $vm['status'],
            'vm_built' => $vm['built'] ? 1 : 0,
            'vm_powered' => $vm['power'] ? 1 : 0,
            'last_sync' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $vmDetails = WhiteLabelServices_getVMDetails($params['serviceid'], $vmId, $token);
        if (!$vmDetails || !isset($vmDetails['vm'])) {
            throw new Exception('Failed to get VM details');
        }
        
        $vmData = $vmDetails['vm'];
        
   $updateData['cores'] = intval($vmData['cores'] ?? 0);
        $updateData['template'] = $vmData['template_name'] ?? '';
        $updateData['mac_address'] = $vmData['mac'] ?? '';
        
        // Network interfaces bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
            $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
        }
        
        // Storage bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        if (isset($vmData['storage']) && is_array($vmData['storage'])) {
            $updateData['vm_storage'] = json_encode($vmData['storage']);
        }
        
        // Resources bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        $resources = [
            'memory' => $vmData['memory'] ?? '',
            'disk' => intval($vmData['disk'] ?? 0),
            'cores' => intval($vmData['cores'] ?? 0),
            'sockets' => intval($vmData['sockets'] ?? 1),
            'cpus' => intval($vmData['cpus'] ?? 1),
            'uptime' => intval($vmData['uptime'] ?? 0),
            'last_check' => time()
        ];
        $updateData['vm_resources'] = json_encode($resources);
        
        // IP listesini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        if (isset($vmData['ip']) && is_array($vmData['ip'])) {
            $allIps = [];
            foreach ($vmData['ip'] as $ipId => $ipInfo) {
                $allIps[] = [
                    'id' => $ipId,
                    'ip' => $ipInfo['ipaddress'],
                    'main' => $ipInfo['main']
                ];
            }
            $updateData['all_ips'] = json_encode($allIps);
        }
        
        // WHMCS servisini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
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
            Capsule::table('tblhosting')
                ->where('id', $params['serviceid'])
                ->update($hostingData);
        }
        
        // Status bilgisini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        $updateData['status'] = 'Active';
        $updateData['service_status'] = 'Active';
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        WLSTokenManager::updateVPSDetails($params['serviceid'], $updateData);
        
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("Refresh Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

// Power Control Functions for Admin Module Commands
function WhiteLabelServices_start(array $params) {
    $result = WhiteLabelServices_powerAction($params, 'start');
    sleep(2); // Wait for VM state to change
    WhiteLabelServices_refresh($params); // Sync data
    return $result;
}

function WhiteLabelServices_stop(array $params) {
    $result = WhiteLabelServices_powerAction($params, 'stop');
    sleep(2);
    WhiteLabelServices_refresh($params);
    return $result;
}

function WhiteLabelServices_shutdown(array $params) {
    $result = WhiteLabelServices_powerAction($params, 'shutdown');
    sleep(2);
    WhiteLabelServices_refresh($params);
    return $result;
}

function WhiteLabelServices_reboot(array $params) {
    $result = WhiteLabelServices_powerAction($params, 'reboot');
    sleep(2);
    WhiteLabelServices_refresh($params);
    return $result;
}

function WhiteLabelServices_reset(array $params) {
    $result = WhiteLabelServices_powerAction($params, 'reset');
    sleep(2);
    WhiteLabelServices_refresh($params);
    return $result;
}

// Generic power action handler
function WhiteLabelServices_powerAction(array $params, string $action, bool $isRetry = false) {
    try {
        WLS_debugLog("Power - {$action} for service: " . $params['serviceid'] . ($isRetry ? " (retry)" : ""));
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_vm_id) {
            throw new Exception('VM ID not found');
        }
        
        // Server bilgilerini al
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
            'serverpassword' => decrypt($server->password)
        ];
        
        // If retry, force new token
        if ($isRetry) {
            WLSTokenManager::clearToken($server->id);
        }
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            throw new Exception('Failed to get API token');
        }
        
        // API endpoint - server hostname'den al
        $vmId = $vpsDetails->wls_vm_id;
        $serviceId = $vpsDetails->wls_service_id;
        $apiHost = rtrim($server->hostname, '/');
        $apiUrl = "https://{$apiHost}/api/service/{$serviceId}/vms/{$vmId}/{$action}";
        
        WLS_debugLog("Power - Calling API: {$apiUrl}");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            WLS_debugLog("Power - cURL Error: {$curlError}", true); // Force log errors
            throw new Exception("Connection error: {$curlError}");
        }
        
        $responseData = json_decode($response, true);
        WLS_debugLog("Power - Response: " . $response);
        
        // Check for token expired / unauthorized error - retry with fresh token
        if (!$isRetry && isset($responseData['error']) && is_array($responseData['error'])) {
            $errors = $responseData['error'];
            if (in_array('token_expired', $errors) || in_array('unauthorized', $errors)) {
                WLS_debugLog("Power - Token expired, refreshing and retrying...");
                return WhiteLabelServices_powerAction($params, $action, true);
            }
        }
        
        // API returns {"status": true} on success
        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['status']) && $responseData['status'] === true) {
            WLS_debugLog("Power - {$action} successful for VM: {$vmId}");
            return 'success';
        } else {
            $errorMsg = $responseData['message'] ?? (is_array($responseData['error'] ?? null) ? implode(', ', $responseData['error']) : ($responseData['error'] ?? "HTTP {$httpCode}"));
            throw new Exception($errorMsg);
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Power Error - {$action}: " . $e->getMessage(), true); // Force log errors
        return $e->getMessage();
    }
}

// Senkronize Et butonu fonksiyonu
function WhiteLabelServices_synchronizeService(array $params) {
    try {
        WLS_debugLog("Sync - Starting synchronization for service: " . $params['serviceid']);
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id) {
            throw new Exception('Service not found');
        }
        
        // API token al
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
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            throw new Exception('Failed to get API token');
        }
        
        // Service durumunu kontrol et
        $serviceStatus = WhiteLabelServices_CheckServiceStatus($vpsDetails->wls_service_id, $token);
        if (!$serviceStatus || !isset($serviceStatus['service'])) {
            throw new Exception('Failed to get service status from WLS API');
        }
        
        // VeritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        $updateData = [
            'service_status' => $serviceStatus['service']['status'],
            'last_check' => date('Y-m-d H:i:s')
        ];
        
        $syncMessage = 'Service synchronized successfully';
        $vmUpdated = false;
        
        // Service Active ise VM'leri kontrol et
        if ($serviceStatus['service']['status'] === 'Active') {
            $vmList = WhiteLabelServices_getVMList($vpsDetails->wls_service_id, $token);
            if ($vmList && isset($vmList['vms']) && !empty($vmList['vms'])) {
                foreach ($vmList['vms'] as $vmId => $vm) {
                    $updateData['wls_vm_id'] = $vmId;
                    $updateData['vm_status'] = $vm['status'];
                    $updateData['vm_built'] = $vm['built'] ? true : false;
                    $updateData['vm_power'] = $vm['power'] ? true : false;
                    
                    // VM running ve built ise detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
                    if ($vm['status'] === 'running' && $vm['built']) {
                        $vmDetails = WhiteLabelServices_getVMDetails($params['serviceid'], $vmId, $token);
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
                            
                            // Storage bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                            if (isset($vmData['storage']) && is_array($vmData['storage'])) {
                                $updateData['vm_storage'] = json_encode($vmData['storage']);
                            }
                            
                            // Network interfaces bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                            if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
                                $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
                            }
                            
                            // IP listesini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                            if (isset($vmData['ip']) && is_array($vmData['ip'])) {
                                $allIps = [];
                                foreach ($vmData['ip'] as $ipId => $ipInfo) {
                                    $allIps[] = [
                                        'id' => $ipId,
                                        'ip' => $ipInfo['ipaddress'],
                                        'main' => $ipInfo['main']
                                    ];
                                }
                                $updateData['vm_all_ips'] = json_encode($allIps);
                            }
                            
                            // WHMCS servisini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
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
                            Capsule::table('tblhosting')
                                ->where('id', $params['serviceid'])
                                    ->update($hostingData);
                            }
                                
                            $vmUpdated = true;
                            $syncMessage = 'Service and VM details synchronized successfully. VM is running and active.';
                        }
                    } else {
                        $syncMessage = 'Service synchronized. VM status: ' . $vm['status'] . ', Built: ' . ($vm['built'] ? 'Yes' : 'No');
                    }
                    
                    break; // ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â°lk VM'i iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸le
                }
            } else {
                $syncMessage = 'Service synchronized. No VMs found for this service.';
            }
        } else {
            $syncMessage = 'Service synchronized. Service status: ' . $serviceStatus['service']['status'];
        }
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
        WLSTokenManager::updateVPSDetails($params['serviceid'], $updateData);
        
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("Sync Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

// VM Durumu Kontrol Et butonu fonksiyonu
function WhiteLabelServices_checkVMStatusButton(array $params) {
    try {
        WLS_debugLog("VM Check - checkVMStatusButton called with params: " . print_r($params, true));
        
        // Service notes'undan WLS bilgilerini al - iyileÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tirilmiÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸
        $notes = '';
        if (!empty($params['notes'])) {
            $notes = $params['notes'];
            WLS_debugLog("VM Check - Notes from params['notes']: " . substr($notes, 0, 200) . "...");
        } else {
            // Manuel olarak service'i ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
            if (isset($params['serviceid'])) {
                $service = Capsule::table('tblhosting')->where('id', $params['serviceid'])->first();
                if ($service && $service->notes) {
                    $notes = $service->notes;
                    WLS_debugLog("VM Check - Notes manually fetched from database: " . substr($notes, 0, 200) . "...");
                } else {
                    WLS_debugLog("VM Check - No service found or no notes in database for service: " . $params['serviceid']);
                }
            } else {
                WLS_debugLog("VM Check - No serviceid in params");
            }
        }
        
        if (empty($notes)) {
            WLS_debugLog("VM Check - No notes found");
            return 'Service notes not found. Service may not be provisioned yet.';
        }
        
        $orderData = json_decode($notes, true) ?: [];
        $wlsServiceId = $orderData['wls_service_id'] ?? null;
        
        WLS_debugLog("VM Check - WLS Service ID found: " . ($wlsServiceId ?: 'NOT FOUND'));
        
        if (!$wlsServiceId) {
            return 'WLS service ID not found. Service may not be provisioned yet.';
        }
        
        WLS_debugLog("VM Check - Starting VM status check for service: " . $params['serviceid'] . " (WLS Service: " . $wlsServiceId . ")");
        
        // VM status check task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± direkt ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§aÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
        $result = WhiteLabelServices_check_vm_status($params);
        
        if ($result === 'success' || $result === true) {
            // GÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncellenmiÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ service bilgilerini al
            $updatedService = Capsule::table('tblhosting')->where('id', $params['serviceid'])->first();
            $updatedNotes = json_decode($updatedService->notes, true) ?: [];
            
            $message = 'VM status checked successfully';
            
            // VM durumu hakkÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nda detaylÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± bilgi ver
            if (isset($updatedNotes['WLS_vm_status'])) {
                $vmStatus = $updatedNotes['WLS_vm_status'];
                $vmBuilt = $updatedNotes['WLS_vm_built'] ?? false;
                
                if ($vmStatus === 'running' && $vmBuilt) {
                    $message = 'VM is running and active. All details have been updated.';
                } elseif ($vmStatus === 'running' && !$vmBuilt) {
                    $message = 'VM is running but not fully built yet. Please check again in a few minutes.';
                } else {
                    $message = 'VM status: ' . $vmStatus . ', Built: ' . ($vmBuilt ? 'Yes' : 'No');
                }
            }
            
            WLS_debugLog("VM Check - VM status check completed for service: " . $params['serviceid'] . " - " . $message);
            WLS_debugLog("check_vm_status - No WLS service ID for service: $serviceId");
            return 'No WLS service ID found';
        }
        
        if (empty($params['serverusername']) || empty($params['serverpassword'])) {
            $server = Capsule::table('tblservers')
                ->where('type', 'WhiteLabelServices')
                ->where('active', '1')
                ->first();
            
            if ($server) {
                $params['serverid'] = $server->id;
                $params['serverusername'] = $server->username;
                $params['serverpassword'] = decrypt($server->password);
            } else {
                WLS_debugLog("check_vm_status - No active WLS server found");
                return 'No active WLS server found';
            }
        }
        
        // Try to load existing queue data if this is called via WHMCS Module Queue
        $queueData = null;
        if (isset($params['queueid'])) {
            $queueData = WLSTokenManager::getQueueData($params['queueid']);
        }
        
        if (!$queueData) {
            $queueData = [
                'service_id' => $serviceId,
                'wls_service_id' => $wlsServiceId,
                'wls_vm_id' => $wlsData->wls_vm_id ?? null,
                'api_base_url' => WLSTokenManager::getApiBaseUrl(),
                'stage' => $wlsData->vm_built ? 'check_vm_details' : 'check_service',
                'retry_count' => 0,
            ];
            WLS_debugLog("check_vm_status - No existing queue data found, starting fresh check");
        } else {
            WLS_debugLog("check_vm_status - Loaded existing queue data (Stage: " . ($queueData['stage'] ?? 'unknown') . ")");
        }
        
        WLS_debugLog("check_vm_status - Processing service $serviceId with WLS ID $wlsServiceId");
        
        return WhiteLabelServices_ProcessVMStatusCheck($params, $queueData);
        
    } catch (\Throwable $e) {
        WLS_debugLog("Fatal error in check_vm_status: " . $e->getMessage(), true);
        return 'Check failed: ' . $e->getMessage();
    }
}

// WHMCS custom field data oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸tur
function WhiteLabelServices_createCustomFieldData($form, $productId) {
            $fieldType = '';
    $fieldOptions = [];
    $fieldName = WhiteLabelServices_generateFieldName($form);

            switch ($form['type']) {
                case 'serverselector':
                    $fieldType = 'dropdown';
            // Sadece seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ili olan location'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
                    foreach ($form['items'] as $item) {
                        if ($item['selected']) {
                    // VirgÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â¼l ve boÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸luklarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± normalize et
                    $normalizedTitle = preg_replace('/[,\s]+/', ' ', trim($item['title']));
                    $fieldOptions[] = $normalizedTitle;
                    break;
                }
            }
            // EÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸er seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ili yoksa ilk item'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
            if (empty($fieldOptions) && !empty($form['items'])) {
                        $firstItem = reset($form['items']);
                $normalizedTitle = preg_replace('/[,\s]+/', ' ', trim($firstItem['title']));
                $fieldOptions[] = $normalizedTitle;
            }
                    break;

                case 'select':
                    $fieldType = 'dropdown';
                    if ($form['metadata']['variable'] === 'os') {
                // OS iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in CentOS 7'yi ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nce ekle
                $centosAdded = false;
                        foreach ($form['items'] as $item) {
                            if (stripos($item['title'], 'CentOS 7') !== false) {
                        $fieldOptions[] = $item['title'];
                        $centosAdded = true;
                                break;
                            }
                        }
                // DiÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸er OS'leri ekle
                        foreach ($form['items'] as $item) {
                            if (stripos($item['title'], 'CentOS 7') === false) {
                        $fieldOptions[] = $item['title'];
                            }
                        }
                // CentOS 7 yoksa ilk item'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸a ekle
                if (!$centosAdded && !empty($form['items'])) {
                            $firstItem = reset($form['items']);
                    array_unshift($fieldOptions, $firstItem['title']);
                }
            } else {
                // DiÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸er select'ler iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼m seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§enekleri ekle
                    foreach ($form['items'] as $item) {
                    $fieldOptions[] = $item['title'];
                }
            }
                    break;

                case 'slider':
                    $fieldType = 'dropdown';
            $min = intval($form['config']['minvalue'] ?? 0);
            $max = intval($form['config']['maxvalue'] ?? 10);
                    $step = intval($form['config']['step'] ?? 1);
            
            // IP Address iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶zel durum - minimum 1, None yok
            if ($form['metadata']['variable'] === 'ipamlimit') {
                $min = max(1, $min); // Minimum 1 yap
                for ($i = $min; $i <= $max; $i += $step) {
                    $fieldOptions[] = $i . ' IP';
                }
            } else {
                // Storage ve Backup iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in - None yok, 0'dan baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸la
                    for ($i = $min; $i <= $max; $i += $step) {
                        $unit = '';
                        switch ($form['metadata']['variable']) {
                            case 'additional_storage':
                                $unit = ' GB';
                                break;
                            case 'backuplimit':
                                $unit = ' Backup';
                                break;
                    }
                    $fieldOptions[] = $i . $unit;
                }
            }
                                break;
            
        case 'multicheckbox':
            $fieldType = 'dropdown';
            // None seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§eneÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸ini kaldÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rdÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±k - WHMCS zaten required olmayan alanlara boÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸ seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§enek ekliyor
            foreach ($form['items'] as $item) {
                $price = $item['unit_price'] > 0 ? ' ($' . number_format($item['unit_price'], 2) . '/mo)' : '';
                $fieldOptions[] = $item['title'] . $price;
            }
            break;
            
        default:
            WLS_debugLog("Debug - Unknown form type: " . $form['type']);
            return null;
    }
    
    if (empty($fieldOptions)) {
        WLS_debugLog("Debug - No field options generated for form: " . $form['title']);
        return null;
    }
    
    return [
                        'type' => 'product',
        'relid' => $productId,
                        'fieldname' => $fieldName,
                        'fieldtype' => $fieldType,
                        'description' => $form['title'],
        'fieldoptions' => implode(',', $fieldOptions),
        'regexpr' => '',
        'adminonly' => '',
        'required' => ($form['required'] || 
                      in_array($form['metadata']['variable'], ['os', 'ipamlimit', 'additional_storage', 'backuplimit']) || 
                      $form['type'] === 'serverselector') ? 'on' : '',
                        'showorder' => 'on',
                        'showinvoice' => 'on',
        'sortorder' => intval($form['metadata']['sort_order'] ?? 0),
    ];
}

// VM Action Handler fonksiyonlarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±
function WhiteLabelServices_handleVMAction($params, $action) {
    try {
        WLS_debugLog("VM Action - Starting action: " . $action . " for service: " . $params['serviceid']);
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            WLS_debugLog("VM Action - Missing service or VM ID");
            return ['error' => 'VM not provisioned yet'];
        }
        
        // API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ncesi durumu kaydet
        $initialStatus = $vpsDetails->vm_status;
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            return ['error' => 'No active WLS server found'];
        }
        
        $apiParams = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            return ['error' => 'Failed to get API token'];
        }
        
        // API endpoint'ini belirle
        $actionMap = [
            'start' => 'start',
            'stop' => 'stop',
            'shutdown' => 'shutdown',
            'reboot' => 'reboot',
            'reset' => 'reset'
        ];
        
        if (!isset($actionMap[$action])) {
            return ['error' => 'Invalid action'];
        }
        
        $apiAction = $actionMap[$action];
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $apiUrl = $apiBaseUrl . "/api/service/{$vpsDetails->wls_service_id}/vms/{$vpsDetails->wls_vm_id}/{$apiAction}";
        
        // API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
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
            WLS_debugLog("VM Action Error - CURL error: " . $error);
            return ['error' => 'Connection error: ' . $error];
        }
        
        curl_close($ch);
        
            $result = json_decode($response, true);
        
        // API yanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±tÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± kontrol et
        if ($httpCode === 200 && isset($result['status'])) {
            if ($result['status'] === true) {
                WLS_debugLog("VM Action - {$action} command sent successfully for service: " . $params['serviceid']);
                
                // 3 saniye bekle
                sleep(3);
                
                // VM bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle (hata olursa sessizce geÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§)
                try {
                    WhiteLabelServices_SyncVMFromAPI($params);
                } catch (Exception $syncError) {
                    WLS_debugLog("VM Action - Sync error (non-fatal): " . $syncError->getMessage());
                }
                
                // GÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncel VM durumunu al
                $updatedVpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
                $newStatus = $updatedVpsDetails ? $updatedVpsDetails->vm_status : $initialStatus;
                
                return [
                    'success' => true,
                    'message' => "VM {$action} command sent successfully",
                    'vm_status' => $newStatus,
                    'needs_refresh' => true
                ];
            } else {
                $errorMessage = isset($result['error']) ? implode(', ', $result['error']) : 'Unknown error';
                WLS_debugLog("VM Action Error - API returned error: " . $errorMessage);
                return ['error' => "Failed to {$action} VM: " . $errorMessage];
            }
        }
        
        WLS_debugLog("VM Action Error - {$action} failed for service: " . $params['serviceid'] . " (HTTP {$httpCode}): " . $response);
        return ['error' => "Failed to {$action} VM: Server returned HTTP " . $httpCode];
        
    } catch (Exception $e) {
        WLS_debugLog("VM Action Error: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

function WhiteLabelServices_addNetworkInterface($params) {
    try {
        WLS_debugLog("Network - Add interface called for service: " . $params['serviceid']);
        
        // VPS detaylarini mod_wls_vps tablosundan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            WLS_debugLog("Network - Missing VPS details for service: " . $params['serviceid']);
            return ['error' => 'VM not provisioned yet'];
        }
        
        $wlsServiceId = $vpsDetails->wls_service_id;
        $vmId = $vpsDetails->wls_vm_id;
        
        WLS_debugLog("Network - WLS Service ID: " . $wlsServiceId);
        WLS_debugLog("Network - VM ID: " . $vmId);
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            WLS_debugLog("Network - No active WLS server found");
            return ['error' => 'No active WLS server found'];
        }
        
        $apiParams = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            WLS_debugLog("Network - Failed to get API token");
            return ['error' => 'Failed to get API token'];
        }
        
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $apiUrl = $apiBaseUrl . "/api/service/{$wlsServiceId}/vms/{$vmId}/interfaces";
        
        WLS_debugLog("Network - Sending POST request to: " . $apiUrl);
        
        // API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
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
            WLS_debugLog("Network - cURL Error: " . $error);
            return ['error' => 'Network error: ' . $error];
        }
        
        curl_close($ch);
        
        WLS_debugLog("Network - API Response (HTTP {$httpCode}): " . $response);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['status']) && $result['status']) {
                WLS_debugLog("Network - Interface added successfully for service: " . $params['serviceid']);
                return ['success' => 'Network interface added successfully'];
            } else {
                WLS_debugLog("Network - API returned success but invalid response structure");
                return ['error' => 'Invalid API response'];
            }
        } else {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown API error';
            WLS_debugLog("Network Error - Add interface failed for service: " . $params['serviceid'] . " (HTTP {$httpCode}): " . $errorMessage);
            return ['error' => 'Failed to add network interface: ' . $errorMessage];
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Network Error: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

function WhiteLabelServices_deleteNetworkInterface($params, $interfaceId) {
    try {
        WLS_debugLog("Network - Delete interface called for service: " . $params['serviceid'] . ", interface: " . $interfaceId);
        
        // VPS detaylarini mod_wls_vps tablosundan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            WLS_debugLog("Network - Missing VPS details for service: " . $params['serviceid']);
            return ['error' => 'VM not provisioned yet'];
        }
        
        $wlsServiceId = $vpsDetails->wls_service_id;
        $vmId = $vpsDetails->wls_vm_id;
        
        WLS_debugLog("Network - WLS Service ID: " . $wlsServiceId);
        WLS_debugLog("Network - VM ID: " . $vmId);
        WLS_debugLog("Network - Interface to delete: " . $interfaceId);
        
        if (empty($interfaceId)) {
            WLS_debugLog("Network - Interface ID is empty");
            return ['error' => 'Interface ID is required'];
        }
        
        // net0'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± silmeye izin verme
        if ($interfaceId === 'net0' || $interfaceId === '0') {
            WLS_debugLog("Network - Attempted to delete primary interface (net0)");
            return ['error' => 'Cannot delete primary network interface (net0)'];
        }
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            WLS_debugLog("Network - No active WLS server found");
            return ['error' => 'No active WLS server found'];
        }
        
        $apiParams = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            WLS_debugLog("Network - Failed to get API token");
            return ['error' => 'Failed to get API token'];
        }
        
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $apiUrl = $apiBaseUrl . "/api/service/{$wlsServiceId}/vms/{$vmId}/interfaces/{$interfaceId}";
        
        WLS_debugLog("Network - Sending DELETE request to: " . $apiUrl);
        
        // API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
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
            WLS_debugLog("Network - cURL Error: " . $error);
            return ['error' => 'Network error: ' . $error];
        }
        
        curl_close($ch);
        
        WLS_debugLog("Network - API Response (HTTP {$httpCode}): " . $response);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['status']) && $result['status']) {
                WLS_debugLog("Network - Interface {$interfaceId} deleted successfully for service: " . $params['serviceid']);
                return ['success' => 'Network interface deleted successfully'];
            } else {
                WLS_debugLog("Network - API returned success but invalid response structure");
                return ['error' => 'Invalid API response'];
            }
        } else {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown API error';
            WLS_debugLog("Network Error - Delete interface {$interfaceId} failed for service: " . $params['serviceid'] . " (HTTP {$httpCode}): " . $errorMessage);
            return ['error' => 'Failed to delete network interface: ' . $errorMessage];
        }

    } catch (Exception $e) {
        WLS_debugLog("Network Error: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// WHMCS Module Queue iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶zel fonksiyonlar
function WhiteLabelServices_process_order($params) {
    try {
        WLS_debugLog("Debug - process_order function called with params: " . print_r($params, true));
        
        // Service ID'yi al
        $serviceId = $params['serviceid'] ?? null;
        if (!$serviceId) {
            WLS_debugLog("Error - No service ID provided to process_order");
            return 'No service ID provided';
        }
        
        // Service bilgilerini al
        $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
        if (!$service) {
            WLS_debugLog("Error - Service not found: " . $serviceId);
            return 'Service not found';
        }
        
        // Notes'dan queue data'yÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al
        $notes = json_decode($service->notes, true) ?: [];
        
        // Queue data'yÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± bul
        $queueData = null;
        foreach ($notes as $key => $value) {
            if (strpos($key, 'queue_data_') === 0 && is_array($value)) {
                $queueData = $value;
                break;
            }
        }
        
        if (!$queueData) {
            WLS_debugLog("Error - No queue data found for service: " . $serviceId);
            return 'No queue data found';
        }
        
        // Process order task'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§aÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±r
        $result = WLS_ProcessOrderTask($queueData);
        
        if ($result) {
            WLS_debugLog("Debug - process_order completed successfully for service: " . $serviceId);
            return 'success';
        } else {
            WLS_debugLog("Error - process_order failed for service: " . $serviceId);
            return 'Process order failed';
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Error in process_order function: " . $e->getMessage());
        return $e->getMessage();
    }
}

// WHMCS Module Queue iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in check_vm_status fonksiyonu
function WhiteLabelServices_check_vm_status($params) {
    try {
        // TablolarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±n varlÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± kontrol et
        WLSTokenManager::ensureTablesExist();
        
        // Service ID'yi al
        $serviceId = $params['serviceid'] ?? null;
        if (!$serviceId) {
            WLS_debugLog("check_vm_status - No service ID provided");
            return 'No service ID provided';
        }
        
        // WLS bilgilerini mod_wls_vps tablosundan al
        $wlsData = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();
        
        if (!$wlsData) {
            WLS_debugLog("check_vm_status - No WLS data found for service: $serviceId");
            return 'No WLS data found';
        }
        
        // WLS service ID'yi al
        $wlsServiceId = $wlsData->wls_service_id ?? null;
        if (!$wlsServiceId) {
            WLS_debugLog("check_vm_status - No WLS service ID for service: $serviceId");
            return 'No WLS service ID found';
        }
        
        // Server credentials'ÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± al (eÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸er params'ta yoksa)
        if (empty($params['serverusername']) || empty($params['serverpassword'])) {
            $server = Capsule::table('tblservers')
                ->where('type', 'WhiteLabelServices')
                ->where('active', '1')
                ->first();
            
            if ($server) {
                $params['serverid'] = $server->id;
                $params['serverusername'] = $server->username;
                $params['serverpassword'] = decrypt($server->password);
            } else {
                WLS_debugLog("check_vm_status - No active WLS server found");
                return 'No active WLS server found';
            }
        }
        
        // Queue benzeri data yapısını hazırla (kendi cron döngümüz için)
        $queueData = [
            'service_id' => $serviceId,
            'wls_service_id' => $wlsServiceId,
            'wls_vm_id' => $wlsData->wls_vm_id ?? null,
            'api_base_url' => WLSTokenManager::getApiBaseUrl(),
            'stage' => $wlsData->vm_built ? 'check_vm_details' : 'check_service',
            'retry_count' => 0,
        ];
        
        WLS_debugLog("check_vm_status - Processing service $serviceId with WLS ID $wlsServiceId");
        
        // ProcessVMStatusCheck'i çağır
        $result = WhiteLabelServices_ProcessVMStatusCheck($params, $queueData);
        
        // WLS result sabitlerini kontrol et
        if ($result === WLS_RESULT_SUCCESS) {
            return 'success';
        } elseif ($result === WLS_RESULT_RESCHEDULED) {
            // Reschedule baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸arÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± - WHMCS iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in bu "success" demek, yeni task oluÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸turuldu
            return 'success';
        } else {
            return $result ?: 'VM status check failed';
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Error in check_vm_status: " . $e->getMessage());
        return $e->getMessage();
    }
}

// WHMCS'nin standart module queue action'larÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in wrapper fonksiyonlar
function WhiteLabelServices_ProcessOrder($params) {
    return WhiteLabelServices_process_order($params);
}

function WhiteLabelServices_CheckVMStatus($params) {
    return WhiteLabelServices_check_vm_status($params);
}

// WHMCS Module Queue action handler
function WhiteLabelServices_ModuleQueueAction($action, $params) {
    try {
        WLS_debugLog("Debug - ModuleQueueAction called: " . $action . " with params: " . print_r($params, true));
        
        switch ($action) {
            case 'process_order':
            case 'ProcessOrder':
                return WhiteLabelServices_process_order($params);
                
            case 'check_vm_status':
            case 'CheckVMStatus':
                return WhiteLabelServices_check_vm_status($params);
                
            default:
                WLS_debugLog("Error - Unknown module queue action: " . $action);
                return 'Unknown action: ' . $action;
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Error in ModuleQueueAction: " . $e->getMessage());
        return $e->getMessage();
    }
}

// Power action'dan sonra VM bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
function WhiteLabelServices_updateVMInfoAfterPowerAction($params) {
    try {
        WLS_debugLog("Debug - Updating VM info after power action for service: " . $params['serviceid']);
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            WLS_debugLog("Debug - Missing service or VM ID for update");
            return false;
        }
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            WLS_debugLog("Debug - No active WLS server found for update");
            return false;
        }
        
        $apiParams = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            WLS_debugLog("Debug - Failed to get API token for update");
            return false;
        }
        
        // VM detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $vmDetails = WhiteLabelServices_getVMDetails($params['serviceid'], $vpsDetails->wls_vm_id, $token);
        if ($vmDetails && isset($vmDetails['vm'])) {
            $vmData = $vmDetails['vm'];
            
            // VeritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            $updateData = [
                'vm_status' => $vmData['status'] ?? $vpsDetails->vm_status,
                'vm_power' => $vmData['power'] ?? $vpsDetails->vm_power,
                'vm_built' => $vmData['built'] ?? $vpsDetails->vm_built,
                'last_check' => date('Y-m-d H:i:s')
            ];
            
            // VM running ise diÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸er bilgileri de gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            if (isset($vmData['status']) && $vmData['status'] === 'running') {
                $updateData['ipv4'] = $vmData['ipv4'] ?? $vpsDetails->ipv4;
                $updateData['username'] = $vmData['username'] ?? $vpsDetails->username;
                $updateData['password'] = $vmData['password'] ?? $vpsDetails->password;
                $updateData['memory'] = $vmData['memory'] ?? $vpsDetails->memory;
                $updateData['disk'] = $vmData['disk'] ?? $vpsDetails->disk;
                $updateData['cores'] = $vmData['cores'] ?? $vpsDetails->cores;
                $updateData['template'] = $vmData['template_name'] ?? $vpsDetails->template;
                $updateData['mac_address'] = $vmData['mac'] ?? $vpsDetails->mac_address;
                $updateData['vm_uptime'] = $vmData['uptime'] ?? $vpsDetails->vm_uptime;
                
                // Storage bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                if (isset($vmData['storage']) && is_array($vmData['storage'])) {
                    $updateData['vm_storage'] = json_encode($vmData['storage']);
                }
                
                // Network interfaces bilgilerini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
                    $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
                }
                
                // IP listesini gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
                if (isset($vmData['ip']) && is_array($vmData['ip'])) {
                    $allIps = [];
                    foreach ($vmData['ip'] as $ipId => $ipInfo) {
                        $allIps[] = [
                            'id' => $ipId,
                            'ip' => $ipInfo['ipaddress'],
                            'main' => $ipInfo['main']
                        ];
                    }
                    $updateData['vm_all_ips'] = json_encode($allIps);
                }
            }
            
            // VeritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            WLSTokenManager::updateVPSDetails($params['serviceid'], $updateData);
                
            WLS_debugLog("Debug - VM info updated successfully after power action. New status: " . ($vmData['status'] ?? 'unknown'));
            return true;
        } else {
            WLS_debugLog("Debug - Failed to get VM details for update");
            return false;
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Error updating VM info after power action: " . $e->getMessage());
        return false;
    }
}

// Hostname/Label gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelleme fonksiyonu
function WhiteLabelServices_updateLabel($params, $label) {
    try {
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $label = trim((string) $label);
        if ($label === '') {
            return ['error' => 'Hostname is required'];
        }
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?$/', $label) && strlen($label) > 1) {
            return ['error' => 'Invalid hostname format'];
        }

        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            return ['error' => 'VM details not found'];
        }
        
        $serviceId = $vpsDetails->wls_service_id;
        $vmId = $vpsDetails->wls_vm_id;
        
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            return ['error' => 'Could not get API token'];
        }
        
        // API base URL al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            return ['error' => 'No active WLS server found'];
        }
        
        $apiBaseUrl = rtrim($server->hostname, '/');
        // Ensure https:// prefix
        if (strpos($apiBaseUrl, 'http://') !== 0 && strpos($apiBaseUrl, 'https://') !== 0) {
            $apiBaseUrl = 'https://' . $apiBaseUrl;
        }
        
        // API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶nder - hostname endpoint
        $apiBaseUrl = rtrim(WhiteLabelServices_getApiBaseUrl($params), '/');
        $encodedLabel = rawurlencode($label);

        $attempts = [
            WhiteLabelServices_ApiCall(
                $apiBaseUrl . '/api/service/' . $serviceId . '/vms/' . $vmId . '/hostname?hostname=' . $encodedLabel,
                $token,
                'POST',
                ['hostname' => $label]
            ),
            WhiteLabelServices_ApiCall(
                $apiBaseUrl . '/api/service/' . $serviceId . '/label?label=' . $encodedLabel,
                $token,
                'POST',
                ['label' => $label]
            ),
        ];

        $success = false;
        $lastError = 'Hostname update failed';
        foreach ($attempts as $attempt) {
            if ($attempt['ok']) {
                $success = true;
                break;
            }
            if ($attempt['api_error']) {
                $lastError = $attempt['api_error'];
            } elseif ($attempt['curl_error']) {
                $lastError = $attempt['curl_error'];
            } elseif ($attempt['http_code']) {
                $lastError = 'API error: HTTP ' . $attempt['http_code'];
            }
        }

        if (!$success) {
            WLS_debugLog('Label Update failed for service ' . $params['serviceid'] . ': ' . $lastError);
            return ['error' => $lastError];
        }

        Capsule::table('tblhosting')
            ->where('id', $params['serviceid'])
            ->update(['domain' => $label]);

        WLS_debugLog('Label Update - Success for service ' . $params['serviceid'] . ' - New hostname: ' . $label);

        WhiteLabelServices_SyncVMFromAPI($params);
        $fresh = WLSTokenManager::getVPSDetails($params['serviceid']);

        return [
            'success' => true,
            'label' => $label,
            'vm_status' => $fresh->vm_status ?? null,
        ];
        
    } catch (Exception $e) {
        WLS_debugLog("Label Update Error: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// Template'leri ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§eken fonksiyon
function WhiteLabelServices_getTemplates($params) {
    try {
        WLS_debugLog("Debug - Getting templates for service: " . $params['serviceid']);
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id) {
            WLS_debugLog("Error - No WLS service ID found for templates");
            return [];
        }
        
        $serviceId = $vpsDetails->wls_service_id;
        
        // API token al - server bilgilerini kullan
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();

        if (!$server) {
            WLS_debugLog("Error - No active WLS server found");
            return [];
        }
        
        $apiParams = [
            'serverid' => $server->id,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            WLS_debugLog("Error - Could not get token for templates");
            return [];
        }
        
        // API'den template'leri ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $url = $apiBaseUrl . "/api/service/{$serviceId}/templates";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            WLS_debugLog("Error - Templates API returned HTTP " . $httpCode . ": " . $response);
            return ['templates' => [], 'grouped' => ['linux' => [], 'windows' => []]];
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['templates'])) {
            WLS_debugLog("Error - Invalid templates response: " . $response);
            return ['templates' => [], 'grouped' => ['linux' => [], 'windows' => []]];
        }
        
        // Get raw templates and group by OS family
        $templates = $data['templates'];
        $osFamilies = [];
        
        // Define OS family detection rules
        $familyRules = [
            'Ubuntu' => ['match' => 'ubuntu', 'icon' => 'fab fa-ubuntu', 'color' => '#E95420', 'isWindows' => false],
            'Debian' => ['match' => 'debian', 'icon' => 'fab fa-linux', 'color' => '#A81D33', 'isWindows' => false],
            'AlmaLinux' => ['match' => 'alma', 'icon' => 'fab fa-redhat', 'color' => '#FF6B35', 'isWindows' => false],
            'RockyLinux' => ['match' => 'rocky', 'icon' => 'fas fa-mountain', 'color' => '#10B981', 'isWindows' => false],
            'CentOS' => ['match' => 'centos', 'icon' => 'fab fa-centos', 'color' => '#262577', 'isWindows' => false],
            'Mikrotik' => ['match' => 'mikrotik', 'icon' => 'fas fa-network-wired', 'color' => '#293239', 'isWindows' => false],
            'Windows' => ['match' => 'windows', 'icon' => 'fab fa-windows', 'color' => '#0078D4', 'isWindows' => true],
        ];
        
        foreach ($templates as $template) {
            $name = $template['name'] ?? '';
            $nameLower = strtolower($name);
            $familyKey = 'Other';
            $familyInfo = ['icon' => 'fab fa-linux', 'color' => '#FCC624', 'isWindows' => false];
            
            // Detect OS family
            foreach ($familyRules as $family => $rule) {
                if (strpos($nameLower, $rule['match']) !== false) {
                    $familyKey = $family;
                    $familyInfo = $rule;
                    break;
                }
            }
            
            // Initialize family if not exists
            if (!isset($osFamilies[$familyKey])) {
                $osFamilies[$familyKey] = [
                    'name' => $familyKey,
                    'icon' => $familyInfo['icon'],
                    'color' => $familyInfo['color'],
                    'isWindows' => $familyInfo['isWindows'],
                    'versions' => []
                ];
            }
            
            // Add version to family
            $osFamilies[$familyKey]['versions'][] = [
                'id' => $template['id'],
                'name' => $name,
                'size_gb' => $template['size_gb'] ?? 0
            ];
        }
        
        // Sort families: Linux first, Windows last
        $linux = [];
        $windows = [];
        foreach ($osFamilies as $key => $family) {
            if ($family['isWindows']) {
                $windows[$key] = $family;
            } else {
                $linux[$key] = $family;
            }
        }
        
        WLS_debugLog("Debug - Found " . count($templates) . " templates in " . count($osFamilies) . " OS families");
        
        return [
            'templates' => $templates,
            'osFamilies' => $osFamilies,
            'linux' => $linux,
            'windows' => $windows
        ];
        
    } catch (Exception $e) {
        WLS_debugLog("Error getting templates: " . $e->getMessage());
        return ['templates' => [], 'grouped' => ['linux' => [], 'windows' => []]];
    }
}

// OS grubunu belirleyen fonksiyon
function WhiteLabelServices_getOSGroup($templateName) {
    $name = strtolower($templateName);
    
    if (strpos($name, 'ubuntu') !== false) return 'Ubuntu';
    if (strpos($name, 'debian') !== false) return 'Debian';
    if (strpos($name, 'centos') !== false) return 'CentOS';
    if (strpos($name, 'almalinux') !== false || strpos($name, 'alma') !== false) return 'AlmaLinux';
    if (strpos($name, 'rocky') !== false) return 'Rocky Linux';
    if (strpos($name, 'fedora') !== false) return 'Fedora';
    if (strpos($name, 'windows') !== false) return 'Windows';
    if (strpos($name, 'mikrotik') !== false) return 'MikroTik';
    
    return 'Other';
}

// OS ikonunu dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ndÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ren fonksiyon
function WhiteLabelServices_getOSIcon($osGroup) {
    $icons = [
        'Ubuntu' => 'fab fa-ubuntu',
        'Debian' => 'fas fa-server',
        'CentOS' => 'fab fa-centos',
        'AlmaLinux' => 'fab fa-redhat',
        'Rocky Linux' => 'fab fa-redhat',
        'Fedora' => 'fab fa-fedora',
        'Windows' => 'fab fa-windows',
        'MikroTik' => 'fas fa-network-wired',
        'Other' => 'fab fa-linux'
    ];
    
    return $icons[$osGroup] ?? 'fab fa-linux';
}

// OS rengini dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ndÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ren fonksiyon
function WhiteLabelServices_getOSColor($osGroup) {
    $colors = [
        'Ubuntu' => '#E95420',
        'Debian' => '#A81D33',
        'CentOS' => '#932279',
        'AlmaLinux' => '#FF6B35',
        'Rocky Linux' => '#10B981',
        'Fedora' => '#294172',
        'Windows' => '#0078D4',
        'MikroTik' => '#293462',
        'Other' => '#FCC624'
    ];
    
    return $colors[$osGroup] ?? '#667eea';
}

// VM rebuild fonksiyonu
function WhiteLabelServices_rebuildVM($params, $templateId) {
    try {
        WLS_debugLog("Debug - Rebuilding VM for service: " . $params['serviceid'] . " with template: " . $templateId);
        
        // VPS detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan al
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            return ['error' => 'VM information not found'];
        }
        
        $serviceId = $vpsDetails->wls_service_id;
        $vmId = $vpsDetails->wls_vm_id;
        
        // Token al
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            return ['error' => 'Authentication failed'];
        }
        
        // Rebuild API isteÃƒÆ’Ã¢â‚¬ÂÃƒâ€¦Ã‚Â¸i
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        $url = $apiBaseUrl . "/api/service/{$serviceId}/vms/{$vmId}/rebuild";
        
        $postData = json_encode([
            'template' => $templateId
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        WLS_debugLog("Debug - Rebuild API response: HTTP " . $httpCode . " - " . $response);
        
        if ($httpCode === 200 || $httpCode === 202) {
            // Rebuild baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸arÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±lÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±, VM durumunu gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            $orderData['WLS_vm_status'] = 'rebuilding';
            $orderData['rebuild_started'] = date('Y-m-d H:i:s');
            $orderData['rebuild_template'] = $templateId;
            
            Capsule::table('tblhosting')
                ->where('id', $params['serviceid'])
                ->update(['notes' => json_encode($orderData)]);
            
            // mod_wls_vps tablosundaki vm_status'u da gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            Capsule::table('mod_wls_vps')
                ->where('id', $params['serviceid'])
                ->update([
                    'vm_status' => 'rebuilding',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            WLS_debugLog("Debug - VM status updated to 'rebuilding' for service: " . $params['serviceid']);
            
            return ['success' => true, 'vm_status' => 'rebuilding'];
        } else {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['message'] ?? 'Rebuild request failed';
            return ['error' => $errorMessage];
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Error rebuilding VM: " . $e->getMessage());
        return ['error' => 'Rebuild failed: ' . $e->getMessage()];
    }
}

// WLS ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼rÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼nlerini getir
function WhiteLabelServices_getProducts($params) {
    return WLSTokenManager::getProducts($params);
}

function ClientArea($params) {
    try {
        // Smarty template engine'i baÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lat
        require_once __DIR__ . '/lib/Smarty/Smarty.class.php';
        $smarty = new Smarty();
        $smarty->template_dir = __DIR__ . '/templates/';
        $smarty->compile_dir = __DIR__ . '/templates_c/';
        
        // Rebuild iÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸lemi devam ediyorsa
        if (isset($_SESSION['WLS_rebuild_' . $params['serviceid']])) {
            $smarty->assign('rebuilding', true);
            $smarty->assign('serviceid', $params['serviceid']);
            $output = $smarty->fetch('rebuilding.tpl');
            return $output;
        }
        
        // VPS bilgilerini veritabanÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ndan ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ek
        $vmInfo = WLSTokenManager::getVPSDetails($params['serviceid']);
        
        // VM detaylarÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶sterilsin mi kontrol et
        $showVMDetails = false;
        if ($vmInfo && $vmInfo->wls_vm_id) {
            $showVMDetails = true;
        }
        
        // Template iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§in VM bilgilerini hazÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±rla
        $vmData = [
            'vm_id' => '',
            'vm_status' => 'unknown',
            'vm_built' => false,
            'vm_power' => false,
            'ip_address' => '',
            'ipv6_address' => '',
            'username' => '',
            'password' => '',
            'memory' => '',
            'disk' => '',
            'cores' => '',
            'template' => '',
            'mac' => '',
            'service_id' => '',
            'all_ips' => [],
            'activated_date' => '',
            'uptime' => 0,
            'interfaces' => [],
            'storage' => [],
            'bandwidth' => null,
            'label' => $params['domain'] ?? 'WLS VPS'
        ];
        
        
        if ($vmInfo) {
            // stdClass objesini array'e ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§evir
            $vmInfoArray = json_decode(json_encode($vmInfo), true);
            
            // Temel bilgileri gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼ncelle
            $vmData['vm_id'] = $vmInfoArray['wls_vm_id'] ?? '';
            $vmData['vm_status'] = $vmInfoArray['vm_status'] ?? 'unknown';
            $vmData['vm_built'] = $vmInfoArray['vm_built'] ?? false;
            $vmData['vm_power'] = $vmInfoArray['vm_power'] ?? false;
            $vmData['ip_address'] = $vmInfoArray['vm_ip'] ?? '';
            $vmData['ipv6_address'] = $vmInfoArray['vm_ipv6'] ?? '';
            $vmData['username'] = $vmInfoArray['vm_username'] ?? '';
            $vmData['password'] = $vmInfoArray['vm_password'] ?? '';
            $vmData['memory'] = $vmInfoArray['vm_memory'] ?? '';
            $vmData['disk'] = $vmInfoArray['vm_disk'] ?? '';
            $vmData['cores'] = $vmInfoArray['vm_cores'] ?? '';
            $vmData['template'] = $vmInfoArray['vm_template'] ?? '';
            $vmData['mac'] = $vmInfoArray['vm_mac'] ?? '';
            $vmData['service_id'] = $vmInfoArray['wls_service_id'] ?? '';
            $vmData['activated_date'] = $vmInfoArray['activated_date'] ?? '';
            $vmData['uptime'] = intval($vmInfoArray['vm_uptime'] ?? 0);
            $vmData['bandwidth'] = $vmInfoArray['vm_bandwidth'] ?? null;
            
            // JSON alanlari parse et
            $vmData['all_ips'] = json_decode($vmInfoArray['vm_all_ips'] ?? '[]', true) ?: [];
            $vmData['interfaces'] = json_decode($vmInfoArray['vm_interfaces'] ?? '[]', true) ?: [];
            $vmData['storage'] = json_decode($vmInfoArray['vm_storage'] ?? '[]', true) ?: [];
            $vmData['resources'] = json_decode($vmInfoArray['vm_resources'] ?? '[]', true) ?: [];
            
            // Debug log for interfaces
        }

        // Smarty template degiskenlerini ata
        $smarty->assign('vmInfo', $vmData);
        $smarty->assign('serviceStatus', $params['domainstatus'] ?? 'Pending');
        $smarty->assign('serviceid', $params['serviceid']);
        $smarty->assign('domain', $params['domain']);
        $smarty->assign('showVMDetails', $showVMDetails);
        $smarty->assign('modulelink', $params['modulelink'] ?? '');
        
        // Template dosyasÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±nÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± render et ve dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ndÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r
        $output = $smarty->fetch('clientarea.tpl');
        return $output;

    } catch (Exception $e) {
        WLS_debugLog("ClientArea Error: " . $e->getMessage());
        
        // Hata durumunda basit HTML dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ndÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¼r
        return '<div class="alert alert-danger">
                    <h4>VPS Management</h4>
                    <p>Unable to load service information: ' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>Service ID: ' . ($params['serviceid'] ?? 'Unknown') . '</p>
                    <p>Domain: ' . ($params['domain'] ?? 'Unknown') . '</p>
                </div>';
    }
}



function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}



/**
 * Get Reverse DNS for VM
 */
function WhiteLabelServices_getRDNS($params) {
    try {
        // Get WLS data
        $wlsData = Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->first();
        
        if (!$wlsData || empty($wlsData->wls_service_id)) {
            return ['error' => 'WLS Service ID not found'];
        }
        
        $wlsServiceId = $wlsData->wls_service_id;
        $token = WLSTokenManager::getToken($params);
        
        if (!$token) {
            return ['error' => 'Failed to get API token'];
        }
        
        // Get API base URL with https
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            return ['error' => 'No active WLS server found'];
        }
        
        if (!class_exists('WLSTokenManager')) {
            require_once __DIR__ . '/lib/TokenManager.php';
        }
        $apiBaseUrl = rtrim($server->hostname, '/');

        if (strpos($apiBaseUrl, 'http://') !== 0 && strpos($apiBaseUrl, 'https://') !== 0) {
            $apiBaseUrl = 'https://' . $apiBaseUrl;
        }
        
        $url = $apiBaseUrl . '/api/service/' . $wlsServiceId . '/rdns';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            // API format: {"rdns":{"IP":{"ipaddress":"IP","ptrname":"...","ptrcontent":"hostname"}}}
            if (isset($data['rdns']) && is_array($data['rdns'])) {
                // Return all IPs with their rDNS values
                $allRdns = [];
                foreach ($data['rdns'] as $ip => $rdnsData) {
                    $allRdns[$ip] = [
                        'ip' => $ip,
                        'rdns' => $rdnsData['ptrcontent'] ?? '',
                        'ptrname' => $rdnsData['ptrname'] ?? ''
                    ];
                }
                // Also return primary IP rdns for backward compatibility
                $primaryIp = $wlsData->ipv4 ?? $params['dedicatedip'] ?? '';
                $primaryRdns = isset($allRdns[$primaryIp]) ? $allRdns[$primaryIp]['rdns'] : '';
                return ['success' => true, 'rdns' => $primaryRdns, 'all_rdns' => $allRdns];
            }
            return ['success' => true, 'rdns' => '', 'all_rdns' => []];
        }
        
        $error = json_decode($response, true);
        return ['error' => $error['message'] ?? 'Failed to get rDNS'];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update Reverse DNS for VM
 * @param array $params WHMCS params
 * @param string $rdns Hostname to set
 * @param string $specificIp Optional - specific IP to update, defaults to primary IPv4
 */
function WhiteLabelServices_updateRDNS($params, $rdns, $specificIp = '') {
    try {
        // Get WLS data
        $wlsData = Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->first();
        
        if (!$wlsData || empty($wlsData->wls_service_id)) {
            return ['error' => 'WLS Service ID not found'];
        }
        
        $wlsServiceId = $wlsData->wls_service_id;
        $token = WLSTokenManager::getToken($params);
        
        if (!$token) {
            return ['error' => 'Failed to get API token'];
        }
        
        // Get API base URL with https
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            return ['error' => 'No active WLS server found'];
        }
        
        $apiBaseUrl = rtrim($server->hostname, '/');
        if (strpos($apiBaseUrl, 'http://') !== 0 && strpos($apiBaseUrl, 'https://') !== 0) {
            $apiBaseUrl = 'https://' . $apiBaseUrl;
        }
        
        $url = $apiBaseUrl . '/api/service/' . $wlsServiceId . '/rdns';
        
        // Use specific IP if provided, otherwise use primary IP
        $ip = !empty($specificIp) ? $specificIp : ($wlsData->ipv4 ?? $params['dedicatedip'] ?? '');
        
        if (empty($ip)) {
            return ['error' => 'No IP address found'];
        }
        $ip = trim($ip);
        
        WLS_debugLog("rDNS Update - URL: " . $url . " IP: " . $ip . " Hostname: " . $rdns);
        
        // POST body: {"id":"<wls_service_id>","<ip>":"<ptr_hostname>"} — id string olmali
        $postData = json_encode([
            'id' => (string) $wlsServiceId,
            $ip => (string) $rdns,
        ], JSON_UNESCAPED_SLASHES);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            WLS_debugLog("rDNS Update - CURL Error: " . $curlError);
            return ['error' => 'Connection error'];
        }
        
        WLS_debugLog("rDNS Update - Response Code: " . $httpCode . " Response: " . $response);
        
        if ($httpCode === 200) {
            WLS_debugLog("- rDNS updated for service " . $params['serviceid'] . ": " . $rdns);
            return ['success' => true];
        }
        
        $error = json_decode($response, true);
        return ['error' => $error['message'] ?? $error['error'] ?? 'rDNS update failed (HTTP ' . $httpCode . ')'];
        
    } catch (Exception $e) {
        WLS_debugLog("rDNS Update Error: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Perform VM rebuild with selected template
 * POST /service/@id/vms/@vmid/rebuild
 */
function WhiteLabelServices_performRebuild($params, $templateId) {
    try {
        WLS_debugLog("Rebuild - Starting rebuild for service " . $params['serviceid'] . " with template: " . $templateId);
        
        // Get WLS data
        $wlsData = Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->first();
        
        if (!$wlsData || empty($wlsData->wls_service_id)) {
            WLS_debugLog("Rebuild Error - No WLS service ID found for service " . $params['serviceid']);
            return ['error' => 'WLS Service ID not found'];
        }
        
        if (empty($wlsData->wls_vm_id)) {
            WLS_debugLog("Rebuild Error - No VM ID found for service " . $params['serviceid']);
            return ['error' => 'VM ID not found'];
        }
        
        $wlsServiceId = $wlsData->wls_service_id;
        $vmId = $wlsData->wls_vm_id;
        
        WLS_debugLog("Rebuild - Service ID: " . $wlsServiceId . ", VM ID: " . $vmId);
        
        $token = WLSTokenManager::getToken($params);
        
        if (!$token) {
            WLS_debugLog("Rebuild Error - Failed to get API token");
            return ['error' => 'Failed to get API token'];
        }
        
        $baseUrl = WLSTokenManager::getApiBaseUrl($params);
        $url = $baseUrl . '/api/service/' . $wlsServiceId . '/vms/' . $vmId . '/rebuild';
        
        WLS_debugLog("Rebuild - Calling API: " . $url);
        
        // Prepare JSON payload
        $postData = json_encode(['template' => $templateId]);
        
        WLS_debugLog("Rebuild - POST data: " . $postData);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        WLS_debugLog("Rebuild - HTTP Code: " . $httpCode . ", Response: " . substr($response, 0, 500));
        
        if ($curlError) {
            WLS_debugLog("Rebuild - CURL Error: " . $curlError);
            return ['error' => 'Connection error: ' . $curlError];
        }
        
        $responseData = json_decode($response, true);
        
        // Check for API success: {"status": 1}
        if ($httpCode === 200 && isset($responseData['status']) && $responseData['status'] == 1) {
            // Update VM status to rebuilding
            Capsule::table('mod_wls_vps')
                ->where('id', $params['serviceid'])
                ->update([
                    'vm_status' => 'rebuilding',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            WLS_debugLog("- Rebuild initiated for service " . $params['serviceid'] . " with template: " . $templateId);
            
            // Queue status check to monitor rebuild progress
            WhiteLabelServices_AddToQueue('check_vm_status', [
                'service_id' => $params['serviceid'],
                'wls_service_id' => $wlsServiceId,
                'wls_vm_id' => $vmId,
                'attempt' => 1,
                'action' => 'rebuild_check',
                'stage' => 'check_vm_details'
            ]);
            
            return [
                'success' => true, 
                'message' => 'Rebuild initiated successfully',
                'locked' => true,
                'vm_status' => 'rebuilding'
            ];
        }
        
        $errorMsg = $responseData['message'] ?? $responseData['error'] ?? 'Rebuild failed';
        WLS_debugLog("- Rebuild failed for service " . $params['serviceid'] . ": " . $errorMsg);
        return ['error' => $errorMsg];
        
    } catch (Exception $e) {
        WLS_debugLog("- Rebuild exception for service " . $params['serviceid'] . ": " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync all active WLS services VM data
 * Runs every 5 minutes via cron to keep customer data fresh
 */
function WhiteLabelServices_SyncAllVMData() {
    try {
        // Sync lock al - 5 dakikada bir ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸sÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±n
        $syncLockToken = WLSTokenManager::acquireCronLock('wls_vm_sync', 300); // 5 dakika lock
        if (!$syncLockToken) {
            // Son 5 dakika iÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§inde ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸mÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€¦Ã‚Â¸, atla
            return 0;
        }
        
        WLS_debugLog("Sync - Starting VM data sync for all active services");
        
        // Aktif WLS servislerini al
        $services = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblhosting.domainstatus', 'Active')
            ->where('tblproducts.servertype', 'WhiteLabelServices')
            ->select('tblhosting.*')
            ->get();
        
        if (empty($services) || count($services) == 0) {
            WLS_debugLog("Sync - No active WLS services found");
            return 0;
        }
        
        WLS_debugLog("Sync - Found " . count($services) . " active services to sync");
        
        // API token al
        $server = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
            
        if (!$server) {
            WLS_debugLog("Sync - No active WLS server found");
            return 0;
        }
        
        $params = [
            'serverid' => $server->id,
            'serverhostname' => $server->hostname,
            'serverusername' => $server->username,
            'serverpassword' => decrypt($server->password)
        ];
        
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            WLS_debugLog("Sync - Failed to get API token");
            return 0;
        }
        
        $syncedCount = 0;
        
        foreach ($services as $service) {
            try {
                $vpsDetails = WLSTokenManager::getVPSDetails($service->id);
                if (!$vpsDetails || !$vpsDetails->wls_service_id) {
                    continue;
                }
                if ($vpsDetails->last_sync) {
                    $lastSync = strtotime($vpsDetails->last_sync);
                    if ($lastSync && (time() - $lastSync) < 300) {
                        continue;
                    }
                }
                $vmParams = array_merge($params, array('serviceid' => $service->id));
                $syncResult = WhiteLabelServices_SyncVMFromAPI($vmParams);
                if (!empty($syncResult['success'])) {
                    $syncedCount++;
                    WLS_debugLog("Sync - Synced service: " . $service->id . (isset($syncResult['reconciled']) ? ' (' . $syncResult['reconciled'] . ')' : ''));
                }
                usleep(100000);
            } catch (Exception $e) {
                WLS_debugLog("Sync Error for service " . $service->id . ": " . $e->getMessage());
                continue;
            }
        }
        
        WLS_debugLog("Sync - Completed. Synced " . $syncedCount . " VM(s)");
        return $syncedCount;
        
    } catch (Exception $e) {
        WLS_debugLog("Sync Error: " . $e->getMessage());
        return 0;
    }
}
