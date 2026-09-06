<?php
/**
 * WHMCS kök dizini: yalnızca init.php yolunu üretmek için.
 * Bu yol dosya sistemidir; asla HTTP Location ile tarayıcıya gönderilmez (path sızıntısını önler).
 */

function wls_find_whmcs_root_dir($startDir) {
    $d = @realpath($startDir);
    if ($d === false || $d === '') {
        $d = $startDir;
    }
    for ($i = 0; $i < 24; $i++) {
        if (is_file($d . DIRECTORY_SEPARATOR . 'init.php')) {
            return $d;
        }
        $parent = dirname($d);
        if ($parent === $d) {
            break;
        }
        $d = $parent;
    }
    return null;
}

/**
 * Misafir için güvenli mutlak URL (SystemURL). init.php yüklendikten sonra çağrılmalıdır.
 *
 * @param string $target client_home = müşteri alanı kökü; admin_login = yönetici girişi
 */
function wls_guest_redirect_url($target = 'client_home') {
    $path = ($target === 'admin_login') ? '/admin/index.php' : '/';
    try {
        if (class_exists('WHMCS\Database\Capsule')) {
            $base = \WHMCS\Database\Capsule::table('tblconfiguration')
                ->where('setting', 'SystemURL')
                ->value('value');
            $base = $base !== null ? rtrim((string) $base, '/') : '';
            if ($base !== '') {
                return $base . $path;
            }
        }
    } catch (Throwable $e) {
    }
    return $path;
}

/**
 * Admin oturumu yoksa yönlendir ve çık (HTML sayfalar için).
 */
function wls_redirect_if_not_admin_session($target = 'client_home') {
    if (!empty($_SESSION['adminid'])) {
        return;
    }
    header('Location: ' . wls_guest_redirect_url($target), true, 302);
    exit;
}
