<?php
/**
 * WhiteLabelServices Admin Settings
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

$lang = $_GET['lang'] ?? $_COOKIE['wls_lang'] ?? 'en';
if (isset($_GET['lang'])) {
    setcookie('wls_lang', $lang, time() + 86400 * 365, '/');
}

// Ayarları kaydet
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_settings') {
        $promoCode = trim($_POST['promo_code'] ?? '');
        $defaultMargin = floatval($_POST['default_margin'] ?? 20);
        $debugMode = isset($_POST['debug_mode']) ? 1 : 0;
        
        try {
            if (!Capsule::schema()->hasTable('mod_wls_settings')) {
                Capsule::schema()->create('mod_wls_settings', function ($table) {
                    $table->string('setting_key', 100)->primary();
                    $table->text('setting_value')->nullable();
                    $table->timestamp('updated_at')->nullable();
                });
            }
            
            Capsule::table('mod_wls_settings')->updateOrInsert(
                ['setting_key' => 'promo_code'],
                ['setting_value' => $promoCode, 'updated_at' => date('Y-m-d H:i:s')]
            );
            
            Capsule::table('mod_wls_settings')->updateOrInsert(
                ['setting_key' => 'default_margin'],
                ['setting_value' => $defaultMargin, 'updated_at' => date('Y-m-d H:i:s')]
            );
            
            Capsule::table('mod_wls_settings')->updateOrInsert(
                ['setting_key' => 'debug_mode'],
                ['setting_value' => $debugMode, 'updated_at' => date('Y-m-d H:i:s')]
            );
            
            $message = $lang === 'tr' ? 'Ayarlar başarıyla kaydedildi!' : 'Settings saved successfully!';
            $messageType = 'success';
            
        } catch (Exception $e) {
            $message = ($lang === 'tr' ? 'Hata: ' : 'Error: ') . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Mevcut ayarları al
$currentPromoCode = '';
$currentMargin = 20;
$currentDebugMode = 0;

try {
    if (Capsule::schema()->hasTable('mod_wls_settings')) {
        $promoSetting = Capsule::table('mod_wls_settings')->where('setting_key', 'promo_code')->first();
        $marginSetting = Capsule::table('mod_wls_settings')->where('setting_key', 'default_margin')->first();
        $debugSetting = Capsule::table('mod_wls_settings')->where('setting_key', 'debug_mode')->first();
        
        if ($promoSetting) $currentPromoCode = $promoSetting->setting_value;
        if ($marginSetting) $currentMargin = floatval($marginSetting->setting_value);
        if ($debugSetting) $currentDebugMode = intval($debugSetting->setting_value);
    }
} catch (Exception $e) {}

$t = [
    'settings' => $lang === 'tr' ? 'Ayarlar' : 'Settings',
    'promo_code' => $lang === 'tr' ? 'Promosyon Kodu' : 'Promotion Code',
    'promo_code_desc' => $lang === 'tr' ? 'Bu kod tüm siparişlerde WLS API\'sine gönderilecek' : 'This code will be sent with all orders to the WLS API',
    'default_margin' => $lang === 'tr' ? 'Varsayılan Kar Marjı (%)' : 'Default Profit Margin (%)',
    'default_margin_desc' => $lang === 'tr' ? 'Yeni ürün fiyatlandırması için varsayılan marj' : 'Default margin for new product pricing',
    'save' => $lang === 'tr' ? 'Ayarları Kaydet' : 'Save Settings',
    'order_settings' => $lang === 'tr' ? 'Sipariş Ayarları' : 'Order Settings',
    'general_settings' => $lang === 'tr' ? 'Genel Ayarlar' : 'General Settings',
    'debug_mode' => $lang === 'tr' ? 'Debug Modu' : 'Debug Mode',
    'debug_mode_desc' => $lang === 'tr' ? 'Etkinleştirildiğinde API istekleri ve yanıtları System Activity Log\'a kaydedilir' : 'When enabled, API requests and responses are logged to System Activity Log',
    'developer_settings' => $lang === 'tr' ? 'Geliştirici Ayarları' : 'Developer Settings',
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['settings']); ?>
</head>
<body>
    <?php wls_render_sidebar('settings', $lang); ?>
    
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-cog"></i> <?= $t['settings'] ?></h1>
        </div>
        
        <?php if ($message): ?>
        <div class="wls-alert wls-alert-<?= $messageType ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            
            <div class="wls-card">
                <div class="wls-card-header">
                    <i class="fas fa-shopping-cart"></i>
                    <h2><?= $t['order_settings'] ?></h2>
                </div>
                
                <div class="wls-form-group">
                    <label for="promo_code"><?= $t['promo_code'] ?></label>
                    <input type="text" id="promo_code" name="promo_code" 
                           value="<?= htmlspecialchars($currentPromoCode) ?>" 
                           placeholder="PROMO2024">
                    <div class="hint"><?= $t['promo_code_desc'] ?></div>
                </div>
            </div>
            
            <div class="wls-card">
                <div class="wls-card-header">
                    <i class="fas fa-sliders-h"></i>
                    <h2><?= $t['general_settings'] ?></h2>
                </div>
                
                <div class="wls-form-group">
                    <label for="default_margin"><?= $t['default_margin'] ?></label>
                    <input type="number" id="default_margin" name="default_margin" 
                           value="<?= $currentMargin ?>" 
                           min="0" max="100" step="0.1">
                    <div class="hint"><?= $t['default_margin_desc'] ?></div>
                </div>
            </div>

            <div class="wls-card">
                <div class="wls-card-header">
                    <i class="fas fa-bug"></i>
                    <h2><?= $t['developer_settings'] ?></h2>
                </div>
                
                <div class="wls-form-group">
                    <label for="debug_mode" style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" id="debug_mode" name="debug_mode" value="1" 
                               <?= $currentDebugMode ? 'checked' : '' ?>
                               style="width: 20px; height: 20px; cursor: pointer;">
                        <span><?= $t['debug_mode'] ?></span>
                    </label>
                    <div class="hint"><?= $t['debug_mode_desc'] ?></div>
                </div>
            </div>
            
            <button type="submit" class="wls-btn wls-btn-primary">
                <i class="fas fa-save"></i> <?= $t['save'] ?>
            </button>
        </form>
    </main>
</body>
</html>
