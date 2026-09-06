<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
require_once __DIR__ . '/lib/TokenManager.php';

// Tablo oluşturma fonksiyonu
function WhiteLabelServices_createTokensTable() {
    try {
        if (!Capsule::schema()->hasTable('mod_wls_tokens')) {
            Capsule::schema()->create('mod_wls_tokens', function ($table) {
                $table->increments('id');
                $table->integer('server_id')->unique();
                $table->text('access_token')->nullable();
                $table->text('refresh_token')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
        return true;
    } catch (\Exception $e) {
        logActivity("WLS Module: Error creating tokens table: " . $e->getMessage());
        return false;
    }
}

// Modül aktivasyon fonksiyonu
function WhiteLabelServices_activate() {
    try {
        WLSTokenManager::createTablesIfNotExists();
        return array(
            'status' => 'success',
            'description' => 'WLS Module has been activated successfully.'
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'description' => 'Could not create required database tables: ' . $e->getMessage()
        );
    }
}

// Modül deaktivasyon fonksiyonu
function WhiteLabelServices_deactivate() {
    try {
        if (Capsule::schema()->hasTable('mod_wls_tokens')) {
            Capsule::schema()->drop('mod_wls_tokens');
        }
        if (Capsule::schema()->hasTable('mod_wls_vps')) {
            Capsule::schema()->drop('mod_wls_vps');
        }
        return array(
            'status' => 'success',
            'description' => 'WLS Module has been deactivated successfully.'
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'description' => 'Could not remove database tables: ' . $e->getMessage()
        );
    }
}

// Modül yükseltme fonksiyonu
function WhiteLabelServices_upgrade($vars) {
    try {
        WLSTokenManager::createTablesIfNotExists();
        return array(
            'status' => 'success',
            'description' => 'WLS Module has been upgraded successfully.'
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'description' => 'Could not upgrade module: ' . $e->getMessage()
        );
    }
} 
