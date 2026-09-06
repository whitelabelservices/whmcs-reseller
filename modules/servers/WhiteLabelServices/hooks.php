<?php
/**
 * WhiteLabelServices Module Hooks
 *
 * This file is automatically loaded by WHMCS for defined hooks.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/hooks_register.php';

add_hook('AdminAreaHeaderOutput', 1, function($vars) {
    $dashboardLink = '../modules/servers/WhiteLabelServices/admin/index.php';
    
    return '<script>
    $(document).ready(function() {
        // WLS Dashboard linkini ekle
        var wlsLink = \'<li><a href="' . $dashboardLink . '"><i class="fas fa-server"></i> WLS Dashboard</a></li>\';
        var wlsDivider = \'<li role="separator" class="divider"></li>\';
        
        // Zaten ekli mi kontrol et
        if ($("a[href*=\'WhiteLabelServices/admin/index.php\']").length > 0) {
            return;
        }
        
        // WHMCS 8 yapısı: #Menu-Addons
        var addonsMenu = $("#Menu-Addons").parent();
        if (addonsMenu.length > 0) {
            addonsMenu.find("> ul").append(wlsDivider + wlsLink);
            return;
        }
        
        // Alternatif: has-dropdown yapısı
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

/**
 * WHMCS'te destek talebi açıldığında: talebin ilişkili servisi WLS ise portal (vps.tc) API'de ticket oluştur.
 * NOT: Bu hook'un çalışması için WHMCS includes/hooks/ içinde bu hooks.php dosyasını yükleyen bir dosya olmalı.
 * Örnek: includes/hooks/whitelabelservices.php içinde require_once '.../modules/servers/WhiteLabelServices/hooks.php';
 * Hazır loader: Modül içindeki whitelabelservices_loader.php dosyasını includes/hooks/whitelabelservices.php olarak kopyalayın.
 */
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

/**
 * WHMCS iptal talebi: WLS hizmeti ise mod_wls_cancel_tasks'a Pending eklenir, cron ile API'ye gonderilir.
 */
add_hook('CancellationRequest', 1, function($vars) {
    if (!defined('ROOTDIR')) return;
    $moduleFile = ROOTDIR . '/modules/servers/WhiteLabelServices/WhiteLabelServices.php';
    if (!file_exists($moduleFile)) return;
    require_once $moduleFile;
    if (function_exists('WhiteLabelServices_AddCancelTaskIfWLS')) {
        WhiteLabelServices_AddCancelTaskIfWLS($vars);
    }
});

/**
 * WHMCS cron tamamlandığında WLS module queue işlemlerini çalıştır
 * (check_vm_status dahil tüm kuyruk görevleri buradan yürür)
 */
