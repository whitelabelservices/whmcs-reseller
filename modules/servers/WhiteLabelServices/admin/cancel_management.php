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
require_once dirname(__DIR__) . '/lib/TokenManager.php';

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['wls_lang']) ? $_COOKIE['wls_lang'] : 'en');
if (isset($_GET['lang'])) {
    setcookie('wls_lang', $lang, time() + 86400 * 365, '/');
}

$cancelStats = ['Pending' => 0, 'Completed' => 0, 'Failed' => 0, 'rows' => []];
$adminBase = '../../../../admin';
try {
    WLSTokenManager::ensureTablesExist();
    if (Capsule::schema()->hasTable('mod_wls_cancel_tasks')) {
        $cancelStats['Pending'] = (int) Capsule::table('mod_wls_cancel_tasks')->where('status', 'Pending')->count();
        $cancelStats['Completed'] = (int) Capsule::table('mod_wls_cancel_tasks')->where('status', 'Completed')->count();
        $cancelStats['Failed'] = (int) Capsule::table('mod_wls_cancel_tasks')->where('status', 'Failed')->count();
        $cancelStats['rows'] = Capsule::table('mod_wls_cancel_tasks')
            ->leftJoin('tblhosting', 'tblhosting.id', '=', 'mod_wls_cancel_tasks.service_id')
            ->select('mod_wls_cancel_tasks.*', 'tblhosting.userid')
            ->orderBy('mod_wls_cancel_tasks.id', 'desc')
            ->limit(50)
            ->get();
    }
} catch (Exception $e) {}

$t = [
    'cancel_management' => $lang === 'tr' ? 'İptal Yönetimi' : 'Cancellation Management',
    'cancel_management_desc' => $lang === 'tr' ? 'WLS hizmetine açılan iptal talepleri burada Pending olarak listelenir; cron ile portal API\'ye gönderilir.' : 'Cancellation requests for WLS services are listed here as Pending and sent to the portal API by cron.',
    'cancel_tasks' => $lang === 'tr' ? 'İptal talepleri' : 'Cancellation requests',
    'pending' => $lang === 'tr' ? 'Bekleyen' : 'Pending',
    'completed' => $lang === 'tr' ? 'Tamamlanan' : 'Completed',
    'failed' => $lang === 'tr' ? 'Başarısız' : 'Failed',
    'id' => 'ID',
    'service_id' => $lang === 'tr' ? 'Hizmet ID' : 'Service ID',
    'wls_service_id' => $lang === 'tr' ? 'WLS Servis ID' : 'WLS Service ID',
    'cancel_type' => $lang === 'tr' ? 'İptal tipi' : 'Cancel type',
    'immediate' => $lang === 'tr' ? 'Hemen' : 'Immediate',
    'end_of_period' => $lang === 'tr' ? 'Dönem sonu' : 'End of period',
    'reason' => $lang === 'tr' ? 'Sebep' : 'Reason',
    'status' => $lang === 'tr' ? 'Durum' : 'Status',
    'created_at' => $lang === 'tr' ? 'Oluşturulma' : 'Created',
    'processed_at' => $lang === 'tr' ? 'İşlenme' : 'Processed',
    'no_tasks' => $lang === 'tr' ? 'Kayıt yok' : 'No records',
];

$extraStyles = '
    .cancel-table { width: 100%; border-collapse: collapse; }
    .cancel-table th {
        background: rgba(15, 23, 42, 0.5); padding: 15px; text-align: left;
        font-weight: 500; font-size: 0.85rem; color: #64748b; text-transform: uppercase;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .cancel-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    .cancel-table tr:hover { background: rgba(255,255,255,0.02); }
    .cancel-table .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; }
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0 -15px; padding: 0 15px; }
    .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
    .cancel-table a { color: #60a5fa; text-decoration: none; }
    .cancel-table a:hover { text-decoration: underline; }
    @media (max-width: 768px) { .cancel-table { min-width: 600px; } }
';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['cancel_management'], $extraStyles); ?>
</head>
<body>
    <?php wls_render_sidebar('cancel_management', $lang); ?>
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-times-circle"></i> <?= $t['cancel_management'] ?></h1>
        </div>
        <p class="hint" style="margin-bottom: 20px;"><?= $t['cancel_management_desc'] ?></p>
        <div class="wls-card">
            <div class="wls-card-header">
                <i class="fas fa-ban"></i>
                <h2><?= $t['cancel_tasks'] ?></h2>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <span class="wls-badge" style="background: #f59e0b; color: #fff;"><?= $t['pending'] ?>: <?= (int)$cancelStats['Pending'] ?></span>
                <span class="wls-badge" style="background: #10b981; color: #fff;"><?= $t['completed'] ?>: <?= (int)$cancelStats['Completed'] ?></span>
                <span class="wls-badge" style="background: #ef4444; color: #fff;"><?= $t['failed'] ?>: <?= (int)$cancelStats['Failed'] ?></span>
            </div>
            <div class="table-responsive">
                <table class="cancel-table">
                    <thead>
                        <tr>
                            <th><?= $t['id'] ?></th>
                            <th><?= $t['service_id'] ?></th>
                            <th><?= $t['wls_service_id'] ?></th>
                            <th><?= $t['cancel_type'] ?></th>
                            <th><?= $t['reason'] ?></th>
                            <th><?= $t['status'] ?></th>
                            <th><?= $t['created_at'] ?></th>
                            <th><?= $t['processed_at'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cancelStats['rows'])): ?>
                        <tr><td colspan="8" class="empty-state"><i class="fas fa-inbox"></i><p><?= $t['no_tasks'] ?></p></td></tr>
                        <?php else: foreach ($cancelStats['rows'] as $r):
                            $st = isset($r->status) ? $r->status : '';
                            $bg = $st === 'Completed' ? '#10b981' : ($st === 'Failed' ? '#ef4444' : '#f59e0b');
                            $sid = (int)(isset($r->service_id) ? $r->service_id : 0);
                            $uid = (int)(isset($r->userid) ? $r->userid : 0);
                            $serviceLink = $uid ? ($adminBase . '/clientsservices.php?userid=' . $uid . '&id=' . $sid) : '';
                            $reason = isset($r->reason) ? $r->reason : '';
                        ?>
                        <tr>
                            <td><?= (int)(isset($r->id) ? $r->id : 0) ?></td>
                            <td><?php if ($sid): ?><?php if ($serviceLink): ?><a href="<?= htmlspecialchars($serviceLink) ?>" target="_blank"><?= $sid ?></a><?php else: ?><?= $sid ?><?php endif; ?><?php else: ?>-<?php endif; ?></td>
                            <td><?= (int)(isset($r->wls_service_id) ? $r->wls_service_id : 0) ?></td>
                            <td><?= !empty($r->immediate) ? $t['immediate'] : $t['end_of_period'] ?></td>
                            <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($reason) ?>"><?= htmlspecialchars(mb_substr($reason, 0, 40)) ?><?= mb_strlen($reason) > 40 ? '…' : '' ?></td>
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
