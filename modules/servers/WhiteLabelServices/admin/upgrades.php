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

$lang = $_GET['lang'] ?? $_COOKIE['wls_lang'] ?? 'en';
if (isset($_GET['lang'])) {
    setcookie('wls_lang', $lang, time() + 86400 * 365, '/');
}

$upgradeStats = ['Pending' => 0, 'Completed' => 0, 'Failed' => 0, 'rows' => []];
try {
    WLSTokenManager::ensureTablesExist();
    if (Capsule::schema()->hasTable('mod_wls_upgrade_tasks')) {
        $upgradeStats['Pending'] = (int) Capsule::table('mod_wls_upgrade_tasks')->where('status', 'Pending')->count();
        $upgradeStats['Completed'] = (int) Capsule::table('mod_wls_upgrade_tasks')->where('status', 'Completed')->count();
        $upgradeStats['Failed'] = (int) Capsule::table('mod_wls_upgrade_tasks')->where('status', 'Failed')->count();
        $upgradeStats['rows'] = Capsule::table('mod_wls_upgrade_tasks')
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();
    }
} catch (Exception $e) {}

$t = [
    'upgrade_management' => $lang === 'tr' ? 'Yükseltme Yönetimi' : 'Upgrade Management',
    'upgrade_management_desc' => $lang === 'tr' ? 'Paket yükseltme görevlerinin durumları ' : 'Package upgrade task status (mod_wls_upgrade_tasks)',
    'package_upgrades' => $lang === 'tr' ? 'Paket yükseltme görevleri' : 'Package upgrade tasks',
    'pending' => $lang === 'tr' ? 'Bekleyen' : 'Pending',
    'completed' => $lang === 'tr' ? 'Tamamlanan' : 'Completed',
    'failed' => $lang === 'tr' ? 'Başarısız' : 'Failed',
    'id' => 'ID',
    'service_id' => $lang === 'tr' ? 'Servis ID' : 'Service ID',
    'order_id' => $lang === 'tr' ? 'Sipariş ID' : 'Order ID',
    'status' => $lang === 'tr' ? 'Durum' : 'Status',
    'created_at' => $lang === 'tr' ? 'Oluşturulma' : 'Created',
    'last_error' => $lang === 'tr' ? 'Son hata' : 'Last error',
    'no_tasks' => $lang === 'tr' ? 'Kayıt yok' : 'No records',
];

$extraStyles = '
    .upgrade-table { width: 100%; border-collapse: collapse; }
    .upgrade-table th {
        background: rgba(15, 23, 42, 0.5); padding: 15px; text-align: left;
        font-weight: 500; font-size: 0.85rem; color: #64748b; text-transform: uppercase;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .upgrade-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    .upgrade-table tr:hover { background: rgba(255,255,255,0.02); }
    .upgrade-table .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; }
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
    @media (max-width: 768px) { .upgrade-table { min-width: 600px; } }
';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['upgrade_management'], $extraStyles); ?>
</head>
<body>
    <?php wls_render_sidebar('upgrades', $lang); ?>
    
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-arrow-circle-up"></i> <?= $t['upgrade_management'] ?></h1>
        </div>
        
        <p class="hint" style="margin-bottom: 20px;"><?= $t['upgrade_management_desc'] ?></p>
        
        <div class="wls-card">
            <div class="wls-card-header">
                <i class="fas fa-box-open"></i>
                <h2><?= $t['package_upgrades'] ?></h2>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <span class="wls-badge" style="background: #f59e0b; color: #fff;"><?= $t['pending'] ?>: <?= (int)$upgradeStats['Pending'] ?></span>
                <span class="wls-badge" style="background: #10b981; color: #fff;"><?= $t['completed'] ?>: <?= (int)$upgradeStats['Completed'] ?></span>
                <span class="wls-badge" style="background: #ef4444; color: #fff;"><?= $t['failed'] ?>: <?= (int)$upgradeStats['Failed'] ?></span>
            </div>
            <div class="table-responsive">
                <table class="upgrade-table">
                    <thead>
                        <tr>
                            <th><?= $t['id'] ?></th>
                            <th><?= $t['service_id'] ?></th>
                            <th><?= $t['order_id'] ?></th>
                            <th><?= $t['status'] ?></th>
                            <th><?= $t['created_at'] ?></th>
                            <th><?= $t['last_error'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($upgradeStats['rows'])): ?>
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-inbox"></i><p><?= $t['no_tasks'] ?></p></td></tr>
                        <?php else: foreach ($upgradeStats['rows'] as $r):
                            $st = $r->status ?? '';
                            $bg = $st === 'Completed' ? '#10b981' : ($st === 'Failed' ? '#ef4444' : '#f59e0b');
                        ?>
                        <tr>
                            <td><?= (int)($r->id ?? 0) ?></td>
                            <td><?= (int)($r->service_id ?? 0) ?></td>
                            <td><?= (int)($r->order_id ?? 0) ?></td>
                            <td><span class="status-badge" style="background: <?= $bg ?>; color: #fff;"><?= htmlspecialchars($st) ?></span></td>
                            <td><?= htmlspecialchars($r->created_at ?? '') ?></td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($r->last_error ?? '') ?>"><?= htmlspecialchars(mb_substr($r->last_error ?? '', 0, 60)) ?><?= mb_strlen($r->last_error ?? '') > 60 ? '…' : '' ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
