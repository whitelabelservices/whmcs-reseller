<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

class WLSTokenManager {
    // Debug mode cache
    private static $debugMode = null;
    
    /**
     * Debug Log Helper - respects debug mode setting
     * @param string $message Log message
     * @param bool $force Force log even if debug mode is off
     */
    private static function debugLog($message, $force = false) {
        // Cache debug mode setting
        if (self::$debugMode === null) {
            try {
                $setting = Capsule::table('mod_wls_settings')
                    ->where('setting_key', 'debug_mode')
                    ->first();
                self::$debugMode = $setting && intval($setting->setting_value) === 1;
            } catch (Exception $e) {
                self::$debugMode = false;
            }
        }
        
        // Only log if debug mode is enabled or force is true
        if (self::$debugMode || $force) {
            logActivity("WLS: " . $message);
        }
    }
    
    // API base URL'ini sunucu yapÄ±landÄ±rmasÄ±ndan al
    public static function getApiBaseUrl() {
        try {
            $server = Capsule::table('tblservers')
                ->where('type', 'WhiteLabelServices')
                ->where('active', '1')
                ->first();
            
            if ($server && !empty($server->hostname)) {
                return 'https://' . $server->hostname;
            }
        } catch (Exception $e) {
            self::debugLog("getApiBaseUrl Error: " . $e->getMessage());
        }
        
        return 'https://api.example.com'; // Fallback - sunucu hostname'i kullanÄ±lmasÄ± Ã¶nerilir
    }
    
    // Public wrapper for createTablesIfNotExists
    public static function ensureTablesExist() {
        self::createTablesIfNotExists();
    }
    
    private static function createTablesIfNotExists() {
        self::createTokensTableIfNotExists();
        self::createVPSTableIfNotExists();
        self::createQueueDataTableIfNotExists();
        self::createUpgradeTasksTableIfNotExists();
        self::createUpdateTasksTableIfNotExists();
        self::createTicketTasksTableIfNotExists();
        self::createCancelTasksTableIfNotExists();
        
        // Mevcut tabloya service_status kolonunu ekle
        if (!Capsule::schema()->hasColumn('mod_wls_vps', 'service_status')) {
            try {
                Capsule::schema()->table('mod_wls_vps', function ($table) {
                    $table->string('service_status', 50)->nullable()->index()->after('vm_powered');
                });
                self::debugLog("VPS - service_status kolonu eklendi");
            } catch (Exception $e) {
                self::debugLog("VPS - service_status kolonu eklenirken hata: " . $e->getMessage());
            }
        }
    }
    
    private static function createTokensTableIfNotExists() {
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
    }
    
    private static function createQueueDataTableIfNotExists() {
        if (!Capsule::schema()->hasTable('mod_wls_queue_data')) {
            Capsule::schema()->create('mod_wls_queue_data', function ($table) {
                $table->increments('id');
                $table->integer('queue_id')->unsigned()->index(); // tblmodulequeue.id referansÄ±
                $table->text('data'); // JSON formatÄ±nda queue data
                $table->string('lock_token', 64)->nullable()->index(); // Race condition iÃ§in lock token
                $table->timestamp('locked_at')->nullable(); // Lock alÄ±ndÄ±ÄŸÄ± zaman
                $table->timestamp('created_at')->nullable();
            });
        }
        
        // Cron lock tablosu (duplicate cron Ã§alÄ±ÅŸmasÄ±nÄ± Ã¶nlemek iÃ§in)
        if (!Capsule::schema()->hasTable('mod_wls_cron_lock')) {
            Capsule::schema()->create('mod_wls_cron_lock', function ($table) {
                $table->string('lock_name', 50)->primary();
                $table->string('lock_token', 64);
                $table->timestamp('locked_at');
                $table->timestamp('expires_at');
            });
        }
    }

