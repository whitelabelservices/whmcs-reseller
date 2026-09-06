<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

 
if (!class_exists('WLSTokenManager')) {
    require_once __DIR__ . '/lib/TokenManager.php';
}


 
define('WLS_RESULT_SUCCESS', 'wls_success');            
define('WLS_RESULT_RESCHEDULED', 'wls_rescheduled');    
define('WLS_RESULT_FAILED', 'wls_failed');              

 

 





function WLS_debugLog($message, $force = false) {
    static $debugMode = null;
    
     
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
            'changePassword' => false,  
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

 
function WhiteLabelServices_getApiBaseUrl($params = null) {
     
    if ($params && !empty($params['serverhostname'])) {
        return 'https://' . $params['serverhostname'];
    }
    
     
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
    
     
    return 'https://portal.WLS.com';
}

 
function WhiteLabelServices_ExtractProductId($selectedOption) {
     
    if (empty($selectedOption)) {
        WLS_debugLog("Debug - ExtractProductId: Empty input");
        return false;
    }
    
     
    if (preg_match('/\((\d+)\)/', $selectedOption, $matches)) {
        WLS_debugLog("Debug - ExtractProductId found ID from parentheses: " . $matches[1]);
        return $matches[1];
    }
    
     
    if (is_numeric($selectedOption)) {
        WLS_debugLog("Debug - ExtractProductId found numeric ID: " . $selectedOption);
        return intval($selectedOption);
    }
    
     
    if (preg_match('/id[:\s]*(\d+)/i', $selectedOption, $matches)) {
        WLS_debugLog("Debug - ExtractProductId found ID from prefix: " . $matches[1]);
        return $matches[1];
    }
    
    WLS_debugLog("Debug - ExtractProductId failed to extract ID from: " . $selectedOption);
    return false;
}

 


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

 
function WhiteLabelServices_ConfigOptions() {
    try {
         
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
                    
                     
                    $allowedSlugs = ['clouds', 'vps', 'cloud', 'cloud-server', 'cloud-vps'];
                    
                    foreach ($categories['categories'] as $cat) {
                        $catId = $cat['id'];
                        $catName = $cat['name'];
                        $catSlug = $cat['slug'] ?? '';
                        
                         
                        if (!in_array($catSlug, $allowedSlugs)) {
                            continue;
                        }
                        
                         
                        $ch2 = curl_init();
                        curl_setopt($ch2, CURLOPT_URL, $apiBaseUrl . '/api/category/' . $catId . '/product');
                        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
                        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
                        $prodResponse = curl_exec($ch2);
                        curl_close($ch2);
                        
                        $prodData = json_decode($prodResponse, true);
                        
                         
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
        
         
        $adminLang = $_SESSION['adminlang'] ?? 'english';
        
         
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
        
         
        $t = $lang['english'];
        if (isset($lang[$adminLang])) {
            $t = $lang[$adminLang];
        }
        
         
        if (empty($options)) {
            $options = ['' => $t['no_server']];
        } else {
            $options = ['' => $t['select_placeholder']] + $options;
        }

         
        
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

 


function WhiteLabelServices_GetQtyForSliderOrQtyForm($form, $params) {
    foreach (WhiteLabelServices_CollectOrderFormRawValueCandidates($form, $params) as $raw) {
        $q = WhiteLabelServices_ParseQuantityFromWhmcsOption($raw);
        if ($q !== null) {
            return $q;
        }
    }
    return null;
}

 


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

 






function WhiteLabelServices_CreateAccount(array $params) {
    try {
        WLS_debugLog("CreateAccount - Starting provisioning for service: " . $params['serviceid']);
        
         
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
        
         
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        
         
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
        
         
        if (isset($result['error'])) {
            $errorMsg = is_array($result['error']) ? json_encode($result['error']) : $result['error'];
            throw new Exception("API Error: " . $errorMsg);
        }
        
        if (!$result || !isset($result['items'])) {
            throw new Exception("Invalid API response - missing items");
        }
        
         
        $wlsServiceId = null;
        $items = $result['items'];
        
         
        if (isset($items['id'])) {
            $items = [$items];
        }
        
         
        foreach ($items as $item) {
            if (isset($item['type']) && $item['type'] === 'Hosting') {
                $wlsServiceId = $item['id'];
                break;
            }
        }
        
         
        if (!$wlsServiceId && !empty($items)) {
            $firstItem = reset($items);
            $wlsServiceId = $firstItem['id'] ?? null;
        }
        
        if (!$wlsServiceId) {
            throw new Exception("Could not extract service ID from API response");
        }
        
        WLS_debugLog("CreateAccount - Order created! Order #" . ($result['order_num'] ?? 'N/A') . ", Service ID: " . $wlsServiceId);

         
        try {
            if (!empty($params['serviceid'])) {
                $labelValue = (string) $params['serviceid'];  
                $labelUrl   = rtrim($apiBaseUrl, '/') . '/api/service/' . $wlsServiceId . '/label?label=' . urlencode($labelValue);

                 
                $labelResponse = WhiteLabelServices_APIRequest($labelUrl, $token, 'POST', ['label' => $labelValue]);
                WLS_debugLog("CreateAccount - Service label set to WHMCS service ID: " . $labelValue);
            } else {
                WLS_debugLog("CreateAccount - serviceid not found in params, skipping label update");
            }
        } catch (Exception $e) {
            WLS_debugLog("CreateAccount - Failed to set service label: " . $e->getMessage());
        }
        
         
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
        
         
        Capsule::table('tblhosting')
            ->where('id', $params['serviceid'])
            ->update([
                'username' => 'wls_' . $wlsServiceId,
            ]);
        
         
        WhiteLabelServices_ScheduleVMCheck($params, $wlsServiceId, $token, $apiBaseUrl);
        
         
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
             
            WLS_debugLog("CreateAccount - Order status update failed: " . $e->getMessage());
        }
        
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("CreateAccount Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

 


function WhiteLabelServices_ScheduleVMCheck($params, $wlsServiceId, $token, $apiBaseUrl) {
     
    try {
        WhiteLabelServices_AddToQueue('check_vm_status', [
            'service_id' => $params['serviceid'],
            'wls_service_id' => $wlsServiceId,
            'api_base_url' => $apiBaseUrl,
        ], 1);  
        
        WLS_debugLog("- Scheduled VM status check for service: " . $wlsServiceId);
    } catch (Exception $e) {
        WLS_debugLog("- Failed to schedule VM check: " . $e->getMessage());
    }
}

 







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
        
         
        if ($retryCount >= 60) {
            WLS_debugLog("VMCheck - FAILED after 60 attempts for service: $wlsServiceId", true);
            sendAdminNotification('system', 'WLS Service Provisioning Failed', 
                "Service ID $wlsServiceId did not complete provisioning after 60 attempts.\n" .
                "WHMCS Service ID: $whmcsServiceId\n" .
                "Stage: $stage"
            );
            return "Provisioning failed after $retryCount attempts. Please check provider panel.";
        }
        
         
        if ($stage === 'check_service') {
            $serviceData = WhiteLabelServices_ApiRequestWithTokenRefresh($apiBaseUrl . '/api/service/' . $wlsServiceId, $token, $params);
            
            if (!$serviceData || !isset($serviceData['service'])) {
                throw new \Exception("Invalid service response from API");
            }
            
            $service = $serviceData['service'];
            $status = $service['status'] ?? 'Unknown';
            
            WLS_debugLog("VMCheck - Service status: $status");
            
            if ($status !== 'Active') {
                 
                if (isset($params['queueid'])) {
                    WLSTokenManager::addQueueData($params['queueid'], array_merge($queueData, [
                        'retry_count' => $retryCount,
                        'stage' => 'check_service'
                    ]));
                }
                return WLS_RESULT_RESCHEDULED;
            }
            
             
            Capsule::table('tblhosting')
                ->where('id', $whmcsServiceId)
                ->update([
                    'domainstatus' => 'Active',
                    'domain' => $service['domain'] ?? '',
                    'nextduedate' => $service['next_due'] ?? null,
                ]);
            
            WLS_debugLog("VMCheck - Service Active, WHMCS service $whmcsServiceId activated");
            
             
            $queueData['stage'] = 'get_vm_id';
            $queueData['retry_count'] = 0;
        }
        
         
        if ($stage === 'get_vm_id' || $queueData['stage'] === 'get_vm_id') {
            $vmsData = WhiteLabelServices_ApiRequestWithTokenRefresh($apiBaseUrl . '/api/service/' . $wlsServiceId . '/vms', $token, $params);
            
            if (!$vmsData || !isset($vmsData['vms']) || empty($vmsData['vms'])) {
                 
                if (isset($params['queueid'])) {
                    WLSTokenManager::addQueueData($params['queueid'], array_merge($queueData, [
                        'retry_count' => $retryCount,
                        'stage' => 'get_vm_id'
                    ]));
                }
                return WLS_RESULT_RESCHEDULED;
            }
            
             
            $firstVm = reset($vmsData['vms']);
            $vmId = $firstVm['vmid'] ?? $firstVm['id'] ?? null;
            
            if (!$vmId) {
                throw new Exception("Could not extract VM ID from VMs response");
            }
            
            WLS_debugLog("VMCheck - Found VM ID: $vmId");
            
             
            $queueData['wls_vm_id'] = $vmId;
            $queueData['stage'] = 'check_vm_details';
            $queueData['retry_count'] = 0;
        }
        
         
        if ($queueData['stage'] === 'check_vm_details') {
            $vmId = $queueData['wls_vm_id'] ?? null;
            
             
            if (!$vmId) {
                WLS_debugLog("VMCheck - No VM ID in queue, fetching from VMs API...");
                
                $vmsData = WhiteLabelServices_ApiRequestWithTokenRefresh($apiBaseUrl . '/api/service/' . $wlsServiceId . '/vms', $token, $params);
                
                if ($vmsData && isset($vmsData['vms']) && !empty($vmsData['vms'])) {
                     
                    $firstVmKey = array_key_first($vmsData['vms']);
                    $firstVm = $vmsData['vms'][$firstVmKey];
                    $vmId = $firstVm['id'] ?? $firstVmKey ?? null;
                    
                    if ($vmId) {
                        WLS_debugLog("VMCheck - Discovered VM ID: $vmId, saving to database");
                        
                         
                        Capsule::table('mod_wls_vps')
                            ->where('id', $whmcsServiceId)
                            ->update(['wls_vm_id' => $vmId]);
                        
                         
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
            
             
            $vm = null;
            if (isset($vmData['vm'])) {
                $vm = $vmData['vm'];
            } elseif (isset($vmData['vms'][$vmId])) {
                $vm = $vmData['vms'][$vmId];
            } elseif (isset($vmData['vms']) && is_array($vmData['vms'])) {
                 
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
            
             
            if (!$vmBuilt || $vmStatus !== 'running') {
                 
                if (isset($params['queueid'])) {
                    WLSTokenManager::addQueueData($params['queueid'], array_merge($queueData, [
                        'retry_count' => $retryCount,
                        'stage' => 'check_vm_details'
                    ]));
                }
                return WLS_RESULT_RESCHEDULED;
            }
            
             
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
            
             
             
            $updateResult = localAPI('UpdateClientProduct', [
                'serviceid' => $whmcsServiceId,
                'serviceusername' => $username,
                'servicepassword' => $password,
                'dedicatedip' => $ipv4,
                'assignedips' => $ipv6 ? "$ipv4\n$ipv6" : $ipv4,
            ]);
            
            if ($updateResult['result'] !== 'success') {
                WLS_debugLog("VMCheck - Failed to update service via localAPI: " . ($updateResult['message'] ?? 'Unknown error'));
                 
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
        
         
        if (isset($params['queueid'])) {
            Capsule::table('tblmodulequeue')
                ->where('id', $params['queueid'])
                ->update(['last_attempt_error' => $e->getMessage()]);
        }
        
        return "VM Check Error: " . $e->getMessage();
    }
}

 


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
    
     
    WLS_debugLog("API Request Success - Raw Response (first 500 chars): " . substr($response, 0, 500));
    
    $decoded = json_decode($response, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        WLS_debugLog("API Request - JSON decode error: " . json_last_error_msg());
    }
    
    return $decoded;
}

 
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

         
        $product = Capsule::table('tblproducts')
            ->where('id', $vars['pid'])
            ->first();

        if (!$product) {
            WLS_debugLog("Debug - Product not found: " . $vars['pid']);
            return;
        }

         
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

         
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            WLS_debugLog("Debug - Failed to get API token");
            return;
        }

         
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

         
        $deletedCount = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $vars['pid'])
            ->delete();
        
        WLS_debugLog("Debug - Deleted " . $deletedCount . " existing custom fields");

         
        $formConfig = [];
        $createdFields = 0;

         
        foreach ($forms as $form) {
            WLS_debugLog("Debug - Processing form: " . $form['title'] . " (ID: " . $form['id'] . ", Type: " . $form['type'] . ")");

             
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

             
            $isEnabled = WhiteLabelServices_isFormEnabled($form, $params);
            
            if (!$isEnabled) {
                WLS_debugLog("Debug - Form disabled for custom field, skipping: " . $form['title'] . " (variable: " . ($form['metadata']['variable'] ?? 'none') . ")");
                continue;
            }

             
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

         
        $configResult = WhiteLabelServices_CreateConfigurableOptions($vars['pid'], $formConfig);
        if ($configResult) {
            WLS_debugLog("Debug - Configurable options created successfully");
            
             
            WhiteLabelServices_UpdateConfigurableOptionsPricing($vars['pid'], $formConfig);
        } else {
            WLS_debugLog("Debug - Failed to create configurable options");
        }

    } catch (Exception $e) {
        WLS_debugLog("Error in AdminProductConfigFieldsSave: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}

 
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
    
     
    if (!empty($variable) && isset($variableMap[$variable])) {
        return $variableMap[$variable];
    }
    
     
    if ($type === 'serverselector') {
        return 'location';
    } elseif ($type === 'multicheckbox') {
        return 'addons';
    }
    
     
    return 'custom_' . $form['id'];
}

 
 
 
 
 
 
 

 



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

 
function WhiteLabelServices_ClientArea($params) {
    $requestedAction = isset($_REQUEST['customAction']) ? $_REQUEST['customAction'] : '';

    if ($requestedAction === 'launchConsole' || $requestedAction === 'showConsole') {
        WhiteLabelServices_renderConsolePage($params);
    }

    if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
         
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
                $ip = $_REQUEST['ip'] ?? '';  
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
                 
                $result = WhiteLabelServices_SyncVMFromAPI($params);
                if (isset($result['error'])) {
                    $response = ['success' => false, 'error' => $result['error']];
                } else {
                    $response = ['success' => true, 'message' => 'VM data synchronized'];
                }
                break;
                
            case 'getVMStatus':
                 
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
    
     
    try {
         
        $serviceStatus = $params['status'] ?? 'Active';
        
         
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->first();
        
         
        $templateVars = [
            'serviceid' => $params['serviceid'],
            'domain' => $params['domain'] ?? '',
            'username' => $params['username'] ?? '',
            'password' => $params['password'] ?? '',
            'dedicatedip' => $params['dedicatedip'] ?? '',
            'serviceStatus' => $serviceStatus,
        ];
        
        if ($vpsDetails) {
             
            $rdnsResult = WhiteLabelServices_getRDNS($params);
            $rdnsValue = ($rdnsResult['success'] ?? false) ? ($rdnsResult['rdns'] ?? '') : '';
            $allRdns = ($rdnsResult['success'] ?? false) ? ($rdnsResult['all_rdns'] ?? []) : [];
            
             
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
            
             
            $vmStatusStr = (string) ($vpsDetails->vm_status ?? '');
            $statusLower = strtolower($vmStatusStr);
            $transitional = $vmStatusStr === '' || $statusLower === 'unknown'
                || in_array($statusLower, ['creating', 'pending', 'provisioning', 'rebuilding'], true);
            $templateVars['showVMDetails'] = !empty($vmStatusStr) && !$transitional
                && (!empty($vpsDetails->vm_built) || !empty($vpsDetails->wls_vm_id));
            
             
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

 
function WhiteLabelServices_SyncVMFromAPI($params) {
    try {
         
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            return ['error' => 'Could not get API token'];
        }
        
        $apiBaseUrl = WhiteLabelServices_getApiBaseUrl($params);
        
         
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
        
         
        $vm = $vmData['vm'] ?? $vmData['data'] ?? $vmData;
        
         
        $updateData = [
            'wls_vm_id' => $wlsVmId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
         
        if (isset($vm['status'])) {
            $updateData['vm_status'] = $vm['status'];
        }
        if (isset($vm['hostname'])) {
            $updateData['hostname'] = $vm['hostname'];
        }
         
        if (isset($vm['template_name'])) {
            $updateData['template'] = $vm['template_name'];
        }
         
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
        
         
        if (isset($vm['built'])) {
            $updateData['vm_built'] = $vm['built'] ? 1 : 0;
        } elseif (isset($updateData['vm_status'])) {
            $stable = ['running', 'stopped', 'shutoff', 'paused', 'active'];
            if (in_array(strtolower((string) $updateData['vm_status']), $stable, true)) {
                $updateData['vm_built'] = 1;
            }
        }

         
        Capsule::table('mod_wls_vps')
            ->where('id', $params['serviceid'])
            ->update($updateData);
        
         
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

 
function WhiteLabelServices_ConfigureProductAddons($params) {
    try {
         
        $formConfig = WhiteLabelServices_DecodeFormConfigFromSource($params);
        if (!$formConfig) {
            return [];
        }

         
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
        
         
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            throw new Exception("API token alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±namadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            throw new Exception("Servis bulunamadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

         
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
        
         
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            throw new Exception("API token alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±namadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            throw new Exception("Servis bulunamadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

         
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
        
         
        $token = WLSTokenManager::getToken($params);
        if (!$token) {
            throw new Exception("API token alÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±namadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â±");
        }

         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id) {
             
            WLS_debugLog("Terminate - No VPS details found, assuming already terminated");
            return 'success';
        }

         
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
            
             
            if ($httpCode !== 200 && $httpCode !== 404) {
                WLS_debugLog("Terminate - VM deletion returned HTTP " . $httpCode);
            }
        }

         
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
        
         
        if ($httpCode !== 200 && $httpCode !== 404) {
            throw new Exception("API Error: HTTP " . $httpCode);
        }

         
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
        
         
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("ChangePassword Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

 



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

 



function WhiteLabelServices_SyncVMData(array $params) {
    try {
        $serviceId = $params['serviceid'];
        WLS_debugLog("Debug - SyncVMData called for service: " . $serviceId);
        
         
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
        
         
        $vmInfo = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();

        if (!$vmInfo) {
            return [
                'VM Information' => '<div class="alert alert-warning">No VM information found.</div>'
            ];
        }

         
        $vmInterfaces = json_decode($vmInfo->vm_interfaces, true) ?: [];
        $vmStorage = json_decode($vmInfo->vm_storage, true) ?: [];
        $vmResources = json_decode($vmInfo->vm_resources, true) ?: [];
        $vmBandwidth = json_decode($vmInfo->vm_bandwidth, true) ?: [];
        
         
        $dataReceivedBytes = isset($vmBandwidth['data_received']) ? intval($vmBandwidth['data_received']) : 0;
        $dataSentBytes = isset($vmBandwidth['data_sent']) ? intval($vmBandwidth['data_sent']) : 0;
        $totalTrafficBytes = $dataReceivedBytes + $dataSentBytes;
        
        $dataReceived = formatBytes($dataReceivedBytes);
        $dataSent = formatBytes($dataSentBytes);
        $totalTraffic = formatBytes($totalTrafficBytes);
        
         
        $trafficLimitBytes = 4398046511104;
        $isOverLimit = $totalTrafficBytes > $trafficLimitBytes;
        $trafficWarning = '';

         
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

         
        $template = strtolower($vmInfo->template ?? '');
        if (strpos($template, 'windows') !== false) {
            $displayUsername = 'administrator';
        } elseif (strpos($template, 'mikrotik') !== false) {
            $displayUsername = 'admin';
        } else {
            $displayUsername = 'root';
        }

         
        $pwdHiddenId = 'pwd-hidden-' . $serviceId;
        $pwdVisibleId = 'pwd-visible-' . $serviceId;
        
         
        $output = '
        <style>
             
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
             
            var refreshBtn = document.querySelector(".vm-refresh i");
            refreshBtn.className = "fas fa-sync-alt fa-spin";
            
             
            WHMCS.http.jqClient.post("addonmodules.php?module=WLS", {
                action: "refresh",
                service_id: serviceId,
                token: csrfToken
            }, function(data) {
                if (data.success) {
                     
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
                 
                hiddenPwd.style.display = "none";
                visiblePwd.style.display = "inline";
                icon.className = "fas fa-eye-slash";
            } else {
                 
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

 
function WhiteLabelServices_CreateConfigurableOptions($productId, $formConfig) {
    try {
        WLS_debugLog("Debug - Creating configurable options for product: " . $productId);
        
         
        $existingLinks = Capsule::table('tblproductconfiglinks')
            ->where('pid', $productId)
            ->get();
            
        foreach ($existingLinks as $link) {
             
            $options = Capsule::table('tblproductconfigoptions')
                ->where('gid', $link->gid)
                ->get();
                
            foreach ($options as $option) {
                 
                Capsule::table('tblproductconfigoptionssub')
                    ->where('configid', $option->id)
                    ->delete();
                    
                 
                Capsule::table('tblpricing')
                    ->where('type', 'configoptions')
                    ->whereIn('relid', function($query) use ($option) {
                        $query->select('id')
                              ->from('tblproductconfigoptionssub')
                              ->where('configid', $option->id);
                    })
                    ->delete();
            }
            
             
            Capsule::table('tblproductconfigoptions')
                ->where('gid', $link->gid)
                ->delete();
        }
        
         
        Capsule::table('tblproductconfiglinks')
            ->where('pid', $productId)
            ->delete();
            
         
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
                continue;  
            }
            
            WLS_debugLog("Debug - Processing paid form for configurable options: " . $form['title']);

             
            $groupId = Capsule::table('tblproductconfiggroups')->insertGetId([
                'name' => $form['title'],
                'description' => $form['title']
            ]);
            
             
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

             
            $optionId = Capsule::table('tblproductconfigoptions')->insertGetId([
                'gid' => $groupId,
                'optionname' => $form['title'],
                'optiontype' => 1,  
                'qtyminimum' => $qtyMin,
                'qtymaximum' => $qtyMax,
                'order' => 1,
                'hidden' => 0
            ]);
            
            WLS_debugLog("Debug - Created config option: " . $form['title'] . " (ID: " . $optionId . ")");
            
             
            $subOrder = 1;
            
             
            switch ($form['type']) {
                case 'slider':
                     
                    if (isset($form['items']) && count($form['items']) > 0) {
                        $item = reset($form['items']);  
                        $unitPrice = $item['unit_price'];
                        
                         
                        $min = 0;
                        $max = 10;
                        $step = 1;
                        
                         
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
                     
                    if (isset($form['items'])) {
                         
                        $noneSubId = Capsule::table('tblproductconfigoptionssub')->insertGetId([
                            'configid' => $optionId,
                            'optionname' => 'None',
                            'sortorder' => $subOrder++,
                            'hidden' => 0
                        ]);
                        
                         
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
                        
                         
                    foreach ($form['items'] as $item) {
                            $subId = Capsule::table('tblproductconfigoptionssub')->insertGetId([
                                'configid' => $optionId,
                                'optionname' => $item['title'],
                                'sortorder' => $subOrder++,
                                'hidden' => 0
                            ]);
                            
                             
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

 
function WhiteLabelServices_UpdateConfigurableOptionsPricing($productId, $formConfig) {
    try {
        WLS_debugLog("Debug - Updating configurable options pricing for product: " . $productId);
        
         
        $links = Capsule::table('tblproductconfiglinks')
            ->where('pid', $productId)
            ->get();
            
        foreach ($links as $link) {
            $group = Capsule::table('tblproductconfiggroups')
                ->where('id', $link->gid)
                ->first();
                
            if (!$group) continue;
            
             
            $matchingForm = null;
            foreach ($formConfig as $form) {
                if ($form['title'] === $group->name) {
                    $matchingForm = $form;
                    break;
                }
            }
            
            if (!$matchingForm) continue;
            
            WLS_debugLog("Debug - Updating pricing for group: " . $group->name);
            
             
            $options = Capsule::table('tblproductconfigoptions')
                ->where('gid', $link->gid)
                ->get();
                
            foreach ($options as $option) {
                 
                $subOptions = Capsule::table('tblproductconfigoptionssub')
                    ->where('configid', $option->id)
                    ->get();
                    
                foreach ($subOptions as $subOption) {
                     
                    $newPrice = WhiteLabelServices_CalculateOptionPrice($matchingForm, $subOption->optionname);
                    
                    if ($newPrice !== null) {
                         
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

 
function WhiteLabelServices_CalculateOptionPrice($form, $optionName) {
    try {
        $formVariable = $form['variable'] ?? '';
        
        switch ($form['type']) {
                case 'slider':
                 
                if (isset($form['items']) && count($form['items']) > 0) {
                    $item = reset($form['items']);  
                    $unitPrice = $item['unit_price'];
                    
                     
                    if (preg_match('/(\d+)/', $optionName, $matches)) {
                        $quantity = intval($matches[1]);
                        
                         
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
                 
                if ($optionName === 'None') {
                    return 0;
                }
                
                 
                if (isset($form['items'])) {
                    foreach ($form['items'] as $item) {
                        if (strpos($optionName, $item['title']) !== false) {
                            return $item['unit_price'];
                        }
                    }
                    }
                    break;
            }

        return null;  
        
    } catch (Exception $e) {
        WLS_debugLog("Error calculating price: " . $e->getMessage());
        return null;
    }
}

 
function WhiteLabelServices_DailyPriceUpdate() {
    try {
        WLS_debugLog("Debug - Daily price update started");
        
         
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

 
function WhiteLabelServices_UpdateSingleProductPricing($product) {
    try {
        $formConfig = WhiteLabelServices_DecodeFormConfigFromSource($product);
        if (!$formConfig) {
            return false;
        }
        
         
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
        
         
        $updatedFormConfig = WLS_FetchProductConfig($apiProductId, $token);
        if (!$updatedFormConfig) return false;
        
        $formCol = WhiteLabelServices_FormConfigStorageColumnName();
        Capsule::table('tblproducts')
            ->where('id', $product->id)
            ->update(array(
                $formCol => json_encode($updatedFormConfig),
            ));
            
         
        WhiteLabelServices_UpdateConfigurableOptionsPricing($product->id, $updatedFormConfig);
        
        WLS_debugLog("Debug - Updated pricing for product: " . $product->name . " (ID: " . $product->id . ")");
        return true;
        
    } catch (Exception $e) {
        WLS_debugLog("Error updating single product pricing: " . $e->getMessage());
        return false;
    }
}

 
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) return false;
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['product']['config']['forms'])) return false;
        
         
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

 
function WhiteLabelServices_MapCustomFieldToItemId($formConfig, $fieldName, $fieldValue) {
    try {
         
        $targetForm = null;
        foreach ($formConfig as $form) {
             
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
        
         
        $normalizedValue = preg_replace('/[,\s]+/', ' ', trim($fieldValue));
        
         
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
                 
                if ($quantity === 'None') {
                     
                    if (isset($targetForm['items']) && count($targetForm['items']) > 0) {
                        $firstItem = reset($targetForm['items']);
                        return [
                            'form_id' => $targetForm['id'],
                            'item_id' => $firstItem['id'],
                            'quantity' => 0
                        ];
                    }
                } else {
                     
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

 
function WhiteLabelServices_ConvertBillingCycle($whmcsCycle) {
    $cycleMap = [
        'Monthly' => 'm',
        'Quarterly' => 'q', 
        'Semi-Annually' => 'q',  
        'Annually' => 'a',
        'Biennially' => 'b',
        'Triennially' => 't',
        'Free Account' => 'm',  
        'One Time' => 'm'  
    ];
    
    return $cycleMap[$whmcsCycle] ?? 'm';  
}

 
function WhiteLabelServices_BuildOrderPayload($params) {
    try {
        WLS_debugLog("Debug - Building order payload for service: " . $params['serviceid']);
        
        $formConfig = WhiteLabelServices_DecodeFormConfigFromSource($params);

        $productId = WhiteLabelServices_ExtractProductId(WhiteLabelServices_ResolveWlsApiProductRaw($params));
        
         
        $domainVal = !empty($params['domain']) ? $params['domain'] : 'wls-vm-' . time() . '-' . $params['serviceid'];
        $payload = [
            'product_id' => $productId,
            'domain' => $domainVal,
            'hostname' => $domainVal,
            'cycle' => WhiteLabelServices_ConvertBillingCycle($params['billingcycle'] ?? 'monthly'),
            'pay_method' => '120',  
            'custom' => []
        ];
        
        $promoCode = WhiteLabelServices_ResolvePromoCodeFromParams($params);
        if ($promoCode === '') {
             
            try {
                $promoSetting = Capsule::table('mod_wls_settings')
                    ->where('setting_key', 'promo_code')
                    ->first();
                if ($promoSetting && !empty($promoSetting->setting_value)) {
                    $promoCode = trim($promoSetting->setting_value);
                }
            } catch (Exception $e) {
                 
            }
        }
        
        if (!empty($promoCode)) {
            $payload['promocode'] = $promoCode;
            WLS_debugLog("Debug - Promocode added: " . $promoCode);
        }
        
         
        if (!$formConfig) {
            WLS_debugLog("Debug - No form config found, using basic payload");
            return $payload;
        }
        
         
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
        
         
        if (!empty($params['configoptions'])) {
            foreach ($params['configoptions'] as $optionName => $selectedValue) {
                if (empty($selectedValue) || $selectedValue === 'None') {
                    continue;  
                }
                
                 
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

 
function WhiteLabelServices_ProcessPendingOrders() {
    try {
        WLS_debugLog("Debug - Processing pending orders");
        
         
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
             
            $vpsDetails = WLSTokenManager::getVPSDetails($service->id);
            
             
            if ($vpsDetails && $vpsDetails->wls_service_id && 
                (!$vpsDetails->vm_status || $vpsDetails->vm_status !== 'running' || 
                 !$vpsDetails->vm_built)) {
                
                 
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

 
function WhiteLabelServices_CheckServiceStatus($serviceId, $token) {
    return WLSTokenManager::checkServiceStatus($serviceId, $token);
}

 
function WhiteLabelServices_getVMList($serviceId, $token) {
    return WLSTokenManager::getVMList($serviceId, $token);
}

 
function WhiteLabelServices_getVMDetails($whmcsServiceId, $vmId, $token) {
    try {
         
        WLS_debugLog("Debug - Getting VM details for WHMCS Service ID: " . $whmcsServiceId);
        
         
        $vpsDetails = WLSTokenManager::getVPSDetails($whmcsServiceId);
        if (!$vpsDetails) {
            throw new Exception("VPS kaydÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± bulunamadÃƒÆ’Ã¢â‚¬ÂÃƒâ€šÃ‚Â± (WHMCS Service ID: " . $whmcsServiceId . ")");
        }

         
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

 

 
function WhiteLabelServices_AddToQueue($action, $data, $priority = 1, $scheduledAt = null) {
    try {
         
        if ($action === 'check_vm_status') {
            WLS_debugLog("Queue - Skipping WHMCS module queue for check_vm_status, will be handled by custom cron loop");
            return 0;
        }
         
        $lastAttempt = null;
        if ($scheduledAt && $scheduledAt > time()) {
            $lastAttempt = date('Y-m-d H:i:s', $scheduledAt);
        }
        
         
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
        
         
        WLSTokenManager::addQueueData($moduleQueueId, $data);
        
        WLS_debugLog("Queue - Added task to WHMCS module queue: " . $action . " (ID: " . $moduleQueueId . ")" . ($lastAttempt ? " scheduled for: " . $lastAttempt : ""));
        return $moduleQueueId;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue Error - Failed to add task to WHMCS queue: " . $e->getMessage());
        return false;
    }
}

 



function WhiteLabelServices_ProcessPendingVMChecks() {
    try {
        WLSTokenManager::ensureTablesExist();

         
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

             
            $result = WhiteLabelServices_check_vm_status(['serviceid' => $serviceId]);

             
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

 



function WhiteLabelServices_QueuePowerAction($params, $action) {
    try {
        $serviceId = $params['serviceid'];
        
         
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();
            
        if (!$vpsDetails || empty($vpsDetails->wls_service_id) || empty($vpsDetails->wls_vm_id)) {
            return ['error' => 'VM not provisioned yet'];
        }
        
         
        $queueData = [
            'service_id' => $serviceId,
            'wls_service_id' => $vpsDetails->wls_service_id,
            'wls_vm_id' => $vpsDetails->wls_vm_id,
            'action' => $action,
            'requested_at' => date('Y-m-d H:i:s'),
            'requested_by' => 'clientarea'
        ];
        
         
        $queueId = WhiteLabelServices_AddToQueue('power_' . $action, $queueData);
        
        if (!$queueId) {
             
            WLS_debugLog("Queue - Failed to queue, executing directly: " . $action);
            return WhiteLabelServices_handleVMAction($params, $action);
        }
        
         
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

 



function WhiteLabelServices_QueueRebuild($params, $template) {
    try {
        $serviceId = $params['serviceid'];
        
         
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();
            
        if (!$vpsDetails || empty($vpsDetails->wls_service_id) || empty($vpsDetails->wls_vm_id)) {
            return ['error' => 'VM not provisioned yet'];
        }
        
         
        $queueData = [
            'service_id' => $serviceId,
            'wls_service_id' => $vpsDetails->wls_service_id,
            'wls_vm_id' => $vpsDetails->wls_vm_id,
            'template' => $template,
            'requested_at' => date('Y-m-d H:i:s'),
            'requested_by' => 'clientarea'
        ];
        
         
        $queueId = WhiteLabelServices_AddToQueue('rebuild', $queueData);
        
        if (!$queueId) {
             
            WLS_debugLog("Queue - Failed to queue rebuild, executing directly");
            return WhiteLabelServices_rebuildVM($params, $template);
        }
        
         
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

 
function WhiteLabelServices_ProcessQueue() {
    try {
         
        $cronLockToken = WLSTokenManager::acquireCronLock('wls_queue_process', 120);
        if (!$cronLockToken) {
            WLS_debugLog("Queue - Already running, skipping this execution");
            return 0;
        }
        
        WLS_debugLog("Queue - Processing WHMCS module queue tasks");
        
         
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
                 
                $taskLockToken = WLSTokenManager::lockQueueData($task->id);
                if (!$taskLockToken) {
                    WLS_debugLog("Queue - Task " . $task->id . " is locked by another process, skipping");
                    continue;
                }
                
                 
                Capsule::table('tblmodulequeue')
                    ->where('id', $task->id)
                    ->update([
                        'last_attempt' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                 
                $data = WLSTokenManager::getQueueData($task->id, $taskLockToken);
                
                if (!$data) {
                    WLS_debugLog("Queue - No data found for task: " . $task->id);
                     
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
                
                 
                switch ($task->module_action) {
                    case 'process_order':
                        $result = WhiteLabelServices_ProcessOrderTask($data);
                        break;
                    case 'check_vm_status':
                         
                        $whmcsServiceId = $data['service_id'] ?? 0;
                        if ($whmcsServiceId) {
                            $service = Capsule::table('tblhosting')->where('id', $whmcsServiceId)->first();
                            if ($service) {
                                $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
                                $serverParams = [];
                                if ($product && $product->servertype === 'WhiteLabelServices') {
                                     
                                    $server = Capsule::table('tblservers')
                                        ->where('type', 'WhiteLabelServices')
                                        ->where('active', '1')
                                        ->first();
                                    
                                    if ($server) {
                                         
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
                         
                        $result = WhiteLabelServices_ProcessOrderTask($data);
                        break;
                    
                     
                    case 'power_start':
                    case 'power_stop':
                    case 'power_shutdown':
                    case 'power_reboot':
                    case 'power_reset':
                        $result = WhiteLabelServices_ProcessPowerActionTask($data);
                        break;
                    
                     
                    case 'rebuild':
                        $result = WhiteLabelServices_ProcessRebuildTask($data);
                        break;
                        
                    default:
                        WLS_debugLog("Queue - Unknown action: " . $task->module_action);
                         
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
                
                 
                if ($result === WLS_RESULT_SUCCESS || $result === true) {
                     
                    Capsule::table('tblmodulequeue')
                        ->where('id', $task->id)
                        ->update([
                            'completed' => 1,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                     
                    WLSTokenManager::deleteQueueData($task->id);
                    
                    $processedCount++;
                    WLS_debugLog("Queue - Task completed: " . $task->module_action . " (ID: " . $task->id . ")");
                } elseif ($result === WLS_RESULT_RESCHEDULED) {
                     
                    Capsule::table('tblmodulequeue')
                        ->where('id', $task->id)
                        ->update([
                            'completed' => 1,
                            'last_attempt_error' => null,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                     
                    WLSTokenManager::deleteQueueData($task->id);
                    
                    WLS_debugLog("Queue - Task rescheduled: " . $task->module_action . " (ID: " . $task->id . ")");
                } else {
                     
                    $retryCount = ($task->num_retries ?? 0) + 1;
                    if ($retryCount >= 5) {
                         
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
        
         
        WLSTokenManager::releaseCronLock('wls_queue_process', $cronLockToken);
        
        WLS_debugLog("Queue - Processed " . $processedCount . " tasks successfully from WHMCS module queue");
        return $processedCount;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue Error: " . $e->getMessage());
         
        WLSTokenManager::releaseCronLock('wls_queue_process');
        return false;
    }
}

 
function WhiteLabelServices_ProcessOrderTask($data) {
    try {
        $serviceId = $data['service_id'];
        $wlsProductId = $data['WLS_product_id'];
        $payload = $data['payload'];
        
        WLS_debugLog("Queue - Processing order for service: " . $serviceId);
        
         
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
        
         
        $updateData = [
            'status' => 'completed',
            'order_num' => $orderData['order_num'],
            'invoice_id' => $orderData['invoice_id'],
            'total' => $orderData['total'] ?? '0.00',
            'completed_date' => date('Y-m-d H:i:s'),
            'last_check' => date('Y-m-d H:i:s')
        ];
            
             
            if (isset($orderData['items']) && is_array($orderData['items'])) {
                foreach ($orderData['items'] as $item) {
                    if (isset($item['id'])) {
                    $updateData['wls_service_id'] = $item['id'];
                    $updateData['status'] = 'provisioning';
                
                 
                WhiteLabelServices_AddToQueue('check_vm_status', [
                    'service_id' => $serviceId,
                    'wls_service_id' => $item['id']
                ]);
                
                WLS_debugLog("Queue - Order completed, added VM status check to queue");
                    break;
            }
            }
        }
        
         
        WLSTokenManager::updateVPSDetails($serviceId, $updateData);
        
        WLS_debugLog("Queue - Order processed successfully for service: " . $serviceId);
        
         
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

 
function WhiteLabelServices_CheckVMStatusTask($data) {
    try {
        $serviceId = $data['service_id'];
        $wlsServiceId = $data['wls_service_id'];
        
        WLS_debugLog("Queue - Checking VM status for service: " . $serviceId);
        
         
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
        
         
        $serviceStatus = WhiteLabelServices_CheckServiceStatus($wlsServiceId, $token);
        if (!$serviceStatus || !isset($serviceStatus['service'])) {
            WLS_debugLog("Queue - Failed to get service status");
            return false;
        }
        
         
        $updateData = [
            'status' => $serviceStatus['service']['status'],
            'last_check' => date('Y-m-d H:i:s')
        ];
        
         
        if ($serviceStatus['service']['status'] === 'Active') {
            $vmList = WhiteLabelServices_getVMList($wlsServiceId, $token);
            if ($vmList && isset($vmList['vms']) && !empty($vmList['vms'])) {
                foreach ($vmList['vms'] as $vmId => $vm) {
                    $updateData['wls_vm_id'] = $vmId;
                    $updateData['vm_status'] = $vm['status'];
                    $updateData['vm_built'] = $vm['built'] ? true : false;
                    $updateData['vm_power'] = $vm['power'] ? true : false;
                    
                     
                    if ($vm['status'] === 'running' && $vm['built']) {
                        $vmDetails = WhiteLabelServices_getVMDetails($serviceId, $vmId, $token);
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
                    break;  
                }
            }
        }
        
         
        WLSTokenManager::updateVPSDetails($serviceId, $updateData);
        
        return true;
        
    } catch (Exception $e) {
        WLS_debugLog("Queue - Error checking VM status: " . $e->getMessage());
        return false;
    }
}

 



function WhiteLabelServices_ProcessPowerActionTask($data) {
    try {
        $serviceId = $data['service_id'];
        $wlsServiceId = $data['wls_service_id'];
        $vmId = $data['wls_vm_id'];
        $action = $data['action'];
        
        WLS_debugLog("Queue - Processing power action: " . $action . " for service " . $serviceId);
        
         
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
            
             
            sleep(2);
            
             
            WhiteLabelServices_CheckVMStatusTask([
                'service_id' => $serviceId,
                'wls_service_id' => $wlsServiceId
            ]);
            
            return true;
        }
        
        $errorMsg = isset($result['error']) ? implode(', ', (array)$result['error']) : 'Unknown error';
        WLS_debugLog("Queue - Power action failed: " . $errorMsg . " (HTTP " . $httpCode . ")");
        
         
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

 



function WhiteLabelServices_ProcessRebuildTask($data) {
    try {
        $serviceId = $data['service_id'];
        $wlsServiceId = $data['wls_service_id'];
        $vmId = $data['wls_vm_id'];
        $template = $data['template'];
        
        WLS_debugLog("Queue - Processing rebuild for service " . $serviceId . " with template: " . $template);
        
         
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);  
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
            
             
            Capsule::table('mod_wls_vps')
                ->where('id', $serviceId)
                ->update([
                    'vm_status' => 'rebuilding',
                    'vm_built' => false,
                    'template' => $template,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
             
            WhiteLabelServices_AddToQueue('check_vm_status', [
                'service_id' => $serviceId,
                'wls_service_id' => $wlsServiceId
            ], 5);  
            
            return true;
        }
        
        $errorMsg = isset($result['message']) ? $result['message'] : 'Unknown error';
        WLS_debugLog("Queue - Rebuild failed: " . $errorMsg . " (HTTP " . $httpCode . ")");
        
         
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

 




 
function WhiteLabelServices_power_start(array $params) {
    WLS_debugLog("Queue - power_start called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'start');
}

 
function WhiteLabelServices_power_stop(array $params) {
    WLS_debugLog("Queue - power_stop called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'stop');
}

 
function WhiteLabelServices_power_shutdown(array $params) {
    WLS_debugLog("Queue - power_shutdown called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'shutdown');
}

 
function WhiteLabelServices_power_reboot(array $params) {
    WLS_debugLog("Queue - power_reboot called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'reboot');
}

 
function WhiteLabelServices_power_reset(array $params) {
    WLS_debugLog("Queue - power_reset called for service: " . $params['serviceid']);
    return WhiteLabelServices_executePowerAction($params, 'reset');
}

 
function WhiteLabelServices_rebuild(array $params) {
    WLS_debugLog("Queue - rebuild called for service: " . $params['serviceid']);
    
     
    $template = $params['template'] ?? '';
    
    if (empty($template)) {
         
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

 



function WhiteLabelServices_executePowerAction($params, $action) {
    try {
        $serviceId = $params['serviceid'];
        
         
        $vpsDetails = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();
            
        if (!$vpsDetails || empty($vpsDetails->wls_service_id) || empty($vpsDetails->wls_vm_id)) {
            return 'VM not provisioned yet';
        }
        
        $wlsServiceId = $vpsDetails->wls_service_id;
        $vmId = $vpsDetails->wls_vm_id;
        
         
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
        
         
        if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
            $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
        }
        
         
        if (isset($vmData['storage']) && is_array($vmData['storage'])) {
            $updateData['vm_storage'] = json_encode($vmData['storage']);
        }
        
         
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
        
         
        $updateData['status'] = 'Active';
        $updateData['service_status'] = 'Active';
        
         
        WLSTokenManager::updateVPSDetails($params['serviceid'], $updateData);
        
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("Refresh Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

 
function WhiteLabelServices_start(array $params) {
    $result = WhiteLabelServices_powerAction($params, 'start');
    sleep(2);  
    WhiteLabelServices_refresh($params);  
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

 
function WhiteLabelServices_powerAction(array $params, string $action, bool $isRetry = false) {
    try {
        WLS_debugLog("Power - {$action} for service: " . $params['serviceid'] . ($isRetry ? " (retry)" : ""));
        
         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_vm_id) {
            throw new Exception('VM ID not found');
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
        
         
        if ($isRetry) {
            WLSTokenManager::clearToken($server->id);
        }
        
        $token = WhiteLabelServices_getToken($apiParams);
        if (!$token) {
            throw new Exception('Failed to get API token');
        }
        
         
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
            WLS_debugLog("Power - cURL Error: {$curlError}", true);  
            throw new Exception("Connection error: {$curlError}");
        }
        
        $responseData = json_decode($response, true);
        WLS_debugLog("Power - Response: " . $response);
        
         
        if (!$isRetry && isset($responseData['error']) && is_array($responseData['error'])) {
            $errors = $responseData['error'];
            if (in_array('token_expired', $errors) || in_array('unauthorized', $errors)) {
                WLS_debugLog("Power - Token expired, refreshing and retrying...");
                return WhiteLabelServices_powerAction($params, $action, true);
            }
        }
        
         
        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['status']) && $responseData['status'] === true) {
            WLS_debugLog("Power - {$action} successful for VM: {$vmId}");
            return 'success';
        } else {
            $errorMsg = $responseData['message'] ?? (is_array($responseData['error'] ?? null) ? implode(', ', $responseData['error']) : ($responseData['error'] ?? "HTTP {$httpCode}"));
            throw new Exception($errorMsg);
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Power Error - {$action}: " . $e->getMessage(), true);  
        return $e->getMessage();
    }
}

 
function WhiteLabelServices_synchronizeService(array $params) {
    try {
        WLS_debugLog("Sync - Starting synchronization for service: " . $params['serviceid']);
        
         
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
        
         
        $serviceStatus = WhiteLabelServices_CheckServiceStatus($vpsDetails->wls_service_id, $token);
        if (!$serviceStatus || !isset($serviceStatus['service'])) {
            throw new Exception('Failed to get service status from WLS API');
        }
        
         
        $updateData = [
            'service_status' => $serviceStatus['service']['status'],
            'last_check' => date('Y-m-d H:i:s')
        ];
        
        $syncMessage = 'Service synchronized successfully';
        $vmUpdated = false;
        
         
        if ($serviceStatus['service']['status'] === 'Active') {
            $vmList = WhiteLabelServices_getVMList($vpsDetails->wls_service_id, $token);
            if ($vmList && isset($vmList['vms']) && !empty($vmList['vms'])) {
                foreach ($vmList['vms'] as $vmId => $vm) {
                    $updateData['wls_vm_id'] = $vmId;
                    $updateData['vm_status'] = $vm['status'];
                    $updateData['vm_built'] = $vm['built'] ? true : false;
                    $updateData['vm_power'] = $vm['power'] ? true : false;
                    
                     
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
                            
                             
                            if (isset($vmData['storage']) && is_array($vmData['storage'])) {
                                $updateData['vm_storage'] = json_encode($vmData['storage']);
                            }
                            
                             
                            if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
                                $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
                            }
                            
                             
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
                    
                    break;  
                }
            } else {
                $syncMessage = 'Service synchronized. No VMs found for this service.';
            }
        } else {
            $syncMessage = 'Service synchronized. Service status: ' . $serviceStatus['service']['status'];
        }
        
         
        WLSTokenManager::updateVPSDetails($params['serviceid'], $updateData);
        
        return 'success';
        
    } catch (Exception $e) {
        WLS_debugLog("Sync Error: " . $e->getMessage());
        return $e->getMessage();
    }
}

 
function WhiteLabelServices_checkVMStatusButton(array $params) {
    try {
        WLS_debugLog("VM Check - checkVMStatusButton called with params: " . print_r($params, true));
        
         
        $notes = '';
        if (!empty($params['notes'])) {
            $notes = $params['notes'];
            WLS_debugLog("VM Check - Notes from params['notes']: " . substr($notes, 0, 200) . "...");
        } else {
             
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
        
         
        $result = WhiteLabelServices_check_vm_status($params);
        
        if ($result === 'success' || $result === true) {
             
            $updatedService = Capsule::table('tblhosting')->where('id', $params['serviceid'])->first();
            $updatedNotes = json_decode($updatedService->notes, true) ?: [];
            
            $message = 'VM status checked successfully';
            
             
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

 
function WhiteLabelServices_createCustomFieldData($form, $productId) {
            $fieldType = '';
    $fieldOptions = [];
    $fieldName = WhiteLabelServices_generateFieldName($form);

            switch ($form['type']) {
                case 'serverselector':
                    $fieldType = 'dropdown';
             
                    foreach ($form['items'] as $item) {
                        if ($item['selected']) {
                     
                    $normalizedTitle = preg_replace('/[,\s]+/', ' ', trim($item['title']));
                    $fieldOptions[] = $normalizedTitle;
                    break;
                }
            }
             
            if (empty($fieldOptions) && !empty($form['items'])) {
                        $firstItem = reset($form['items']);
                $normalizedTitle = preg_replace('/[,\s]+/', ' ', trim($firstItem['title']));
                $fieldOptions[] = $normalizedTitle;
            }
                    break;

                case 'select':
                    $fieldType = 'dropdown';
                    if ($form['metadata']['variable'] === 'os') {
                 
                $centosAdded = false;
                        foreach ($form['items'] as $item) {
                            if (stripos($item['title'], 'CentOS 7') !== false) {
                        $fieldOptions[] = $item['title'];
                        $centosAdded = true;
                                break;
                            }
                        }
                 
                        foreach ($form['items'] as $item) {
                            if (stripos($item['title'], 'CentOS 7') === false) {
                        $fieldOptions[] = $item['title'];
                            }
                        }
                 
                if (!$centosAdded && !empty($form['items'])) {
                            $firstItem = reset($form['items']);
                    array_unshift($fieldOptions, $firstItem['title']);
                }
            } else {
                 
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
            
             
            if ($form['metadata']['variable'] === 'ipamlimit') {
                $min = max(1, $min);  
                for ($i = $min; $i <= $max; $i += $step) {
                    $fieldOptions[] = $i . ' IP';
                }
            } else {
                 
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

 
function WhiteLabelServices_handleVMAction($params, $action) {
    try {
        WLS_debugLog("VM Action - Starting action: " . $action . " for service: " . $params['serviceid']);
        
         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            WLS_debugLog("VM Action - Missing service or VM ID");
            return ['error' => 'VM not provisioned yet'];
        }
        
         
        $initialStatus = $vpsDetails->vm_status;
        
         
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
        
         
        if ($httpCode === 200 && isset($result['status'])) {
            if ($result['status'] === true) {
                WLS_debugLog("VM Action - {$action} command sent successfully for service: " . $params['serviceid']);
                
                 
                sleep(3);
                
                 
                try {
                    WhiteLabelServices_SyncVMFromAPI($params);
                } catch (Exception $syncError) {
                    WLS_debugLog("VM Action - Sync error (non-fatal): " . $syncError->getMessage());
                }
                
                 
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
        
         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            WLS_debugLog("Network - Missing VPS details for service: " . $params['serviceid']);
            return ['error' => 'VM not provisioned yet'];
        }
        
        $wlsServiceId = $vpsDetails->wls_service_id;
        $vmId = $vpsDetails->wls_vm_id;
        
        WLS_debugLog("Network - WLS Service ID: " . $wlsServiceId);
        WLS_debugLog("Network - VM ID: " . $vmId);
        
         
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
        
         
        if ($interfaceId === 'net0' || $interfaceId === '0') {
            WLS_debugLog("Network - Attempted to delete primary interface (net0)");
            return ['error' => 'Cannot delete primary network interface (net0)'];
        }
        
         
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

 
function WhiteLabelServices_process_order($params) {
    try {
        WLS_debugLog("Debug - process_order function called with params: " . print_r($params, true));
        
         
        $serviceId = $params['serviceid'] ?? null;
        if (!$serviceId) {
            WLS_debugLog("Error - No service ID provided to process_order");
            return 'No service ID provided';
        }
        
         
        $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
        if (!$service) {
            WLS_debugLog("Error - Service not found: " . $serviceId);
            return 'Service not found';
        }
        
         
        $notes = json_decode($service->notes, true) ?: [];
        
         
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

 
function WhiteLabelServices_check_vm_status($params) {
    try {
         
        WLSTokenManager::ensureTablesExist();
        
         
        $serviceId = $params['serviceid'] ?? null;
        if (!$serviceId) {
            WLS_debugLog("check_vm_status - No service ID provided");
            return 'No service ID provided';
        }
        
         
        $wlsData = Capsule::table('mod_wls_vps')
            ->where('id', $serviceId)
            ->first();
        
        if (!$wlsData) {
            WLS_debugLog("check_vm_status - No WLS data found for service: $serviceId");
            return 'No WLS data found';
        }
        
         
        $wlsServiceId = $wlsData->wls_service_id ?? null;
        if (!$wlsServiceId) {
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
        
         
        $queueData = [
            'service_id' => $serviceId,
            'wls_service_id' => $wlsServiceId,
            'wls_vm_id' => $wlsData->wls_vm_id ?? null,
            'api_base_url' => WLSTokenManager::getApiBaseUrl(),
            'stage' => $wlsData->vm_built ? 'check_vm_details' : 'check_service',
            'retry_count' => 0,
        ];
        
        WLS_debugLog("check_vm_status - Processing service $serviceId with WLS ID $wlsServiceId");
        
         
        $result = WhiteLabelServices_ProcessVMStatusCheck($params, $queueData);
        
         
        if ($result === WLS_RESULT_SUCCESS) {
            return 'success';
        } elseif ($result === WLS_RESULT_RESCHEDULED) {
             
            return 'success';
        } else {
            return $result ?: 'VM status check failed';
        }
        
    } catch (Exception $e) {
        WLS_debugLog("Error in check_vm_status: " . $e->getMessage());
        return $e->getMessage();
    }
}

 
function WhiteLabelServices_ProcessOrder($params) {
    return WhiteLabelServices_process_order($params);
}

function WhiteLabelServices_CheckVMStatus($params) {
    return WhiteLabelServices_check_vm_status($params);
}

 
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

 
function WhiteLabelServices_updateVMInfoAfterPowerAction($params) {
    try {
        WLS_debugLog("Debug - Updating VM info after power action for service: " . $params['serviceid']);
        
         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            WLS_debugLog("Debug - Missing service or VM ID for update");
            return false;
        }
        
         
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
        
         
        $vmDetails = WhiteLabelServices_getVMDetails($params['serviceid'], $vpsDetails->wls_vm_id, $token);
        if ($vmDetails && isset($vmDetails['vm'])) {
            $vmData = $vmDetails['vm'];
            
             
            $updateData = [
                'vm_status' => $vmData['status'] ?? $vpsDetails->vm_status,
                'vm_power' => $vmData['power'] ?? $vpsDetails->vm_power,
                'vm_built' => $vmData['built'] ?? $vpsDetails->vm_built,
                'last_check' => date('Y-m-d H:i:s')
            ];
            
             
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
                
                 
                if (isset($vmData['storage']) && is_array($vmData['storage'])) {
                    $updateData['vm_storage'] = json_encode($vmData['storage']);
                }
                
                 
                if (isset($vmData['interfaces']) && is_array($vmData['interfaces'])) {
                    $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
                }
                
                 
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

 
function WhiteLabelServices_updateLabel($params, $label) {
    try {
         
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

 
function WhiteLabelServices_getTemplates($params) {
    try {
        WLS_debugLog("Debug - Getting templates for service: " . $params['serviceid']);
        
         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id) {
            WLS_debugLog("Error - No WLS service ID found for templates");
            return [];
        }
        
        $serviceId = $vpsDetails->wls_service_id;
        
         
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
        
         
        $templates = $data['templates'];
        $osFamilies = [];
        
         
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
            
             
            foreach ($familyRules as $family => $rule) {
                if (strpos($nameLower, $rule['match']) !== false) {
                    $familyKey = $family;
                    $familyInfo = $rule;
                    break;
                }
            }
            
             
            if (!isset($osFamilies[$familyKey])) {
                $osFamilies[$familyKey] = [
                    'name' => $familyKey,
                    'icon' => $familyInfo['icon'],
                    'color' => $familyInfo['color'],
                    'isWindows' => $familyInfo['isWindows'],
                    'versions' => []
                ];
            }
            
             
            $osFamilies[$familyKey]['versions'][] = [
                'id' => $template['id'],
                'name' => $name,
                'size_gb' => $template['size_gb'] ?? 0
            ];
        }
        
         
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

 
function WhiteLabelServices_rebuildVM($params, $templateId) {
    try {
        WLS_debugLog("Debug - Rebuilding VM for service: " . $params['serviceid'] . " with template: " . $templateId);
        
         
        $vpsDetails = WLSTokenManager::getVPSDetails($params['serviceid']);
        if (!$vpsDetails || !$vpsDetails->wls_service_id || !$vpsDetails->wls_vm_id) {
            return ['error' => 'VM information not found'];
        }
        
        $serviceId = $vpsDetails->wls_service_id;
        $vmId = $vpsDetails->wls_vm_id;
        
         
        $token = WhiteLabelServices_getToken($params);
        if (!$token) {
            return ['error' => 'Authentication failed'];
        }
        
         
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
             
            $orderData['WLS_vm_status'] = 'rebuilding';
            $orderData['rebuild_started'] = date('Y-m-d H:i:s');
            $orderData['rebuild_template'] = $templateId;
            
            Capsule::table('tblhosting')
                ->where('id', $params['serviceid'])
                ->update(['notes' => json_encode($orderData)]);
            
             
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

 
function WhiteLabelServices_getProducts($params) {
    return WLSTokenManager::getProducts($params);
}

function ClientArea($params) {
    try {
         
        require_once __DIR__ . '/lib/Smarty/Smarty.class.php';
        $smarty = new Smarty();
        $smarty->template_dir = __DIR__ . '/templates/';
        $smarty->compile_dir = __DIR__ . '/templates_c/';
        
         
        if (isset($_SESSION['WLS_rebuild_' . $params['serviceid']])) {
            $smarty->assign('rebuilding', true);
            $smarty->assign('serviceid', $params['serviceid']);
            $output = $smarty->fetch('rebuilding.tpl');
            return $output;
        }
        
         
        $vmInfo = WLSTokenManager::getVPSDetails($params['serviceid']);
        
         
        $showVMDetails = false;
        if ($vmInfo && $vmInfo->wls_vm_id) {
            $showVMDetails = true;
        }
        
         
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
             
            $vmInfoArray = json_decode(json_encode($vmInfo), true);
            
             
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
            
             
            $vmData['all_ips'] = json_decode($vmInfoArray['vm_all_ips'] ?? '[]', true) ?: [];
            $vmData['interfaces'] = json_decode($vmInfoArray['vm_interfaces'] ?? '[]', true) ?: [];
            $vmData['storage'] = json_decode($vmInfoArray['vm_storage'] ?? '[]', true) ?: [];
            $vmData['resources'] = json_decode($vmInfoArray['vm_resources'] ?? '[]', true) ?: [];
            
             
        }

         
        $smarty->assign('vmInfo', $vmData);
        $smarty->assign('serviceStatus', $params['domainstatus'] ?? 'Pending');
        $smarty->assign('serviceid', $params['serviceid']);
        $smarty->assign('domain', $params['domain']);
        $smarty->assign('showVMDetails', $showVMDetails);
        $smarty->assign('modulelink', $params['modulelink'] ?? '');
        
         
        $output = $smarty->fetch('clientarea.tpl');
        return $output;

    } catch (Exception $e) {
        WLS_debugLog("ClientArea Error: " . $e->getMessage());
        
         
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



 


function WhiteLabelServices_getRDNS($params) {
    try {
         
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
             
            if (isset($data['rdns']) && is_array($data['rdns'])) {
                 
                $allRdns = [];
                foreach ($data['rdns'] as $ip => $rdnsData) {
                    $allRdns[$ip] = [
                        'ip' => $ip,
                        'rdns' => $rdnsData['ptrcontent'] ?? '',
                        'ptrname' => $rdnsData['ptrname'] ?? ''
                    ];
                }
                 
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

 





function WhiteLabelServices_updateRDNS($params, $rdns, $specificIp = '') {
    try {
         
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
        
         
        $ip = !empty($specificIp) ? $specificIp : ($wlsData->ipv4 ?? $params['dedicatedip'] ?? '');
        
        if (empty($ip)) {
            return ['error' => 'No IP address found'];
        }
        $ip = trim($ip);
        
        WLS_debugLog("rDNS Update - URL: " . $url . " IP: " . $ip . " Hostname: " . $rdns);
        
         
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

 



function WhiteLabelServices_performRebuild($params, $templateId) {
    try {
        WLS_debugLog("Rebuild - Starting rebuild for service " . $params['serviceid'] . " with template: " . $templateId);
        
         
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
        
         
        if ($httpCode === 200 && isset($responseData['status']) && $responseData['status'] == 1) {
             
            Capsule::table('mod_wls_vps')
                ->where('id', $params['serviceid'])
                ->update([
                    'vm_status' => 'rebuilding',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            WLS_debugLog("- Rebuild initiated for service " . $params['serviceid'] . " with template: " . $templateId);
            
             
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

 



function WhiteLabelServices_SyncAllVMData() {
    try {
         
        $syncLockToken = WLSTokenManager::acquireCronLock('wls_vm_sync', 300);  
        if (!$syncLockToken) {
             
            return 0;
        }
        
        WLS_debugLog("Sync - Starting VM data sync for all active services");
        
         
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
