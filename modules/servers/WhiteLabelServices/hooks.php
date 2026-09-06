<?php
 





if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/hooks_register.php';

add_hook('AdminAreaHeaderOutput', 1, function($vars) {
    $dashboardLink = '../modules/servers/WhiteLabelServices/admin/index.php';
    
    return '<script>
    $(document).ready(function() {

        var wlsLink = \'<li><a href="' . $dashboardLink . '"><i class="fas fa-server"></i> WLS Dashboard</a></li>\';
        var wlsDivider = \'<li role="separator" class="divider"></li>\';
        

        if ($("a[href*=\'WhiteLabelServices/admin/index.php\']").length > 0) {
            return;
        }
        

        var addonsMenu = $("#Menu-Addons").parent();
        if (addonsMenu.length > 0) {
            addonsMenu.find("> ul").append(wlsDivider + wlsLink);
            return;
        }
        

        $(".navigation li.has-dropdown, .navbar-collapse li.has-dropdown").each(function() {
            var menuLink = $(this).find("> a");
            var menuText = menuLink.text().toLowerCase();
            if (menuText.indexOf("addon") > -1 || menuLink.attr("id") === "Menu-Addons") {
                $(this).find("> ul").append(wlsDivider + wlsLink);
                return false;
            }
        });
    });
    </script>';
});

 





add_hook('TicketOpen', 1, function($vars) {
    logActivity("WLS: TicketOpen hook fired - ticketid=" . ($vars['ticketid'] ?? '?'));
    if (!defined('ROOTDIR')) return;
    $moduleFile = ROOTDIR . '/modules/servers/WhiteLabelServices/WhiteLabelServices.php';
    if (!file_exists($moduleFile)) return;
    require_once $moduleFile;
    if (function_exists('WhiteLabelServices_CreatePortalTicketIfWLS')) {
        WhiteLabelServices_CreatePortalTicketIfWLS($vars);
    }
});

 


add_hook('CancellationRequest', 1, function($vars) {
    if (!defined('ROOTDIR')) return;
    $moduleFile = ROOTDIR . '/modules/servers/WhiteLabelServices/WhiteLabelServices.php';
    if (!file_exists($moduleFile)) return;
    require_once $moduleFile;
    if (function_exists('WhiteLabelServices_AddCancelTaskIfWLS')) {
        WhiteLabelServices_AddCancelTaskIfWLS($vars);
    }
});

 



add_hook('AfterCronJob', 1, function($vars) {
     
    if (!defined('ROOTDIR')) {
        return;
    }

     
    $moduleFile = ROOTDIR . '/modules/servers/WhiteLabelServices/WhiteLabelServices.php';
    if (!file_exists($moduleFile)) {
        return;
    }

    require_once $moduleFile;

    if (!function_exists('WhiteLabelServices_ProcessQueue')) {
        return;
    }

    try {
         
        WLS_debugLog("Debug - AfterCronJob triggered from hooks.php");

         
        if (function_exists('WhiteLabelServices_ProcessQueue')) {
            $processedQueue = WhiteLabelServices_ProcessQueue();
            if ($processedQueue > 0) {
                WLS_debugLog("Debug - Processed " . $processedQueue . " queue tasks");
            }
        }

         
        if (function_exists('WhiteLabelServices_ProcessPendingVMChecks')) {
            $vmChecks = WhiteLabelServices_ProcessPendingVMChecks();
            if ($vmChecks > 0) {
                WLS_debugLog("Debug - Processed " . $vmChecks . " pending VM status checks");
            }
        }

         
        if (function_exists('WhiteLabelServices_ProcessPendingOrders')) {
            $processedPending = WhiteLabelServices_ProcessPendingOrders();
            if ($processedPending > 0) {
                WLS_debugLog("Debug - Processed " . $processedPending . " pending orders");
            }
        }

         
        if (function_exists('WhiteLabelServices_SyncAllVMData')) {
            $syncedCount = WhiteLabelServices_SyncAllVMData();
            if ($syncedCount > 0) {
                WLS_debugLog("Debug - Synced " . $syncedCount . " VM(s)");
            }
        }

         
        if (function_exists('WhiteLabelServices_ProcessUpdateTasks')) {
            $updateTasks = WhiteLabelServices_ProcessUpdateTasks();
            if ($updateTasks > 0) {
                WLS_debugLog("Debug - Processed " . $updateTasks . " update tasks (invoice paid -> API)");
            }
        }

         
        if (function_exists('WhiteLabelServices_ProcessUpgradeTasks')) {
            $upgradeTasks = WhiteLabelServices_ProcessUpgradeTasks();
            if ($upgradeTasks > 0) {
                WLS_debugLog("Debug - Processed " . $upgradeTasks . " upgrade tasks");
            }
        }

         
        if (function_exists('WhiteLabelServices_ProcessTicketTasks')) {
            $ticketTasks = WhiteLabelServices_ProcessTicketTasks();
            if ($ticketTasks > 0) {
                WLS_debugLog("Debug - Processed " . $ticketTasks . " ticket tasks (portal API)");
            }
        }

         
        if (function_exists('WhiteLabelServices_ProcessCancelTasks')) {
            $cancelTasks = WhiteLabelServices_ProcessCancelTasks();
            if ($cancelTasks > 0) {
                WLS_debugLog("Debug - Processed " . $cancelTasks . " cancel tasks (portal API)");
            }
        }

        WLS_debugLog("Debug - AfterCronJob completed in hooks.php");

    } catch (Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog("Error in AfterCronJob hook (hooks.php): " . $e->getMessage());
        }
    }
});
 



