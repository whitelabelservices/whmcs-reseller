<?php
/**
 * WhiteLabelServices Admin - Ticket Yönetimi
 * Iliskili WLS hizmetine acilan taleplerin portal API'ye gonderim durumu (mod_wls_ticket_tasks)
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
require_once dirname(__DIR__) . '/lib/TokenManager.php';

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['wls_lang']) ? $_COOKIE['wls_lang'] : 'en');
if (isset($_GET['lang'])) {
    setcookie('wls_lang', $lang, time() + 86400 * 365, '/');
}

$ticketStats = ['Pending' => 0, 'Completed' => 0, 'Failed' => 0, 'rows' => []];
$adminBase = '../../../../admin';
try {
    WLSTokenManager::ensureTablesExist();
    if (Capsule::schema()->hasTable('mod_wls_ticket_tasks')) {
        $ticketStats['Pending'] = (int) Capsule::table('mod_wls_ticket_tasks')->where('status', 'Pending')->count();
        $ticketStats['Completed'] = (int) Capsule::table('mod_wls_ticket_tasks')->where('status', 'Completed')->count();
        $ticketStats['Failed'] = (int) Capsule::table('mod_wls_ticket_tasks')->where('status', 'Failed')->count();
        $ticketStats['rows'] = Capsule::table('mod_wls_ticket_tasks')
            ->leftJoin('tblhosting', 'tblhosting.id', '=', 'mod_wls_ticket_tasks.hosting_id')
            ->select('mod_wls_ticket_tasks.*', 'tblhosting.userid')
            ->orderBy('mod_wls_ticket_tasks.id', 'desc')
            ->limit(50)
            ->get();
    }
} catch (Exception $e) {}

$t = [
    'ticket_management' => $lang === 'tr' ? 'Ticket Yönetimi' : 'Ticket Management',
    'ticket_management_desc' => $lang === 'tr' ? 'İlişkili WLS hizmetine açılan talepler burada Pending olarak listelenir; cron ile portal API\'ye gönderilir.' : 'Tickets opened for related WLS services are listed here as Pending and sent to the portal API by cron.',
    'ticket_tasks' => $lang === 'tr' ? 'Talep senkron görevleri' : 'Ticket sync tasks',
    'pending' => $lang === 'tr' ? 'Bekleyen' : 'Pending',
    'completed' => $lang === 'tr' ? 'Tamamlanan' : 'Completed',
    'failed' => $lang === 'tr' ? 'Başarısız' : 'Failed',
    'id' => 'ID',
    'whmcs_ticket' => $lang === 'tr' ? 'WHMCS Talep' : 'WHMCS Ticket',
    'hosting_id' => $lang === 'tr' ? 'Hizmet ID' : 'Hosting ID',
    'subject' => $lang === 'tr' ? 'Konu' : 'Subject',
    'status' => $lang === 'tr' ? 'Durum' : 'Status',
    'created_at' => $lang === 'tr' ? 'Oluşturulma' : 'Created',
    'processed_at' => $lang === 'tr' ? 'İşlenme' : 'Processed',
    'no_tasks' => $lang === 'tr' ? 'Kayıt yok' : 'No records',
];

$extraStyles = '
    .ticket-table { width: 100%; border-collapse: collapse; }
    .ticket-table th {
        background: rgba(15, 23, 42, 0.5); padding: 15px; text-align: left;
        font-weight: 500; font-size: 0.85rem; color: #64748b; text-transform: uppercase;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .ticket-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    .ticket-table tr:hover { background: rgba(255,255,255,0.02); }
    .ticket-table .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; }
    .ticket-table .subject-cell { max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0 -15px;
        padding: 0 15px;
    }
    .table-responsive::-webkit-scrollbar { height: 6px; }
    .table-responsive::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 3px; }
    .table-responsive::-webkit-scrollbar-thumb { background: rgba(96, 165, 250, 0.3); border-radius: 3px; }
    .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
    .empty-state i { font-size: 3rem; margin-bottom: 15px; color: #475569; }
    @media (max-width: 768px) { .ticket-table { min-width: 700px; } }
    .ticket-table a { color: #60a5fa; text-decoration: none; }
    .ticket-table a:hover { text-decoration: underline; }
';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['ticket_management'], $extraStyles); ?>
</head>
<body>
    <?php wls_render_sidebar('ticket_management', $lang); ?>
    
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-list-alt"></i> <?= $t['ticket_management'] ?></h1>
        </div>
        
        <p class="hint" style="margin-bottom: 20px;"><?= $t['ticket_management_desc'] ?></p>
        
        <div class="wls-card">
            <div class="wls-card-header">
                <i class="fas fa-ticket-alt"></i>
                <h2><?= $t['ticket_tasks'] ?></h2>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <span class="wls-badge" style="background: #f59e0b; color: #fff;"><?= $t['pending'] ?>: <?= (int)$ticketStats['Pending'] ?></span>
                <span class="wls-badge" style="background: #10b981; color: #fff;"><?= $t['completed'] ?>: <?= (int)$ticketStats['Completed'] ?></span>
                <span class="wls-badge" style="background: #ef4444; color: #fff;"><?= $t['failed'] ?>: <?= (int)$ticketStats['Failed'] ?></span>
            </div>
            <div class="table-responsive">
                <table class="ticket-table">
                    <thead>
                        <tr>
                            <th><?= $t['id'] ?></th>
                            <th><?= $t['whmcs_ticket'] ?></th>
                            <th><?= $t['hosting_id'] ?></th>
                            <th><?= $t['subject'] ?></th>
                            <th><?= $t['status'] ?></th>
                            <th><?= $t['created_at'] ?></th>
                            <th><?= $t['processed_at'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ticketStats['rows'])): ?>
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-inbox"></i><p><?= $t['no_tasks'] ?></p></td></tr>
                        <?php else: foreach ($ticketStats['rows'] as $r):
                            $st = isset($r->status) ? $r->status : '';
                            $bg = $st === 'Completed' ? '#10b981' : ($st === 'Failed' ? '#ef4444' : '#f59e0b');
                            $subj = isset($r->subject) ? $r->subject : '';
                            $tid = (int)(isset($r->whmcs_ticket_id) ? $r->whmcs_ticket_id : 0);
                            $hid = (int)(isset($r->hosting_id) ? $r->hosting_id : 0);
                            $uid = (int)(isset($r->userid) ? $r->userid : 0);
                            $ticketLink = $adminBase . '/supporttickets.php?action=view&id=' . $tid;
                            $serviceLink = $uid ? ($adminBase . '/clientsservices.php?userid=' . $uid . '&id=' . $hid) : '';
                        ?>
                        <tr>
                            <td><?= (int)(isset($r->id) ? $r->id : 0) ?></td>
                            <td><?php if ($tid): ?><a href="<?= htmlspecialchars($ticketLink) ?>" target="_blank">#<?= $tid ?></a><?php else: ?>-<?php endif; ?></td>
                            <td><?php if ($hid): ?><?php if ($serviceLink): ?><a href="<?= htmlspecialchars($serviceLink) ?>" target="_blank"><?= $hid ?></a><?php else: ?><?= $hid ?><?php endif; ?><?php else: ?>-<?php endif; ?></td>
                            <td class="subject-cell" title="<?= htmlspecialchars($subj) ?>"><?= htmlspecialchars(mb_substr($subj, 0, 40)) ?><?= mb_strlen($subj) > 40 ? '…' : '' ?></td>
                            <td><span class="status-badge" style="background: <?= $bg ?>; color: #fff;"><?= htmlspecialchars($st) ?></span></td>
                            <td><?= htmlspecialchars(isset($r->created_at) ? $r->created_at : '') ?></td>
                            <td><?= htmlspecialchars(isset($r->processed_at) ? $r->processed_at : '-') ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
