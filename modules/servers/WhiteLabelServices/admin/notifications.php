<?php
 




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

$lang = $_GET['lang'] ?? $_COOKIE['wls_lang'] ?? 'en';
if (isset($_GET['lang'])) {
    setcookie('wls_lang', $lang, time() + 86400 * 365, '/');
}

$t = [
    'notifications' => $lang === 'tr' ? 'Bildirimler' : 'Notifications',
    'unread' => $lang === 'tr' ? 'Okunmamış' : 'Unread',
    'read' => $lang === 'tr' ? 'Okunmuş' : 'Read',
    'all' => $lang === 'tr' ? 'Tümü' : 'All',
    'mark_read' => $lang === 'tr' ? 'Okundu İşaretle' : 'Mark as Read',
    'mark_all_read' => $lang === 'tr' ? 'Tümünü Okundu İşaretle' : 'Mark All as Read',
    'no_notifications' => $lang === 'tr' ? 'Bildirim yok' : 'No notifications',
    'loading' => $lang === 'tr' ? 'Yükleniyor...' : 'Loading...',
    'unread_label' => $lang === 'tr' ? 'YENİ' : 'NEW',
    'read_label' => $lang === 'tr' ? 'Okundu' : 'Read',
    'close' => $lang === 'tr' ? 'Kapat' : 'Close',
    'notification_details' => $lang === 'tr' ? 'Bildirim Detayı' : 'Notification Details',
];

