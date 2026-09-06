<?php
/**
 * WhiteLabelServices Ticket Management
 * Using shared sidebar layout
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

$modulePath = __DIR__ . '/../WhiteLabelServices.php';
if (file_exists($modulePath) && !function_exists('WhiteLabelServices_MetaData')) {
    require_once $modulePath;
}

$lang = $_GET['lang'] ?? $_COOKIE['wls_lang'] ?? 'en';
if (isset($_GET['lang'])) {
    setcookie('wls_lang', $lang, time() + 86400 * 365, '/');
}

$t = [
    'tickets' => $lang === 'tr' ? 'Destek Talepleri' : 'Support Tickets',
    'all_tickets' => $lang === 'tr' ? 'Tüm Ticketlar' : 'All Tickets',
    'new_ticket' => $lang === 'tr' ? 'Yeni Ticket' : 'New Ticket',
    'select_ticket' => $lang === 'tr' ? 'Ticket Seçin' : 'Select a Ticket',
    'select_ticket_desc' => $lang === 'tr' ? 'Görüşmeyi görüntülemek için listeden bir ticket seçin' : 'Choose a ticket from the list to view conversation',
    'reply_placeholder' => $lang === 'tr' ? 'Yanıtınızı buraya yazın...' : 'Type your reply here...',
    'send' => $lang === 'tr' ? 'Yanıt Gönder' : 'Send Reply',
    'department' => $lang === 'tr' ? 'Departman' : 'Department',
    'subject' => $lang === 'tr' ? 'Konu' : 'Subject',
    'message' => $lang === 'tr' ? 'Mesaj' : 'Message',
    'create' => $lang === 'tr' ? 'Ticket Oluştur' : 'Create Ticket',
    'cancel' => $lang === 'tr' ? 'İptal' : 'Cancel',
    'no_tickets' => $lang === 'tr' ? 'Ticket bulunamadı' : 'No tickets found',
    'closed_notice' => $lang === 'tr' ? 'Bu ticket kapalıdır ve yeni yanıt alamaz.' : 'This ticket is closed and cannot receive new replies.',
    'loading' => $lang === 'tr' ? 'Yükleniyor...' : 'Loading...',
    'original_message' => $lang === 'tr' ? 'İlk Mesaj' : 'Original Message',
    'close_ticket' => $lang === 'tr' ? 'Ticketı Kapat' : 'Close Ticket',
    'confirm_close' => $lang === 'tr' ? 'Bu ticketı kapatmak istediğinizden emin misiniz?' : 'Are you sure you want to close this ticket?',
];

$extraStyles = '
    .tickets-layout { display: grid; grid-template-columns: 350px 1fr; gap: 25px; height: calc(100vh - 150px); min-height: 500px; }
    
    /* Ticket List Panel */
    .ticket-panel {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .panel-header {
        padding: 20px;
        background: rgba(15, 23, 42, 0.5);
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .panel-header h3 { margin: 0; font-size: 1rem; font-weight: 600; color: #e2e8f0; }
    .btn-new-ticket {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff; border: none; padding: 8px 16px; border-radius: 8px;
        font-size: 0.85rem; cursor: pointer; transition: all 0.3s;
    }
    .btn-new-ticket:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3); }
    
    .ticket-list { flex: 1; overflow-y: auto; }
    .ticket-item {
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        cursor: pointer;
        transition: all 0.3s;
    }
    .ticket-item:hover { background: rgba(255,255,255,0.02); }
    .ticket-item.active { background: rgba(96, 165, 250, 0.15); border-left: 3px solid #60a5fa; }
    .ticket-item .ticket-id { font-weight: 600; color: #e2e8f0; font-size: 0.9rem; }
    .ticket-item .ticket-subject { color: #94a3b8; font-size: 0.85rem; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ticket-item .ticket-meta { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
    
    .ticket-status { font-size: 0.7rem; padding: 4px 10px; border-radius: 20px; font-weight: 500; }
    .status-open { background: rgba(234, 179, 8, 0.2); color: #facc15; }
    .status-answered { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
    .status-closed { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    
    /* Chat Panel */
    .chat-panel {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .chat-header {
        padding: 20px;
        background: rgba(15, 23, 42, 0.5);
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .chat-header h3 { margin: 0; font-size: 1rem; font-weight: 600; color: #e2e8f0; }
    
    .chat-messages { flex: 1; overflow-y: auto; padding: 20px; background: rgba(15, 23, 42, 0.3); }
    .message { max-width: 80%; margin-bottom: 15px; padding: 15px; border-radius: 12px; }
    .message-client { background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.2); margin-right: auto; }
    .message-admin { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.2); margin-left: auto; }
    .message-original { background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.2); }
    .message-content { font-size: 0.9rem; line-height: 1.6; color: #e2e8f0; }
    .message-meta { font-size: 0.75rem; color: #64748b; margin-top: 10px; }
    
    .chat-input { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
    .chat-input textarea {
        width: 100%; padding: 12px 15px;
        background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.15);
        border-radius: 10px; color: #e2e8f0; font-size: 0.9rem; resize: none;
    }
    .chat-input textarea:focus { outline: none; border-color: #60a5fa; }
    .chat-input .btn-send {
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        color: #fff; border: none; padding: 12px 25px; border-radius: 10px;
        margin-top: 10px; cursor: pointer; transition: all 0.3s;
    }
    .chat-input .btn-send:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4); }
    
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #64748b; }
    .empty-state i { font-size: 4rem; margin-bottom: 20px; color: #475569; }
    .empty-state h4 { margin: 0 0 10px; color: #94a3b8; font-weight: 500; }
    
    .closed-notice { background: rgba(239, 68, 68, 0.2); color: #f87171; padding: 15px 20px; text-align: center; font-size: 0.9rem; }
    
    /* Modal */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; }
    .modal-content {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px; width: 90%; max-width: 500px;
    }
    .modal-header {
        background: linear-gradient(135deg, rgba(96, 165, 250, 0.2), rgba(167, 139, 250, 0.2));
        padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-header h4 { margin: 0; color: #e2e8f0; }
    .modal-close { background: rgba(255,255,255,0.1); border: none; color: #e2e8f0; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; }
    .modal-body { padding: 25px; }
    .modal-footer { padding: 15px 25px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; gap: 10px; }
    
    @media (max-width: 1024px) {
        .tickets-layout { grid-template-columns: 1fr; height: auto; }
        .ticket-panel { height: 350px; }
        .chat-panel { height: 450px; }
    }
    
    @media (max-width: 768px) {
        .tickets-layout { gap: 15px; }
        .ticket-panel { height: 280px; }
        .chat-panel { height: 400px; }
        .panel-header { padding: 15px; flex-wrap: wrap; gap: 10px; }
        .panel-header h3 { font-size: 0.9rem; }
        .btn-new-ticket { padding: 6px 12px; font-size: 0.8rem; }
        .ticket-item { padding: 12px 15px; }
        .ticket-item .ticket-id { font-size: 0.85rem; }
        .ticket-item .ticket-subject { font-size: 0.8rem; }
        .chat-header { padding: 15px; flex-wrap: wrap; gap: 10px; }
        .chat-messages { padding: 15px; }
        .message { max-width: 90%; padding: 12px; }
        .message-content { font-size: 0.85rem; }
        .chat-input { padding: 15px; }
        .chat-input .btn-send { padding: 10px 20px; font-size: 0.85rem; }
        .modal-content { width: 95%; }
        .modal-body { padding: 20px; }
    }
';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['tickets'], $extraStyles); ?>
</head>
<body>
    <?php wls_render_sidebar('tickets', $lang); ?>
    
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-ticket-alt"></i> <?= $t['tickets'] ?></h1>
        </div>
        
        <div class="tickets-layout">
            <!-- Ticket List Panel -->
            <div class="ticket-panel">
                <div class="panel-header">
                    <h3><i class="fas fa-inbox"></i> <?= $t['all_tickets'] ?></h3>
                    <button class="btn-new-ticket" onclick="openNewTicketModal()">
                        <i class="fas fa-plus"></i> <?= $t['new_ticket'] ?>
                    </button>
                </div>
                <div class="ticket-list" id="ticketList">
                    <div style="padding:40px;text-align:center;color:#64748b;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top:15px;"><?= $t['loading'] ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Chat Panel -->
            <div class="chat-panel">
                <div id="emptyState" class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h4><?= $t['select_ticket'] ?></h4>
                    <p><?= $t['select_ticket_desc'] ?></p>
                </div>
                
                <div id="chatView" style="display:none; height:100%; flex-direction:column;">
                    <div class="chat-header" id="chatHeader">
                        <h3>Ticket</h3>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span class="ticket-status" id="chatStatus"></span>
                            <button id="closeTicketBtn" class="wls-btn wls-btn-secondary" onclick="closeTicket()" style="display:none; padding:6px 12px; font-size:0.8rem;">
                                <i class="fas fa-times-circle"></i> <?= $t['close_ticket'] ?>
                            </button>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages"></div>
                    
                    <div class="chat-input" id="replyArea">
                        <textarea id="replyMessage" rows="3" placeholder="<?= $t['reply_placeholder'] ?>"></textarea>
                        <button class="btn-send" onclick="sendReply()">
                            <i class="fas fa-paper-plane"></i> <?= $t['send'] ?>
                        </button>
                    </div>
                    
                    <div class="closed-notice" id="closedNotice" style="display:none;">
                        <i class="fas fa-lock"></i> <?= $t['closed_notice'] ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

<!-- New Ticket Modal -->
<div class="modal-overlay" id="newTicketModal" onclick="closeNewTicketModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h4><i class="fas fa-plus-circle"></i> <?= $t['new_ticket'] ?></h4>
            <button class="modal-close" onclick="closeNewTicketModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="wls-form-group">
                <label><i class="fas fa-building"></i> <?= $t['department'] ?></label>
                <select id="newDept" style="max-width:100%;"></select>
            </div>
            <div class="wls-form-group">
                <label><i class="fas fa-heading"></i> <?= $t['subject'] ?></label>
                <input type="text" id="newSubj" placeholder="<?= $t['subject'] ?>..." style="max-width:100%;">
            </div>
            <div class="wls-form-group">
                <label><i class="fas fa-comment"></i> <?= $t['message'] ?></label>
                <textarea id="newMsg" rows="5" placeholder="<?= $t['message'] ?>..." style="max-width:100%;"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="wls-btn wls-btn-secondary" onclick="closeNewTicketModal()"><?= $t['cancel'] ?></button>
            <button class="wls-btn wls-btn-primary" onclick="createTicket()">
                <i class="fas fa-paper-plane"></i> <?= $t['create'] ?>
            </button>
        </div>
    </div>
</div>

<script>
var activeId = null;
var activeStatus = null;
var lang = <?= json_encode($t) ?>;

$(function() { 
    loadTickets(); 
    loadDepts();
    $("#chatView").hide();
});

function openNewTicketModal() { $("#newTicketModal").fadeIn(200); }
function closeNewTicketModal(e) {
    if(e && e.target !== e.currentTarget) return;
    $("#newTicketModal").fadeOut(200);
}

function loadTickets() {
    $.post("ajax/tickets.php", {action:"list"}, function(d) {
        if(d.status=="success" && d.data.tickets && d.data.tickets.length > 0) {
            var h = "";
            d.data.tickets.forEach(function(t) {
                var statusClass = t.status == "Closed" ? "status-closed" : (t.status == "Open" ? "status-open" : "status-answered");
                h += '<div class="ticket-item" data-status="'+t.status+'" onclick="viewTicket('+(t.ticket_number)+',this, \''+t.status+'\')">';
                h += '<div class="ticket-id">#'+t.ticket_number+'</div>';
                h += '<div class="ticket-subject">'+t.subject+'</div>';
                h += '<div class="ticket-meta">';
                h += '<span class="ticket-status '+statusClass+'">'+t.status+'</span>';
                h += '<span style="color:#64748b;font-size:0.7rem;">'+t.deptname+'</span>';
                h += '</div></div>';
            });
            $("#ticketList").html(h);
        } else {
            $("#ticketList").html('<div class="empty-state" style="padding:40px;"><i class="fas fa-inbox"></i><p>' + lang.no_tickets + '</p></div>');
        }
    }, "json");
}

function loadDepts() {
    $.post("ajax/tickets.php", {action:"departments"}, function(d) {
        if(d.status=="success" && d.data.departments) {
            var h = "";
            d.data.departments.forEach(function(x) { h += '<option value="'+x.id+'">'+x.name+'</option>'; });
            $("#newDept").html(h);
        }
    }, "json");
}

function viewTicket(id, el, status) {
    activeId = id;
    activeStatus = status;
    $(".ticket-item").removeClass("active");
    $(el).addClass("active");
    $("#emptyState").hide();
    $("#chatView").show().css("display", "flex");
    
    // Loading state - show loading in chat messages
    $("#chatMessages").html('<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#64748b;"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:15px;">' + lang.loading + '</p></div>');
    $("#chatHeader h3").html('<i class="fas fa-spinner fa-spin"></i> ' + lang.loading);
    
    // Mobilde chat paneline scroll
    if(window.innerWidth <= 768) {
        $('html, body').animate({
            scrollTop: $(".chat-panel").offset().top - 80
        }, 300);
    }
    
    if(status === "Closed") {
        $("#replyArea").hide();
        $("#closedNotice").show();
        $("#closeTicketBtn").hide();
    } else {
        $("#replyArea").show();
        $("#closedNotice").hide();
        $("#closeTicketBtn").show();
    }
    
    $.post("ajax/tickets.php", {action:"view",id:id}, function(d) {
        if(d.status=="success") {
            var t = d.data.ticket;
            var statusClass = t.status == "Closed" ? "status-closed" : (t.status == "Open" ? "status-open" : "status-answered");
            
            $("#chatHeader h3").html('<i class="fas fa-ticket-alt"></i> #'+t.ticket_number+' - '+t.subject);
            $("#chatStatus").attr("class", "ticket-status " + statusClass).text(t.status);
            
            var h = '<div class="message message-original"><div class="message-content">'+t.body.replace(/\n/g,"<br>")+'</div><div class="message-meta"><i class="fas fa-user"></i> '+lang.original_message+' • '+t.date+'</div></div>';
            
            if(d.data.replies) d.data.replies.forEach(function(r) {
                var msgClass = r.type=="Admin" ? "message-admin" : "message-client";
                var icon = r.type=="Admin" ? "fas fa-headset" : "fas fa-user";
                h += '<div class="message '+msgClass+'"><div class="message-content">'+r.body.replace(/\n/g,"<br>")+'</div><div class="message-meta"><i class="'+icon+'"></i> '+r.name+' • '+r.date+'</div></div>';
            });
            
            $("#chatMessages").html(h);
            $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
            
            if(t.status === "Closed") {
                $("#replyArea").hide();
                $("#closedNotice").show();
            }
        } else {
            $("#chatMessages").html('<div style="text-align:center;padding:40px;color:#f87171;"><i class="fas fa-exclamation-triangle fa-2x"></i><p style="margin-top:15px;">Error loading ticket</p></div>');
        }
    }, "json").fail(function() {
        $("#chatMessages").html('<div style="text-align:center;padding:40px;color:#f87171;"><i class="fas fa-exclamation-triangle fa-2x"></i><p style="margin-top:15px;">Connection error</p></div>');
    });
}

function sendReply() {
    if(activeStatus === "Closed") return;
    var m = $("#replyMessage").val();
    if(!m) return;
    
    var btn = $(".btn-send");
    btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.post("ajax/tickets.php", {action:"reply",id:activeId,message:m}, function(d) {
        btn.prop("disabled", false).html('<i class="fas fa-paper-plane"></i> ' + lang.send);
        if(d.status=="success") { 
            $("#replyMessage").val(""); 
            viewTicket(activeId, $(".ticket-item.active"), activeStatus); 
        } else {
            alert(d.message);
        }
    }, "json");
}

function createTicket() {
    var dept = $("#newDept").val();
    var subj = $("#newSubj").val();
    var msg = $("#newMsg").val();
    if(!dept || !subj || !msg) return;
    
    $.post("ajax/tickets.php", {action:"create",dept_id:dept,subject:subj,message:msg}, function(d) {
        if(d.status=="success") { 
            closeNewTicketModal(); 
            $("#newSubj, #newMsg").val("");
            loadTickets(); 
        } else {
            alert(d.message);
        }
    }, "json");
}

function closeTicket() {
    if(!activeId) return;
    if(!confirm(lang.confirm_close)) return;
    
    var btn = $("#closeTicketBtn");
    btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.post("ajax/tickets.php", {action:"close", id:activeId}, function(d) {
        if(d.status=="success") {
            activeStatus = "Closed";
            $("#replyArea").hide();
            $("#closedNotice").show();
            $("#closeTicketBtn").hide();
            $("#chatStatus").attr("class", "ticket-status status-closed").text("Closed");
            loadTickets();
        } else {
            alert(d.message);
            btn.prop("disabled", false).html('<i class="fas fa-times-circle"></i> ' + lang.close_ticket);
        }
    }, "json");
}

$(document).keyup(function(e) { if(e.key === "Escape") closeNewTicketModal(); });
</script>

</body>
</html>
