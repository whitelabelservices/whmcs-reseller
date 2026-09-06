<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

if (!function_exists('wls_hooks_require_module')) {
    function wls_hooks_require_module() {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        if (!defined('ROOTDIR')) {
            return;
        }
        $moduleFile = ROOTDIR . '/modules/servers/WhiteLabelServices/WhiteLabelServices.php';
        if (is_file($moduleFile)) {
            require_once $moduleFile;
            $loaded = true;
        }
    }
}

add_hook('ProductDelete', 1, function ($vars) {
    wls_hooks_require_module();
    try {
        if (isset($vars['pid'])) {
            Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $vars['pid'])
                ->delete();
            if (function_exists('WLS_debugLog')) {
                WLS_debugLog('Debug - Cleaned up custom fields for deleted product: ' . $vars['pid']);
            }
        }
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Error in ProductDelete hook: ' . $e->getMessage());
        }
    }
});

add_hook('InvoicePaid', 1, function ($vars) {
    wls_hooks_require_module();
    require_once __DIR__ . '/lib/TokenManager.php';
    try {
        $invoiceId = (int) ($vars['invoiceid'] ?? 0);
        if (!$invoiceId) {
            return;
        }
        WLSTokenManager::ensureTablesExist();
        $items = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->where('type', 'Hosting')
            ->get();
        foreach ($items as $item) {
            $serviceId = (int) ($item->relid ?? 0);
            if (!$serviceId) {
                continue;
            }
            $hosting = Capsule::table('tblhosting')->where('id', $serviceId)->first();
            if (!$hosting) {
                continue;
            }
            $product = Capsule::table('tblproducts')->where('id', $hosting->packageid)->first();
            if (!$product || $product->servertype !== 'WhiteLabelServices') {
                continue;
            }
            WLSTokenManager::addUpdateTask($invoiceId, $serviceId, [
                'invoice_id' => $invoiceId,
                'service_id' => $serviceId,
                'userid' => $hosting->userid,
            ]);
            if (function_exists('WLS_debugLog')) {
                WLS_debugLog('InvoicePaid - Added update task for invoice #' . $invoiceId . ', service #' . $serviceId);
            }
        }
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('InvoicePaid hook error: ' . $e->getMessage());
        }
    }
});

add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
    wls_hooks_require_module();
    try {
        if (empty($_SESSION['cart']['products']) || !is_array($_SESSION['cart']['products'])) {
            return $vars;
        }
        foreach ($_SESSION['cart']['products'] as $key => $product) {
            $productInfo = Capsule::table('tblproducts')->where('id', $product['pid'])->first();
            if (!$productInfo || $productInfo->servertype != 'WhiteLabelServices') {
                continue;
            }
            $_SESSION['cart']['products'][$key]['hostname'] = 'wls-auto-' . time();
            $_SESSION['cart']['products'][$key]['ns1prefix'] = 'ns1';
            $_SESSION['cart']['products'][$key]['ns2prefix'] = 'ns2';
            $_SESSION['cart']['products'][$key]['rootpw'] = 'auto-generated';
        }
        return $vars;
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Checkout Validation Hook Error: ' . $e->getMessage());
        }
        return $vars;
    }
});

add_hook('ShoppingCartValidateProductUpdate', 1, function ($vars) {
    wls_hooks_require_module();
    try {
        $product = Capsule::table('tblproducts')->where('id', $vars['pid'])->first();
        if (!$product || $product->servertype != 'WhiteLabelServices') {
            return $vars;
        }
        $vars['hostname'] = 'wls-' . uniqid();
        $vars['ns1prefix'] = 'ns1';
        $vars['ns2prefix'] = 'ns2';
        $vars['rootpw'] = 'auto-' . uniqid();
        return $vars;
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Product Update Hook Error: ' . $e->getMessage());
        }
        return $vars;
    }
});

add_hook('ShoppingCartValidateProduct', 1, function ($vars) {
    wls_hooks_require_module();
    try {
        $product = Capsule::table('tblproducts')->where('id', $vars['pid'])->first();
        if (!$product || $product->servertype != 'WhiteLabelServices') {
            return $vars;
        }
        if (isset($_SESSION['cart']['errors'])) {
            foreach ($_SESSION['cart']['errors'] as $key => $error) {
                if (strpos($error, 'hostname') !== false
                    || strpos($error, 'nameserver') !== false
                    || strpos($error, 'prefix') !== false) {
                    unset($_SESSION['cart']['errors'][$key]);
                }
            }
        }
        return $vars;
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Product Validation Hook Error: ' . $e->getMessage());
        }
        return $vars;
    }
});