$extraStyles = '
    .filter-tabs { display: flex; gap: 10px; margin-bottom: 25px; }
    .filter-tab {
        padding: 10px 20px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.3s;
    }
    .filter-tab:hover, .filter-tab.active {
        background: rgba(96, 165, 250, 0.15);
        border-color: rgba(96, 165, 250, 0.3);
        color: #60a5fa;
    }
    .notification-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        margin-bottom: 15px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .notification-card:hover {
        transform: translateX(5px);
        border-color: rgba(96, 165, 250, 0.3);
    }
    .notification-card.unread { border-left: 4px solid #60a5fa; }
    .notification-card.read { opacity: 0.7; }
    .notification-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
    .notification-title { font-size: 1rem; font-weight: 600; color: #e2e8f0; }
    .notification-body { color: #94a3b8; line-height: 1.6; margin-bottom: 10px; }
    .notification-meta { display: flex; align-items: center; gap: 15px; }
    .notification-date { color: #64748b; font-size: 0.85rem; }
    .btn-mark-read {
        background: rgba(34, 197, 94, 0.2);
        border: 1px solid rgba(34, 197, 94, 0.3);
        color: #4ade80;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.3s;
    }
    .btn-mark-read:hover { background: rgba(34, 197, 94, 0.3); }
    .btn-mark-all-read {
        margin-left: auto;
        background: rgba(96, 165, 250, 0.2);
        border: 1px solid rgba(96, 165, 250, 0.35);
        color: #93c5fd;
        padding: 10px 18px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    .btn-mark-all-read:hover { background: rgba(96, 165, 250, 0.3); }
    .btn-mark-all-read:disabled { opacity: 0.5; cursor: not-allowed; }
    .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
    .empty-state i { font-size: 3rem; margin-bottom: 15px; color: #475569; }
    
     
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; }
    .modal-content {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px; max-width: 700px; width: 90%; max-height: 80vh;
        overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }
    .modal-header {
        background: linear-gradient(135deg, rgba(96, 165, 250, 0.2), rgba(167, 139, 250, 0.2));
        padding: 20px 25px; display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .modal-header h3 { margin: 0; font-size: 1.1rem; color: #e2e8f0; }
    .modal-close { background: rgba(255,255,255,0.1); border: none; color: #e2e8f0; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; }
    .modal-close:hover { background: rgba(255,255,255,0.2); }
    .modal-body { padding: 25px; max-height: 60vh; overflow-y: auto; color: #e2e8f0; }
    .modal-body h4 { margin: 0 0 15px; }
    .modal-body .meta-info { display: flex; gap: 20px; margin-bottom: 20px; color: #64748b; font-size: 0.9rem; }
    .modal-body .content { line-height: 1.7; color: #94a3b8; }
    .modal-footer { padding: 15px 25px; border-top: 1px solid rgba(255,255,255,0.1); text-align: right; }
    
     
    @media (max-width: 768px) {
        .filter-tabs { flex-wrap: wrap; gap: 8px; }
        .filter-tab { padding: 8px 15px; font-size: 0.85rem; flex: 1; min-width: 100px; text-align: center; justify-content: center; }
        .notification-card { padding: 15px; }
        .notification-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        .notification-title { font-size: 0.95rem; }
        .notification-body { font-size: 0.85rem; }
        .notification-meta { flex-wrap: wrap; gap: 10px; }
        .btn-mark-read { font-size: 0.75rem; padding: 4px 10px; }
        .modal-content { width: 95%; }
        .modal-header { padding: 15px 20px; }
        .modal-body { padding: 20px; }
        .modal-body .meta-info { flex-direction: column; gap: 10px; }
    }
';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['notifications'], $extraStyles); ?>
</head>
<body>
    <?php wls_render_sidebar('notifications', $lang); ?>
    
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-bell"></i> <?= $t['notifications'] ?></h1>
        </div>
        
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterNotifications('all')" id="filterAll">
                <i class="fas fa-list"></i> <?= $t['all'] ?> <span id="allCount" class="wls-badge wls-badge-info" style="margin-left:5px;">0</span>
            </button>
            <button class="filter-tab" onclick="filterNotifications('unread')" id="filterUnread">
                <i class="fas fa-envelope"></i> <?= $t['unread'] ?> <span id="unreadCount" class="wls-badge wls-badge-info" style="margin-left:5px;">0</span>
            </button>
            <button class="filter-tab" onclick="filterNotifications('read')" id="filterRead">
                <i class="fas fa-envelope-open"></i> <?= $t['read'] ?> <span id="readCount" class="wls-badge wls-badge-success" style="margin-left:5px;">0</span>
            </button>
            <button type="button" class="btn-mark-all-read" id="btnMarkAllRead" onclick="markAllAsRead()">
                <i class="fas fa-check-double"></i> <?= $t['mark_all_read'] ?>
            </button>
        </div>
        
        <div id="notificationsList">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p><?= $t['loading'] ?></p>
            </div>
        </div>
    </main>

 
<div class="modal-overlay" id="notifModal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fas fa-bell"></i> <?= $t['notification_details'] ?></h3>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="notifModalBody"></div>
        <div class="modal-footer">
            <button class="wls-btn wls-btn-primary" onclick="closeModal()"><?= $t['close'] ?></button>
        </div>
    </div>
</div>

<script>
var lang = <?= json_encode($t) ?>;
var allNotifications = [];
var currentFilter = 'all';

$(document).ready(function() {
    loadNotifications();
});

function isUnreadNotif(n) {
    return n.seen === "0" || n.seen === 0 || n.seen === null || n.seen === undefined;
}

function updateNotificationCounts() {
    var unreadCount = allNotifications.filter(isUnreadNotif).length;
    var readCount = allNotifications.length - unreadCount;
    $("#allCount").text(allNotifications.length);
    $("#unreadCount").text(unreadCount);
    $("#readCount").text(readCount);
    $("#btnMarkAllRead").prop('disabled', unreadCount === 0);
}

function loadNotifications() {
    $.post("ajax/notifications.php", {action: "list"}, function(d) {
        var notifications = [];
        if(d.status == "success" && d.data) {
            notifications = d.data.notifications || d.data || [];
        }
        if(!Array.isArray(notifications)) notifications = Object.values(notifications);
        
        if(notifications.length > 0) {
            allNotifications = notifications;
            
            allNotifications.sort(function(a, b) {
                var aUnread = isUnreadNotif(a) ? 0 : 1;
                var bUnread = isUnreadNotif(b) ? 0 : 1;
                if(aUnread === bUnread) {
                    return new Date(b.date_added || b.date) - new Date(a.date_added || a.date);
                }
                return aUnread - bUnread;
            });
            
            updateNotificationCounts();
            renderNotifications();
        } else {
            allNotifications = [];
            updateNotificationCounts();
            $("#notificationsList").html('<div class="empty-state"><i class="fas fa-bell-slash"></i><p>' + lang.no_notifications + '</p></div>');
        }
    }, "json").fail(function() {
        allNotifications = [];
        updateNotificationCounts();
        $("#notificationsList").html('<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error</p></div>');
    });
}

function renderNotifications() {
    var filtered = allNotifications;
    
    if(currentFilter == 'unread') filtered = allNotifications.filter(n => isUnreadNotif(n));
    else if(currentFilter == 'read') filtered = allNotifications.filter(n => !isUnreadNotif(n));
    
    if(filtered.length == 0) {
        $("#notificationsList").html('<div class="empty-state"><i class="fas fa-bell-slash"></i><p>' + lang.no_notifications + '</p></div>');
        return;
    }
    
    var html = '';
    filtered.forEach(function(n) {
        var isUnread = isUnreadNotif(n);
        var cardClass = isUnread ? 'unread' : 'read';
        var badgeClass = isUnread ? 'wls-badge-info' : 'wls-badge-success';
        var badgeText = isUnread ? lang.unread_label : lang.read_label;
        
        var bodyText = (n.body || n.content || n.message || '').replace(/<[^>]*>/g, '').substring(0, 200);
        if(bodyText.length >= 200) bodyText += '...';
        
        html += '<div class="notification-card ' + cardClass + '" id="notif-' + n.id + '" onclick="viewNotification(' + n.id + ')">';
        html += '<div class="notification-header">';
        html += '<span class="notification-title">' + (n.subject || n.title || 'Notification') + '</span>';
        html += '<span class="wls-badge ' + badgeClass + '">' + badgeText + '</span>';
        html += '</div>';
        html += '<div class="notification-body">' + bodyText + '</div>';
        html += '<div class="notification-meta">';
        html += '<span class="notification-date"><i class="fas fa-clock"></i> ' + (n.date_added || n.date || '') + '</span>';
        if(isUnread) {
            html += '<button class="btn-mark-read" onclick="event.stopPropagation(); markAsRead(' + n.id + ')"><i class="fas fa-check"></i> ' + lang.mark_read + '</button>';
        }
        html += '</div></div>';
    });
    
    $("#notificationsList").html(html);
}

function filterNotifications(filter) {
    currentFilter = filter;
    $(".filter-tab").removeClass("active");
    $("#filter" + filter.charAt(0).toUpperCase() + filter.slice(1)).addClass("active");
    renderNotifications();
}

function markAsRead(id) {
    $.post("ajax/notifications.php", {action: "ack", id: id}, function(d) {
        if(d.status == "success") {
            allNotifications = allNotifications.map(function(n) {
                if(n.id == id) n.seen = 1;
                return n;
            });
            updateNotificationCounts();
            $("#notif-" + id).removeClass("unread").addClass("read");
            $("#notif-" + id + " .wls-badge").removeClass("wls-badge-info").addClass("wls-badge-success").text(lang.read_label);
            $("#notif-" + id + " .btn-mark-read").remove();
        }
    }, "json");
}

function markAllAsRead() {
    var unread = allNotifications.filter(isUnreadNotif);
    if (unread.length === 0) {
        return;
    }

    $("#btnMarkAllRead").prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + lang.mark_all_read);

    $.post("ajax/notifications.php", {action: "ack_all"}, function(d) {
        if (d.status == "success") {
            allNotifications = allNotifications.map(function(n) {
                n.seen = 1;
                return n;
            });
            updateNotificationCounts();
            renderNotifications();
        }
        $("#btnMarkAllRead").html('<i class="fas fa-check-double"></i> ' + lang.mark_all_read);
    }, "json").fail(function() {
        $("#btnMarkAllRead").html('<i class="fas fa-check-double"></i> ' + lang.mark_all_read);
    });
}

function viewNotification(id) {
    var n = allNotifications.find(function(x) { return x.id == id; });
    if(!n) return;
    
    var isUnread = n.seen === "0" || n.seen === 0 || n.seen === null || n.seen === undefined;
    
    var html = '<h4>' + (n.subject || n.title || 'Notification') + '</h4>';
    html += '<div class="meta-info"><span><i class="fas fa-clock"></i> ' + (n.date_added || n.date || '') + '</span></div>';
    html += '<div class="content">' + (n.body || n.content || n.message || '') + '</div>';
    
    $("#notifModalBody").html(html);
    $("#notifModal").fadeIn(200);
    
    if(isUnread) markAsRead(id);
}

function closeModal(event) {
    if(event && event.target !== event.currentTarget) return;
    $("#notifModal").fadeOut(200);
}

$(document).keyup(function(e) { if(e.key === "Escape") closeModal(); });
</script>

</body>
</html>