    private static function createUpgradeTasksTableIfNotExists() {
        if (!Capsule::schema()->hasTable('mod_wls_upgrade_tasks')) {
            Capsule::schema()->create('mod_wls_upgrade_tasks', function ($table) {
                $table->increments('id');
                $table->integer('service_id')->unsigned()->index();
                $table->integer('order_id')->unsigned()->index();
                $table->string('type', 20)->default('upgrade'); // upgrade or addon
                $table->text('data'); // JSON format upgrade data
                $table->string('status', 20)->default('Pending')->index(); // Pending, Paid, Completed, Failed
                $table->text('last_error')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    /** Invoice paid -> external (HostBill) update task table */
    private static function createUpdateTasksTableIfNotExists() {
        if (!Capsule::schema()->hasTable('mod_wls_update_tasks')) {
            Capsule::schema()->create('mod_wls_update_tasks', function ($table) {
                $table->increments('id');
                $table->integer('invoice_id')->unsigned()->index();
                $table->integer('service_id')->unsigned()->nullable()->index();
                $table->string('status', 20)->default('paid')->index(); // paid, completed, failed
                $table->text('payload')->nullable(); // JSON
                $table->timestamp('last_attempt')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    /** Portal ticket sync: WHMCS ticket -> API'ye cron ile gonderilir */
    private static function createTicketTasksTableIfNotExists() {
        if (!Capsule::schema()->hasTable('mod_wls_ticket_tasks')) {
            Capsule::schema()->create('mod_wls_ticket_tasks', function ($table) {
                $table->increments('id');
                $table->integer('whmcs_ticket_id')->unsigned()->index();
                $table->integer('hosting_id')->unsigned()->index();
                $table->string('subject', 500)->nullable();
                $table->text('message')->nullable();
                $table->string('status', 20)->default('Pending')->index(); // Pending, Completed, Failed
                $table->string('portal_ticket_id', 100)->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('processed_at')->nullable();
            });
        }
    }

    public static function addTicketTask($whmcsTicketId, $hostingId, $subject, $message) {
        try {
            self::createTablesIfNotExists();
            Capsule::table('mod_wls_ticket_tasks')->insert([
                'whmcs_ticket_id' => (int) $whmcsTicketId,
                'hosting_id' => (int) $hostingId,
                'subject' => $subject,
                'message' => $message,
                'status' => 'Pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (Exception $e) {
            self::debugLog("AddTicketTask Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getPendingTicketTasks($limit = 50) {
        try {
            self::createTablesIfNotExists();
            return Capsule::table('mod_wls_ticket_tasks')
                ->where('status', 'Pending')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            self::debugLog("GetPendingTicketTasks Error: " . $e->getMessage());
            return [];
        }
    }

    public static function setTicketTaskStatus($id, $status, $portalTicketId = null, $errorMessage = null) {
        try {
            $data = [
                'status' => $status,
                'processed_at' => date('Y-m-d H:i:s'),
            ];
            if ($portalTicketId !== null) {
                $data['portal_ticket_id'] = $portalTicketId;
            }
            if ($errorMessage !== null) {
                $data['error_message'] = $errorMessage;
            }
            return Capsule::table('mod_wls_ticket_tasks')->where('id', $id)->update($data);
        } catch (Exception $e) {
            self::debugLog("SetTicketTaskStatus Error: " . $e->getMessage());
            return false;
        }
    }

    /** Iptal talebi: WHMCS iptal talebi -> portal API'ye cron ile gonderilir */
    private static function createCancelTasksTableIfNotExists() {
        if (!Capsule::schema()->hasTable('mod_wls_cancel_tasks')) {
            Capsule::schema()->create('mod_wls_cancel_tasks', function ($table) {
                $table->increments('id');
                $table->integer('service_id')->unsigned()->index();
                $table->integer('wls_service_id')->unsigned()->index();
                $table->tinyInteger('immediate')->default(0);
                $table->text('reason')->nullable();
                $table->string('status', 20)->default('Pending')->index();
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('processed_at')->nullable();
            });
        }
    }

    public static function addCancelTask($serviceId, $wlsServiceId, $immediate, $reason) {
        try {
            self::createTablesIfNotExists();
            Capsule::table('mod_wls_cancel_tasks')->insert([
                'service_id' => (int) $serviceId,
                'wls_service_id' => (int) $wlsServiceId,
                'immediate' => $immediate ? 1 : 0,
                'reason' => $reason,
                'status' => 'Pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (Exception $e) {
            self::debugLog("AddCancelTask Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getPendingCancelTasks($limit = 50) {
        try {
            self::createTablesIfNotExists();
            return Capsule::table('mod_wls_cancel_tasks')
                ->where('status', 'Pending')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            self::debugLog("GetPendingCancelTasks Error: " . $e->getMessage());
            return [];
        }
    }

    public static function setCancelTaskStatus($id, $status, $errorMessage = null) {
        try {
            $data = [
                'status' => $status,
                'processed_at' => date('Y-m-d H:i:s'),
            ];
            if ($errorMessage !== null) {
                $data['error_message'] = $errorMessage;
            }
            return Capsule::table('mod_wls_cancel_tasks')->where('id', $id)->update($data);
        } catch (Exception $e) {
            self::debugLog("SetCancelTaskStatus Error: " . $e->getMessage());
            return false;
        }
    }

    public static function addUpdateTask($invoiceId, $serviceId = null, $payload = []) {
        try {
            self::createTablesIfNotExists();
            Capsule::table('mod_wls_update_tasks')->insert([
                'invoice_id' => $invoiceId,
                'service_id' => $serviceId,
                'status' => 'paid',
                'payload' => json_encode($payload),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (Exception $e) {
            self::debugLog("AddUpdateTask Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getPendingUpdateTasks($limit = 50) {
        try {
            self::createTablesIfNotExists();
            return Capsule::table('mod_wls_update_tasks')
                ->where('status', 'paid')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            self::debugLog("GetPendingUpdateTasks Error: " . $e->getMessage());
            return [];
        }
    }

    public static function setUpdateTaskStatus($id, $status, $lastError = null) {
        try {
            $data = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
                'last_attempt' => date('Y-m-d H:i:s'),
            ];
            if ($lastError !== null) {
                $data['last_error'] = $lastError;
            }
            return Capsule::table('mod_wls_update_tasks')->where('id', $id)->update($data);
        } catch (Exception $e) {
            self::debugLog("SetUpdateTaskStatus Error: " . $e->getMessage());
            return false;
        }
    }

    /** Upgrade task (ChangePackage) - add / get pending / set status */
    public static function addUpgradeTask($serviceId, $orderId, $type, $data) {
        try {
            self::createTablesIfNotExists();
            $now = date('Y-m-d H:i:s');
            Capsule::table('mod_wls_upgrade_tasks')->insert([
                'service_id' => (int) $serviceId,
                'order_id' => (int) $orderId,
                'type' => $type ?: 'upgrade',
                'data' => is_string($data) ? $data : json_encode($data ?: []),
                'status' => 'Pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return true;
        } catch (Exception $e) {
            self::debugLog("AddUpgradeTask Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getPendingUpgradeTasks($limit = 50) {
        try {
            self::createTablesIfNotExists();
            return Capsule::table('mod_wls_upgrade_tasks')
                ->where('status', 'Pending')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            self::debugLog("GetPendingUpgradeTasks Error: " . $e->getMessage());
            return [];
        }
    }

    public static function setUpgradeTaskStatus($id, $status, $lastError = null) {
        try {
            $data = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($lastError !== null) {
                $data['last_error'] = $lastError;
            }
            return Capsule::table('mod_wls_upgrade_tasks')->where('id', $id)->update($data);
        } catch (Exception $e) {
            self::debugLog("SetUpgradeTaskStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Queue data ekleme
    public static function addQueueData($queueId, $data) {
        try {
            self::createTablesIfNotExists();
            
            return Capsule::table('mod_wls_queue_data')->insertGetId([
                'queue_id' => $queueId,
                'data' => json_encode($data),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            self::debugLog("Queue Data Add Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Queue data alma (lock ile)
    public static function getQueueData($queueId, $lockToken = null) {
        try {
            self::createTablesIfNotExists();
            
            $record = Capsule::table('mod_wls_queue_data')
                ->where('queue_id', $queueId)
                ->first();
                
            if (!$record) {
                return null;
            }
            
            // Lock token kontrolÃ¼
            if ($lockToken && $record->lock_token && $record->lock_token !== $lockToken) {
                // BaÅŸka bir process tarafÄ±ndan kilitlenmiÅŸ
                return null;
            }
            
            return json_decode($record->data, true);
        } catch (Exception $e) {
            self::debugLog("Queue Data Get Error: " . $e->getMessage());
            return null;
        }
    }
    
    // Queue data iÃ§in lock al
    public static function lockQueueData($queueId) {
        try {
            self::createTablesIfNotExists();
            
            $lockToken = bin2hex(random_bytes(32));
            
            // Atomik lock: sadece lock_token null ise gÃ¼ncelle
            $updated = Capsule::table('mod_wls_queue_data')
                ->where('queue_id', $queueId)
                ->whereNull('lock_token')
                ->update([
                    'lock_token' => $lockToken,
                    'locked_at' => date('Y-m-d H:i:s')
                ]);
            
            if ($updated > 0) {
                return $lockToken;
            }
            
            // Zaten kilitli ama 5 dakikadan eski lock'larÄ± temizle
            $staleTime = date('Y-m-d H:i:s', strtotime('-5 minutes'));
            $updated = Capsule::table('mod_wls_queue_data')
                ->where('queue_id', $queueId)
                ->where('locked_at', '<', $staleTime)
                ->update([
                    'lock_token' => $lockToken,
                    'locked_at' => date('Y-m-d H:i:s')
                ]);
            
            return $updated > 0 ? $lockToken : false;
        } catch (Exception $e) {
            self::debugLog("Queue Lock Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Queue data sil
    public static function deleteQueueData($queueId) {
        try {
            self::createTablesIfNotExists();
            
            return Capsule::table('mod_wls_queue_data')
                ->where('queue_id', $queueId)
                ->delete();
        } catch (Exception $e) {
            self::debugLog("Queue Data Delete Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Cron lock al (duplicate Ã§alÄ±ÅŸmayÄ± Ã¶nlemek iÃ§in)
    public static function acquireCronLock($lockName = 'wls_queue_process', $duration = 60) {
        try {
            self::createTablesIfNotExists();
            
            $lockToken = bin2hex(random_bytes(16));
            $now = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', time() + $duration);
            
            // Ã–nce sÃ¼resi dolmuÅŸ lock'larÄ± temizle
            Capsule::table('mod_wls_cron_lock')
                ->where('expires_at', '<', $now)
                ->delete();
            
            // Lock almayÄ± dene
            try {
                Capsule::table('mod_wls_cron_lock')->insert([
                    'lock_name' => $lockName,
                    'lock_token' => $lockToken,
                    'locked_at' => $now,
                    'expires_at' => $expiresAt
                ]);
                return $lockToken;
            } catch (Exception $e) {
                // Lock zaten var
                return false;
            }
        } catch (Exception $e) {
            self::debugLog("Cron Lock Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Cron lock serbest bÄ±rak
    public static function releaseCronLock($lockName = 'wls_queue_process', $lockToken = null) {
        try {
            $query = Capsule::table('mod_wls_cron_lock')
                ->where('lock_name', $lockName);
            
            if ($lockToken) {
                $query->where('lock_token', $lockToken);
            }
            
            return $query->delete();
        } catch (Exception $e) {
            self::debugLog("Cron Lock Release Error: " . $e->getMessage());
            return false;
        }
    }
    
    
    private static function createVPSTableIfNotExists() {
        if (!Capsule::schema()->hasTable('mod_wls_vps')) {
            Capsule::schema()->create('mod_wls_vps', function ($table) {
                // WHMCS service ID'yi primary key yap
                $table->integer('id')->unsigned()->primary();
                
                // WLS API bilgileri
                $table->integer('wls_service_id')->nullable()->index();
                $table->integer('wls_vm_id')->nullable()->index();
                
                // Servis durumu
                $table->string('status', 50)->nullable()->index();
                $table->string('vm_status', 50)->nullable()->index();
                $table->boolean('vm_built')->default(false);
                $table->boolean('vm_powered')->default(false);
                $table->string('service_status', 50)->nullable()->index();
                
                // IP bilgileri
                $table->string('ipv4', 45)->nullable();
                $table->text('all_ips')->nullable(); // JSON array
                
                // EriÅŸim bilgileri
                $table->string('username', 100)->nullable();
                $table->text('password')->nullable();
                
                // Kaynak bilgileri
                $table->integer('memory')->nullable();
                $table->integer('disk')->nullable();
                $table->integer('cores')->nullable();
                $table->string('template', 100)->nullable();
                $table->string('mac_address', 50)->nullable();
                
                // VM detay bilgileri (JSON)
                $table->text('vm_interfaces')->nullable();
                $table->text('vm_storage')->nullable();
                $table->text('vm_resources')->nullable();
                $table->text('vm_bandwidth')->nullable();
                
                // Fatura/SipariÅŸ bilgileri
                $table->string('order_number', 50)->nullable()->index();
                $table->string('invoice_id', 50)->nullable();
                
                // Zaman damgalarÄ±
                $table->timestamp('vm_created_at')->nullable();
                $table->timestamp('last_sync')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        // Yeni kolonlarÄ± kontrol et ve ekle
        $columns = [
            'vm_interfaces' => 'text',
            'vm_storage' => 'text',
            'vm_resources' => 'text',
            'vm_bandwidth' => 'text',
            'service_status' => 'string',
            'ipv6' => 'string'
        ];

        foreach ($columns as $column => $type) {
            if (!Capsule::schema()->hasColumn('mod_wls_vps', $column)) {
                try {
                    Capsule::schema()->table('mod_wls_vps', function ($table) use ($column, $type) {
                        if ($type === 'text') {
                            $table->text($column)->nullable();
                        } else {
                            $table->string($column, 50)->nullable()->index();
                        }
                    });
                    self::debugLog("VPS - $column kolonu eklendi");
                } catch (Exception $e) {
                    self::debugLog("VPS - $column kolonu eklenirken hata: " . $e->getMessage());
                }
            }
        }
    }
    
    public static function getStoredToken($serverId) {
        self::createTablesIfNotExists();
        
        return Capsule::table('mod_wls_tokens')
            ->where('server_id', $serverId)
            ->first();
    }
    
    public static function updateTokens($serverId, $accessToken, $refreshToken) {
        self::createTablesIfNotExists();
        
        return Capsule::table('mod_wls_tokens')->updateOrInsert(
            ['server_id' => $serverId],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'updated_at' => date('Y-m-d H:i:s'),
                'created_at' => Capsule::raw('IF(created_at IS NULL, NOW(), created_at)')
            ]
        );
    }

    /**
     * Cached token invalid (e.g. API returned unauthorized); force next getToken to login or refresh.
     */
    public static function clearToken($serverId) {
        self::createTablesIfNotExists();
        try {
            return Capsule::table('mod_wls_tokens')->where('server_id', (int) $serverId)->delete();
        } catch (Exception $e) {
            self::debugLog('clearToken Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function isTokenExpired($token) {
        if (empty($token)) return true;
        
        $tokenParts = explode('.', $token);
        if (count($tokenParts) != 3) return true;
        
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        if (!$payload || !isset($payload['exp'])) return true;
        
        return $payload['exp'] < time();
    }
    
    public static function refreshToken($refreshToken) {
        try {
            $apiBaseUrl = self::getApiBaseUrl();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . '/api/token');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['refresh_token' => $refreshToken]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result['token']) && isset($result['refresh'])) {
                    return $result;
                }
            }
            
            return false;
        } catch (Exception $e) {
            self::debugLog("Token Refresh Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate portal client login (WHMCS server username/password) against POST /api/login.
     * Does not require serverid — used by Test Connection before save.
     *
     * @return array{success:bool,error?:string,token?:string}
     */
    public static function validateClientLogin(array $params, $apiBaseUrlOverride = null) {
        if (!isset($params['serverusername']) || trim($params['serverusername']) === ''
            || !isset($params['serverpassword']) || $params['serverpassword'] === '') {
            return ['success' => false, 'error' => 'API username and password are required'];
        }

        $apiBaseUrl = ($apiBaseUrlOverride !== null && $apiBaseUrlOverride !== '')
            ? rtrim($apiBaseUrlOverride, '/')
            : rtrim(self::getApiBaseUrl(), '/');

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . '/api/login');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'username' => trim($params['serverusername']),
                'password' => $params['serverpassword'],
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (curl_errno($ch)) {
                $err = curl_error($ch);
                curl_close($ch);
                self::debugLog('validateClientLogin cURL: ' . $err);
                return ['success' => false, 'error' => 'Could not reach API: ' . $err];
            }
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (is_array($result) && !empty($result['token'])) {
                    return ['success' => true, 'token' => $result['token']];
                }
                return ['success' => false, 'error' => 'Invalid API login response'];
            }

            if ($httpCode === 401 || $httpCode === 403) {
                return ['success' => false, 'error' => 'Authentication failed — invalid username or password'];
            }

            $bodySnippet = substr((string) $response, 0, 200);
            self::debugLog('validateClientLogin failed HTTP ' . $httpCode . ' base=' . $apiBaseUrl . ' body=' . $bodySnippet);
            return ['success' => false, 'error' => 'API login failed (HTTP ' . $httpCode . ')'];
        } catch (Exception $e) {
            self::debugLog('validateClientLogin: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Password login. $apiBaseUrlOverride must be the same origin as API calls (scheme + host [+ port]),
     * e.g. https://portal.dchost.com — otherwise token is valid for wrong host and requests return unauthorized.
     */
    public static function loginWithPassword($params, $apiBaseUrlOverride = null) {
        self::createTablesIfNotExists();
        if (empty($params['serverid']) || !isset($params['serverusername']) || trim($params['serverusername']) === ''
            || !isset($params['serverpassword']) || $params['serverpassword'] === '') {
            self::debugLog('loginWithPassword: missing serverid/username/password');
            return false;
        }
        $apiBaseUrl = ($apiBaseUrlOverride !== null && $apiBaseUrlOverride !== '')
            ? rtrim($apiBaseUrlOverride, '/')
            : rtrim(self::getApiBaseUrl(), '/');
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . '/api/login');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'username' => trim($params['serverusername']),
                'password' => trim($params['serverpassword']),
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (curl_errno($ch)) {
                $err = curl_error($ch);
                curl_close($ch);
                self::debugLog('loginWithPassword cURL: ' . $err);
                return false;
            }
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result['token']) && isset($result['refresh'])) {
                    self::updateTokens($params['serverid'], $result['token'], $result['refresh']);
                    return $result['token'];
                }
            }
            self::debugLog('loginWithPassword failed HTTP ' . $httpCode . ' base=' . $apiBaseUrl . ' body=' . substr((string) $response, 0, 200));
            return false;
        } catch (Exception $e) {
            self::debugLog('loginWithPassword: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function getToken($params, $forceRefresh = false) {
        try {
            // Force refresh istenmiyorsa mevcut token'Ä± kontrol et
            if (!$forceRefresh) {
                $storedToken = self::getStoredToken($params['serverid']);
                
                if ($storedToken && !empty($storedToken->access_token)) {
                    // Token'Ä±n geÃ§erliliÄŸini kontrol et
                    if (!self::isTokenExpired($storedToken->access_token)) {
                        return $storedToken->access_token;
                    }
                    
                    // Token sÃ¼resi dolmuÅŸsa refresh token ile yenile
                    if (!empty($storedToken->refresh_token)) {
                        $newTokens = self::refreshToken($storedToken->refresh_token);
                        if ($newTokens && isset($newTokens['token']) && isset($newTokens['refresh'])) {
                            self::updateTokens($params['serverid'], $newTokens['token'], $newTokens['refresh']);
                            return $newTokens['token'];
                        }
                    }
                }
            }
            
            $newToken = self::loginWithPassword($params);
            if ($newToken) {
                return $newToken;
            }
            throw new Exception('Failed to obtain access token');
            
        } catch (Exception $e) {
            self::debugLog("Token Error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getProducts($params) {
        try {
            // Ã–nce tablolarÄ±n varlÄ±ÄŸÄ±nÄ± kontrol et ve oluÅŸtur
            self::createTablesIfNotExists();
            
            // TablolarÄ±n varlÄ±ÄŸÄ±nÄ± tekrar kontrol et
            if (!Capsule::schema()->hasTable('mod_wls_tokens') || !Capsule::schema()->hasTable('mod_wls_vps')) {
                throw new Exception('ModÃ¼l tablolarÄ± oluÅŸturulamadÄ±. LÃ¼tfen modÃ¼lÃ¼ yeniden yÃ¼kleyin.');
            }

            $token = self::getToken($params);
            if (!$token) {
                throw new Exception('API token alÄ±namadÄ±');
            }

            $apiBaseUrl = self::getApiBaseUrl();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/category/8/product");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception("API hatasÄ±: " . curl_error($ch));
            }
            
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("API yanÄ±t kodu: " . $httpCode);
            }

            $data = json_decode($response, true);
            if (!$data || !isset($data['products'])) {
                throw new Exception("GeÃ§ersiz API yanÄ±tÄ±");
            }

            // Tablo durumunu logla
            self::debugLog("Module Check - Tables exist: mod_wls_tokens and mod_wls_vps");

            $options = [];
            foreach ($data['products'] as $product) {
                // ÃœrÃ¼n adÄ± ve ID'sini doÄŸru formatta birleÅŸtir
                $description = strip_tags(str_replace('<br>', ' | ', $product['description']));
                $options[] = $product['name'] . " (" . $product['id'] . ") - " . $description;
            }

            return implode(',', $options);
            
        } catch (Exception $e) {
            // Hata durumunda daha detaylÄ± log
            $errorMsg = $e->getMessage();
            $tableStatus = [];
            
            try {
                $tableStatus['tokens'] = Capsule::schema()->hasTable('mod_wls_tokens') ? 'exists' : 'missing';
                $tableStatus['vps'] = Capsule::schema()->hasTable('mod_wls_vps') ? 'exists' : 'missing';
                
                self::debugLog("Module Error - Table Status: " . json_encode($tableStatus));
            } catch (Exception $tableError) {
                self::debugLog("Module Error - Could not check table status: " . $tableError->getMessage());
            }
            
            self::debugLog("Products Error: " . $errorMsg);
            return "Hata: " . $errorMsg . " (Tablo durumu: " . json_encode($tableStatus) . ")";
        }
    }
    
    public static function checkServiceStatus($serviceId, $token) {
        try {
            $apiBaseUrl = self::getApiBaseUrl();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/service/" . $serviceId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                return $result;
            }
            
            throw new Exception("API HatasÄ±: HTTP " . $httpCode);
            
        } catch (Exception $e) {
            self::debugLog("Servis Durum KontrolÃ¼ HatasÄ±: " . $e->getMessage());
            return null;
        }
    }
    
    public static function getVMList($serviceId, $token) {
        try {
            if (!$serviceId) {
                throw new Exception("WLS Service ID bulunamadÄ±");
            }

            $apiBaseUrl = self::getApiBaseUrl();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/service/" . $serviceId . "/vms");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode === 200) {
                return json_decode($response, true);
            }
            
            throw new Exception("API HatasÄ±: HTTP " . $httpCode);
            
        } catch (Exception $e) {
            self::debugLog("VM Listesi Alma HatasÄ±: " . $e->getMessage());
            return null;
        }
    }
    
    public static function getVMDetails($whmcsServiceId, $vmId, $token) {
        try {
            // VPS detaylarÄ±nÄ± al
            $vpsDetails = self::getVPSDetails($whmcsServiceId);
            
            // Debug log ekle
            self::debugLog("Debug - VPS Details for service " . $whmcsServiceId . ": " . json_encode($vpsDetails));
            
            // wls_service_id kontrolÃ¼
            $serviceId = $vpsDetails ? $vpsDetails->wls_service_id : null;
            if (!$serviceId) {
                if ($vpsDetails) {
                    throw new Exception("WLS Service ID bulunamadÄ±. VPS Details: " . json_encode($vpsDetails));
                } else {
                    throw new Exception("VPS kaydÄ± bulunamadÄ± (WHMCS Service ID: " . $whmcsServiceId . ")");
                }
            }

            $apiBaseUrl = self::getApiBaseUrl();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . "/api/service/" . $serviceId . "/vms/" . $vmId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception("CURL Error: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                
                if (!$result || !isset($result['vm'])) {
                    if (is_array($result) && function_exists('WhiteLabelServices_ApiPayloadIndicatesVmGone') && WhiteLabelServices_ApiPayloadIndicatesVmGone($result)
                        && function_exists('WhiteLabelServices_ReconcileWlsServiceFromPortal')) {
                        WhiteLabelServices_ReconcileWlsServiceFromPortal($whmcsServiceId, $serviceId, $token, null);
                    }
                    throw new Exception("GeÃ§ersiz API yanÄ±tÄ±: " . $response);
                }

                $vmData = $result['vm'];
                
                // Ã–nceki bandwidth deÄŸerlerini kontrol et
                $previousBandwidth = null;
                if ($vpsDetails && $vpsDetails->vm_bandwidth) {
                    $previousBandwidth = json_decode($vpsDetails->vm_bandwidth, true);
                }

                // Bandwidth deÄŸiÅŸimini hesapla ve logla
                if ($previousBandwidth && isset($vmData['bandwidth'])) {
                    $receivedDiff = $vmData['bandwidth']['data_received'] - $previousBandwidth['data_received'];
                    $sentDiff = $vmData['bandwidth']['data_sent'] - $previousBandwidth['data_sent'];
                    
                    if ($receivedDiff > 0 || $sentDiff > 0) {
                        self::debugLog("VM Bandwidth Change - Service: " . $whmcsServiceId . 
                                  " - Downloaded: " . self::formatBytes($receivedDiff) . 
                                  " - Uploaded: " . self::formatBytes($sentDiff));
                    }
                }

                // Temel VM bilgilerini gÃ¼ncelle
                $updateData = [
                    'wls_service_id' => $serviceId,
                    'wls_vm_id' => $vmId,
                    'vm_status' => $vmData['status'] ?? null,
                    'vm_built' => $vmData['built'] ?? false,
                    'vm_powered' => $vmData['power'] ?? false,
                    'ipv4' => $vmData['ipv4'] ?? null,
                    'username' => $vmData['username'] ?? null,
                    'password' => $vmData['password'] ?? null,
                    'memory' => $vmData['memory'] ?? null,
                    'disk' => $vmData['disk'] ?? null,
                    'cores' => $vmData['cores'] ?? null,
                    'template' => $vmData['template_name'] ?? null,
                    'mac_address' => $vmData['mac'] ?? null,
                    'last_sync' => date('Y-m-d H:i:s')
                ];

                // Network arayÃ¼zlerini iÅŸle
                if (isset($vmData['interfaces'])) {
                    $updateData['vm_interfaces'] = json_encode($vmData['interfaces']);
                }

                // Depolama bilgilerini iÅŸle
                if (isset($vmData['storage'])) {
                    $updateData['vm_storage'] = json_encode($vmData['storage']);
                }

                // Bandwidth bilgilerini iÅŸle
                if (isset($vmData['bandwidth'])) {
                    $updateData['vm_bandwidth'] = json_encode($vmData['bandwidth']);
                }

                // Kaynak kullanÄ±m bilgilerini iÅŸle
                $resources = [
                    'memory' => $vmData['memory'] ?? null,
                    'disk' => $vmData['disk'] ?? null,
                    'cores' => $vmData['cores'] ?? null,
                    'sockets' => $vmData['sockets'] ?? null,
                    'cpus' => $vmData['cpus'] ?? null,
                    'uptime' => $vmData['uptime'] ?? null,
                    'last_check' => time()
                ];
                $updateData['vm_resources'] = json_encode($resources);

                // IP listesini iÅŸle
                if (isset($vmData['ip']) && is_array($vmData['ip'])) {
                    $allIps = array_map(function($ipId, $ipInfo) {
                        return [
                            'id' => $ipId,
                            'ip' => $ipInfo['ipaddress'],
                            'main' => $ipInfo['main']
                        ];
                    }, array_keys($vmData['ip']), $vmData['ip']);
                    
                    $updateData['all_ips'] = json_encode($allIps);
                }

                // VM oluÅŸturma tarihini ayarla
                if (!$vpsDetails->vm_created_at) {
                    $updateData['vm_created_at'] = date('Y-m-d H:i:s');
                }

                // VeritabanÄ±nÄ± gÃ¼ncelle
                self::updateVPSDetails($whmcsServiceId, $updateData);

                return $result;
            }
            
            if ($httpCode === 404) {
                self::updateVPSDetails($whmcsServiceId, [
                    'wls_vm_id' => null,
                    'vm_status' => 'deleted',
                    'last_sync' => date('Y-m-d H:i:s')
                ]);
                throw new Exception("VM bulunamadÄ± (Service ID: " . $serviceId . ", VM ID: " . $vmId . ")");
            }
            
            throw new Exception("API HatasÄ±: HTTP " . $httpCode . " - YanÄ±t: " . $response);
            
        } catch (Exception $e) {
            self::debugLog("VM Detay Alma HatasÄ±: " . $e->getMessage());
            return null;
        }
    }
    
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public static function updateVPSDetails($whmcsServiceId, $data) {
        try {
            self::createTablesIfNotExists();
            
            // WHMCS ID kontrolÃ¼
            if (!is_numeric($whmcsServiceId) || $whmcsServiceId <= 0) {
                throw new Exception("GeÃ§ersiz WHMCS ID: " . $whmcsServiceId);
            }

            // Debug log
            self::debugLog("Debug - Updating VPS details for service ID: " . $whmcsServiceId);

            // Mevcut kaydÄ± kontrol et
            $existingRecord = Capsule::table('mod_wls_vps')
                ->where('id', $whmcsServiceId)
                ->first();

            // GÃ¼ncelleme verilerini hazÄ±rla
            $updateData = [
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // VM API yanÄ±tÄ±ndan gelen verileri iÅŸle
            if (isset($data['vm'])) {
                $vm = $data['vm'];
                
                // Network arayÃ¼zlerini iÅŸle
                if (isset($vm['interfaces'])) {
                    $updateData['vm_interfaces'] = json_encode($vm['interfaces']);
                }
                
                // Depolama bilgilerini iÅŸle
                if (isset($vm['storage'])) {
                    $updateData['vm_storage'] = json_encode($vm['storage']);
                }
                
                // Bandwidth bilgilerini iÅŸle
                if (isset($vm['bandwidth'])) {
                    $updateData['vm_bandwidth'] = json_encode($vm['bandwidth']);
                }
                
                // Kaynak kullanÄ±m bilgilerini iÅŸle
                $resources = [
                    'memory' => $vm['memory'] ?? null,
                    'disk' => $vm['disk'] ?? null,
                    'cores' => $vm['cores'] ?? null,
                    'sockets' => $vm['sockets'] ?? null,
                    'cpus' => $vm['cpus'] ?? null,
                    'uptime' => $vm['uptime'] ?? null
                ];
                $updateData['vm_resources'] = json_encode($resources);
                
                // Temel VM bilgilerini gÃ¼ncelle
                $updateData = array_merge($updateData, [
                    'vm_status' => $vm['status'] ?? null,
                    'vm_built' => $vm['built'] ?? false,
                    'vm_powered' => $vm['power'] ?? false,
                    'ipv4' => $vm['ipv4'] ?? null,
                    'username' => $vm['username'] ?? null,
                    'password' => $vm['password'] ?? null,
                    'memory' => $vm['memory'] ?? null,
                    'disk' => $vm['disk'] ?? null,
                    'cores' => $vm['cores'] ?? null,
                    'template' => $vm['template_name'] ?? null,
                    'mac_address' => $vm['mac'] ?? null,
                    'last_sync' => date('Y-m-d H:i:s')
                ]);
                
                // IP listesini iÅŸle
                if (isset($vm['ip']) && is_array($vm['ip'])) {
                    $allIps = [];
                    foreach ($vm['ip'] as $ipId => $ipInfo) {
                        $allIps[] = [
                            'id' => $ipId,
                            'ip' => $ipInfo['ipaddress'],
                            'main' => $ipInfo['main']
                        ];
                    }
                    $updateData['all_ips'] = json_encode($allIps);
                }
            } else {
                // VM verisi yoksa normal gÃ¼ncelleme verilerini iÅŸle
                foreach ($data as $field => $value) {
                    switch ($field) {
                        case 'interfaces':
                            $updateData['vm_interfaces'] = json_encode($value);
                            break;
                        case 'storage':
                            $updateData['vm_storage'] = json_encode($value);
                            break;
                        case 'bandwidth':
                            $updateData['vm_bandwidth'] = json_encode($value);
                            break;
                        case 'resources':
                            $updateData['vm_resources'] = json_encode($value);
                            break;
                        case 'last_check':
                            $updateData['last_sync'] = $value;
                            break;
                        case 'vm_power':
                            $updateData['vm_powered'] = $value;
                            break;
                        default:
                            if ($field === 'power') {
                                $updateData['vm_powered'] = $value;
                            } else {
                                $updateData[$field] = $value;
                            }
                    }
                }
            }

            // Status kontrolÃ¼
            if (!isset($updateData['status']) && $existingRecord) {
                $updateData['status'] = $existingRecord->status;
            }

            // WLS Service ID kontrolÃ¼
            if (!isset($updateData['wls_service_id']) && $existingRecord && $existingRecord->wls_service_id) {
                $updateData['wls_service_id'] = $existingRecord->wls_service_id;
            }

            // Debug log
            self::debugLog("Debug - Final update data: " . json_encode($updateData));

            // Kaydet veya gÃ¼ncelle
            $result = Capsule::table('mod_wls_vps')->updateOrInsert(
                ['id' => $whmcsServiceId],
                array_merge($updateData, ['created_at' => Capsule::raw('IFNULL(created_at, NOW())')])
            );
            
            // Also update tblhosting.domain with label/hostname
            if (isset($data['vm']['label'])) {
                Capsule::table('tblhosting')
                    ->where('id', $whmcsServiceId)
                    ->update(['domain' => $data['vm']['label']]);
            }

            // Debug log
            self::debugLog("Debug - Database update result: " . ($result ? "Success" : "Failed"));

            return $result;

        } catch (Exception $e) {
            self::debugLog("VPS GÃ¼ncelleme HatasÄ±: " . $e->getMessage());
            self::debugLog("Debug - Error stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    public static function getVPSDetails($whmcsServiceId) {
        try {
            self::createTablesIfNotExists();
            return Capsule::table('mod_wls_vps')
                ->where('id', $whmcsServiceId)
                ->first();
        } catch (Exception $e) {
            self::debugLog("VPS Bilgi Alma HatasÄ±: " . $e->getMessage());
            return null;
        }
    }
    
    public static function deleteVPSDetails($whmcsServiceId) {
        try {
            self::createTablesIfNotExists();
            return Capsule::table('mod_wls_vps')
                ->where('id', $whmcsServiceId)
                ->delete();
        } catch (Exception $e) {
            self::debugLog("VPS Silme HatasÄ±: " . $e->getMessage());
            return false;
        }
    }
} 
