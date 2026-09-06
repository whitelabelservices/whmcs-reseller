<?php
 




 

function wls_render_sidebar($currentPage = 'dashboard', $lang = 'en') {
    $translations = [
        'en' => [
            'dashboard' => 'Dashboard',
            'tickets' => 'Tickets',
            'ticket_management' => 'Ticket Management',
            'cancel_management' => 'Cancellation Management',
            'linked_services' => 'Linked Services',
            'pricing' => 'Products Manager',
            'upgrades' => 'Upgrade Management',
            'notifications' => 'Notifications',
            'news' => 'News',
            'settings' => 'Settings',
            'back_whmcs' => 'Back to WHMCS'
        ],
        'tr' => [
            'dashboard' => 'Panel',
            'tickets' => 'Ticketlar',
            'ticket_management' => 'Ticket Yönetimi',
            'cancel_management' => 'İptal Yönetimi',
            'linked_services' => 'Bağlı Hizmetler',
            'pricing' => 'Ürün Yönetimi',
            'upgrades' => 'Yükseltme Yönetimi',
            'notifications' => 'Bildirimler',
            'news' => 'Haberler',
            'settings' => 'Ayarlar',
            'back_whmcs' => 'WHMCS\'e Dön'
        ]
    ];
    $t = $translations[$lang] ?? $translations['en'];
    
    $menuItems = [
        'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'label' => $t['dashboard'], 'url' => 'index.php'],
        'tickets' => ['icon' => 'fas fa-ticket-alt', 'label' => $t['tickets'], 'url' => 'tickets.php'],
        'ticket_management' => ['icon' => 'fas fa-list-alt', 'label' => $t['ticket_management'], 'url' => 'ticket_management.php'],
        'cancel_management' => ['icon' => 'fas fa-times-circle', 'label' => $t['cancel_management'], 'url' => 'cancel_management.php'],
        'linked_services' => ['icon' => 'fas fa-link', 'label' => $t['linked_services'], 'url' => 'linked_services.php'],
        'pricing' => ['icon' => 'fas fa-tags', 'label' => $t['pricing'], 'url' => 'pricing.php'],
        'notifications' => ['icon' => 'fas fa-bell', 'label' => $t['notifications'], 'url' => 'notifications.php'],
        'news' => ['icon' => 'fas fa-newspaper', 'label' => $t['news'], 'url' => 'news.php'],
        'upgrades' => ['icon' => 'fas fa-arrow-circle-up', 'label' => $t['upgrades'], 'url' => 'upgrades.php'],
        'settings' => ['icon' => 'fas fa-cog', 'label' => $t['settings'], 'url' => 'settings.php'],
    ];
    
    ?>
     
    <button class="wls-mobile-toggle" onclick="toggleMobileMenu()">
        <i class="fas fa-bars"></i>
    </button>
    
     
    <div class="wls-overlay" id="wlsOverlay" onclick="toggleMobileMenu()"></div>
    
    <aside class="wls-sidebar" id="wlsSidebar">
        <div class="wls-sidebar-header">
            <div class="wls-logo">
                <i class="fas fa-server" style="font-size: 1.8rem; color: #60a5fa;"></i>
                <span>WLS Panel</span>
            </div>
            <button class="wls-sidebar-close" onclick="toggleMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="wls-nav">
            <?php foreach ($menuItems as $key => $item): ?>
            <li>
                <a href="<?= $item['url'] ?>?lang=<?= $lang ?>" class="<?= $currentPage === $key ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i> <?= $item['label'] ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="wls-sidebar-footer">
            <div class="lang-switch">
                <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
                <a href="?lang=tr" class="<?= $lang === 'tr' ? 'active' : '' ?>">🇹🇷 TR</a>
            </div>
            <a href="../../../../admin/index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> <?= $t['back_whmcs'] ?>
            </a>
        </div>
    </aside>
    
    <script>
    function toggleMobileMenu() {
        document.getElementById('wlsSidebar').classList.toggle('open');
        document.getElementById('wlsOverlay').classList.toggle('open');
        document.body.classList.toggle('menu-open');
    }
    </script>
    <?php
}

