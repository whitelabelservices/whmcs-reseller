<?php
/**
 * WhiteLabelServices Admin — Bağlı Hizmetler (senkron takılan / provisioning kalan WLS hizmetleri)
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

$adminBase = '../../../../admin';

// fetch için mutlak URL (iframe / göreli yol / proxy senaryolarında "Request failed" önlemi)
$wlsTriggerAjaxUrl = '';
if (!empty($_SERVER['HTTP_HOST'])) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $wlsTriggerAjaxUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim($scriptDir, '/') . '/ajax/trigger_stuck_service.php';
}

$rows = [];

try {
    WLSTokenManager::ensureTablesExist();
    if (Capsule::schema()->hasTable('mod_wls_vps')) {
        $q = Capsule::table('mod_wls_vps as v')
            ->join('tblhosting as h', 'h.id', '=', 'v.id')
            ->join('tblproducts as p', 'p.id', '=', 'h.packageid')
            ->leftJoin('tblclients as c', 'c.id', '=', 'h.userid')
            ->where('p.servertype', 'WhiteLabelServices')
            ->whereNotIn('h.domainstatus', ['Cancelled', 'Terminated'])
            ->where(function ($w) {
                $w->whereIn('v.status', ['Pending', 'provisioning'])
                    ->orWhereNull('v.vm_built')
                    ->orWhere('v.vm_built', '=', 0)
                    ->orWhere(function ($w2) {
                        $w2->whereNotNull('v.wls_service_id')
                            ->where(function ($w3) {
                                $w3->whereNull('v.wls_vm_id')
                                    ->orWhereNull('v.vm_status')
                                    ->orWhere('v.vm_status', '=', '');
                            });
                    });
            })
            ->select([
                'v.id as service_id',
                'v.wls_service_id',
                'v.wls_vm_id',
                'v.vm_built',
                'v.vm_status',
                'v.status as wls_row_status',
                'v.updated_at as vps_updated',
                'h.userid',
                'h.domain',
                'h.domainstatus',
            ])
            ->selectRaw('COALESCE(CONCAT(c.firstname, \' \', c.lastname), \'\') as client_name')
            ->orderBy('v.id', 'desc')
            ->limit(200);

        $rows = $q->get()->all();
    }
} catch (Exception $e) {
    $rows = [];
}

$t = [
    'title' => $lang === 'tr' ? 'Bağlı Hizmetler' : 'Linked Services',
    'desc' => $lang === 'tr'
        ? 'Portal API yanıt veriyor ancak WHMCS’te vm_built / VM durumu güncellenmediyse müşteri alanında “Preparing Your VPS” takılır. Buradan aynı cron mantığını veya doğrudan API VM senkronunu tetikleyebilirsiniz.'
        : 'If the portal API works but WHMCS never got vm_built / VM status, the client area stays on “Preparing Your VPS”. Trigger the same check as cron or pull VM details from the API here.',
    'service_id' => $lang === 'tr' ? 'Hizmet ID' : 'Service ID',
    'wls_sid' => $lang === 'tr' ? 'WLS servis' : 'WLS service',
    'wls_vid' => $lang === 'tr' ? 'WLS VM' : 'WLS VM',
    'client' => $lang === 'tr' ? 'Müşteri' : 'Client',
    'domain' => $lang === 'tr' ? 'Alan / etiket' : 'Domain / label',
    'whmcs_status' => $lang === 'tr' ? 'WHMCS durum' : 'WHMCS status',
    'vm_built' => 'vm_built',
    'vm_status' => 'vm_status',
    'row_status' => $lang === 'tr' ? 'WLS satır durumu' : 'WLS row status',
    'actions' => $lang === 'tr' ? 'İşlemler' : 'Actions',
    'btn_check' => $lang === 'tr' ? 'VM durum kontrolü' : 'Run VM status check',
    'btn_sync' => $lang === 'tr' ? 'API’den VM senkron' : 'Sync VM from API',
    'empty' => $lang === 'tr' ? 'Şu an kriterlere uyan takılı kayıt yok.' : 'No services match the stuck criteria.',
    'working' => $lang === 'tr' ? 'İşleniyor…' : 'Working…',
];

$extraStyles = '
    .linked-table { width: 100%; border-collapse: collapse; }
    .linked-table th {
        background: rgba(15, 23, 42, 0.5); padding: 12px; text-align: left;
        font-weight: 500; font-size: 0.8rem; color: #64748b; text-transform: uppercase;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .linked-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    .linked-table tr:hover { background: rgba(255,255,255,0.02); }
    .linked-table .btn-sm {
        display: inline-block; margin: 2px 4px 2px 0; padding: 6px 10px; font-size: 0.75rem;
        border-radius: 6px; border: none; cursor: pointer; background: rgba(96, 165, 250, 0.25); color: #93c5fd;
    }
    .linked-table .btn-sm:hover { background: rgba(96, 165, 250, 0.4); }
    .linked-table .btn-sm:disabled { opacity: 0.5; cursor: not-allowed; }
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0 -15px; padding: 0 15px; }
    .empty-state { text-align: center; padding: 48px 20px; color: #64748b; }
    .linked-table a { color: #60a5fa; text-decoration: none; }
    .hint { color: #94a3b8; font-size: 0.9rem; line-height: 1.5; max-width: 900px; }
';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <?php wls_render_head('WLS ' . $t['title'], $extraStyles); ?>
</head>
<body>
    <?php wls_render_sidebar('linked_services', $lang); ?>
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-link"></i> <?= htmlspecialchars($t['title']) ?></h1>
        </div>
        <p class="hint" style="margin-bottom: 20px;"><?= htmlspecialchars($t['desc']) ?></p>
        <div class="wls-card">
            <div class="wls-card-header">
                <i class="fas fa-server"></i>
                <h2><?= htmlspecialchars($lang === 'tr' ? 'Takılı / eksik senkron' : 'Stuck or incomplete sync') ?></h2>
            </div>
            <div class="table-responsive">
                <table class="linked-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($t['service_id']) ?></th>
                            <th><?= htmlspecialchars($t['wls_sid']) ?></th>
                            <th><?= htmlspecialchars($t['wls_vid']) ?></th>
                            <th><?= htmlspecialchars($t['client']) ?></th>
                            <th><?= htmlspecialchars($t['domain']) ?></th>
                            <th><?= htmlspecialchars($t['whmcs_status']) ?></th>
                            <th><?= htmlspecialchars($t['vm_built']) ?></th>
                            <th><?= htmlspecialchars($t['vm_status']) ?></th>
                            <th><?= htmlspecialchars($t['row_status']) ?></th>
                            <th><?= htmlspecialchars($t['actions']) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                        <tr><td colspan="10" class="empty-state"><i class="fas fa-check-circle"></i><p><?= htmlspecialchars($t['empty']) ?></p></td></tr>
                        <?php else: foreach ($rows as $r):
                            $sid = (int) $r->service_id;
                            $uid = (int) $r->userid;
                            $serviceLink = $uid ? ($adminBase . '/clientsservices.php?userid=' . $uid . '&id=' . $sid) : '';
                        ?>
                        <tr data-sid="<?= $sid ?>">
                            <td><?php if ($serviceLink): ?><a href="<?= htmlspecialchars($serviceLink) ?>" target="_blank" rel="noopener"><?= $sid ?></a><?php else: ?><?= $sid ?><?php endif; ?></td>
                            <td><?= $r->wls_service_id !== null && $r->wls_service_id !== '' ? htmlspecialchars((string) $r->wls_service_id) : '—' ?></td>
                            <td><?= $r->wls_vm_id !== null && $r->wls_vm_id !== '' ? htmlspecialchars((string) $r->wls_vm_id) : '—' ?></td>
                            <td><?= htmlspecialchars(trim($r->client_name ?? '')) ?: '—' ?></td>
                            <td><?= htmlspecialchars($r->domain ?? '') ?></td>
                            <td><?= htmlspecialchars($r->domainstatus ?? '') ?></td>
                            <td><?= isset($r->vm_built) && (int) $r->vm_built ? '1' : '0' ?></td>
                            <td><?= htmlspecialchars((string) ($r->vm_status ?? '—')) ?></td>
                            <td><?= htmlspecialchars((string) ($r->wls_row_status ?? '—')) ?></td>
                            <td>
                                <button type="button" class="btn-sm js-wls-trigger" data-action="vm_check"><?= htmlspecialchars($t['btn_check']) ?></button>
                                <button type="button" class="btn-sm js-wls-trigger" data-action="sync_vm"><?= htmlspecialchars($t['btn_sync']) ?></button>
                                <span class="js-wls-msg" style="font-size:0.75rem;color:#94a3b8;display:block;margin-top:4px;"></span>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script>
    (function() {
        var ajaxUrl = <?= json_encode($wlsTriggerAjaxUrl !== '' ? $wlsTriggerAjaxUrl : 'ajax/trigger_stuck_service.php', JSON_UNESCAPED_SLASHES) ?>;
        document.querySelectorAll('tr[data-sid]').forEach(function(tr) {
            tr.querySelectorAll('.js-wls-trigger').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var sid = tr.getAttribute('data-sid');
                    var action = btn.getAttribute('data-action');
                    var msg = tr.querySelector('.js-wls-msg');
                    btn.disabled = true;
                    msg.textContent = <?= json_encode($t['working']) ?>;
                    fetch(ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                        body: 'service_id=' + encodeURIComponent(sid) + '&action=' + encodeURIComponent(action)
                    }).then(function(r) {
                        return r.text().then(function(text) {
                            var data = null;
                            try {
                                data = text ? JSON.parse(text) : null;
                            } catch (e) {
                                throw new Error('HTTP ' + r.status + ' — Yanıt JSON değil. ' + (text ? text.substring(0, 280) : '(boş)'));
                            }
                            if (!r.ok && (!data || !data.message)) {
                                throw new Error('HTTP ' + r.status + (data && data.message ? (': ' + data.message) : ''));
                            }
                            return data;
                        });
                    }).then(function(data) {
                        msg.textContent = (data && data.message) ? data.message : (data && data.success ? 'OK' : 'Bilinmeyen yanıt');
                        msg.style.color = (data && data.success) ? '#34d399' : '#f87171';
                    }).catch(function(err) {
                        msg.textContent = (err && err.message) ? err.message : 'Request failed';
                        msg.style.color = '#f87171';
                    }).finally(function() {
                        btn.disabled = false;
                    });
                });
            });
        });
    })();
    </script>
</body>
</html>
