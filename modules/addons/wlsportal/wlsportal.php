<?php
 











if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

function wlsportal_config()
{
    return array(
        'name' => 'WLS Panel',
        'description' => 'WhiteLabel Services yönetim paneline kısayol. Adres çubuğunda uzun modül yolu yerine Addon sayfası görünür.',
        'version' => '1.0',
        'author' => 'WhiteLabelServices',
        'language' => 'english',
        'fields' => array(),
    );
}

function wlsportal_activate()
{
    return array('status' => 'success', 'description' => 'WLS Panel kısayolu kullanıma hazır.');
}

function wlsportal_deactivate()
{
    return array('status' => 'success', 'description' => 'Devre dışı bırakıldı.');
}

function wlsportal_output($vars)
{
    if (empty($_SESSION['adminid'])) {
        echo '<p>Unauthorized.</p>';
        return;
    }

    $systemUrl = '';
    try {
        $systemUrl = (string) Capsule::table('tblconfiguration')
            ->where('setting', 'SystemURL')
            ->value('value');
    } catch (Exception $e) {
        $systemUrl = '';
    }
    $systemUrl = rtrim($systemUrl, '/');
    if ($systemUrl === '') {
        echo '<p>SystemURL could not be read. Check tblconfiguration.</p>';
        return;
    }

    $lang = isset($_GET['lang']) ? preg_replace('/[^a-z]/', '', strtolower((string) $_GET['lang'])) : '';
    if ($lang !== 'tr' && $lang !== 'en') {
        $lang = isset($_COOKIE['wls_lang']) ? preg_replace('/[^a-z]/', '', strtolower((string) $_COOKIE['wls_lang'])) : 'en';
    }
    if ($lang !== 'tr') {
        $lang = 'en';
    }

    $panelUrl = $systemUrl . '/modules/servers/WhiteLabelServices/admin/index.php?lang=' . rawurlencode($lang);

    echo '<div class="wlsportal-addon-wrap" style="max-width:100%;">';
    echo '<p style="margin:0 0 12px;color:#64748b;font-size:13px;">';
    echo '<a href="' . htmlspecialchars($panelUrl) . '" target="_blank" rel="noopener" class="btn btn-default btn-sm"><i class="fas fa-external-link-alt"></i> ';
    echo ($lang === 'tr' ? 'Tam ekran (yeni sekme)' : 'Open full screen (new tab)');
    echo '</a></p>';
  echo '</div></div>';
}