function wls_render_head($title = 'WLS Panel', $extraStyles = '') {
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #e2e8f0;
        }
        
        .wls-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: rgba(15, 23, 42, 0.95);
            border-right: 1px solid rgba(255,255,255,0.1);
            padding: 20px;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        
        .wls-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .wls-logo span {
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .wls-nav { list-style: none; flex: 1; }
        .wls-nav li { margin-bottom: 5px; }
        .wls-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .wls-nav a:hover, .wls-nav a.active {
            background: rgba(96, 165, 250, 0.15);
            color: #60a5fa;
        }
        .wls-nav a.active {
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.2), rgba(167, 139, 250, 0.2));
            border: 1px solid rgba(96, 165, 250, 0.3);
        }
        .wls-nav i { width: 20px; text-align: center; }
        
        .wls-sidebar-footer {
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .wls-sidebar-footer .lang-switch {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .wls-sidebar-footer .lang-switch a {
            color: #64748b;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .wls-sidebar-footer .lang-switch a:hover,
        .wls-sidebar-footer .lang-switch a.active {
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
        }
        
        .wls-sidebar-footer .back-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .wls-sidebar-footer .back-link:hover {
            color: #e2e8f0;
            background: rgba(255,255,255,0.05);
        }
        
        .wls-main {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .wls-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .wls-page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .wls-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }
        
        .wls-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .wls-card-header i { font-size: 1.3rem; color: #60a5fa; }
        .wls-card-header h2 { font-size: 1.2rem; font-weight: 600; }
        
        .wls-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .wls-btn-primary {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
        }
        .wls-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
        }
        
        .wls-btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #e2e8f0;
        }
        .wls-btn-secondary:hover {
            background: rgba(255,255,255,0.15);
        }
        
        .wls-btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .wls-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .wls-stat-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .wls-stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(96, 165, 250, 0.3);
        }
        
        .wls-stat-card .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .wls-stat-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .wls-stat-card .label {
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .wls-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .wls-table th, .wls-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .wls-table th {
            color: #64748b;
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .wls-table tr:hover {
            background: rgba(255,255,255,0.02);
        }
        
         
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-responsive::-webkit-scrollbar { height: 6px; }
        .table-responsive::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 3px; }
        .table-responsive::-webkit-scrollbar-thumb { background: rgba(96, 165, 250, 0.3); border-radius: 3px; }
        
        @media (max-width: 768px) {
            .table-responsive .wls-table { min-width: 500px; }
        }
        
        .wls-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .wls-badge-success { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .wls-badge-warning { background: rgba(234, 179, 8, 0.2); color: #facc15; }
        .wls-badge-danger { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .wls-badge-info { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        
        .wls-alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .wls-alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
        .wls-alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
        .wls-alert-warning { background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.3); color: #facc15; }
        .wls-alert-info { background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa; }
        
        .wls-form-group { margin-bottom: 20px; }
        .wls-form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .wls-form-group input, .wls-form-group select, .wls-form-group textarea {
            width: 100%;
            max-width: 400px;
            padding: 12px 15px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .wls-form-group input:focus, .wls-form-group select:focus, .wls-form-group textarea:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
        }
        .wls-form-group .hint { font-size: 0.85rem; color: #64748b; margin-top: 6px; }
        
        .wls-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .wls-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        
        @media (max-width: 1200px) {
            .wls-grid-3 { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            .wls-sidebar { 
                display: flex;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .wls-sidebar.open {
                transform: translateX(0);
            }
            .wls-main { 
                margin-left: 0; 
                padding: 15px; 
                padding-top: 75px; 
                overflow-x: hidden;
                max-width: 100vw;
            }
            .wls-grid-2, .wls-grid-3 { grid-template-columns: 1fr; }
            .wls-page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .wls-page-header h1 { font-size: 1.4rem; }
            
             
            .wls-stat-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .wls-stat-card {
                padding: 15px 10px;
            }
            .wls-stat-card .icon { font-size: 1.5rem; margin-bottom: 8px; }
            .wls-stat-card .value { font-size: 1.3rem; }
            .wls-stat-card .label { font-size: 0.7rem; letter-spacing: 0; }
            
             
            .wls-card { 
                overflow-x: hidden;
                padding: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .wls-stat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .wls-stat-card .value { font-size: 1.1rem; }
            .wls-stat-card .label { font-size: 0.65rem; }
            .wls-main { padding: 10px; padding-top: 70px; }
        }
        
         
        .wls-mobile-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 200;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border: none;
            color: #fff;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            font-size: 1.3rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            transition: all 0.3s;
        }
        .wls-mobile-toggle:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .wls-mobile-toggle { display: flex; align-items: center; justify-content: center; }
        }
        
         
        .wls-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 99;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .wls-overlay.open {
            display: block;
            opacity: 1;
        }
        
         
        .wls-sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .wls-sidebar-header .wls-logo {
            padding: 0;
            border: none;
            margin: 0;
        }
        .wls-sidebar-close {
            display: none;
            background: rgba(255,255,255,0.1);
            border: none;
            color: #94a3b8;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .wls-sidebar-close:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        @media (max-width: 768px) {
            .wls-sidebar-close { display: flex; align-items: center; justify-content: center; }
        }
        
         
        body.menu-open {
            overflow: hidden;
        }
        
        <?= $extraStyles ?>
    </style>
    <?php
}