add_hook('ClientAreaFooterOutput', 1, function($vars) {
     
    $currentPage = $_GET['a'] ?? '';
    if (strpos($_SERVER['REQUEST_URI'], 'cart.php') === false) {
        return '';
    }
    
    return '
    <style>
         
        #inputRootpw,
        #inputNs1prefix,
        #inputNs2prefix {
            display: none !important;
        }
        
         
        #inputRootpw.form-control,
        #inputNs1prefix.form-control,
        #inputNs2prefix.form-control {
            display: none !important;
        }
    </style>
    <script>
    jQuery(document).ready(function($) {
        function hideServerConfigFields() {
             
            $("#inputRootpw").closest(".form-group").hide();
            $("#inputRootpw").closest(".col-sm-6").hide();
            $("#inputRootpw").val("auto-generated");
            
             
            $("#inputNs1prefix").closest(".form-group").hide();
            $("#inputNs1prefix").closest(".col-sm-6").hide();
            $("#inputNs1prefix").val("ns1");
            
             
            $("#inputNs2prefix").closest(".form-group").hide();
            $("#inputNs2prefix").closest(".col-sm-6").hide();
            $("#inputNs2prefix").val("ns2");
            
             
            var hostnameCol = $("#inputHostname").closest(".col-sm-6");
            if (hostnameCol.length && hostnameCol.siblings(".col-sm-6:visible").length === 0) {
                hostnameCol.removeClass("col-sm-6").addClass("col-sm-12");
            }
            
             
            var ns1Row = $("#inputNs1prefix").closest(".row");
            if (ns1Row.length && ns1Row.find(".col-sm-6:visible").length === 0) {
                ns1Row.hide();
            }
        }
        
         
        function forceSetValues() {
            if ($("#inputRootpw").length && !$("#inputRootpw").val()) {
                $("#inputRootpw").val("AutoGen" + Math.random().toString(36).substr(2, 8));
            }
            if ($("#inputNs1prefix").length && !$("#inputNs1prefix").val()) {
                $("#inputNs1prefix").val("ns1");
            }
            if ($("#inputNs2prefix").length && !$("#inputNs2prefix").val()) {
                $("#inputNs2prefix").val("ns2");
            }
        }
        
         
        hideServerConfigFields();
        forceSetValues();
        
         
        $("form").on("submit", function(e) {
            forceSetValues();
        });
        
         
        $("button:contains(\'Continue\'), input[type=\'submit\'], .btn-primary").on("click", function() {
            forceSetValues();
        });
        
         
        $(document).ajaxComplete(function() {
            setTimeout(function() {
                hideServerConfigFields();
                forceSetValues();
            }, 100);
        });
        
         
        var observer = new MutationObserver(function(mutations) {
            hideServerConfigFields();
            forceSetValues();
        });
        
        var target = document.querySelector(".field-container, #containerProductConfig, form");
        if (target) {
            observer.observe(target, { childList: true, subtree: true });
        }
    });
    </script>';
});

 



add_hook('AfterShoppingCartCheckout', 1, function ($vars) {
    $orderId = (int) ($vars['OrderID'] ?? 0);
    if (!$orderId) {
        return;
    }
    try {
        $count = \WHMCS\Database\Capsule::table('tblupgrades')
            ->join('tblhosting', 'tblhosting.id', '=', 'tblupgrades.relid')
            ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
            ->where('tblupgrades.orderid', $orderId)
            ->where('tblproducts.servertype', 'WhiteLabelServices')
            ->count();
        if ($count > 0 && function_exists('WLS_debugLog')) {
            WLS_debugLog("AfterShoppingCartCheckout - Order #" . $orderId . " has " . $count . " WLS upgrade(s)", true);
        }
    } catch (\Exception $e) {
        if (function_exists('WLS_debugLog')) {
            WLS_debugLog("AfterShoppingCartCheckout error: " . $e->getMessage(), true);
        }
    }
});
