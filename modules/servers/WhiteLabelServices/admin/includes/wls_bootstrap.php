<?php
 




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

 


function wls_redirect_if_not_admin_session($target = 'client_home') {
    if (!empty($_SESSION['adminid'])) {
        return;
    }
    header('Location: ' . wls_guest_redirect_url($target), true, 302);
    exit;
}