add_hook('ClientAreaPageProductsServices', 1, function ($vars) {
    wls_hooks_require_module();
    try {
        $customCSS = '
        <style>
        .wls-product .form-group:has(#inputHostname),
        .wls-product .form-group:has(#inputRootpw),
        .wls-product .form-group:has(#inputNs1prefix),
        .wls-product .form-group:has(#inputNs2prefix) {
            display: none !important;
        }
        </style>';
        return array_merge($vars, ['customCSS' => $customCSS]);
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('ClientArea Hook Error: ' . $e->getMessage());
        }
        return $vars;
    }
});

add_hook('ClientAreaPageCart', 1, function ($vars) {
    wls_hooks_require_module();
    try {
        $hasWLSProduct = false;
        if (!empty($_SESSION['cart']['products'])) {
            foreach ($_SESSION['cart']['products'] as $product) {
                $productInfo = Capsule::table('tblproducts')->where('id', $product['pid'])->first();
                if ($productInfo && $productInfo->servertype == 'WhiteLabelServices') {
                    $hasWLSProduct = true;
                    break;
                }
            }
        }
        if (!$hasWLSProduct) {
            return $vars;
        }
        $customScript = '<script>
        jQuery(document).ready(function($) {
            function hideWLSFields() {
                $("#inputRootpw").closest(".form-group,.col-sm-6").hide().end().val("auto-generated");
                $("#inputNs1prefix").closest(".form-group,.col-sm-6").hide().end().val("ns1");
                $("#inputNs2prefix").closest(".form-group,.col-sm-6").hide().end().val("ns2");
                $("#containerProductValidationErrors").hide();
            }
            hideWLSFields();
            $("#frmConfigureProduct").on("submit", hideWLSFields);
            $(document).ajaxComplete(function() { setTimeout(hideWLSFields, 100); });
        });
        </script>
        <style>
        #inputRootpw,#inputNs1prefix,#inputNs2prefix{display:none!important;}
        .form-group:has(#inputRootpw),.form-group:has(#inputNs1prefix),.form-group:has(#inputNs2prefix){display:none!important;}
        #containerProductValidationErrors{display:none!important;}
        </style>';
        return array_merge($vars, ['customScript' => $customScript]);
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Cart Page Hook Error: ' . $e->getMessage());
        }
        return $vars;
    }
});

add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    wls_hooks_require_module();
    try {
        if (($vars['filename'] ?? '') !== 'cart') {
            return '';
        }
        $hasWLSProduct = false;
        if (!empty($_SESSION['cart']['products'])) {
            foreach ($_SESSION['cart']['products'] as $product) {
                $productInfo = Capsule::table('tblproducts')->where('id', $product['pid'])->first();
                if ($productInfo && $productInfo->servertype == 'WhiteLabelServices') {
                    $hasWLSProduct = true;
                    break;
                }
            }
        }
        if (!$hasWLSProduct) {
            return '';
        }
        return '<script>
        jQuery(document).ready(function($) {
            function hideWLSFields() {
                $("#inputRootpw").closest(".form-group").hide().end().val("auto-generated");
                $("#inputNs1prefix").closest(".form-group").hide().end().val("ns1");
                $("#inputNs2prefix").closest(".form-group").hide().end().val("ns2");
                $("#containerProductValidationErrors").hide();
            }
            hideWLSFields();
            $("#frmConfigureProduct").on("submit", function() { hideWLSFields(); return true; });
            $(document).ajaxComplete(function() { setTimeout(hideWLSFields, 100); });
            setInterval(hideWLSFields, 5000);
        });
        </script>
        <style>
        #inputRootpw,#inputNs1prefix,#inputNs2prefix{display:none!important;}
        .form-group:has(#inputRootpw),.form-group:has(#inputNs1prefix),.form-group:has(#inputNs2prefix){display:none!important;}
        #containerProductValidationErrors{display:none!important;}
        </style>';
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Head Output Hook Error: ' . $e->getMessage());
        }
        return '';
    }
});

add_hook('DailyCronJob', 1, function ($vars) {
    wls_hooks_require_module();
    try {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Debug - Daily cron job started');
        }
        if (function_exists('WhiteLabelServices_DailyPriceUpdate')) {
            WhiteLabelServices_DailyPriceUpdate();
        }
        if (function_exists('WhiteLabelServices_ProcessPendingOrders')) {
            WhiteLabelServices_ProcessPendingOrders();
        }
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Debug - Daily cron job completed');
        }
    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog('Error in daily cron job: ' . $e->getMessage());
        }
    }
});