add_hook('AfterCronJob', 1, function($vars) {
    // WHMCS kök dizini tanımlı değilse çık
    if (!defined('ROOTDIR')) {
        return;
    }

    // Ana modül dosyasını yükle (ProcessQueue vb. fonksiyonlar burada)
    $moduleFile = ROOTDIR . '/modules/servers/WhiteLabelServices/WhiteLabelServices.php';
    if (!file_exists($moduleFile)) {
        return;
    }

    require_once $moduleFile;

    if (!function_exists('WhiteLabelServices_ProcessQueue')) {
        return;
    }

    try {
        // Cron tetiklendi
        WLS_debugLog("Debug - AfterCronJob triggered from hooks.php");

        // 1) WLS module queue (power actions, rebuild vs. için)
        if (function_exists('WhiteLabelServices_ProcessQueue')) {
            $processedQueue = WhiteLabelServices_ProcessQueue();
            if ($processedQueue > 0) {
                WLS_debugLog("Debug - Processed " . $processedQueue . " queue tasks");
            }
        }

        // 2) check_vm_status için module queue OLMADAN pending VM kontrolleri
        if (function_exists('WhiteLabelServices_ProcessPendingVMChecks')) {
            $vmChecks = WhiteLabelServices_ProcessPendingVMChecks();
            if ($vmChecks > 0) {
                WLS_debugLog("Debug - Processed " . $vmChecks . " pending VM status checks");
            }
        }

        // 3) Bekleyen siparişleri de işleme al
        if (function_exists('WhiteLabelServices_ProcessPendingOrders')) {
            $processedPending = WhiteLabelServices_ProcessPendingOrders();
            if ($processedPending > 0) {
                WLS_debugLog("Debug - Processed " . $processedPending . " pending orders");
            }
        }

        // 4) VM senkronizasyonu
        if (function_exists('WhiteLabelServices_SyncAllVMData')) {
            $syncedCount = WhiteLabelServices_SyncAllVMData();
            if ($syncedCount > 0) {
                WLS_debugLog("Debug - Synced " . $syncedCount . " VM(s)");
            }
        }

        // 5) Fatura odeme -> portal upgrade API (mod_wls_update_tasks)
        if (function_exists('WhiteLabelServices_ProcessUpdateTasks')) {
            $updateTasks = WhiteLabelServices_ProcessUpdateTasks();
            if ($updateTasks > 0) {
                WLS_debugLog("Debug - Processed " . $updateTasks . " update tasks (invoice paid -> API)");
            }
        }

        // 6) Paket degisikligi / upgrade task'lari (mod_wls_upgrade_tasks -> portal upgrade API)
        if (function_exists('WhiteLabelServices_ProcessUpgradeTasks')) {
            $upgradeTasks = WhiteLabelServices_ProcessUpgradeTasks();
            if ($upgradeTasks > 0) {
                WLS_debugLog("Debug - Processed " . $upgradeTasks . " upgrade tasks");
            }
        }

        // 7) Talep senkronu: mod_wls_ticket_tasks Pending -> portal API'ye ticket olustur
        if (function_exists('WhiteLabelServices_ProcessTicketTasks')) {
            $ticketTasks = WhiteLabelServices_ProcessTicketTasks();
            if ($ticketTasks > 0) {
                WLS_debugLog("Debug - Processed " . $ticketTasks . " ticket tasks (portal API)");
            }
        }

        // 8) Iptal talepleri: mod_wls_cancel_tasks Pending -> portal API POST /service/@id/cancel
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
/**
 * Hide "Configure Server" fields (root password, ns1, ns2) for WhiteLabelServices products
 * Hostname remains visible for customer input
 */
add_hook('ClientAreaFooterOutput', 1, function($vars) {
    // Sadece cart sayfasında çalış
    $currentPage = $_GET['a'] ?? '';
    if (strpos($_SERVER['REQUEST_URI'], 'cart.php') === false) {
        return '';
    }
    
    return '
    <style>
        /* Root Password ve NS alanlarını gizle - Hostname kalacak */
        #inputRootpw,
        #inputNs1prefix,
        #inputNs2prefix {
            display: none !important;
        }
        
        /* Parent form-group elementlerini de gizle */
        #inputRootpw.form-control,
        #inputNs1prefix.form-control,
        #inputNs2prefix.form-control {
            display: none !important;
        }
    </style>
    <script>
    jQuery(document).ready(function($) {
        function hideServerConfigFields() {
            // Root Password alanı ve parent container
            $("#inputRootpw").closest(".form-group").hide();
            $("#inputRootpw").closest(".col-sm-6").hide();
            $("#inputRootpw").val("auto-generated");
            
            // NS1 Prefix alanı ve parent container
            $("#inputNs1prefix").closest(".form-group").hide();
            $("#inputNs1prefix").closest(".col-sm-6").hide();
            $("#inputNs1prefix").val("ns1");
            
            // NS2 Prefix alanı ve parent container
            $("#inputNs2prefix").closest(".form-group").hide();
            $("#inputNs2prefix").closest(".col-sm-6").hide();
            $("#inputNs2prefix").val("ns2");
            
            // Hostname satırını full width yap
            var hostnameCol = $("#inputHostname").closest(".col-sm-6");
            if (hostnameCol.length && hostnameCol.siblings(".col-sm-6:visible").length === 0) {
                hostnameCol.removeClass("col-sm-6").addClass("col-sm-12");
            }
            
            // NS satırını tamamen gizle (ikisi de gizli)
            var ns1Row = $("#inputNs1prefix").closest(".row");
            if (ns1Row.length && ns1Row.find(".col-sm-6:visible").length === 0) {
                ns1Row.hide();
            }
        }
        
        // Değerleri zorla set et
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
        
        // Sayfa yüklendiğinde
        hideServerConfigFields();
        forceSetValues();
        
        // Form submit öncesi değerleri kontrol et ve set et
        $("form").on("submit", function(e) {
            forceSetValues();
        });
        
        // Continue butonuna tıklandığında
        $("button:contains(\'Continue\'), input[type=\'submit\'], .btn-primary").on("click", function() {
            forceSetValues();
        });
        
        // AJAX sonrası
        $(document).ajaxComplete(function() {
            setTimeout(function() {
                hideServerConfigFields();
                forceSetValues();
            }, 100);
        });
        
        // MutationObserver ile dinamik değişiklikleri izle
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

/**
 * AfterShoppingCartCheckout: WLS upgrade sayisi vb. icin tblproducts uzerinden servertype kullan.
 * tblhosting tablosunda servertype kolonu YOK - WHMCS'te servertype tblproducts'ta.
 */
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
