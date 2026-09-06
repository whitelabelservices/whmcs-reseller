<?php
/**
 * WhiteLabelServices Admin Dashboard Hook
 * 
 * Uyumluluk: WHMCS 6.x, 7.x, 8.x
 * 
 * Kurulum: includes/hooks/wls_admin_dashboard.php
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

add_hook('AdminAreaHeaderOutput', 1, function($vars) {
    // Dil çevirileri
    $lang = [
        'english' => [
            'wls_dashboard' => 'WLS Dashboard',
            'wls_main_panel' => 'Main Panel',
            'wls_support_tickets' => 'Support Tickets',
            'wls_pricing' => 'Pricing Management'
        ],
        'turkish' => [
            'wls_dashboard' => 'WLS Yönetim Paneli',
            'wls_main_panel' => 'Ana Panel',
            'wls_support_tickets' => 'Destek Talepleri',
            'wls_pricing' => 'Fiyat Yönetimi'
        ]
    ];
    
    $adminLang = isset($_SESSION['adminlang']) ? $_SESSION['adminlang'] : 'english';
    $activeLang = isset($lang[$adminLang]) ? $lang[$adminLang] : $lang['english'];
    
    $baseLink = '../modules/servers/WhiteLabelServices/admin/';
    
    return '<script>
    $(document).ready(function() {
        var wlsMenuAdded = false;
        
        // Menu HTML
        var wlsMenuHtml = \'<li role="separator" class="divider" style="margin:5px 0;border-top:1px solid rgba(0,0,0,0.1);"></li>\' +
            \'<li class="dropdown-header" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:10px 15px;font-weight:bold;"><i class="fas fa-server fa-fw"></i> ' . addslashes($activeLang['wls_dashboard']) . '</li>\' +
            \'<li><a href="' . $baseLink . 'index.php"><i class="fas fa-tachometer-alt fa-fw"></i> ' . addslashes($activeLang['wls_main_panel']) . '</a></li>\' +
            \'<li><a href="' . $baseLink . 'tickets.php"><i class="fas fa-ticket-alt fa-fw"></i> ' . addslashes($activeLang['wls_support_tickets']) . '</a></li>\' +
            \'<li><a href="' . $baseLink . 'pricing.php"><i class="fas fa-tags fa-fw"></i> ' . addslashes($activeLang['wls_pricing']) . '</a></li>\';
        
        function addWLSMenu(targetMenu) {
            if (wlsMenuAdded) return;
            if (targetMenu && targetMenu.length > 0 && targetMenu.find("a[href*=\'WhiteLabelServices/admin\']").length === 0) {
                targetMenu.append(wlsMenuHtml);
                wlsMenuAdded = true;
                console.log("WLS: Menu added successfully");
            }
        }
        
        // ========== WHMCS 8.x (Bootstrap 4/5) ==========
        // Yöntem 1: Addons menü linkinden parent bul
        var addonsLink8 = $("a.nav-link:contains(\'Addons\'), a.nav-link:contains(\'Eklentiler\')").first();
        if (addonsLink8.length) {
            var menu8 = addonsLink8.siblings(".dropdown-menu").first();
            if (!menu8.length) menu8 = addonsLink8.next(".dropdown-menu");
            if (!menu8.length) menu8 = addonsLink8.parent().find(".dropdown-menu").first();
            addWLSMenu(menu8);
        }
        
        // Yöntem 2: Apps & Integrations linkinden ul bul
        if (!wlsMenuAdded) {
            var appsLink = $("a:contains(\'Apps & Integrations\')").first();
            if (appsLink.length) {
                addWLSMenu(appsLink.closest("ul, .dropdown-menu"));
            }
        }
        
        // ========== WHMCS 7.x (Bootstrap 3) ==========
        if (!wlsMenuAdded) {
            $(".navbar-nav > li.dropdown").each(function() {
                var trigger = $(this).find("> a");
                var menuText = trigger.text().trim().toLowerCase();
                if (menuText.includes("addon") || menuText.includes("eklenti")) {
                    addWLSMenu($(this).find("> .dropdown-menu, > ul"));
                }
            });
        }
        
        // ========== WHMCS 6.x (Legacy) ==========
        if (!wlsMenuAdded) {
            $("ul.nav > li").each(function() {
                var link = $(this).find("> a").first();
                var text = link.text().trim().toLowerCase();
                if (text.includes("addon") || text.includes("eklenti")) {
                    var submenu = $(this).find("ul").first();
                    addWLSMenu(submenu);
                }
            });
        }
        
        // ========== Fallback: Tüm dropdown menüleri tara ==========
        if (!wlsMenuAdded) {
            $(".dropdown-menu, ul.dropdown").each(function() {
                var hasApps = $(this).find("a:contains(\'Apps\'), a:contains(\'Marketplace\')").length > 0;
                if (hasApps) {
                    addWLSMenu($(this));
                }
            });
        }
        
        // ========== Son Fallback: İlk uygun menüye ekle ==========
        if (!wlsMenuAdded) {
            var anyDropdown = $(".navbar .dropdown-menu, .nav .dropdown-menu").first();
            if (anyDropdown.length) {
                console.log("WLS: Using fallback - adding to first dropdown");
                addWLSMenu(anyDropdown);
            }
        }
        
        if (!wlsMenuAdded) {
            console.warn("WLS: Could not find suitable menu. Please report WHMCS version.");
        }
    });
    </script>
    <style>
    .dropdown-header i.fa-server { margin-right: 5px; }
    </style>';
});
