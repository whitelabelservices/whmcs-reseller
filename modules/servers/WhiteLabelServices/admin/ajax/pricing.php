<?php
 




 
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Direct access not allowed']));
}

require_once dirname(__DIR__) . '/includes/wls_bootstrap.php';

$whmcsRoot = wls_find_whmcs_root_dir(__DIR__);
$initPath = $whmcsRoot ? ($whmcsRoot . DIRECTORY_SEPARATOR . 'init.php') : '';

if ($whmcsRoot === null || !is_file($initPath)) {
    die(json_encode(['status' => 'error', 'message' => 'WHMCS not found']));
}

require $initPath;

use WHMCS\Database\Capsule;

 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['adminid'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

require_once dirname(dirname(__DIR__)) . '/lib/TokenManager.php';
require_once dirname(dirname(__DIR__)) . '/WhiteLabelServices.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? 'get_products';

try {
    $server = Capsule::table('tblservers')
        ->where('type', 'WhiteLabelServices')
        ->where('active', 1)
        ->first();

    if (!$server) throw new Exception("Aktif WLS sunucusu bulunamadı.");

    $params = [
        'serverhostname' => $server->hostname,
        'serverusername' => $server->username,
        'serverpassword' => decrypt($server->password),
        'serverid' => $server->id
    ];

    $token = WLSTokenManager::getToken($params);
    $apiBaseUrl = WLSTokenManager::getApiBaseUrl();

    if ($action == 'get_categories') {
         
        $categories = wls_api_call($apiBaseUrl . "/api/category", $token);
        echo json_encode(['status' => 'success', 'categories' => $categories]);
        exit;
    }

    if ($action == 'get_products') {
        $wlsProducts = [];
        $categoryId = $_POST['category_id'] ?? null;
        
        try {
             
            if (!$categoryId) {
                $categoriesResponse = wls_api_call($apiBaseUrl . "/api/category", $token);
                
                 
                $prioritySlugs = ['vps', 'clouds', 'cloud', 'cloud-server', 'container', 'containers'];
                
                if (isset($categoriesResponse['categories'])) {
                    $categories = $categoriesResponse['categories'];
                    
                     
                    foreach ($prioritySlugs as $targetSlug) {
                         
                        foreach ($categories as $cat) {
                            if (isset($cat['slug']) && $cat['slug'] === $targetSlug) {
                                $categoryId = $cat['id'];
                                break 2;  
                            }
                             
                            if (isset($cat['subcategories'])) {
                                foreach ($cat['subcategories'] as $subcat) {
                                    if (isset($subcat['slug']) && $subcat['slug'] === $targetSlug) {
                                        $categoryId = $subcat['id'];
                                        break 3;  
                                    }
                                }
                            }
                        }
                    }
                    
                     
                    if (!$categoryId) {
                        $firstCat = reset($categories);
                        $categoryId = $firstCat['id'] ?? null;
                    }
                }
            }
            
            if ($categoryId) {
                $wlsResponse = wls_api_call($apiBaseUrl . "/api/category/{$categoryId}/product", $token);
                if (isset($wlsResponse['products'])) {
                    foreach ($wlsResponse['products'] as $prod) {
                         
                        $price = 0;
                        $currency = 'USD';  
                        
                        if (isset($prod['periods']) && is_array($prod['periods'])) {
                            foreach ($prod['periods'] as $period) {
                                 
                                if (isset($period['value']) && $period['value'] === 'm') {
                                    $price = floatval($period['price'] ?? 0);
                                    
                                     
                                    if (isset($period['formatted'])) {
                                        if (strpos($period['formatted'], '₺') !== false || strpos($period['formatted'], 'TL') !== false) {
                                            $currency = 'TRY';
                                        } elseif (strpos($period['formatted'], '$') !== false) {
                                            $currency = 'USD';
                                        } elseif (strpos($period['formatted'], '€') !== false) {
                                            $currency = 'EUR';
                                        }
                                    }
                                    break;
                                }
                            }
                             
                            if ($price == 0) {
                                foreach ($prod['periods'] as $period) {
                                    if (!empty($period['selected'])) {
                                        $price = floatval($period['price'] ?? 0);
                                        break;
                                    }
                                }
                            }
                        } else {
                             
                            $price = floatval($prod['price'] ?? $prod['total'] ?? $prod['monthly'] ?? 0);
                        }
                        
                         
                        $name = $prod['name'] ?? 'Unknown';
                        $description = $prod['description'] ?? '';
                        $location = 'Istanbul, TR';  
                        
                         
                        if (preg_match('/LOCATION:\s*([^<\r\n]+)/i', $description, $locMatch)) {
                            $location = trim($locMatch[1]);
                        } elseif (strpos($name, 'US - ') === 0 || strpos($name, 'US-') === 0) {
                            $location = 'New York, US';
                        }
                        
                        $wlsProducts[$prod['id']] = [
                            'id' => $prod['id'],
                            'name' => $name,
                            'price' => $price,
                            'currency' => $currency,
                            'location' => $location,
                            'description' => $description
                        ];
                    }
                }
            }
        } catch (Exception $e) {}

        $wlsApiIdx = WhiteLabelServices_SemanticKeyToConfigOptionIndex('wls_api_product');
        $wlsApiCol = $wlsApiIdx ? WhiteLabelServices_ConfigOptionFieldName($wlsApiIdx) : 'configoption1';
        $whmcsProducts = Capsule::table('tblproducts')
            ->select('tblproducts.id', 'tblproducts.name', Capsule::raw('`tblproducts`.`' . $wlsApiCol . '` as wls_id'), 'tblpricing.monthly', 'tblpricing.currency')
            ->join('tblpricing', 'tblproducts.id', '=', 'tblpricing.relid')
            ->where('tblproducts.servertype', 'WhiteLabelServices')
            ->where('tblpricing.type', 'product')
            ->get();

         
        $matchedWlsIds = [];
        $matched = [];
        
        foreach ($whmcsProducts as $p) {
            $wlsId = $p->wls_id;
            $matchedWlsIds[] = $wlsId;
            
            $wlsInfo = isset($wlsProducts[$wlsId]) ? $wlsProducts[$wlsId] : null;
            $matched[] = [
                'whmcs_id' => $p->id,
                'whmcs_name' => $p->name,
                'current_price' => $p->monthly ?: 0,
                'currency' => $p->currency,
                'wls_id' => $wlsId,
                'wls_name' => $wlsInfo ? $wlsInfo['name'] : 'Bulunamadı',
                'wls_price' => $wlsInfo ? $wlsInfo['price'] : 0,
                'wls_currency' => $wlsInfo ? $wlsInfo['currency'] : '-',
                'wls_location' => $wlsInfo ? ($wlsInfo['location'] ?? '') : '',
                'wls_description' => $wlsInfo ? ($wlsInfo['description'] ?? '') : ''
            ];
        }
        
         
        $unmatched = [];
        foreach ($wlsProducts as $wlsId => $wls) {
            if (!in_array($wlsId, $matchedWlsIds)) {
                $unmatched[] = [
                    'wls_id' => $wlsId,
                    'wls_name' => $wls['name'],
                    'wls_price' => $wls['price'],
                    'wls_currency' => $wls['currency'],
                    'wls_location' => $wls['location'] ?? '',
                    'wls_description' => $wls['description'] ?? ''
                ];
            }
        }

        echo json_encode([
            'status' => 'success', 
            'matched' => $matched,
            'unmatched' => $unmatched,
            'products' => $matched  
        ]);

    } elseif ($action == 'update_price') {
        $items = $_POST['items'] ?? [];
        $margin = floatval($_POST['margin'] ?? 20);  
        
        if (!is_array($items)) throw new Exception("Geçersiz veri");

        $count = 0;
        $applyMargin = function($price) use ($margin) {
            return round($price * (1 + $margin / 100), 2);
        };
        
        foreach ($items as $item) {
            $whmcsId = intval($item['whmcs_id'] ?? 0);
            $newPrice = floatval($item['new_price'] ?? 0);
            $wlsId = $item['wls_id'] ?? '';
            
            if ($whmcsId > 0 && $newPrice > 0) {
                 
                $periods = [];
                if (!empty($wlsId)) {
                    try {
                        $productDetails = wls_api_call($apiBaseUrl . "/api/order/{$wlsId}", $token);
                        if (isset($productDetails['product']['config']['product'])) {
                            foreach ($productDetails['product']['config']['product'] as $configItem) {
                                if ($configItem['id'] === 'cycle' && isset($configItem['items'])) {
                                    foreach ($configItem['items'] as $periodItem) {
                                        $periods[$periodItem['value']] = floatval($periodItem['price']);
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                         
                    }
                }
                
                 
                $currencies = Capsule::table('tblcurrencies')->get();
                foreach ($currencies as $curr) {
                    $updateData = ['monthly' => number_format($newPrice, 2, '.', '')];
                    
                     
                    if (!empty($periods)) {
                        if (isset($periods['q'])) $updateData['quarterly'] = number_format($applyMargin($periods['q']), 2, '.', '');
                        if (isset($periods['s'])) $updateData['semiannually'] = number_format($applyMargin($periods['s']), 2, '.', '');
                        if (isset($periods['a'])) $updateData['annually'] = number_format($applyMargin($periods['a']), 2, '.', '');
                        if (isset($periods['b'])) $updateData['biennially'] = number_format($applyMargin($periods['b']), 2, '.', '');
                        if (isset($periods['t'])) $updateData['triennially'] = number_format($applyMargin($periods['t']), 2, '.', '');
                    }
                    
                    Capsule::table('tblpricing')
                        ->where('type', 'product')
                        ->where('relid', $whmcsId)
                        ->where('currency', $curr->id)
                        ->update($updateData);
                }
                $count++;
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => "$count ürün güncellendi (tüm dönemler)."]);
        
    } elseif ($action == 'create_product') {
        $wlsId = $_POST['wls_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $margin = floatval($_POST['margin'] ?? 20);  
        
        if (empty($wlsId)) {
            throw new Exception("WLS ID gerekli");
        }
        
         
        $productDetails = wls_api_call($apiBaseUrl . "/api/order/{$wlsId}", $token);
        
        if (!isset($productDetails['product'])) {
            throw new Exception("Ürün detayları API'den alınamadı");
        }
        
        $prod = $productDetails['product'];
        $productName = $name ?: ($prod['name'] ?? 'Unknown Product');
        $description = $prod['description'] ?? '';
        
         
        $periods = [];
        if (isset($prod['config']['product'])) {
            foreach ($prod['config']['product'] as $configItem) {
                if ($configItem['id'] === 'cycle' && isset($configItem['items'])) {
                    foreach ($configItem['items'] as $item) {
                        $periods[$item['value']] = [
                            'price' => floatval($item['price']),
                            'setup' => floatval($item['setup'] ?? 0)
                        ];
                    }
                }
            }
        }
        
         
        $groupName = 'Cloud VPS';
        $group = Capsule::table('tblproductgroups')->where('name', $groupName)->first();
        
        if (!$group) {
            $groupId = Capsule::table('tblproductgroups')->insertGetId([
                'name' => $groupName,
                'slug' => 'cloud-vps',
                'headline' => 'Cloud VPS Services',
                'tagline' => '',
                'orderfrmtpl' => '',
                'disabledgateways' => '',
                'hidden' => 0,
                'order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $groupId = $group->id;
        }
        
         
        $welcomeEmailId = 0;
        $emailTemplate = Capsule::table('tblemailtemplates')
            ->where('name', 'LIKE', '%Dedicated%VPS%Welcome%')
            ->orWhere('name', 'LIKE', '%VPS%Server%Welcome%')
            ->first();
        if ($emailTemplate) {
            $welcomeEmailId = $emailTemplate->id;
        }
        
         
        $serverGroupId = 0;
        $wlsServer = Capsule::table('tblservers')
            ->where('type', 'WhiteLabelServices')
            ->where('active', '1')
            ->first();
        
        if ($wlsServer) {
             
            $serverGroups = Capsule::table('tblservergroupsrel')
                ->where('serverid', $wlsServer->id)
                ->first();
            if ($serverGroups) {
                $serverGroupId = $serverGroups->groupid;
            }
        }
        
        $productInsert = [
            'type' => 'server',
            'gid' => $groupId,
            'name' => $productName,
            'description' => $description,
            'hidden' => 0,
            'showdomainoptions' => 0,
            'welcomeemail' => $welcomeEmailId,
            'stockcontrol' => 0,
            'qty' => 0,
            'proratabilling' => 0,
            'proratadate' => 0,
            'proratachargenextmonth' => 0,
            'paytype' => 'recurring',
            'allowqty' => 0,
            'subdomain' => '',
            'autosetup' => 'payment',
            'servertype' => 'WhiteLabelServices',
            'servergroup' => $serverGroupId,
            'tax' => 0,
            'order' => 0,
            'retired' => 0,
            'is_featured' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $apiSlot = WhiteLabelServices_SemanticKeyToConfigOptionIndex('wls_api_product');
        for ($i = 1; $i <= 24; $i++) {
            $cf = WhiteLabelServices_ConfigOptionFieldName($i);
            $productInsert[$cf] = ($apiSlot !== null && $i === $apiSlot) ? $wlsId : '';
        }
        $productId = Capsule::table('tblproducts')->insertGetId($productInsert);
        
         
        $applyMargin = function($price) use ($margin) {
            return round($price * (1 + $margin / 100), 2);
        };
        
         
        $currencies = Capsule::table('tblcurrencies')->get();
        foreach ($currencies as $curr) {
             
            $monthly = isset($periods['m']) ? $applyMargin($periods['m']['price']) : -1;
            $quarterly = isset($periods['q']) ? $applyMargin($periods['q']['price']) : -1;
            $semiannually = isset($periods['s']) ? $applyMargin($periods['s']['price']) : -1;
            $annually = isset($periods['a']) ? $applyMargin($periods['a']['price']) : -1;
            $biennially = isset($periods['b']) ? $applyMargin($periods['b']['price']) : -1;
            $triennially = isset($periods['t']) ? $applyMargin($periods['t']['price']) : -1;
            
             
            $msetup = isset($periods['m']) ? $applyMargin($periods['m']['setup']) : 0;
            $qsetup = isset($periods['q']) ? $applyMargin($periods['q']['setup']) : 0;
            $ssetup = isset($periods['s']) ? $applyMargin($periods['s']['setup']) : 0;
            $asetup = isset($periods['a']) ? $applyMargin($periods['a']['setup']) : 0;
            $bsetup = isset($periods['b']) ? $applyMargin($periods['b']['setup']) : 0;
            $tsetup = isset($periods['t']) ? $applyMargin($periods['t']['setup']) : 0;
            
             
             
            
            Capsule::table('tblpricing')->insert([
                'type' => 'product',
                'currency' => $curr->id,
                'relid' => $productId,
                'msetupfee' => number_format($msetup, 2, '.', ''),
                'qsetupfee' => number_format($qsetup, 2, '.', ''),
                'ssetupfee' => number_format($ssetup, 2, '.', ''),
                'asetupfee' => number_format($asetup, 2, '.', ''),
                'bsetupfee' => number_format($bsetup, 2, '.', ''),
                'tsetupfee' => number_format($tsetup, 2, '.', ''),
                'monthly' => number_format($monthly, 2, '.', ''),
                'quarterly' => number_format($quarterly, 2, '.', ''),
                'semiannually' => number_format($semiannually, 2, '.', ''),
                'annually' => number_format($annually, 2, '.', ''),
                'biennially' => number_format($biennially, 2, '.', ''),
                'triennially' => number_format($triennially, 2, '.', '')
            ]);
        }
        
         
        $configOptionCount = 0;
        $configGroupId = null;
        
        if (isset($prod['config']['forms']) && is_array($prod['config']['forms']) && count($prod['config']['forms']) > 0) {
             
            $configGroupId = Capsule::table('tblproductconfiggroups')->insertGetId([
                'name' => $productName . ' Options',
                'description' => 'Configurable options for ' . $productName
            ]);
            
             
            Capsule::table('tblproductconfiglinks')->insert([
                'gid' => $configGroupId,
                'pid' => $productId
            ]);
            
            foreach ($prod['config']['forms'] as $form) {
                $fieldType = $form['type'] ?? '';
                $fieldTitle = $form['title'] ?? '';
                $fieldId = $form['id'] ?? '';
                $metadata = $form['metadata'] ?? [];
                $config = $form['config'] ?? [];
                $items = $form['items'] ?? [];
                
                 
                $supportedTypes = ['select', 'slider', 'qty'];
                if (!in_array($fieldType, $supportedTypes) || empty($fieldTitle)) {
                    continue;
                }
                
                 
                 
                $optionType = 1;  
                $qtyMinimum = 0;
                $qtyMaximum = 0;
                $includedQty = 0;  
                
                if ($fieldType === 'slider' || $fieldType === 'qty') {
                    $optionType = 4;  
                    $initialVal = intval($config['initialval'] ?? $config['minvalue'] ?? 0);
                    $minVal = intval($config['minvalue'] ?? 0);
                    $maxVal = intval($config['maxvalue'] ?? 100);
                    
                     
                    if (!empty($config['dontchargedefault']) && $initialVal > 0) {
                        $includedQty = $initialVal;
                         
                        $qtyMinimum = 0;
                         
                        $qtyMaximum = max(0, $maxVal - $includedQty);
                    } else {
                        $qtyMinimum = $minVal;
                        $qtyMaximum = $maxVal;
                    }

                    $metaVar = $metadata['variable'] ?? '';
                    if ($metaVar === 'ipamlimit') {
                        $qtyMinimum = max(1, (int) $qtyMinimum);
                    }
                }
                
                 
                $displayName = $fieldTitle;
                if ($includedQty > 0) {
                    $displayName = 'Additional ' . $fieldTitle;
                }
                
                 
                $configOptionCount++;
                $optionId = Capsule::table('tblproductconfigoptions')->insertGetId([
                    'gid' => $configGroupId,
                    'optionname' => $displayName,
                    'optiontype' => $optionType,
                    'qtyminimum' => $qtyMinimum,
                    'qtymaximum' => $qtyMaximum,
                    'order' => $configOptionCount,
                    'hidden' => 0
                ]);
                
                 
                if ($fieldType === 'select') {
                     
                    $subOrder = 0;
                    foreach ($items as $item) {
                        $itemTitle = $item['title'] ?? '';
                        if (empty($itemTitle)) continue;
                        
                        $subOrder++;
                        $itemPrice = floatval($item['price'] ?? $item['unit_price'] ?? 0);
                        $itemSetup = floatval($item['setup'] ?? 0);
                        
                         
                        $sellingPrice = round($itemPrice * (1 + $margin / 100), 2);
                        $sellingSetup = round($itemSetup * (1 + $margin / 100), 2);
                        
                        $subId = Capsule::table('tblproductconfigoptionssub')->insertGetId([
                            'configid' => $optionId,
                            'optionname' => $itemTitle,
                            'sortorder' => $subOrder,
                            'hidden' => 0
                        ]);
                        
                         
                        foreach ($currencies as $curr) {
                            Capsule::table('tblpricing')->insert([
                                'type' => 'configoptions',
                                'currency' => $curr->id,
                                'relid' => $subId,
                                'msetupfee' => number_format($sellingSetup, 2, '.', ''),
                                'qsetupfee' => number_format($sellingSetup, 2, '.', ''),
                                'ssetupfee' => number_format($sellingSetup, 2, '.', ''),
                                'asetupfee' => number_format($sellingSetup, 2, '.', ''),
                                'bsetupfee' => number_format($sellingSetup, 2, '.', ''),
                                'tsetupfee' => number_format($sellingSetup, 2, '.', ''),
                                'monthly' => number_format($sellingPrice, 2, '.', ''),
                                'quarterly' => number_format($sellingPrice * 3, 2, '.', ''),
                                'semiannually' => number_format($sellingPrice * 6, 2, '.', ''),
                                'annually' => number_format($sellingPrice * 12, 2, '.', ''),
                                'biennially' => number_format($sellingPrice * 24, 2, '.', ''),
                                'triennially' => number_format($sellingPrice * 36, 2, '.', '')
                            ]);
                        }
                    }
                } else {
                     
                    $unitPrice = 0;
                    $unitSetup = 0;
                    
                    if (!empty($items)) {
                        $firstItem = reset($items);
                        $unitPrice = floatval($firstItem['unit_price'] ?? $firstItem['price'] ?? 0);
                        $unitSetup = floatval($firstItem['setup'] ?? 0);
                    }
                    
                     
                    $sellingPrice = round($unitPrice * (1 + $margin / 100), 2);
                    $sellingSetup = round($unitSetup * (1 + $margin / 100), 2);
                    
                    $subId = Capsule::table('tblproductconfigoptionssub')->insertGetId([
                        'configid' => $optionId,
                        'optionname' => $fieldTitle,
                        'sortorder' => 1,
                        'hidden' => 0
                    ]);
                    
                     
                    foreach ($currencies as $curr) {
                        Capsule::table('tblpricing')->insert([
                            'type' => 'configoptions',
                            'currency' => $curr->id,
                            'relid' => $subId,
                            'msetupfee' => number_format($sellingSetup, 2, '.', ''),
                            'qsetupfee' => number_format($sellingSetup, 2, '.', ''),
                            'ssetupfee' => number_format($sellingSetup, 2, '.', ''),
                            'asetupfee' => number_format($sellingSetup, 2, '.', ''),
                            'bsetupfee' => number_format($sellingSetup, 2, '.', ''),
                            'tsetupfee' => number_format($sellingSetup, 2, '.', ''),
                            'monthly' => number_format($sellingPrice, 2, '.', ''),
                            'quarterly' => number_format($sellingPrice, 2, '.', ''),
                            'semiannually' => number_format($sellingPrice, 2, '.', ''),
                            'annually' => number_format($sellingPrice, 2, '.', ''),
                            'biennially' => number_format($sellingPrice, 2, '.', ''),
                            'triennially' => number_format($sellingPrice, 2, '.', '')
                        ]);
                    }
                }
            }
        }
        
        echo json_encode([
            'status' => 'success', 
            'message' => "Ürün oluşturuldu: $productName (ID: $productId) - $configOptionCount configurable option eklendi",
            'product_id' => $productId,
            'config_options' => $configOptionCount,
            'config_group_id' => $configGroupId
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function wls_api_call($url, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) throw new Exception("API Error ($httpCode)");

    return json_decode($response, true) ?: [];
}
