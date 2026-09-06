<?php
 




require_once __DIR__ . '/includes/wls_bootstrap.php';

$whmcsRoot = wls_find_whmcs_root_dir(__DIR__);
if ($whmcsRoot === null || !is_file($whmcsRoot . DIRECTORY_SEPARATOR . 'init.php')) {
    die('WHMCS init.php bulunamadı');
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

 
$defaultMargin = 20;
try {
    if (Capsule::schema()->hasTable('mod_wls_settings')) {
        $marginSetting = Capsule::table('mod_wls_settings')->where('setting_key', 'default_margin')->first();
        if ($marginSetting) {
            $defaultMargin = floatval($marginSetting->setting_value);
        }
    }
} catch (Exception $e) {}


$t = [
    'pricing' => $lang === 'tr' ? 'Ürün Fiyatlandırma' : 'Product Pricing',
    'matched_products' => $lang === 'tr' ? 'Eşleşmiş Ürünler' : 'Matched Products',
    'unmatched_products' => $lang === 'tr' ? 'Eşleşmemiş Ürünler' : 'Unmatched Products',
    'product_name' => $lang === 'tr' ? 'Ürün Adı' : 'Product Name',
    'wls_product' => $lang === 'tr' ? 'WLS Ürünü' : 'WLS Product',
    'cost' => $lang === 'tr' ? 'Maliyet' : 'Cost',
    'current_price' => $lang === 'tr' ? 'Mevcut Fiyat' : 'Current Price',
    'new_price' => $lang === 'tr' ? 'Yeni Fiyat' : 'New Price',
    'location' => $lang === 'tr' ? 'Lokasyon' : 'Location',
    'margin' => $lang === 'tr' ? 'Kar Marjı' : 'Profit Margin',
    'calculate' => $lang === 'tr' ? 'Hesapla' : 'Calculate',
    'save' => $lang === 'tr' ? 'Kaydet' : 'Save',
    'no_products' => $lang === 'tr' ? 'Ürün bulunamadı' : 'No products found',
    'create_product' => $lang === 'tr' ? 'Ürün Oluştur' : 'Create Product',
    'creating' => $lang === 'tr' ? 'Oluşturuluyor...' : 'Creating...',
    'product_created' => $lang === 'tr' ? 'Ürün başarıyla oluşturuldu!' : 'Product created successfully!',
    'create_error' => $lang === 'tr' ? 'Ürün oluşturma hatası' : 'Error creating product',
    'confirm_save' => $lang === 'tr' ? 'Fiyatlar güncellenecek?' : 'Update prices?',
    'loading' => $lang === 'tr' ? 'Yükleniyor...' : 'Loading...',
];

$extraStyles = '
    .tab-buttons { display: flex; gap: 15px; margin-bottom: 25px; }
    .tab-btn {
        padding: 12px 25px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #94a3b8;
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .tab-btn:hover, .tab-btn.active {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(16, 185, 129, 0.2));
        border-color: rgba(34, 197, 94, 0.3);
        color: #4ade80;
    }
    .tab-btn .badge { background: rgba(239, 68, 68, 0.2); color: #f87171; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; }
    .tab-btn.active .badge { background: rgba(255,255,255,0.2); color: #fff; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    
    .pricing-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
    .margin-controls { display: flex; align-items: center; gap: 10px; }
    .margin-controls label { color: #94a3b8; }
    .margin-controls input {
        width: 80px; padding: 10px; background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #e2e8f0; text-align: center;
    }
    .margin-controls button {
        padding: 10px 20px; background: #3b82f6; border: none; border-radius: 8px; color: #fff; cursor: pointer;
    }
    
    .pricing-table { width: 100%; border-collapse: collapse; }
    .pricing-table th {
        background: rgba(15, 23, 42, 0.5); padding: 15px; text-align: left;
        font-weight: 500; font-size: 0.85rem; color: #64748b; text-transform: uppercase;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .pricing-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    .pricing-table tr:hover { background: rgba(255,255,255,0.02); }
    .product-name { font-weight: 600; color: #e2e8f0; }
    .product-location { font-size: 0.8rem; color: #64748b; margin-top: 3px; }
    
    .price-input {
        width: 100px; padding: 10px; background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
        color: #e2e8f0; text-align: right;
    }
    .price-input:focus { border-color: #22c55e; outline: none; }
    .price-input.updated { background: rgba(34, 197, 94, 0.2); border-color: rgba(34, 197, 94, 0.3); }
    
    .btn-create {
        padding: 8px 16px; background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border: none; border-radius: 8px; color: #fff; cursor: pointer; font-size: 0.85rem;
        transition: all 0.3s;
    }
    .btn-create:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3); }
    .btn-create:disabled { opacity: 0.6; cursor: not-allowed; }
    
    .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
    .empty-state i { font-size: 3rem; margin-bottom: 15px; color: #475569; }
    
     
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0 -15px;
        padding: 0 15px;
    }
    .table-responsive::-webkit-scrollbar { height: 6px; }
    .table-responsive::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 3px; }
    .table-responsive::-webkit-scrollbar-thumb { background: rgba(96, 165, 250, 0.3); border-radius: 3px; }
    
    @media (max-width: 768px) {
        .pricing-table { min-width: 600px; }
        .pricing-header { flex-direction: column; gap: 15px; }
        .margin-controls { flex-wrap: wrap; }
        .tab-buttons { flex-direction: column; }
        .tab-btn { justify-content: center; }
    }
';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['pricing'], $extraStyles); ?>
</head>
<body>
    <?php wls_render_sidebar('pricing', $lang); ?>
    
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-tags"></i> <?= $t['pricing'] ?></h1>
        </div>
        
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="switchTab('matched')" id="tabMatched">
                <i class="fas fa-link"></i> <?= $t['matched_products'] ?>
                <span class="badge" id="matchedCount">0</span>
            </button>
            <button class="tab-btn" onclick="switchTab('unmatched')" id="tabUnmatched">
                <i class="fas fa-unlink"></i> <?= $t['unmatched_products'] ?>
                <span class="badge" id="unmatchedCount">0</span>
            </button>
        </div>
        
         
        <div class="tab-content active" id="contentMatched">
            <div class="wls-card">
                <div class="pricing-header">
                    <div class="margin-controls">
                        <label><?= $t['margin'] ?> (%):</label>
                        <input type="number" id="margin" value="<?= $defaultMargin ?>">
                        <button onclick="applyMargin()"><i class="fas fa-calculator"></i> <?= $t['calculate'] ?></button>
                    </div>
                    <button class="wls-btn wls-btn-success" onclick="savePrices()">
                        <i class="fas fa-save"></i> <?= $t['save'] ?>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="pricing-table" id="matchedTable">
                        <thead>
                            <tr>
                                <th><?= $t['product_name'] ?></th>
                                <th><?= $t['wls_product'] ?></th>
                                <th><?= $t['cost'] ?></th>
                                <th><?= $t['current_price'] ?></th>
                                <th><?= $t['new_price'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> <?= $t['loading'] ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
         
        <div class="tab-content" id="contentUnmatched">
            <div class="wls-card">
                <div class="table-responsive">
                    <table class="pricing-table" id="unmatchedTable">
                        <thead>
                            <tr>
                                <th>WLS ID</th>
                                <th><?= $t['product_name'] ?></th>
                                <th><?= $t['cost'] ?></th>
                                <th><?= $t['location'] ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> <?= $t['loading'] ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<script>
var lang = <?= json_encode($t) ?>;

$(document).ready(function() {
    loadProducts();
});

function switchTab(tab) {
    $('.tab-btn').removeClass('active');
    $('.tab-content').removeClass('active');
    if(tab == 'matched') {
        $('#tabMatched').addClass('active');
        $('#contentMatched').addClass('active');
    } else {
        $('#tabUnmatched').addClass('active');
        $('#contentUnmatched').addClass('active');
    }
}

function loadProducts() {
    $.post("ajax/pricing.php", {action: "get_products"}, function(d) {
        if(d.status == "success") {
            renderMatched(d.matched || d.products || []);
            renderUnmatched(d.unmatched || []);
        }
    }, "json").fail(function() {
        $("#matchedTable tbody, #unmatchedTable tbody").html('<tr><td colspan="5" style="color:#f87171;">Error loading products</td></tr>');
    });
}

function renderMatched(products) {
    $("#matchedCount").text(products.length);
    if(products.length == 0) {
        $("#matchedTable tbody").html('<tr><td colspan="5" class="empty-state"><i class="fas fa-box-open"></i><p>' + lang.no_products + '</p></td></tr>');
        return;
    }
    var html = '';
    products.forEach(function(p) {
        html += '<tr data-id="' + p.whmcs_id + '" data-wls-id="' + p.wls_id + '" data-cost="' + p.wls_price + '">';
        html += '<td><div class="product-name">' + p.whmcs_name + '</div></td>';
        html += '<td>' + p.wls_name + ' <small style="color:#64748b;">(' + p.wls_currency + ')</small></td>';
        html += '<td><strong style="color:#4ade80;">' + parseFloat(p.wls_price).toFixed(2) + '</strong> ' + p.wls_currency + '</td>';
        html += '<td>' + parseFloat(p.current_price).toFixed(2) + '</td>';
        html += '<td><input type="number" class="price-input newp" value="' + parseFloat(p.current_price).toFixed(2) + '" step="0.01"></td>';
        html += '</tr>';
    });
    $("#matchedTable tbody").html(html);
}

function renderUnmatched(products) {
    $("#unmatchedCount").text(products.length);
    if(products.length == 0) {
        $("#unmatchedTable tbody").html('<tr><td colspan="5" class="empty-state"><i class="fas fa-check-circle" style="color:#4ade80;"></i><p>All products are matched!</p></td></tr>');
        return;
    }
    var html = '';
    products.forEach(function(p) {
        var desc = (p.wls_description || '').replace(/<[^>]*>/g, ' ').substring(0, 100);
        if(desc.length >= 100) desc += '...';
        html += '<tr>';
        html += '<td><strong style="color:#60a5fa;">#' + p.wls_id + '</strong></td>';
        html += '<td><div class="product-name">' + p.wls_name + '</div><div class="product-location">' + desc + '</div></td>';
        html += '<td><strong style="color:#4ade80;">' + parseFloat(p.wls_price).toFixed(2) + '</strong> ' + p.wls_currency + '</td>';
        html += '<td>' + (p.wls_location || '-') + '</td>';
        html += '<td><button class="btn-create" onclick="createProduct(' + p.wls_id + ', \'' + escapeQuotes(p.wls_name) + '\', ' + p.wls_price + ', \'' + p.wls_currency + '\', this)"><i class="fas fa-plus"></i> ' + lang.create_product + '</button></td>';
        html += '</tr>';
    });
    $("#unmatchedTable tbody").html(html);
}

function escapeQuotes(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function applyMargin() {
    var m = parseFloat($("#margin").val());
    $("#matchedTable tbody tr").each(function() {
        var c = parseFloat($(this).data("cost"));
        if(c > 0) {
            $(this).find(".newp").val((c * (1 + m / 100)).toFixed(2)).addClass("updated");
        }
    });
}

function savePrices() {
    if(!confirm(lang.confirm_save)) return;
    var margin = parseFloat($("#margin").val()) || 20;
    var updates = [];
    $("#matchedTable tbody tr").each(function() {
        var id = $(this).data("id");
        var wlsId = $(this).data("wls-id");
        var price = $(this).find(".newp").val();
        if(id && price) {
            updates.push({whmcs_id: id, wls_id: wlsId, new_price: price});
        }
    });
    $.post("ajax/pricing.php", {action: "update_price", items: updates, margin: margin}, function(d) {
        alert(d.message || "Completed!");
        loadProducts();
    }, "json");
}

function createProduct(wlsId, wlsName, wlsPrice, wlsCurrency, btn) {
    var margin = parseFloat($("#margin").val()) || 20;
    $(btn).prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> ' + lang.creating);
    $.post("ajax/pricing.php", {
        action: "create_product",
        wls_id: wlsId,
        name: wlsName,
        margin: margin
    }, function(d) {
        if(d.status == "success") {
            var msg = lang.product_created;
            if(d.config_options > 0) msg += " (" + d.config_options + " configurable options)";
            alert(msg);
            loadProducts();
        } else {
            alert(lang.create_error + ": " + (d.message || "Unknown error"));
            $(btn).prop("disabled", false).html('<i class="fas fa-plus"></i> ' + lang.create_product);
        }
    }, "json").fail(function() {
        alert(lang.create_error);
        $(btn).prop("disabled", false).html('<i class="fas fa-plus"></i> ' + lang.create_product);
    });
}
</script>

</body>
</html>
