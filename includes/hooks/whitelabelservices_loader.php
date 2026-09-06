<?php
/**
 * WHMCS Hook Loader - WhiteLabelServices
 *
 * Bu dosya WHMCS kurulumundaki includes/hooks/ klasöründe bulunmalıdır.
 * Depo yapısı WHMCS kök dizinine kopyalandığında doğru konuma yerleşir.
 *
 * Kopyalama:
 *   Hedef: {WHMCS_ROOT}/includes/hooks/whitelabelservices_loader.php
 *
 * Böylece TicketOpen, AfterCronJob, InvoicePaid, DailyCronJob vb. tüm modül hook'ları kayıt olur.
 * hooks.php -> hooks_register.php (tek kaynak; WhiteLabelServices.php içinde add_hook yok).
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$hooksFile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'servers' . DIRECTORY_SEPARATOR . 'WhiteLabelServices' . DIRECTORY_SEPARATOR . 'hooks.php';
if (file_exists($hooksFile)) {
    require_once $hooksFile;
}
