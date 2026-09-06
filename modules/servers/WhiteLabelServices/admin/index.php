<?php
/**
 * WhiteLabelServices Admin Dashboard
 * With new sidebar layout
 */

require_once __DIR__ . '/includes/wls_bootstrap.php';

$whmcsRoot = wls_find_whmcs_root_dir(__DIR__);
if ($whmcsRoot === null || !is_file($whmcsRoot . DIRECTORY_SEPARATOR . 'init.php')) {
    die('WHMCS init.php not found');
}
$initPath = $whmcsRoot . DIRECTORY_SEPARATOR . 'init.php';

define('ADMINAREA', true);
require $initPath;

use WHMCS\Database\Capsule;

wls_redirect_if_not_admin_session('client_home');

require_once __DIR__ . '/includes/layout.php';

$adminUser = Capsule::table('tbladmins')->where('id', $_SESSION['adminid'])->first();

$lang = $_GET['lang'] ?? $_COOKIE['wls_lang'] ?? 'en';
if (isset($_GET['lang'])) {
    setcookie('wls_lang', $lang, time() + 86400 * 365, '/');
}

$t = [
    'dashboard' => $lang === 'tr' ? 'Kontrol Paneli' : 'Dashboard',
    'wls_active' => $lang === 'tr' ? 'WLS Aktif Hizmet' : 'WLS Active',
    'whmcs_active' => $lang === 'tr' ? 'WHMCS Aktif Hizmet' : 'WHMCS Active',
    'credit' => $lang === 'tr' ? 'Kredimiz' : 'Credit',
    'debt' => $lang === 'tr' ? 'Borcumuz' : 'Debt',
    'pending_tickets' => $lang === 'tr' ? 'Bekleyen Ticket' : 'Pending Tickets',
    'system_status' => $lang === 'tr' ? 'Sistem Durumu' : 'System Status',
    'stable' => $lang === 'tr' ? 'Stabil' : 'Stable',
    'recent_tickets' => $lang === 'tr' ? 'Son Destek Talepleri' : 'Recent Tickets',
    'recent_invoices' => $lang === 'tr' ? 'Son Faturalar' : 'Recent Invoices',
    'recent_activity' => $lang === 'tr' ? 'Son Aktiviteler' : 'Recent Activity',
    'loading' => $lang === 'tr' ? 'Yükleniyor...' : 'Loading...',
    'hello' => $lang === 'tr' ? 'Merhaba' : 'Hello',
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['dashboard']); ?>
</head>
<body>
    <?php wls_render_sidebar('dashboard', $lang); ?>
    
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-tachometer-alt"></i> <?= $t['dashboard'] ?></h1>
            <span style="color: #64748b;"><?= $t['hello'] ?>, <?= htmlspecialchars($adminUser->firstname ?? 'Admin') ?></span>
        </div>
        
        <!-- İstatistikler -->
        <div class="wls-stat-grid">
            <div class="wls-stat-card">
                <div class="icon" style="color: #3b82f6;">
                    <i class="fas fa-server"></i>
                </div>
                <div class="value" id="stats-wls-active">-</div>
                <div class="label"><?= $t['wls_active'] ?></div>
            </div>
            
            <div class="wls-stat-card">
                <div class="icon" style="color: #8b5cf6;">
                    <i class="fas fa-cloud"></i>
                </div>
                <div class="value" id="stats-whmcs-active">-</div>
                <div class="label"><?= $t['whmcs_active'] ?></div>
            </div>
            
            <div class="wls-stat-card">
                <div class="icon" style="color: #22c55e;">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="value" id="stats-credit">-</div>
                <div class="label"><?= $t['credit'] ?></div>
            </div>
            
            <div class="wls-stat-card">
                <div class="icon" style="color: #f59e0b;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="value" id="stats-debt">-</div>
                <div class="label"><?= $t['debt'] ?></div>
            </div>
            
            <div class="wls-stat-card">
                <div class="icon" style="color: #ec4899;">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="value" id="stats-tickets">-</div>
                <div class="label"><?= $t['pending_tickets'] ?></div>
            </div>
            
            <div class="wls-stat-card">
                <div class="icon" style="color: #10b981;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="value"><?= $t['stable'] ?></div>
                <div class="label"><?= $t['system_status'] ?></div>
            </div>
        </div>
        
        <!-- İki Kolon -->
        <div class="wls-grid-2">
            <div class="wls-card">
                <div class="wls-card-header">
                    <i class="fas fa-ticket-alt"></i>
                    <h2><?= $t['recent_tickets'] ?></h2>
                </div>
                <div id="recent-tickets">
                    <p style="color: #64748b;"><?= $t['loading'] ?></p>
                </div>
            </div>
            
            <div class="wls-card">
                <div class="wls-card-header">
                    <i class="fas fa-file-invoice"></i>
                    <h2><?= $t['recent_invoices'] ?></h2>
                </div>
                <div id="recent-invoices">
                    <p style="color: #64748b;"><?= $t['loading'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="wls-card">
            <div class="wls-card-header">
                <i class="fas fa-history"></i>
                <h2><?= $t['recent_activity'] ?></h2>
            </div>
            <div id="sync-logs">
                <p style="color: #64748b;"><?= $t['loading'] ?></p>
            </div>
        </div>
    </main>

<script>
$(document).ready(function() {
    // İstatistikler
    $.post("ajax/get_stats.php", {}, function(data) {
        if(data && data.status == "success") {
            $("#stats-wls-active").text(data.wls_active || 0);
            $("#stats-whmcs-active").text(data.whmcs_active || 0);
            $("#stats-credit").text(data.credit || "0.00");
            $("#stats-debt").text(data.debt || "0.00");
            $("#stats-tickets").text(data.tickets || 0);
        }
    }, "json").fail(function() { $("[id^=stats-]").text("!"); });

    // Faturalar
    $.post("ajax/get_invoices.php", {}, function(data) {
        if(data && data.status == "success" && data.invoices && data.invoices.length > 0) {
            var h = "<div class='table-responsive'><table class='wls-table'><thead><tr><th>#</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>";
            data.invoices.forEach(function(i) { 
                var link = (data.endpoint_url || "") + "/clientarea/invoice/" + i.id + "/";
                var sc = i.status == "Paid" ? "wls-badge-success" : "wls-badge-danger";
                h += "<tr><td><a href='" + link + "' target='_blank' style='color:#60a5fa;'>#" + i.id + "</a></td><td>" + i.total + "</td><td><span class='wls-badge " + sc + "'>" + i.status + "</span></td><td>" + i.date + "</td></tr>"; 
            });
            $("#recent-invoices").html(h + "</tbody></table></div>");
        } else { 
            $("#recent-invoices").html("<p style='color:#64748b;'>No invoices</p>"); 
        }
    }, "json");

    // Loglar
    $.post("ajax/get_logs.php", {}, function(data) {
        if(data && data.status == "success" && data.logs && data.logs.length) {
            var h = "<div class='table-responsive'><table class='wls-table'><thead><tr><th>Date</th><th>Activity</th></tr></thead><tbody>";
            data.logs.forEach(function(l) { h += "<tr><td style='white-space:nowrap;'>" + l.date + "</td><td>" + l.description + "</td></tr>"; });
            $("#sync-logs").html(h + "</tbody></table></div>");
        } else { 
            $("#sync-logs").html("<p style='color:#64748b;'>No logs</p>"); 
        }
    }, "json");

    // Ticketlar
    $.post("ajax/tickets.php", {action: "list"}, function(data) {
        if(data && data.status == "success" && data.data && data.data.tickets && data.data.tickets.length > 0) {
            var tickets = data.data.tickets.slice(0, 5);
            var h = "<div class='table-responsive'><table class='wls-table'><thead><tr><th>#</th><th>Subject</th><th>Status</th><th>Date</th></tr></thead><tbody>";
            tickets.forEach(function(t) {
                var sc = t.status == "Open" ? "wls-badge-warning" : (t.status == "Answered" ? "wls-badge-success" : "wls-badge-info");
                h += "<tr style='cursor:pointer;' onclick=\"window.location='tickets.php?id=" + t.ticket_number + "'\">";
                h += "<td><strong>#" + t.ticket_number + "</strong></td><td>" + t.subject + "</td><td><span class='wls-badge " + sc + "'>" + t.status + "</span></td><td>" + t.date + "</td></tr>";
            });
            $("#recent-tickets").html(h + "</tbody></table></div>");
        } else { 
            $("#recent-tickets").html("<p style='color:#64748b;'>No tickets</p>"); 
        }
    }, "json");
});
</script>

</body>
</html>
