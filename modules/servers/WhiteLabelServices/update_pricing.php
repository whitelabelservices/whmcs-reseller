<?php

// WHMCS'yi yükle
require_once('../../../init.php');
require_once('WhiteLabelServices.php');

// Admin yetkisi kontrolü
if (!isset($_SESSION['adminid'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'error' => 'Product ID required']);
        exit;
    }
    
    // Ürünün WLS ürünü olduğunu kontrol et
    $product = WHMCS\Database\Capsule::table('tblproducts')
        ->where('id', $productId)
        ->where('servertype', 'WhiteLabelServices')
        ->first();
        
    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'WLS product not found']);
        exit;
    }
    
    // Manuel fiyat güncelleme fonksiyonunu çağır
    $result = WhiteLabelServices_ManualPriceUpdate($productId);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true, 
            'message' => 'Pricing updated successfully',
            'count' => $result
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update pricing']);
    }
    
} catch (Exception $e) {
    logActivity("WLS Manual Price Update Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
} 
