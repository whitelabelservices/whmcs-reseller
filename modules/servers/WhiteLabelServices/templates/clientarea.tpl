

<div class="vps-client-area">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --vps-primary: #6366f1;
            --vps-primary-dark: #4f46e5;
            --vps-success: #10b981;
            --vps-danger: #ef4444;
            --vps-warning: #f59e0b;
            --vps-info: #3b82f6;
            --vps-dark: #1e293b;
            --vps-gray: #64748b;
            --vps-light: #f8fafc;
            --vps-border: #e2e8f0;
            --vps-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --vps-shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }

        .sidebar{
            display: none !important;
        }
        
         
        #Primary_Sidebar-Service_Details_Actions-Change_Password,
        a[menuitemname="Change Password"],
        #tabChangepw { display: none !important; }
        
        .vps-client-area {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--vps-light);
            min-height: 100vh;
            padding: 0;
        }
        
        .vps-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
         
        .vps-lang-selector {
            position: absolute;
            top: 15px;
            right: 20px;
            display: flex;
            gap: 5px;
            z-index: 100;
        }
        
        .vps-lang-btn {
            padding: 6px 12px;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .vps-lang-btn:hover, .vps-lang-btn.active {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
        }
        
        .vps-cancel-request-wrap {
            display: inline-flex;
        }
        .vps-cancel-request-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(255,255,255,0.95);
            background: rgba(239, 68, 68, 0.25);
            border: 2px solid rgba(239, 68, 68, 0.5);
            border-radius: 10px;
            text-decoration: none !important;
            transition: all 0.2s;
        }
        .vps-cancel-request-btn:hover {
            background: rgba(239, 68, 68, 0.4);
            border-color: rgba(239, 68, 68, 0.8);
            color: white;
            text-decoration: none !important;
        }
        .vps-cancel-request-received {
            cursor: default;
            opacity: 0.65;
            background: rgba(100, 116, 139, 0.25) !important;
            border-color: rgba(100, 116, 139, 0.4) !important;
            color: rgba(255,255,255,0.85) !important;
        }
        .vps-cancel-request-received:hover {
            background: rgba(100, 116, 139, 0.25) !important;
            border-color: rgba(100, 116, 139, 0.4) !important;
            color: rgba(255,255,255,0.85) !important;
        }
        
         
        .vps-header {
            background: linear-gradient(135deg, var(--vps-primary) 0%, var(--vps-primary-dark) 100%);
            color: white;
            padding: 30px 35px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: var(--vps-shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .vps-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .vps-header-content {
            position: relative;
            z-index: 1;
        }
        
        .vps-server-name {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .vps-server-name i {
            font-size: 1.6rem;
            opacity: 0.9;
        }
        
        .vps-server-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .vps-status-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .vps-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .vps-status-badge.running {
            background: var(--vps-success);
        }
        
        .vps-status-badge.stopped {
            background: var(--vps-danger);
        }
        
        .vps-status-badge.pending {
            background: var(--vps-warning);
            color: #000;
        }
        
        .vps-status-badge i {
            font-size: 8px;
        }
        
        .vps-uptime-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .vps-uptime-info i {
            opacity: 0.8;
        }
        
         
        .vps-actions-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
        }
        
        .vps-right-actions {
               display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
    margin-left: auto;
    top: -29px;
    position: relative;
        }
        
        .vps-upgrade-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            flex-direction: row-reverse;
        }
        
        .vps-upgrade-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: 2px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.12);
            color: white;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none !important;
            cursor: pointer;
            transition: all 0.2s;
            backdrop-filter: blur(10px);
        }
        
        .vps-upgrade-btn:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.6);
            color: white;
            text-decoration: none !important;
            transform: translateY(-2px);
        }
        
        .vps-upgrade-btn i {
            opacity: 0.95;
        }
        
         
        .vps-power-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .vps-power-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            backdrop-filter: blur(10px);
        }
        
        .vps-power-btn:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
        }
        
        .vps-power-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .vps-power-btn.btn-success {
            background: rgba(16, 185, 129, 0.3);
            border-color: rgba(16, 185, 129, 0.5);
        }
        
        .vps-power-btn.btn-danger {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
        }
        
        .vps-power-btn.btn-warning {
            background: rgba(245, 158, 11, 0.3);
            border-color: rgba(245, 158, 11, 0.5);
        }
        
        .vps-power-btn.loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
         
        .vps-tabs {
            display: flex;
            gap: 5px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: var(--vps-shadow);
            overflow-x: auto;
        }
        
        .vps-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: var(--vps-gray);
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .vps-tab:hover {
            background: var(--vps-light);
            color: var(--vps-dark);
        }
        
        .vps-tab.active {
            background: var(--vps-primary);
            color: white;
        }
        
        .vps-tabs {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 25px;
            box-shadow: var(--vps-shadow);
        }
        
        .vps-tabs-left {
            display: flex;
            gap: 5px;
        }
        
        .vps-tabs-right {
            display: flex;
            align-items: center;
        }
        
        .vps-sync-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: none;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .vps-sync-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .vps-sync-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .vps-sync-btn.syncing i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
         
        .vps-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--vps-shadow);
            border: 1px solid var(--vps-border);
        }
        
        .vps-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--vps-light);
        }
        
        .vps-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }
        
        .vps-card-icon.icon-server { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .vps-card-icon.icon-network { background: linear-gradient(135deg, #3b82f6, #06b6d4); }
        .vps-card-icon.icon-access { background: linear-gradient(135deg, #10b981, #34d399); }
        .vps-card-icon.icon-rebuild { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .vps-card-icon.icon-resources { background: linear-gradient(135deg, #ec4899, #f472b6); }
        
        .vps-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--vps-dark);
            margin: 0;
        }
        
         
        .vps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .vps-grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        
         
        .vps-info-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .vps-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--vps-border);
        }
        
        .vps-info-item:last-child {
            border-bottom: none;
        }
        
        .vps-info-label {
            font-weight: 500;
            color: var(--vps-gray);
            font-size: 0.9rem;
        }
        
        .vps-info-value {
            font-weight: 600;
            color: var(--vps-dark);
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
         
        .vps-copy-btn {
            padding: 6px 10px;
            background: var(--vps-light);
            border: 1px solid var(--vps-border);
            border-radius: 6px;
            color: var(--vps-gray);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
        }
        
        .vps-copy-btn:hover {
            background: var(--vps-primary);
            color: white;
            border-color: var(--vps-primary);
        }
        
         
        .vps-password-field {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .vps-password-hidden {
            font-family: monospace;
            letter-spacing: 2px;
        }
        
        .vps-password-toggle {
            padding: 4px 8px;
            background: transparent;
            border: none;
            color: var(--vps-gray);
            cursor: pointer;
        }
        
         
        .vps-tab-content {
            display: none;
        }
        
        .vps-tab-content.active {
            display: block;
        }
        
         
        .vps-interface {
            background: var(--vps-light);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--vps-primary);
        }
        
        .vps-interface-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .vps-interface-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--vps-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .vps-interface-badge {
            padding: 4px 10px;
            background: var(--vps-primary);
            color: white;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .vps-ip-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .vps-ip-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--vps-border);
        }
        
        .vps-ip-address {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: var(--vps-dark);
        }
        
        .vps-ip-type {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 4px;
            background: var(--vps-light);
            color: var(--vps-gray);
        }
        
         
        .vps-template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .vps-template-item {
            padding: 20px;
            background: var(--vps-light);
            border: 2px solid var(--vps-border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .vps-template-item:hover {
            border-color: var(--vps-primary);
            background: white;
        }
        
        .vps-template-item.selected {
            border-color: var(--vps-primary);
            background: rgba(99, 102, 241, 0.1);
        }
        
        .vps-template-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .vps-template-name {
            font-weight: 600;
            color: var(--vps-dark);
            margin-bottom: 5px;
        }
        
        .vps-template-version {
            font-size: 0.85rem;
            color: var(--vps-gray);
        }
        
         
        .vps-alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .vps-alert i {
            font-size: 1.2rem;
            margin-top: 2px;
        }
        
        .vps-alert-content h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }
        
        .vps-alert-content p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .vps-alert.alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .vps-alert.alert-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }
        
        .vps-alert.alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .vps-alert.alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }
        
         
        .vps-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .vps-btn-primary {
            background: var(--vps-primary);
            color: white;
        }
        
        .vps-btn-primary:hover {
            background: var(--vps-primary-dark);
            transform: translateY(-2px);
        }
        
        .vps-btn-danger {
            background: var(--vps-danger);
            color: white;
        }
        
        .vps-btn-danger:hover {
            background: #dc2626;
        }
        
        .vps-btn-outline {
            background: transparent;
            border: 2px solid var(--vps-border);
            color: var(--vps-dark);
        }
        
        .vps-btn-outline:hover {
            border-color: var(--vps-primary);
            color: var(--vps-primary);
        }
        
         
        .vps-resource-bar {
            height: 10px;
            background: var(--vps-light);
            border-radius: 5px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .vps-resource-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s ease;
        }
        
        .vps-resource-fill.low { background: var(--vps-success); }
        .vps-resource-fill.medium { background: var(--vps-warning); }
        .vps-resource-fill.high { background: var(--vps-danger); }
        
         
        @media (max-width: 768px) {
            .vps-container {
                padding: 10px;
            }
            
            .vps-header {
                padding: 20px;
            }
            
            .vps-server-name {
                font-size: 1.5rem;
            }
            
            .vps-power-controls {
                justify-content: center;
            }
            
            .vps-tabs {
                padding: 5px;
            }
            
            .vps-tab {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
            
            .vps-grid {
                grid-template-columns: 1fr;
            }
        }
        
         
        .vps-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .vps-loading-overlay.active {
            display: flex;
        }
        
        .vps-loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
         
        .vps-refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 56px;
            height: 56px;
            background: var(--vps-primary);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--vps-shadow-lg);
            transition: all 0.3s;
            z-index: 100;
        }
        
        .vps-refresh-btn:hover {
            background: var(--vps-primary-dark);
            transform: translateY(-3px);
        }
        
         
        .vps-resource-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 1100px) {
            .vps-resource-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .vps-resource-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .vps-resource-card {
            background: white;
            border-radius: 16px;
            padding: 18px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 0;
        }
        
        .vps-resource-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            border-radius: 20px 20px 0 0;
        }
        
        .vps-resource-card.card-cpu::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
        .vps-resource-card.card-ram::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .vps-resource-card.card-disk::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .vps-resource-card.card-os::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        
        .vps-resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }
        
        .vps-resource-card-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .vps-resource-ring {
            position: relative;
            width: 60px;
            height: 60px;
            flex-shrink: 0;
        }
        
        .vps-resource-ring svg {
            transform: rotate(-90deg);
            width: 60px;
            height: 60px;
        }
        
        .vps-resource-ring .ring-bg {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 8;
        }
        
        .vps-resource-ring .ring-fill {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease-out;
        }
        
        .vps-resource-ring .ring-fill.cpu { stroke: url(#gradient-cpu); }
        .vps-resource-ring .ring-fill.ram { stroke: url(#gradient-ram); }
        .vps-resource-ring .ring-fill.disk { stroke: url(#gradient-disk); }
        
        .vps-resource-ring-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.2rem;
            color: var(--vps-gray);
        }
        
        .vps-resource-ring-icon.cpu { color: #6366f1; }
        .vps-resource-ring-icon.ram { color: #10b981; }
        .vps-resource-ring-icon.disk { color: #f59e0b; }
        .vps-resource-ring-icon.os { color: #3b82f6; }
        
        .vps-resource-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        .vps-resource-label {
            font-size: 0.75rem;
            color: var(--vps-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .vps-resource-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--vps-dark);
            line-height: 1;
        }
        
        .vps-resource-value span {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--vps-gray);
            margin-left: 4px;
        }
        
        .vps-resource-subtitle {
            font-size: 0.8rem;
            color: var(--vps-gray);
            margin-top: 5px;
        }
        
         
        .vps-quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .vps-stat-item {
            background: white;
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid var(--vps-border);
            transition: all 0.2s;
        }
        
        .vps-stat-item:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .vps-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        
        .vps-stat-icon.ip { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
        .vps-stat-icon.ssh { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
        .vps-stat-icon.vmid { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #db2777; }
        .vps-stat-icon.uptime { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
        
        .vps-stat-content {
            flex: 1;
            min-width: 0;
        }
        
        .vps-stat-label {
            font-size: 0.75rem;
            color: var(--vps-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .vps-stat-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--vps-dark);
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .vps-stat-copy {
            padding: 8px;
            background: var(--vps-light);
            border: none;
            border-radius: 8px;
            color: var(--vps-gray);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .vps-stat-copy:hover {
            background: var(--vps-primary);
            color: white;
        }
        
        .vps-stat-copy.copied::after {
            content: 'Copied';
            position: absolute;
            top: -26px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15,23,42,0.95);
            color: #e2e8f0;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(15,23,42,0.5);
            z-index: 10;
        }
        
        .vps-stat-copy.copied::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 4px;
            border-style: solid;
            border-color: rgba(15,23,42,0.95) transparent transparent transparent;
        }
        
         
        .vps-access-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            padding: 28px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        
        .vps-access-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .vps-access-header i {
            font-size: 1.4rem;
            color: #10b981;
        }
        
        .vps-access-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .vps-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .vps-access-item {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .vps-access-item-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .vps-access-item-value {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        
        .vps-access-item-value code {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 1rem;
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            padding: 8px 14px;
            border-radius: 8px;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .vps-access-item-value .password-mask {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }
        
        .vps-access-btn {
            padding: 8px 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
            position: relative;
        }
        
        .vps-access-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .vps-access-btn.copied::after {
            content: 'Copied';
            position: absolute;
            top: -26px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15,23,42,0.95);
            color: #e2e8f0;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(15,23,42,0.5);
            z-index: 10;
        }
        
        .vps-access-btn.copied::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 4px;
            border-style: solid;
            border-color: rgba(15,23,42,0.95) transparent transparent transparent;
        }
        
        .vps-ssh-command {
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 16px 20px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .vps-ssh-command i {
            font-size: 1.3rem;
            color: #60a5fa;
        }
        
        .vps-ssh-command code {
            flex: 1;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            color: #a5f3fc;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
         
        .vps-os-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 25px;
            border: 1px solid var(--vps-border);
            gap: 20px;
        }
        
        .vps-os-banner-content {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }
        
        .vps-os-banner-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }
        
        .vps-os-banner-icon.linux {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #b45309;
        }
        
        .vps-os-banner-icon.windows {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1d4ed8;
        }
        
        .vps-os-banner-info h4 {
            margin: 0 0 4px 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--vps-dark);
        }
        
        .vps-os-banner-info .os-type {
            font-size: 0.85rem;
            color: var(--vps-gray);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .vps-os-banner-info .os-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .vps-os-banner-info .os-badge.linux {
            background: #fef3c7;
            color: #b45309;
        }
        
        .vps-os-banner-info .os-badge.windows {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .vps-os-banner-rebuild {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--vps-primary), var(--vps-primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .vps-os-banner-rebuild:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            color: white;
        }
        
        @media (max-width: 640px) {
            .vps-os-banner {
                flex-direction: column;
                text-align: center;
            }
            .vps-os-banner-content {
                flex-direction: column;
            }
        }
        
         
        .vps-os-family-card {
            background: white;
            border: 2px solid var(--vps-border);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .vps-os-family-card:hover {
            border-color: var(--vps-primary);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
            transform: translateY(-2px);
        }
        
        .vps-os-family-card.selected {
            border-color: var(--vps-primary);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.02));
        }
        
        .vps-os-family-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .vps-os-family-info {
            flex: 1;
            min-width: 0;
        }
        
        .vps-os-family-name {
            font-weight: 600;
            font-size: 1rem;
            color: var(--vps-dark);
            margin-bottom: 6px;
        }
        
        .vps-os-version-select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--vps-border);
            border-radius: 6px;
            font-size: 0.85rem;
            color: var(--vps-gray);
            background: white;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        
        .vps-os-version-select:focus {
            outline: none;
            border-color: var(--vps-primary);
        }
        
        .vps-os-family-check {
            position: absolute;
            top: 8px;
            right: 8px;
            color: var(--vps-primary);
            font-size: 1.2rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .vps-os-family-card.selected .vps-os-family-check {
            opacity: 1;
        }
        
         
        .vps-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .vps-loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .vps-loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--vps-primary);
            border-radius: 50%;
            animation: loadingSpin 1s linear infinite;
        }
        
        @keyframes loadingSpin {
            to { transform: rotate(360deg); }
        }
        
         
        .vps-lock-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .vps-lock-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .vps-lock-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top-color: var(--vps-primary);
            border-radius: 50%;
            animation: lockSpin 1s linear infinite;
            margin-bottom: 25px;
        }
        
        @keyframes lockSpin {
            to { transform: rotate(360deg); }
        }
        
        .vps-lock-content {
            text-align: center;
            color: white;
        }
        
        .vps-lock-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .vps-lock-status {
            font-size: 1rem;
            opacity: 0.8;
            margin-bottom: 8px;
        }
        
        .vps-lock-message {
            font-size: 0.9rem;
            opacity: 0.6;
            max-width: 400px;
        }
        
        .vps-lock-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 15px;
        }
        
        .vps-lock-status-badge .status-dot {
            width: 10px;
            height: 10px;
            background: #f59e0b;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        
         
        .vps-hostname-edit-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-size: 0.5em;
            cursor: pointer;
            padding: 6px 10px;
            margin-left: 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .vps-hostname-edit-btn:hover {
            background: rgba(124, 58, 237, 0.8);
            border-color: var(--vps-primary);
            color: white;
            transform: scale(1.1);
        }
        
         
        .vps-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .vps-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .vps-modal {
            background: #ffffff;
            border-radius: 16px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .vps-modal-overlay.active .vps-modal {
            transform: scale(1) translateY(0);
        }
        
        .vps-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            border-radius: 16px 16px 0 0;
        }
        
        .vps-modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .vps-modal-header h3 i {
            color: var(--vps-primary);
        }
        
        .vps-modal-close {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .vps-modal-close:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .vps-modal-body {
            padding: 25px;
        }
        
        .vps-form-group {
            margin-bottom: 15px;
        }
        
        .vps-form-group label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .vps-form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            color: #1e293b;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        
        .vps-form-control:focus {
            outline: none;
            border-color: var(--vps-primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            background: #ffffff;
        }
        
        .vps-form-help {
            display: block;
            margin-top: 8px;
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .vps-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px 25px;
            border-top: 1px solid var(--vps-border);
            background: rgba(0, 0, 0, 0.02);
            border-radius: 0 0 16px 16px;
        }
        
        .vps-btn-primary {
            background: linear-gradient(135deg, var(--vps-primary), #8b5cf6);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        
        .vps-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.3);
        }
        
        .vps-btn-outline {
            background: transparent;
            color: var(--vps-gray);
            border: 2px solid var(--vps-border);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        
        .vps-btn-outline:hover {
            border-color: var(--vps-gray);
            background: rgba(0, 0, 0, 0.03);
        }
        
        .vps-btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        
        .vps-btn-danger:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(239, 68, 68, 0.4);
            background: linear-gradient(135deg, #f87171, #ef4444);
        }
        
        .vps-btn:disabled,
        .vps-btn-danger:disabled,
        .vps-btn-primary:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
            background: #94a3b8 !important;
            border-color: #94a3b8 !important;
            pointer-events: none;
        }
    </style>

     
    <div class="vps-loading-overlay" id="loadingOverlay">
        <div class="vps-loading-spinner"></div>
    </div>
    
     
    <div class="vps-lock-overlay" id="vmLockOverlay">
        <div class="vps-lock-spinner"></div>
        <div class="vps-lock-content">
            <div class="vps-lock-title" id="lockTitle">VM Operation in Progress</div>
            <div class="vps-lock-status" id="lockStatus">Please wait...</div>
            <div class="vps-lock-message" id="lockMessage">Your server is being processed. This may take a few minutes.</div>
            <div class="vps-lock-status-badge">
                <span class="status-dot"></span>
                <span id="lockStatusText">Processing</span>
            </div>
        </div>
    </div>

     
    <div class="vps-modal-overlay" id="powerConfirmModal">
        <div class="vps-modal" style="max-width: 420px;">
            <div class="vps-modal-header">
                <h3><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> <span id="power-confirm-title" data-lang="confirm_action">Are you sure?</span></h3>
                <button class="vps-modal-close" onclick="closePowerConfirmModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="vps-modal-body">
                <p id="power-confirm-message" style="margin: 0; color: var(--vps-text-muted); line-height: 1.6;"></p>
            </div>
            <div class="vps-modal-footer">
                <button class="vps-btn vps-btn-outline" onclick="closePowerConfirmModal()">
                    <i class="fas fa-times"></i> <span data-lang="cancel">Cancel</span>
                </button>
                <button class="vps-btn vps-btn-danger" id="power-confirm-btn" onclick="confirmPowerAction()">
                    <i class="fas fa-check"></i> <span data-lang="confirm">Confirm</span>
                </button>
            </div>
        </div>
    </div>

     
    <div class="vps-modal-overlay" id="hostnameModal">
        <div class="vps-modal">
            <div class="vps-modal-header">
                <h3><i class="fas fa-edit"></i> <span data-lang="edit_hostname">Edit Hostname</span></h3>
                <button class="vps-modal-close" onclick="closeHostnameModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="vps-modal-body">
                <div class="vps-form-group">
                    <label for="hostname-input" data-lang="hostname">Hostname</label>
                    <input type="text" id="hostname-input" class="vps-form-control" placeholder="Enter hostname...">
                    <small class="vps-form-help" data-lang="hostname_help">The hostname will be set on your server. Use only letters, numbers, and hyphens.</small>
                </div>
            </div>
            <div class="vps-modal-footer">
                <button class="vps-btn vps-btn-outline" onclick="closeHostnameModal()">
                    <i class="fas fa-times"></i> <span data-lang="cancel">Cancel</span>
                </button>
                <button class="vps-btn vps-btn-primary" onclick="saveHostname()">
                    <i class="fas fa-save"></i> <span data-lang="save">Save</span>
                </button>
            </div>
        </div>
    </div>

    <div class="vps-container">
         
        {if $serviceStatus == 'Terminated' || $serviceStatus == 'Cancelled'}
            <div class="vps-alert alert-danger">
                <i class="fas fa-times-circle"></i>
                <div class="vps-alert-content">
                    <h4 data-lang="service_terminated">Service Terminated</h4>
                    <p data-lang="service_terminated_desc">This VPS service has been terminated. Please contact support to reactivate.</p>
                </div>
            </div>
        {elseif $serviceStatus == 'Suspended'}
            <div class="vps-alert alert-warning">
                <i class="fas fa-pause-circle"></i>
                <div class="vps-alert-content">
                    <h4 data-lang="service_suspended">Service Suspended</h4>
                    <p data-lang="service_suspended_desc">This VPS service has been suspended. Please check your payment status.</p>
                </div>
            </div>
        {elseif $serviceStatus == 'Pending' || !$showVMDetails}
            <div class="vps-card">
                <div class="vps-card-header">
                    <div class="vps-card-icon icon-server">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h3 class="vps-card-title" data-lang="order_processing">Order Processing</h3>
                </div>
                <div class="vps-alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div class="vps-alert-content">
                        <h4 data-lang="preparing_vps">Preparing Your VPS</h4>
                        <p data-lang="preparing_vps_desc">Your VPS is being provisioned. This usually takes 2-5 minutes. The page will refresh automatically.</p>
                    </div>
                </div>
                <script>setTimeout(function() { location.reload(); }, 30000);</script>
            </div>
        {else}
             
            
             
            <div class="vps-header">
                 
                <div class="vps-lang-selector">
                    <button class="vps-lang-btn active" data-lang-switch="en">EN</button>
                    <button class="vps-lang-btn" data-lang-switch="tr">TR</button>
                </div>
                
                <div class="vps-header-content">
                    <h1 class="vps-server-name">
                        <i class="fas fa-server"></i>
                        <span id="server-hostname-display">{$vmInfo.label|default:$domain|default:'VPS Server'}</span>
                        <button class="vps-hostname-edit-btn" onclick="openHostnameModal()" title="Edit Hostname">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </h1>
                    <p class="vps-server-subtitle">
                        VM ID: {$vmInfo.vm_id|default:'N/A'} &bull; 
                        {$vmInfo.template|default:'Linux'}
                    </p>
                    
                    <div class="vps-status-row">
                        <span class="vps-status-badge {if $vmInfo.vm_status == 'running'}running{elseif $vmInfo.vm_status == 'stopped'}stopped{else}pending{/if}">
                            <i class="fas fa-circle"></i>
                            <span data-lang="status_{$vmInfo.vm_status|default:'unknown'}">{$vmInfo.vm_status|default:'Unknown'|ucfirst}</span>
                        </span>
                        
                        {if $vmInfo.vm_status == 'running' && isset($vmInfo.uptime)}
                        <div class="vps-uptime-info">
                            <i class="fas fa-clock"></i>
                            <span data-lang="uptime">Uptime:</span>
                            <span id="uptime-display">{$vmInfo.uptime|default:'N/A'}</span>
                        </div>
                        {/if}
                    </div>
                    
                     
                    {assign var="isVMLocked" value=($vmInfo.vm_status == 'rebuild' || $vmInfo.vm_status == 'rebuilding' || $vmInfo.vm_status == 'creating' || $vmInfo.vm_status == 'resetting' || $vmInfo.vm_status == 'shutdown' || $vmInfo.vm_status == 'stopping' || $vmInfo.vm_status == 'starting' || $vmInfo.vm_status == 'backup' || $vmInfo.vm_status == 'snapshot' || $vmInfo.vm_status == 'restore' || $vmInfo.vm_status == 'updating')}
                    <div class="vps-actions-row">
                    {if !$isVMLocked}
                        <div class="vps-power-controls">
                            {if $vmInfo.vm_status == 'running'}
                                <button class="vps-power-btn btn-danger" onclick="vmAction('stop')" data-action="stop">
                                    <i class="fas fa-stop"></i>
                                    <span data-lang="power_off">Power Off</span>
                                </button>
                                <button class="vps-power-btn btn-warning" onclick="vmAction('reboot')" data-action="reboot">
                                    <i class="fas fa-sync"></i>
                                    <span data-lang="reboot">Reboot</span>
                                </button>
                                <button class="vps-power-btn" onclick="vmAction('shutdown')" data-action="shutdown">
                                    <i class="fas fa-power-off"></i>
                                    <span data-lang="shutdown">Shutdown</span>
                                </button>
                                <button class="vps-power-btn" onclick="vmAction('reset')" data-action="reset">
                                    <i class="fas fa-bolt"></i>
                                    <span data-lang="hard_reset">Hard Reset</span>
                                </button>
                                <button class="vps-power-btn btn-primary" onclick="openConsole()" data-action="console" style="background: linear-gradient(135deg, #6366f1, #4f46e5); border-color: #4f46e5;">
                                    <i class="fas fa-terminal"></i>
                                    <span data-lang="console">Console</span>
                                </button>
                            {else}
                                <button class="vps-power-btn btn-success" onclick="vmAction('start')" data-action="start">
                                    <i class="fas fa-play"></i>
                                    <span data-lang="power_on">Power On</span>
                                </button>
                            {/if}
                        </div>
                    {else}
                        <div class="vps-power-controls" style="opacity: 0.5; pointer-events: none;">
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 8px; color: #94a3b8;">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>VM operation in progress...</span>
                            </div>
                        </div>
                    {/if}
                        <div class="vps-right-actions">
                            <div class="vps-cancel-request-wrap">
                                {if $hasCancelRequest}
                                <span class="vps-cancel-request-btn vps-cancel-request-received" data-lang="cancel_request_received">
                                    <i class="fas fa-check-circle"></i> <span data-lang="cancel_request_received">İptal talebiniz alındı</span>
                                </span>
                                {else}
                                <a href="clientarea.php?action=cancel&id={$serviceid}" class="vps-cancel-request-btn" data-lang="cancel_request">
                                    <i class="fas fa-times-circle"></i> <span data-lang="cancel_request">İptal Talebi</span>
                                </a>
                                {/if}
                            </div>
                            {if $configoptionsupgrade || $packagesupgrade}
                            <div class="vps-upgrade-controls">
                            {if $configoptionsupgrade}
                            <a href="upgrade.php?step=1&type=configoptions&id={$serviceid}" class="vps-upgrade-btn" title="Özellik Yükselt">
                                <i class="fas fa-sliders-h"></i>
                                <span data-lang="upgrade_features">Özellik Yükselt</span>
                            </a>
                            {/if}
                            {if $packagesupgrade}
                            <a href="upgrade.php?step=1&type=package&id={$serviceid}" class="vps-upgrade-btn" title="Paket Yükselt / Düşür">
                                <i class="fas fa-box-open"></i>
                                <span data-lang="upgrade_package">Paket Yükselt</span>
                            </a>
                            {/if}
                            </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
            
             
            <div class="vps-tabs">
                <div class="vps-tabs-left">
                    <button class="vps-tab active" data-tab="overview">
                        <i class="fas fa-th-large"></i>
                        <span data-lang="tab_overview">Overview</span>
                    </button>
                    <button class="vps-tab" data-tab="network">
                        <i class="fas fa-network-wired"></i>
                        <span data-lang="tab_network">Network</span>
                    </button>
                    <button class="vps-tab" data-tab="storage">
                        <i class="fas fa-hdd"></i>
                        <span data-lang="tab_storage">Storage</span>
                    </button>
                    <button class="vps-tab" data-tab="rebuild">
                        <i class="fas fa-sync-alt"></i>
                        <span data-lang="tab_rebuild">Rebuild</span>
                    </button>
                    <button class="vps-tab" data-tab="settings">
                        <i class="fas fa-cog"></i>
                        <span data-lang="tab_settings">Settings</span>
                    </button>
                </div>
                <div class="vps-tabs-right">
                    <button class="vps-sync-btn" onclick="syncVMData()" id="sync-vm-btn">
                        <i class="fas fa-sync"></i>
                        <span data-lang="sync_data">Sync Data</span>
                    </button>
                </div>
            </div>
            
             
            <div class="vps-tab-content active" id="tab-overview">
                 
                <svg width="0" height="0">
                    <defs>
                        <linearGradient id="gradient-cpu" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#6366f1" />
                            <stop offset="100%" stop-color="#8b5cf6" />
                        </linearGradient>
                        <linearGradient id="gradient-ram" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#10b981" />
                            <stop offset="100%" stop-color="#34d399" />
                        </linearGradient>
                        <linearGradient id="gradient-disk" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#f59e0b" />
                            <stop offset="100%" stop-color="#fbbf24" />
                        </linearGradient>
                    </defs>
                </svg>
                
                 
                <div class="vps-resource-grid">
                     
                    <div class="vps-resource-card card-cpu">
                        <div class="vps-resource-card-content">
                            <div class="vps-resource-ring">
                                <svg viewBox="0 0 36 36">
                                    <circle class="ring-bg" cx="18" cy="18" r="15.915"/>
                                    <circle class="ring-fill cpu" cx="18" cy="18" r="15.915" 
                                        stroke-dasharray="100, 100" 
                                        stroke-dashoffset="0"/>
                                </svg>
                                <div class="vps-resource-ring-icon cpu">
                                    <i class="fas fa-microchip"></i>
                                </div>
                            </div>
                            <div class="vps-resource-info">
                                <div class="vps-resource-label" data-lang="cpu_cores">CPU Cores</div>
                                <div class="vps-resource-value">{$vmInfo.cores|default:'N/A'}<span>vCPU</span></div>
                                <div class="vps-resource-subtitle" data-lang="processor">Processor</div>
                            </div>
                        </div>
                    </div>
                    
                     
                    <div class="vps-resource-card card-ram">
                        <div class="vps-resource-card-content">
                            <div class="vps-resource-ring">
                                <svg viewBox="0 0 36 36">
                                    <circle class="ring-bg" cx="18" cy="18" r="15.915"/>
                                    <circle class="ring-fill ram" cx="18" cy="18" r="15.915" 
                                        stroke-dasharray="100, 100" 
                                        stroke-dashoffset="0"/>
                                </svg>
                                <div class="vps-resource-ring-icon ram">
                                    <i class="fas fa-memory"></i>
                                </div>
                            </div>
                            <div class="vps-resource-info">
                                <div class="vps-resource-label" data-lang="memory">Memory</div>
                                {assign var="memoryGB" value={$vmInfo.memory|default:0}/1024}
                                <div class="vps-resource-value">{$memoryGB|string_format:"%.1f"}<span>GB</span></div>
                                <div class="vps-resource-subtitle">{$vmInfo.memory|default:'N/A'} MB RAM</div>
                            </div>
                        </div>
                    </div>
                    
                     
                    <div class="vps-resource-card card-disk">
                        <div class="vps-resource-card-content">
                            <div class="vps-resource-ring">
                                <svg viewBox="0 0 36 36">
                                    <circle class="ring-bg" cx="18" cy="18" r="15.915"/>
                                    <circle class="ring-fill disk" cx="18" cy="18" r="15.915" 
                                        stroke-dasharray="100, 100" 
                                        stroke-dashoffset="0"/>
                                </svg>
                                <div class="vps-resource-ring-icon disk">
                                    <i class="fas fa-hdd"></i>
                                </div>
                            </div>
                            <div class="vps-resource-info">
                                <div class="vps-resource-label" data-lang="storage">Storage</div>
                                <div class="vps-resource-value">{$vmInfo.disk|default:'N/A'}<span>GB</span></div>
                                <div class="vps-resource-subtitle" data-lang="nvme_ssd">NVMe SSD</div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                 
                <div class="vps-quick-stats" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="vps-stat-item">
                        <div class="vps-stat-icon ip">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="vps-stat-content">
                            <div class="vps-stat-label">IPv4</div>
                            <div class="vps-stat-value">{$vmInfo.ip_address|default:$dedicatedip|default:'N/A'}</div>
                        </div>
                        <button class="vps-stat-copy" onclick="copyToClipboard('{$vmInfo.ip_address|default:$dedicatedip}', this)" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    
                    <div class="vps-stat-item">
                        <div class="vps-stat-icon" style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5;">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <div class="vps-stat-content">
                            <div class="vps-stat-label">IPv6</div>
                            <div class="vps-stat-value">{$vmInfo.ipv6|default:'N/A'}</div>
                        </div>
                        {if $vmInfo.ipv6}
                        <button class="vps-stat-copy" onclick="copyToClipboard('{$vmInfo.ipv6}', this)" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                        {/if}
                    </div>
                    
                    <div class="vps-stat-item">
                        <div class="vps-stat-icon uptime">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="vps-stat-content">
                            <div class="vps-stat-label" data-lang="status">Status</div>
                            <div class="vps-stat-value" style="color:{if $vmInfo.vm_status == 'running'}#10b981{else}#ef4444{/if};">
                                {if $vmInfo.vm_status == 'running'}● Online{else}○ Offline{/if}
                            </div>
                        </div>
                    </div>
                </div>
                
                 
                {assign var="isWindows" value=($vmInfo.template|strpos:'Windows' !== false)}
                <div class="vps-os-banner">
                    <div class="vps-os-banner-content">
                        <div class="vps-os-banner-icon {if $isWindows}windows{else}linux{/if}">
                            {if $isWindows}
                                <i class="fab fa-windows"></i>
                            {elseif $vmInfo.template|strpos:'Ubuntu' !== false}
                                <i class="fab fa-ubuntu"></i>
                            {elseif $vmInfo.template|strpos:'Debian' !== false}
                                <i class="fab fa-linux"></i>
                            {elseif $vmInfo.template|strpos:'CentOS' !== false}
                                <i class="fab fa-centos"></i>
                            {elseif $vmInfo.template|strpos:'Alma' !== false}
                                <i class="fab fa-redhat"></i>
                            {elseif $vmInfo.template|strpos:'Rocky' !== false}
                                <i class="fab fa-fedora"></i>
                            {else}
                                <i class="fab fa-linux"></i>
                            {/if}
                        </div>
                        <div class="vps-os-banner-info">
                            <h4>{$vmInfo.template|default:'Operating System'}</h4>
                            <div class="os-type">
                                <span class="os-badge {if $isWindows}windows{else}linux{/if}">
                                    {if $isWindows}
                                        <i class="fab fa-windows"></i> Windows
                                    {else}
                                        <i class="fab fa-linux"></i> Linux
                                    {/if}
                                </span>
                                <span data-lang="os_template">Operating System</span>
                            </div>
                        </div>
                    </div>
                    <a href="javascript:void(0)" class="vps-os-banner-rebuild" onclick="document.querySelector('[data-tab=rebuild]').click()">
                        <i class="fas fa-sync-alt"></i>
                        <span data-lang="rebuild">Rebuild</span>
                    </a>
                </div>
                
                 
                <div class="vps-access-card">
                    <div class="vps-access-header">
                        {if $isWindows}
                            <i class="fas fa-desktop"></i>
                            <h3>Remote Desktop (RDP)</h3>
                        {else}
                            <i class="fas fa-terminal"></i>
                            <h3 data-lang="access_credentials">Access Credentials</h3>
                        {/if}
                    </div>
                    
                    <div class="vps-access-grid">
                        <div class="vps-access-item">
                            <div class="vps-access-item-label" data-lang="username">Username</div>
                            <div class="vps-access-item-value">
                                {if $isWindows}
                                    <code>Administrator</code>
                                    <button class="vps-access-btn" onclick="copyToClipboard('Administrator', this)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                {else}
                                    <code>{$vmInfo.username|default:'root'}</code>
                                    <button class="vps-access-btn" onclick="copyToClipboard('{$vmInfo.username|default:'root'}', this)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                {/if}
                            </div>
                        </div>
                        
                        <div class="vps-access-item">
                            <div class="vps-access-item-label" data-lang="password">Password</div>
                            <div class="vps-access-item-value">
                                <code class="password-mask">
                                    <span id="password-hidden-new">••••••••</span>
                                    <span id="password-visible-new" style="display:none;">{$vmInfo.password|default:''}</span>
                                </code>
                                <button class="vps-access-btn" onclick="togglePasswordNew()">
                                    <i class="fas fa-eye" id="password-icon-new"></i>
                                </button>
                                <button class="vps-access-btn" onclick="copyPasswordNew(this)">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vps-ssh-command">
                            {if $isWindows}
                            <i class="fas fa-desktop" style="color:#60a5fa;"></i>
                            <code>mstsc /v:{$vmInfo.ip_address|default:$dedicatedip}</code>
                            <span style="color:#94a3b8;font-size:0.8rem;margin-left:8px;">(User: Administrator)</span>
                            <button class="vps-access-btn" onclick="copyToClipboard('mstsc /v:{$vmInfo.ip_address|default:$dedicatedip}', this)" title="Copy RDP Command">
                                <i class="fas fa-copy"></i>
                            </button>
                        {else}
                            <i class="fas fa-terminal"></i>
                            <code>ssh {$vmInfo.username|default:'root'}@{$vmInfo.ip_address|default:$dedicatedip}</code>
                            <button class="vps-access-btn" onclick="copyToClipboard('ssh {$vmInfo.username|default:'root'}@{$vmInfo.ip_address|default:$dedicatedip}', this)" title="Copy SSH Command">
                                <i class="fas fa-copy"></i>
                            </button>
                        {/if}
                    </div>
                </div>
            </div>
            
             
            <div class="vps-tab-content" id="tab-network">
                <div class="vps-card">
                    <div class="vps-card-header" style="display:flex;justify-content:space-between;align-items:center;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div class="vps-card-icon icon-network">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            <h3 class="vps-card-title" data-lang="network_interfaces">Network Interfaces</h3>
                        </div>
                        <button class="vps-btn vps-btn-primary" onclick="openAddNetworkModal()" style="padding:8px 16px;font-size:0.85rem;">
                            <i class="fas fa-plus"></i>
                            <span data-lang="add_interface">Add Interface</span>
                        </button>
                    </div>
                    
                    <div id="network-interfaces-list">
                        {if isset($vmInfo.interfaces) && $vmInfo.interfaces|@count > 0}
                            {foreach from=$vmInfo.interfaces key=ifaceName item=iface}
                            <div class="vps-interface" style="background:#f8fafc;border-radius:12px;padding:20px;margin-bottom:15px;border-left:4px solid {if $ifaceName == 'net0'}#6366f1{else}#10b981{/if};">
                                <div class="vps-interface-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                                    <span class="vps-interface-name" style="font-weight:600;font-size:1.1rem;color:#1e293b;">
                                        <i class="fas fa-ethernet" style="margin-right:8px;color:{if $ifaceName == 'net0'}#6366f1{else}#10b981{/if};"></i>
                                        {$ifaceName}
                                        {if $ifaceName == 'net0'}
                                        <span class="vps-interface-badge" style="margin-left:10px;padding:4px 12px;background:#6366f1;color:white;border-radius:20px;font-size:0.75rem;font-weight:600;">Primary</span>
                                        {/if}
                                    </span>
                                    <span style="font-size:0.8rem;color:#64748b;">
                                        <i class="fas fa-microchip"></i> {$iface.model|default:'virtio'}
                                        &nbsp;|&nbsp;
                                        <i class="fas fa-network-wired"></i> {$iface.bridge|default:'vmbr0'}
                                    </span>
                                </div>
                                
                                <div class="vps-ip-list">
                                    {if $iface.ip && count($iface.ip) > 0}
                                        {foreach from=$iface.ip item=ipData}
                                        <div class="vps-ip-card" style="background:white;border-radius:10px;margin-bottom:10px;border:1px solid #e5e7eb;overflow:hidden;">
                                            <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:linear-gradient(135deg, {if $ipData.type == 'ipv6'}#8b5cf6, #7c3aed{else}#3b82f6, #1d4ed8{/if});color:white;">
                                                <span style="font-family:monospace;font-size:0.95rem;font-weight:600;flex:1;">{$ipData.ipaddress|default:$ipData.ip}</span>
                                                <span style="background:rgba(255,255,255,0.2);padding:3px 8px;border-radius:4px;font-size:0.7rem;font-weight:600;text-transform:uppercase;">{$ipData.type|default:'ipv4'}</span>
                                                <button class="vps-access-btn" onclick="copyToClipboard('{$ipData.ipaddress|default:$ipData.ip}', this)" title="Copy IP" style="background:rgba(255,255,255,0.2);border:none;color:white;padding:5px 8px;border-radius:5px;cursor:pointer;">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            <div style="padding:10px 14px;display:grid;grid-template-columns:repeat(3, 1fr);gap:8px;font-size:0.85rem;">
                                                <div>
                                                    <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;">Gateway</div>
                                                    <div style="font-family:monospace;color:#1e293b;">{$ipData.gateway|default:'-'}</div>
                                                </div>
                                                <div>
                                                    <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;">Mask</div>
                                                    <div style="font-family:monospace;color:#1e293b;">{$ipData.mask|default:'-'}</div>
                                                </div>
                                                <div>
                                                    <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;">Network</div>
                                                    <div style="font-family:monospace;color:#1e293b;">{$ipData.network|default:'-'}</div>
                                                </div>
                                            </div>
                                        </div>
                                        {/foreach}
                                    {else}
                                        <div style="padding:15px;background:white;border-radius:8px;color:#64748b;text-align:center;border:1px dashed #d1d5db;">
                                            <i class="fas fa-info-circle"></i> <span data-lang="no_ip_assigned">No IP addresses assigned to this interface</span>
                                        </div>
                                    {/if}
                                </div>
                                
                                <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;font-size:0.8rem;color:#64748b;display:flex;justify-content:space-between;align-items:center;">
                                    <span>
                                        <i class="fas fa-barcode"></i> MAC: <span style="font-family:monospace;">{$iface.mac|default:'-'}</span>
                                    </span>
                                    {if $ifaceName != 'net0'}
                                    <button class="vps-btn vps-btn-danger" onclick="deleteNetworkInterface('{$ifaceName}')" style="padding:6px 12px;font-size:0.8rem;background:#ef4444;color:white;border:none;border-radius:6px;cursor:pointer;">
                                        <i class="fas fa-trash"></i> <span data-lang="delete">Delete</span>
                                    </button>
                                    {/if}
                                </div>
                            </div>
                            {/foreach}
                        {else}
                             
                            <div class="vps-interface" style="background:#f8fafc;border-radius:12px;padding:20px;border-left:4px solid #6366f1;">
                                <div class="vps-interface-header" style="margin-bottom:15px;">
                                    <span style="font-weight:600;font-size:1.1rem;color:#1e293b;">
                                        <i class="fas fa-ethernet" style="margin-right:8px;color:#6366f1;"></i>
                                        net0
                                        <span style="margin-left:10px;padding:4px 12px;background:#6366f1;color:white;border-radius:20px;font-size:0.75rem;">Primary</span>
                                    </span>
                                </div>
                                <div class="vps-ip-list">
                                    {if $vmInfo.ip_address}
                                    <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:white;border-radius:8px;margin-bottom:8px;border:1px solid #e5e7eb;">
                                        <span style="font-family:monospace;font-size:0.95rem;color:#1e293b;flex:1;">{$vmInfo.ip_address}</span>
                                        <span style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);color:white;padding:4px 10px;border-radius:5px;font-size:0.75rem;font-weight:600;">IPv4</span>
                                        <button onclick="copyToClipboard('{$vmInfo.ip_address}', this)" style="background:transparent;border:1px solid #d1d5db;color:#64748b;padding:6px 10px;border-radius:6px;cursor:pointer;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    {/if}
                                    {if $vmInfo.ipv6_address}
                                    <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:white;border-radius:8px;border:1px solid #e5e7eb;">
                                        <span style="font-family:monospace;font-size:0.95rem;color:#1e293b;flex:1;">{$vmInfo.ipv6_address}</span>
                                        <span style="background:linear-gradient(135deg, #8b5cf6, #7c3aed);color:white;padding:4px 10px;border-radius:5px;font-size:0.75rem;font-weight:600;">IPv6</span>
                                        <button onclick="copyToClipboard('{$vmInfo.ipv6_address}', this)" style="background:transparent;border:1px solid #d1d5db;color:#64748b;padding:6px 10px;border-radius:6px;cursor:pointer;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    {/if}
                                </div>
                            </div>
                        {/if}
                    </div>
                    
                    <div style="margin-top:20px;">
                        <h4 data-lang="reverse_dns" style="margin-bottom:15px;">Reverse DNS (rDNS)</h4>
                        
                        {if $vmInfo.all_rdns && count($vmInfo.all_rdns) > 0}
                            {foreach from=$vmInfo.all_rdns key=ip item=rdnsData}
                            <div class="vps-rdns-item" style="display:flex;gap:10px;align-items:center;margin-bottom:12px;padding:12px;background:#f8fafc;border-radius:10px;border:1px solid #e5e7eb;">
                                <div style="min-width:140px;">
                                    <span class="vps-ip-badge" style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);color:white;padding:6px 12px;border-radius:6px;font-family:monospace;font-size:0.85rem;">{$ip}</span>
                                </div>
                                <input type="text" class="rdns-input form-control" data-ip="{$ip}" placeholder="hostname.example.com" value="{$rdnsData.rdns|default:''}" style="flex:1;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:0.9rem;">
                                <button class="vps-btn vps-btn-primary" onclick="updateRDNS('{$ip}')" style="padding:10px 16px;">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                            {/foreach}
                        {else}
                             
                            <div class="vps-rdns-item" style="display:flex;gap:10px;align-items:center;margin-bottom:12px;padding:12px;background:#f8fafc;border-radius:10px;border:1px solid #e5e7eb;">
                                <div style="min-width:140px;">
                                    <span class="vps-ip-badge" style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);color:white;padding:6px 12px;border-radius:6px;font-family:monospace;font-size:0.85rem;">{$vmInfo.ip_address}</span>
                                </div>
                                <input type="text" id="rdns-input" class="rdns-input form-control" data-ip="{$vmInfo.ip_address}" placeholder="hostname.example.com" value="{$vmInfo.rdns|default:''}" style="flex:1;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:0.9rem;">
                                <button class="vps-btn vps-btn-primary" onclick="updateRDNS('{$vmInfo.ip_address}')" style="padding:10px 16px;">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                        {/if}
                        
                        <p style="margin-top:10px;font-size:0.85rem;color:#64748b;">
                            <i class="fas fa-info-circle"></i> 
                            <span data-lang="rdns_help">rDNS records may take up to 24 hours to propagate globally.</span>
                        </p>
                    </div>
                </div>
            </div>
            
             
            <div class="vps-tab-content" id="tab-storage">
                <div class="vps-card">
                    <div class="vps-card-header">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div class="vps-card-icon" style="background:linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <h3 class="vps-card-title" data-lang="storage_disks">Storage Disks</h3>
                        </div>
                    </div>
                    
                    <div id="storage-disks-list">
                        {if isset($vmInfo.storage) && $vmInfo.storage|@count > 0}
                            {foreach from=$vmInfo.storage key=diskKey item=disk}
                            <div class="vps-disk-item" style="background:#f8fafc;border-radius:12px;padding:20px;margin-bottom:15px;border-left:4px solid {if $disk.bootable}#6366f1{else}#10b981{/if};">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div style="width:48px;height:48px;background:linear-gradient(135deg, {if $disk.bootable}#6366f1, #4f46e5{else}#10b981, #059669{/if});border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-{if $disk.cdrom}compact-disc{else}hdd{/if}" style="color:white;font-size:1.3rem;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:1.1rem;color:#1e293b;">
                                                {$disk.name|default:$diskKey}
                                                {if $disk.bootable}
                                                <span style="margin-left:8px;padding:3px 10px;background:#6366f1;color:white;border-radius:15px;font-size:0.7rem;font-weight:600;">Boot Disk</span>
                                                {/if}
                                                {if $disk.cdrom}
                                                <span style="margin-left:8px;padding:3px 10px;background:#f59e0b;color:white;border-radius:15px;font-size:0.7rem;font-weight:600;">CD-ROM</span>
                                                {/if}
                                                {if $disk.cloudinit}
                                                <span style="margin-left:8px;padding:3px 10px;background:#06b6d4;color:white;border-radius:15px;font-size:0.7rem;font-weight:600;">CloudInit</span>
                                                {/if}
                                            </div>
                                            <div style="font-size:0.85rem;color:#64748b;">{$disk.type|default:'disk'} | {$disk.format|default:'raw'}</div>
                                        </div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:1.8rem;font-weight:700;color:#1e293b;">
                                            {$disk.size_gb|default:0}<span style="font-size:0.9rem;color:#64748b;font-weight:400;">GB</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:15px;padding-top:15px;border-top:1px solid #e5e7eb;">
                                    <div>
                                        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Zone</div>
                                        <div style="font-weight:600;color:#1e293b;">
                                            <i class="fas fa-server" style="color:#6366f1;margin-right:6px;"></i>{$disk.zone|default:'Standard'}
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Storage</div>
                                        <div style="font-weight:600;color:#1e293b;">
                                            <i class="fas fa-database" style="color:#10b981;margin-right:6px;"></i>{$disk.storage|default:'default'}
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Mount Point</div>
                                        <div style="font-family:monospace;color:#1e293b;">{$disk.mp|default:'/'}</div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Status</div>
                                        <div>
                                            {if $disk.locked}
                                            <span style="padding:4px 10px;background:#ef4444;color:white;border-radius:15px;font-size:0.75rem;font-weight:600;">
                                                <i class="fas fa-lock"></i> Locked
                                            </span>
                                            {else}
                                            <span style="padding:4px 10px;background:#10b981;color:white;border-radius:15px;font-size:0.75rem;font-weight:600;">
                                                <i class="fas fa-check"></i> Active
                                            </span>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                                
                                {if $disk.volid}
                                <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;font-size:0.8rem;color:#64748b;">
                                    <i class="fas fa-fingerprint"></i> Volume ID: <span style="font-family:monospace;">{$disk.volid}</span>
                                </div>
                                {/if}
                            </div>
                            {/foreach}
                        {else}
                            <div style="padding:40px;text-align:center;color:#64748b;">
                                <i class="fas fa-hdd" style="font-size:3rem;color:#d1d5db;margin-bottom:15px;"></i>
                                <p data-lang="no_storage_info">No storage information available</p>
                            </div>
                        {/if}
                    </div>
                    
                    <div style="margin-top:20px;padding:15px;background:linear-gradient(135deg, #f0f9ff, #e0f2fe);border-radius:10px;border:1px solid #bae6fd;">
                        <div style="display:flex;align-items:center;gap:10px;color:#0369a1;">
                            <i class="fas fa-info-circle"></i>
                            <span data-lang="storage_info">Storage information is synchronized from your VPS. Use "Sync Data" to refresh.</span>
                        </div>
                    </div>
                </div>
            </div>
            
             
            <div class="vps-tab-content" id="tab-rebuild">
                <div class="vps-alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="vps-alert-content">
                        <h4 data-lang="rebuild_warning">Warning</h4>
                        <p data-lang="rebuild_warning_desc">Rebuilding will erase all data on your VPS. This action cannot be undone. Please backup your data before proceeding.</p>
                    </div>
                </div>
                
                <div class="vps-card">
                    <div class="vps-card-header">
                        <div class="vps-card-icon icon-rebuild">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h3 class="vps-card-title" data-lang="select_os">Select Operating System</h3>
                    </div>
                    
                    <div class="vps-template-grid" id="os-templates" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                        {if $linuxFamilies|count > 0 || $windowsFamilies|count > 0}
                             
                            {if $linuxFamilies|count > 0}
                            <div style="grid-column:1/-1;margin-bottom:5px;">
                                <h4 style="color:var(--vps-gray);font-size:0.85rem;margin:0;display:flex;align-items:center;gap:8px;text-transform:uppercase;letter-spacing:0.5px;">
                                    <i class="fab fa-linux"></i> Linux
                                </h4>
                            </div>
                            {foreach $linuxFamilies as $familyKey => $family}
                            <div class="vps-os-family-card" data-family="{$familyKey}" onclick="selectOSFamily(this)">
                                <div class="vps-os-family-icon" style="background: linear-gradient(135deg, {$family.color}20, {$family.color}10);">
                                    <i class="{$family.icon}" style="color:{$family.color};font-size:1.8rem;"></i>
                                </div>
                                <div class="vps-os-family-info">
                                    <div class="vps-os-family-name">{$family.name}</div>
                                    <select class="vps-os-version-select" onchange="selectVersion(this)" onclick="event.stopPropagation()">
                                        <option value="">Select version...</option>
                                        {foreach $family.versions as $version}
                                        <option value="{$version.id}">{$version.name}</option>
                                        {/foreach}
                                    </select>
                                </div>
                                <div class="vps-os-family-check"><i class="fas fa-check-circle"></i></div>
                            </div>
                            {/foreach}
                            {/if}
                            
                             
                            {if $windowsFamilies|count > 0}
                            <div style="grid-column:1/-1;margin:15px 0 5px 0;">
                                <h4 style="color:var(--vps-gray);font-size:0.85rem;margin:0;display:flex;align-items:center;gap:8px;text-transform:uppercase;letter-spacing:0.5px;">
                                    <i class="fab fa-windows" style="color:#0078D4;"></i> Windows
                                </h4>
                            </div>
                            {foreach $windowsFamilies as $familyKey => $family}
                            <div class="vps-os-family-card" data-family="{$familyKey}" onclick="selectOSFamily(this)">
                                <div class="vps-os-family-icon" style="background: linear-gradient(135deg, {$family.color}20, {$family.color}10);">
                                    <i class="{$family.icon}" style="color:{$family.color};font-size:1.8rem;"></i>
                                </div>
                                <div class="vps-os-family-info">
                                    <div class="vps-os-family-name">{$family.name}</div>
                                    <select class="vps-os-version-select" onchange="selectVersion(this)" onclick="event.stopPropagation()">
                                        <option value="">Select version...</option>
                                        {foreach $family.versions as $version}
                                        <option value="{$version.id}">{$version.name}</option>
                                        {/foreach}
                                    </select>
                                </div>
                                <div class="vps-os-family-check"><i class="fas fa-check-circle"></i></div>
                            </div>
                            {/foreach}
                            {/if}
                        {else}
                            <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--vps-gray);">
                                <i class="fas fa-cloud-download-alt" style="font-size:3rem;margin-bottom:15px;opacity:0.5;"></i>
                                <p>No templates available. Please contact support.</p>
                            </div>
                        {/if}
                    </div>
                    
                    <input type="hidden" id="selected-template-id" value="">
                    
                    <div style="margin-top:25px;padding-top:20px;border-top:1px solid var(--vps-border);">
                        <label style="display:flex;align-items:center;gap:10px;margin-bottom:20px;cursor:pointer;">
                            <input type="checkbox" id="rebuild-confirm" style="width:20px;height:20px;">
                            <span data-lang="rebuild_confirm">I understand that all data will be permanently deleted</span>
                        </label>
                        <button class="vps-btn vps-btn-danger" onclick="rebuildVM()" id="rebuild-btn" disabled>
                            <i class="fas fa-sync-alt"></i>
                            <span data-lang="rebuild_now">Rebuild Now</span>
                        </button>
                    </div>
                </div>
            </div>
            
             
            <div class="vps-tab-content" id="tab-settings">
                <div class="vps-card">
                    <div class="vps-card-header">
                        <div class="vps-card-icon icon-server">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h3 class="vps-card-title" data-lang="server_settings">Server Settings</h3>
                    </div>
                    
                    <div class="vps-info-list">
                        <div class="vps-info-item">
                            <span class="vps-info-label" data-lang="server_label">Server Label</span>
                            <span class="vps-info-value" style="flex:1;">
                                <input type="text" id="server-label" class="form-control" value="{$vmInfo.label|default:$domain}" style="width:100%;max-width:300px;padding:10px;border-radius:8px;border:1px solid var(--vps-border);">
                            </span>
                            <button class="vps-btn vps-btn-outline" onclick="updateLabel()">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                        <div class="vps-info-item">
                            <span class="vps-info-label" data-lang="wls_service_id">WLS Service ID</span>
                            <span class="vps-info-value">{$vmInfo.wls_service_id|default:'N/A'}</span>
                        </div>
                        <div class="vps-info-item">
                            <span class="vps-info-label" data-lang="order_number">Order Number</span>
                            <span class="vps-info-value">{$vmInfo.order_number|default:'N/A'}</span>
                        </div>
                    </div>
                </div>
            </div>
            
             
            <button class="vps-refresh-btn" onclick="refreshData()" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        {/if}
    </div>

    <script>
  $('.primary-content').removeClass('col-lg-8').addClass('col-lg-12');
    $('.primary-content').removeClass('col-xl-9').addClass('col-xl-12');

         
        var translations = {
            en: {
                 
                service_terminated: 'Service Terminated',
                service_terminated_desc: 'This VPS service has been terminated. Please contact support to reactivate.',
                service_suspended: 'Service Suspended',
                service_suspended_desc: 'This VPS service has been suspended. Please check your payment status.',
                order_processing: 'Order Processing',
                preparing_vps: 'Preparing Your VPS',
                preparing_vps_desc: 'Your VPS is being provisioned. This usually takes 2-5 minutes. The page will refresh automatically.',
                
                 
                status_running: 'Running',
                status_stopped: 'Stopped',
                status_pending: 'Pending',
                status_unknown: 'Unknown',
                uptime: 'Uptime:',
                
                 
                power_on: 'Power On',
                power_off: 'Power Off',
                reboot: 'Reboot',
                shutdown: 'Shutdown',
                hard_reset: 'Hard Reset',
                
                 
                tab_overview: 'Overview',
                tab_network: 'Network',
                tab_rebuild: 'Rebuild',
                tab_settings: 'Settings',
                
                 
                server_info: 'Server Information',
                access_info: 'Access Information',
                network_interfaces: 'Network Interfaces',
                
                 
                cpu_cores: 'CPU Cores',
                memory: 'Memory',
                disk: 'Disk',
                os_template: 'OS Template',
                ip_address: 'IP Address',
                username: 'Username',
                password: 'Password',
                ssh_command: 'SSH Command',
                reverse_dns: 'Reverse DNS (rDNS)',
                save: 'Save',
                cancel_request: 'Cancellation Request',
                cancel_request_received: 'Your cancellation request has been received',
                
                 
                rebuild_warning: 'Warning',
                rebuild_warning_desc: 'Rebuilding will erase all data on your VPS. This action cannot be undone. Please backup your data before proceeding.',
                select_os: 'Select Operating System',
                rebuild_confirm: 'I understand that all data will be permanently deleted',
                rebuild_now: 'Rebuild Now',
                
                 
                server_settings: 'Server Settings',
                server_label: 'Server Label',
                wls_service_id: 'WLS Service ID',
                order_number: 'Order Number',
                
                 
                copied: 'Copied!',
                action_success: 'Action completed successfully',
                action_error: 'An error occurred',
                confirm_action: 'Are you sure?',
                confirm: 'Confirm',
                cancel: 'Cancel',
                console: 'Console',
                console_opening: 'Opening console...',
                console_error: 'Could not open console',
                hostname_updated: 'Hostname updated successfully',
                sync_success: 'Data synchronized!',
                sync_failed: 'Sync failed',
                power_confirm_stop: 'Power off will immediately stop your server.',
                power_confirm_reboot: 'Reboot will restart your server.',
                power_confirm_shutdown: 'Shutdown will gracefully stop your server.',
                power_confirm_reset: 'Hard reset will forcefully restart your server.',
                power_confirm_start: 'Start will power on your server.'
            },
            tr: {
                 
                service_terminated: 'Hizmet Sonlandırıldı',
                service_terminated_desc: 'Bu VPS hizmeti sonlandırılmıştır. Yeniden aktive etmek için destek ekibimizle iletişime geçin.',
                service_suspended: 'Hizmet Askıya Alındı',
                service_suspended_desc: 'Bu VPS hizmeti askıya alınmıştır. Lütfen ödeme durumunuzu kontrol edin.',
                order_processing: 'Sipariş İşleniyor',
                preparing_vps: 'VPS\'iniz Hazırlanıyor',
                preparing_vps_desc: 'VPS\'iniz oluşturuluyor. Bu işlem genellikle 2-5 dakika sürer. Sayfa otomatik olarak yenilenecek.',
                
                 
                status_running: 'Çalışıyor',
                status_stopped: 'Durduruldu',
                status_pending: 'Bekliyor',
                status_unknown: 'Bilinmiyor',
                uptime: 'Çalışma Süresi:',
                
                 
                power_on: 'Başlat',
                power_off: 'Durdur',
                reboot: 'Yeniden Başlat',
                shutdown: 'Güvenli Kapat',
                hard_reset: 'Sert Reset',
                
                 
                tab_overview: 'Genel Bakış',
                tab_network: 'Ağ',
                tab_rebuild: 'Yeniden Yükle',
                tab_settings: 'Ayarlar',
                sync_data: 'Verileri Senkronize Et',
                
                 
                server_info: 'Sunucu Bilgileri',
                access_info: 'Erişim Bilgileri',
                network_interfaces: 'Ağ Arayüzleri',
                
                 
                cpu_cores: 'CPU Çekirdek',
                memory: 'Bellek',
                disk: 'Disk',
                os_template: 'İşletim Sistemi',
                ip_address: 'IP Adresi',
                username: 'Kullanıcı Adı',
                password: 'Şifre',
                ssh_command: 'SSH Komutu',
                reverse_dns: 'Ters DNS (rDNS)',
                save: 'Kaydet',
                cancel_request: 'İptal Talebi',
                cancel_request_received: 'İptal talebiniz alındı',
                
                 
                rebuild_warning: 'Uyarı',
                rebuild_warning_desc: 'Yeniden yükleme VPS\'inizdeki tüm verileri silecektir. Bu işlem geri alınamaz. Lütfen devam etmeden önce verilerinizi yedekleyin.',
                select_os: 'İşletim Sistemi Seçin',
                rebuild_confirm: 'Tüm verilerin kalıcı olarak silineceğini anlıyorum',
                rebuild_now: 'Şimdi Yeniden Yükle',
                
                 
                server_settings: 'Sunucu Ayarları',
                server_label: 'Sunucu Etiketi',
                wls_service_id: 'WLS Servis ID',
                order_number: 'Sipariş Numarası',
                
                 
                copied: 'Kopyalandı!',
                action_success: 'İşlem başarıyla tamamlandı',
                action_error: 'Bir hata oluştu',
                confirm_action: 'Emin misiniz?',
                confirm: 'Onayla',
                cancel: 'İptal',
                console: 'Konsol',
                console_opening: 'Konsol açılıyor...',
                console_error: 'Konsol açılamadı',
                hostname_updated: 'Hostname başarıyla güncellendi',
                sync_success: 'Veriler senkronize edildi!',
                sync_failed: 'Senkronizasyon başarısız',
                power_confirm_stop: 'Durdurma işlemi sunucunuzu anında kapatır.',
                power_confirm_reboot: 'Yeniden başlatma sunucunuzu restart eder.',
                power_confirm_shutdown: 'Güvenli kapatma sunucunuzu düzgün şekilde kapatır.',
                power_confirm_reset: 'Sert reset sunucunuzu zorla yeniden başlatır.',
                power_confirm_start: 'Başlatma sunucunuzu açar.'
            }
        };
        
        var currentLang = localStorage.getItem('vps_lang') || 'en';
        var serviceId = {$serviceid};
        var csrfToken = '{$token}';
        var selectedTemplate = null;
        var vmStatusPollingInterval = null;
        var currentVMStatus = '{$vmInfo.vm_status|default:"unknown"}';
        
         
        var lockedStates = ['rebuild', 'rebuilding', 'creating', 'resetting', 'reset', 'rebooting', 'shutdown', 'stopping', 'starting', 'backup', 'snapshot', 'restore', 'updating'];
        
         
        
         
        function showLoading() {
            var overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.add('active');
            }
        }
        
         
        function hideLoading() {
            var overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
        
         
        function showNotification(message, type) {
            type = type || 'info';
            
             
            var existing = document.querySelector('.vps-notification');
            if (existing) existing.remove();
            
             
            var notification = document.createElement('div');
            notification.className = 'vps-notification vps-notification-' + type;
            notification.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle') + '"></i> ' + message;
            
             
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 8px; color: white; font-weight: 500; z-index: 10000; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); animation: slideIn 0.3s ease;';
            
            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            } else if (type === 'error') {
                notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            } else {
                notification.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
            }
            
            document.body.appendChild(notification);
            
             
            setTimeout(function() {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100px)';
                notification.style.transition = 'all 0.3s ease';
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 4000);
        }
        
         
        document.addEventListener('DOMContentLoaded', function() {
            setLanguage(currentLang);
            initTabs();
            initTemplateSelection();
            initRebuildConfirm();
            
             
            checkInitialVMLock();
        });
        
         
        function setLanguage(lang) {
            currentLang = lang;
            localStorage.setItem('vps_lang', lang);
            
             
            document.querySelectorAll('.vps-lang-btn').forEach(function(btn) {
                btn.classList.remove('active');
                if (btn.getAttribute('data-lang-switch') === lang) {
                    btn.classList.add('active');
                }
            });
            
             
            document.querySelectorAll('[data-lang]').forEach(function(el) {
                var key = el.getAttribute('data-lang');
                if (translations[lang] && translations[lang][key]) {
                    el.textContent = translations[lang][key];
                }
            });
        }
        
        document.querySelectorAll('.vps-lang-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                setLanguage(this.getAttribute('data-lang-switch'));
            });
        });
        
         
        var templatesLoaded = false;  
        
        function initTabs() {
            document.querySelectorAll('.vps-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var tabName = this.getAttribute('data-tab');
                    
                     
                    document.querySelectorAll('.vps-tab').forEach(function(t) { t.classList.remove('active'); });
                    this.classList.add('active');
                    
                     
                    document.querySelectorAll('.vps-tab-content').forEach(function(c) { c.classList.remove('active'); });
                    document.getElementById('tab-' + tabName).classList.add('active');
                    
                     
                    if (tabName === 'rebuild' && !templatesLoaded) {
                        loadTemplates();
                    }
                });
            });
        }
        
         
        function loadTemplates() {
            var container = document.getElementById('os-templates');
            if (!container) return;
            
             
            container.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--vps-primary);"></i><br><br>Loading templates...</div>';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            templatesLoaded = true;
                            renderTemplates(response.linux || {}, response.windows || {});
                        } else {
                            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Failed to load templates</div>';
                        }
                    } catch (e) {
                        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading templates</div>';
                    }
                }
            };
            
            xhr.send('customAction=getTemplates&token=' + csrfToken + '&ajax=1');
        }
        
         
        function renderTemplates(linuxFamilies, windowsFamilies) {
            var container = document.getElementById('os-templates');
            if (!container) return;
            
            var html = '';
            
             
            var linuxCount = Object.keys(linuxFamilies).length;
            if (linuxCount > 0) {
                html += '<div style="grid-column:1/-1;margin-bottom:5px;">';
                html += '<h4 style="color:var(--vps-gray);font-size:0.85rem;margin:0;display:flex;align-items:center;gap:8px;text-transform:uppercase;letter-spacing:0.5px;">';
                html += '<i class="fab fa-linux"></i> Linux';
                html += '</h4></div>';
                
                for (var familyKey in linuxFamilies) {
                    var family = linuxFamilies[familyKey];
                    html += renderFamilyCard(familyKey, family);
                }
            }
            
             
            var windowsCount = Object.keys(windowsFamilies).length;
            if (windowsCount > 0) {
                html += '<div style="grid-column:1/-1;margin:15px 0 5px 0;">';
                html += '<h4 style="color:var(--vps-gray);font-size:0.85rem;margin:0;display:flex;align-items:center;gap:8px;text-transform:uppercase;letter-spacing:0.5px;">';
                html += '<i class="fab fa-windows" style="color:#0078D4;"></i> Windows';
                html += '</h4></div>';
                
                for (var familyKey in windowsFamilies) {
                    var family = windowsFamilies[familyKey];
                    html += renderFamilyCard(familyKey, family);
                }
            }
            
            if (linuxCount === 0 && windowsCount === 0) {
                html = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--vps-gray);">';
                html += '<i class="fas fa-cloud-download-alt" style="font-size:3rem;margin-bottom:15px;opacity:0.5;"></i>';
                html += '<p>No templates available. Please contact support.</p>';
                html += '</div>';
            }
            
            container.innerHTML = html;
        }
        
         
        function renderFamilyCard(familyKey, family) {
            var html = '';
            html += '<div class="vps-os-family-card" data-family="' + familyKey + '" onclick="selectOSFamily(this)">';
            html += '<div class="vps-os-family-icon" style="background: linear-gradient(135deg, ' + family.color + '20, ' + family.color + '10);">';
            html += '<i class="' + family.icon + '" style="color:' + family.color + ';font-size:1.8rem;"></i>';
            html += '</div>';
            html += '<div class="vps-os-family-info">';
            html += '<div class="vps-os-family-name">' + family.name + '</div>';
            html += '<select class="vps-os-version-select" onchange="selectVersion(this)" onclick="event.stopPropagation()">';
            html += '<option value="">Select version...</option>';
            if (family.versions) {
                family.versions.forEach(function(version) {
                    html += '<option value="' + version.id + '">' + version.name + '</option>';
                });
            }
            html += '</select>';
            html += '</div>';
            html += '<div class="vps-os-family-check"><i class="fas fa-check-circle"></i></div>';
            html += '</div>';
            return html;
        }
        
         
        function getOSIcon(family) {
            var f = family.toLowerCase();
            if (f.indexOf('windows') !== -1) return 'fab fa-windows';
            if (f.indexOf('ubuntu') !== -1) return 'fab fa-ubuntu';
            if (f.indexOf('centos') !== -1) return 'fab fa-centos';
            if (f.indexOf('debian') !== -1) return 'fab fa-linux';
            if (f.indexOf('alma') !== -1) return 'fab fa-redhat';
            if (f.indexOf('rocky') !== -1) return 'fab fa-fedora';
            if (f.indexOf('fedora') !== -1) return 'fab fa-fedora';
            return 'fab fa-linux';
        }
        
         
         
        function initTemplateSelection() {
             
        }
        
         
        function selectOSFamily(card) {
             
            document.querySelectorAll('.vps-os-family-card').forEach(function(c) {
                c.classList.remove('selected');
                 
                if (c !== card) {
                    var select = c.querySelector('.vps-os-version-select');
                    if (select) select.selectedIndex = 0;
                }
            });
            
             
            card.classList.add('selected');
            
             
            var select = card.querySelector('.vps-os-version-select');
            if (select && select.value) {
                selectedTemplate = select.value;
                document.getElementById('selected-template-id').value = selectedTemplate;
            } else {
                selectedTemplate = null;
                document.getElementById('selected-template-id').value = '';
            }
            
            checkRebuildButton();
        }
        
         
        function selectVersion(selectElement) {
            var card = selectElement.closest('.vps-os-family-card');
            
             
            document.querySelectorAll('.vps-os-family-card').forEach(function(c) {
                if (c !== card) {
                    c.classList.remove('selected');
                    var select = c.querySelector('.vps-os-version-select');
                    if (select) select.selectedIndex = 0;
                }
            });
            
             
            card.classList.add('selected');
            
             
            selectedTemplate = selectElement.value || null;
            document.getElementById('selected-template-id').value = selectedTemplate || '';
            
            checkRebuildButton();
        }
        
         
        function initRebuildConfirm() {
            var checkbox = document.getElementById('rebuild-confirm');
            if (checkbox) {
                checkbox.addEventListener('change', checkRebuildButton);
            }
        }
        
        function checkRebuildButton() {
            var checkbox = document.getElementById('rebuild-confirm');
            var btn = document.getElementById('rebuild-btn');
            if (btn) {
                var shouldEnable = checkbox && checkbox.checked && selectedTemplate;
                btn.disabled = !shouldEnable;
            }
        }
        
         
        
         
        var statusLabels = {
            'rebuild': 'Rebuilding Server',
            'rebuilding': 'Rebuilding Server',
            'creating': 'Creating Server',
            'resetting': 'Resetting Server',
            'shutdown': 'Shutting Down',
            'stopping': 'Stopping Server',
            'starting': 'Starting Server',
            'backup': 'Backup in progress',
            'snapshot': 'Snapshot in progress',
            'restore': 'Restore in progress',
            'updating': 'Updating Server'
        };
        
         
        function checkInitialVMLock() {
            if (lockedStates.indexOf(currentVMStatus) !== -1) {
                showVMLock(currentVMStatus);
                startVMStatusPolling();
            }
        }
        
         
        function showVMLock(status) {
            var overlay = document.getElementById('vmLockOverlay');
            var lockTitle = document.getElementById('lockTitle');
            var lockStatus = document.getElementById('lockStatus');
            var lockStatusText = document.getElementById('lockStatusText');
            
            if (overlay) {
                overlay.classList.add('active');
                
                var label = statusLabels[status] || 'Processing';
                if (lockTitle) lockTitle.textContent = label;
                if (lockStatus) lockStatus.textContent = 'Status: ' + status;
                if (lockStatusText) lockStatusText.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            }
        }
        
         
        function hideVMLock() {
            var overlay = document.getElementById('vmLockOverlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
            
             
            if (vmStatusPollingInterval) {
                clearInterval(vmStatusPollingInterval);
                vmStatusPollingInterval = null;
            }
        }
        
         
        function startVMStatusPolling() {
             
            if (vmStatusPollingInterval) {
                clearInterval(vmStatusPollingInterval);
            }
            
             
            vmStatusPollingInterval = setInterval(function() {
                checkVMStatusFromAPI();
            }, 20000);
            
             
            setTimeout(function() {
                checkVMStatusFromAPI();
            }, 5000);
        }
        
         
        function checkVMStatusFromAPI() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            currentVMStatus = response.vm_status;
                            
                             
                            if (lockedStates.indexOf(currentVMStatus) !== -1) {
                                 
                                var lockStatusText = document.getElementById('lockStatusText');
                                var lockStatus = document.getElementById('lockStatus');
                                if (lockStatusText) lockStatusText.textContent = currentVMStatus.charAt(0).toUpperCase() + currentVMStatus.slice(1);
                                if (lockStatus) lockStatus.textContent = 'Status: ' + currentVMStatus;
                            } else {
                                 
                                hideVMLock();
                               location.reload();
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing VM status response');
                    }
                }
            };
            
            xhr.send('customAction=getVMStatus&token=' + csrfToken + '&ajax=1');
        }
        
        var pendingPowerAction = null;

        function parseAjaxResponse(xhr) {
            if (!xhr.responseText || xhr.responseText.trim() === '') {
                return { parseError: true, message: 'Empty response' };
            }
            try {
                return JSON.parse(xhr.responseText);
            } catch (e) {
                var text = xhr.responseText.trim();
                if (text.indexOf('<') === 0) {
                    return { parseError: true, message: 'Unexpected server response' };
                }
                return { parseError: true, message: 'Invalid JSON response' };
            }
        }

        function closePowerConfirmModal() {
            var modal = document.getElementById('powerConfirmModal');
            if (modal) modal.classList.remove('active');
            pendingPowerAction = null;
        }

        function confirmPowerAction() {
            if (!pendingPowerAction) return;
            var action = pendingPowerAction;
            closePowerConfirmModal();
            executeVmAction(action);
        }

        function vmAction(action) {
            var messages = {
                stop: translations[currentLang].power_confirm_stop,
                reboot: translations[currentLang].power_confirm_reboot,
                shutdown: translations[currentLang].power_confirm_shutdown,
                reset: translations[currentLang].power_confirm_reset,
                start: translations[currentLang].power_confirm_start
            };

            pendingPowerAction = action;
            var modal = document.getElementById('powerConfirmModal');
            var msg = document.getElementById('power-confirm-message');
            if (msg) msg.textContent = messages[action] || translations[currentLang].confirm_action;
            if (modal) modal.classList.add('active');
        }

        function executeVmAction(action) {
            showLoading();

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoading();
                    if (xhr.status === 200) {
                        var response = parseAjaxResponse(xhr);
                        if (response.parseError) {
                            showNotification(response.message || translations[currentLang].action_error, 'error');
                            return;
                        }
                        if (response.success) {
                            showNotification(translations[currentLang].action_success, 'success');
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            showNotification(response.error || translations[currentLang].action_error, 'error');
                        }
                    } else {
                        showNotification(translations[currentLang].action_error, 'error');
                    }
                }
            };

            xhr.onerror = function() {
                hideLoading();
                showNotification(translations[currentLang].action_error, 'error');
            };

            xhr.send('customAction=' + action + '&token=' + csrfToken + '&ajax=1');
        }

        function openConsole() {
            showLoading();

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoading();
                    if (xhr.status === 200) {
                        var response = parseAjaxResponse(xhr);
                        if (response.parseError) {
                            showNotification(response.message || translations[currentLang].console_error, 'error');
                            return;
                        }
                        if (response.success && response.url) {
                             
                             
                            window.open(response.url, '_blank', 'noopener,noreferrer');
                            showNotification(translations[currentLang].console_opening, 'success');
                        } else {
                            showNotification(response.error || translations[currentLang].console_error, 'error');
                        }
                    } else {
                        showNotification(translations[currentLang].console_error, 'error');
                    }
                }
            };

            xhr.onerror = function() {
                hideLoading();
                showNotification(translations[currentLang].console_error, 'error');
            };

            xhr.send('customAction=getConsole&token=' + csrfToken + '&ajax=1');
        }
        
         
        function rebuildVM() {
            if (!selectedTemplate) {
                showNotification('Please select an operating system', 'error');
                return;
            }
            
             
            showLoading();
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoading();
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showNotification(currentLang === 'tr' ? 'Yeniden yükleme başlatıldı' : 'Rebuild started successfully', 'success');
                                 
                                currentVMStatus = response.vm_status || 'rebuilding';
                                showVMLock(currentVMStatus);
                                startVMStatusPolling();
                            } else {
                                showNotification(response.error || 'Rebuild failed', 'error');
                            }
                        } catch (e) {
                            showNotification('Error processing response', 'error');
                        }
                    } else {
                        showNotification('Request failed', 'error');
                    }
                }
            };
            
            xhr.onerror = function() {
                hideLoading();
                showNotification('Network error', 'error');
            };
            
            xhr.send('customAction=rebuild&template=' + selectedTemplate + '&token=' + csrfToken + '&ajax=1');
        }
        
         
        function syncVMData() {
            var btn = document.getElementById('sync-vm-btn');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('syncing');
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (btn) {
                        btn.disabled = false;
                        btn.classList.remove('syncing');
                    }

                    if (xhr.status === 200) {
                        var response = parseAjaxResponse(xhr);
                        if (response.parseError) {
                            showNotification(response.message || translations[currentLang].sync_failed, 'error');
                            return;
                        }
                        if (response.success) {
                            showNotification(translations[currentLang].sync_success, 'success');
                            setTimeout(function() { location.reload(); }, 1000);
                        } else {
                            showNotification(response.error || translations[currentLang].sync_failed, 'error');
                        }
                    } else {
                        showNotification(translations[currentLang].sync_failed, 'error');
                    }
                }
            };

            xhr.onerror = function() {
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('syncing');
                }
                showNotification(translations[currentLang].action_error, 'error');
            };

            xhr.send('customAction=syncVMData&token=' + csrfToken + '&ajax=1');
        }
        
         
        function updateRDNS(ip) {
             
            var input = document.querySelector('.rdns-input[data-ip="' + ip + '"]');
            if (!input) {
                 
                input = document.getElementById('rdns-input');
            }
            
            if (!input) {
                showNotification('Could not find rDNS input for ' + ip, 'error');
                return;
            }
            
            var rdns = input.value.trim();
            
            if (!rdns) {
                showNotification('Please enter a hostname for ' + ip, 'error');
                return;
            }
            
            showLoading();
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoading();
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showNotification('rDNS updated for ' + ip + ': ' + rdns, 'success');
                            } else {
                                showNotification(response.error || 'Failed to update rDNS', 'error');
                            }
                        } catch (e) {
                             
                            showNotification('rDNS updated for ' + ip, 'success');
                        }
                    } else {
                        showNotification('Failed to update rDNS for ' + ip, 'error');
                    }
                }
            };
            
            xhr.send('customAction=updateRDNS&rdns=' + encodeURIComponent(rdns) + '&ip=' + encodeURIComponent(ip) + '&token=' + csrfToken + '&ajax=1');
        }
        
         
        function updateLabel() {
            var label = document.getElementById('server-label').value;
            
            if (!label || label.trim() === '') {
                showNotification('Please enter a hostname', 'error');
                return;
            }
            
            showLoading();
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoading();
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                               location.reload();
                            } else {
                                showNotification(response.error || 'Failed to update hostname', 'error');
                            }
                        } catch (e) {
                            showNotification('Error processing response', 'error');
                        }
                    } else {
                        showNotification('Failed to update hostname', 'error');
                    }
                }
            };
            
            xhr.send('customAction=updateLabel&label=' + encodeURIComponent(label) + '&token=' + csrfToken + '&ajax=1');
        }
        
         
        
         
        function openHostnameModal() {
            var modal = document.getElementById('hostnameModal');
            var input = document.getElementById('hostname-input');
            var currentHostname = document.getElementById('server-hostname-display');
            
            if (input && currentHostname) {
                input.value = currentHostname.textContent.trim();
            }
            
            if (modal) {
                modal.classList.add('active');
                if (input) input.focus();
            }
        }
        
         
        function closeHostnameModal() {
            var modal = document.getElementById('hostnameModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }
        
         
        function saveHostname() {
            var input = document.getElementById('hostname-input');
            if (!input) return;
            
            var hostname = input.value.trim();
            if (!hostname) {
                showNotification('Please enter a hostname', 'error');
                return;
            }
            
             
            var hostnameRegex = /^[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]$|^[a-zA-Z0-9]$/;
            if (!hostnameRegex.test(hostname)) {
                showNotification('Invalid hostname format. Use only letters, numbers, and hyphens.', 'error');
                return;
            }
            
            closeHostnameModal();
            
             
            var labelInput = document.getElementById('server-label');
            if (labelInput) {
                labelInput.value = hostname;
            }
            
             
            showLoading();
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoading();
                    if (xhr.status === 200) {
                        var response = parseAjaxResponse(xhr);
                        if (response.parseError) {
                            showNotification(response.message || translations[currentLang].action_error, 'error');
                            return;
                        }
                        if (response.success) {
                            var display = document.getElementById('server-hostname-display');
                            var labelInput = document.getElementById('server-label');
                            if (display) display.textContent = response.label || hostname;
                            if (labelInput) labelInput.value = response.label || hostname;
                            showNotification(translations[currentLang].hostname_updated, 'success');
                            if (response.vm_status && lockedStates.indexOf(response.vm_status) !== -1) {
                                currentVMStatus = response.vm_status;
                                showVMLock(response.vm_status);
                                startVMStatusPolling();
                            }
                        } else {
                            showNotification(response.error || translations[currentLang].action_error, 'error');
                        }
                    } else {
                        showNotification(translations[currentLang].action_error, 'error');
                    }
                }
            };
            
            xhr.send('customAction=updateLabel&label=' + encodeURIComponent(hostname) + '&token=' + csrfToken + '&ajax=1');
        }
        
         
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('vps-modal-overlay')) {
                closeHostnameModal();
                closePowerConfirmModal();
            }
        });
        
         
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHostnameModal();
            }
        });
        
         
        function refreshData() {
            showLoading();
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoading();
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showNotification(translations[currentLang].refreshSuccess || 'Data synchronized successfully', 'success');
                                setTimeout(function() { location.reload(); }, 1000);
                            } else {
                                showNotification(response.error || 'Sync failed', 'error');
                            }
                        } catch (e) {
                             
                            location.reload();
                        }
                    } else {
                        showNotification('Failed to sync data', 'error');
                    }
                }
            };
            
            xhr.send('customAction=syncVMData&token=' + csrfToken + '&ajax=1');
        }
        
        function showCopiedTooltip(btn) {
            if (!btn) return;
            btn.classList.add('copied');
            setTimeout(function() {
                btn.classList.remove('copied');
            }, 1500);
        }
        
        function copyToClipboard(text, btn) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopiedTooltip(btn);
                });
            } else {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showCopiedTooltip(btn);
            }
        }
        
        function togglePassword() {
            var hidden = document.getElementById('password-hidden');
            var visible = document.getElementById('password-visible');
            var icon = document.getElementById('password-icon');
            
            if (hidden.style.display === 'none') {
                hidden.style.display = 'inline';
                visible.style.display = 'none';
                icon.className = 'fas fa-eye';
            } else {
                hidden.style.display = 'none';
                visible.style.display = 'inline';
                icon.className = 'fas fa-eye-slash';
            }
        }
        
        function copyPassword(btn) {
            var visible = document.getElementById('password-visible');
            if (!visible) return;
            copyToClipboard(visible.textContent, btn || null);
        }
        
         
        function togglePasswordNew() {
            var hidden = document.getElementById('password-hidden-new');
            var visible = document.getElementById('password-visible-new');
            var icon = document.getElementById('password-icon-new');
            
            if (hidden.style.display === 'none') {
                hidden.style.display = 'inline';
                visible.style.display = 'none';
                icon.className = 'fas fa-eye';
            } else {
                hidden.style.display = 'none';
                visible.style.display = 'inline';
                icon.className = 'fas fa-eye-slash';
            }
        }
        
        function copyPasswordNew(btn) {
            var visible = document.getElementById('password-visible-new');
            if (!visible) return;
            copyToClipboard(visible.textContent, btn || null);
        }
        
         
        checkInitialVMLock();
        
         
        function openAddNetworkModal() {
            var modal = document.getElementById('addNetworkModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }
        
        function closeAddNetworkModal() {
            var modal = document.getElementById('addNetworkModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        function addNetworkInterface() {
            var btn = document.querySelector('#addNetworkModal .vps-btn-primary');
            var originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            btn.disabled = true;
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId + '&ajax=1', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showNotification(currentLang === 'tr' ? 'Network interface eklendi! Veriler güncelleniyor...' : 'Network interface added! Syncing data...', 'success');
                                closeAddNetworkModal();
                                btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> Syncing...';
                                
                                 
                                setTimeout(function() {
                                    var syncXhr = new XMLHttpRequest();
                                    syncXhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId + '&ajax=1', true);
                                    syncXhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                    syncXhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                    syncXhr.onreadystatechange = function() {
                                        if (syncXhr.readyState === 4) {
                                             
                                            location.reload();
                                        }
                                    };
                                    syncXhr.send('customAction=syncVMData');
                                }, 3000);
                            } else {
                                btn.innerHTML = originalText;
                                btn.disabled = false;
                                showNotification(response.error || 'Failed to add interface', 'error');
                            }
                        } catch (e) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            showNotification('Invalid response', 'error');
                        }
                    } else {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        showNotification('Request failed', 'error');
                    }
                }
            };
            
            xhr.send('customAction=addNetworkInterface');
        }
        
        function deleteNetworkInterface(ifaceName) {
            var confirmMsg = currentLang === 'tr' 
                ? 'Bu network interface\'i silmek istediğinizden emin misiniz? (' + ifaceName + ')'
                : 'Are you sure you want to delete this network interface? (' + ifaceName + ')';
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            showNotification(currentLang === 'tr' ? 'Interface siliniyor...' : 'Deleting interface...', 'info');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId + '&ajax=1', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showNotification(currentLang === 'tr' ? 'Interface silindi! Veriler güncelleniyor...' : 'Interface deleted! Syncing data...', 'success');
                                
                                 
                                setTimeout(function() {
                                    var syncXhr = new XMLHttpRequest();
                                    syncXhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId + '&ajax=1', true);
                                    syncXhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                    syncXhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                    syncXhr.onreadystatechange = function() {
                                        if (syncXhr.readyState === 4) {
                                            location.reload();
                                        }
                                    };
                                    syncXhr.send('customAction=syncVMData');
                                }, 2000);
                            } else {
                                showNotification(response.error || 'Failed to delete interface', 'error');
                            }
                        } catch (e) {
                            showNotification('Invalid response', 'error');
                        }
                    } else {
                        showNotification('Request failed', 'error');
                    }
                }
            };
            
            xhr.send('customAction=deleteNetworkInterface&interfaceName=' + encodeURIComponent(ifaceName));
        }
    </script>
    
     
    <div id="addNetworkModal" class="vps-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;justify-content:center;align-items:center;">
        <div style="background:white;border-radius:16px;padding:30px;max-width:450px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3 style="margin:0;font-size:1.3rem;color:#1e293b;">
                    <i class="fas fa-network-wired" style="color:#6366f1;margin-right:10px;"></i>
                    <span data-lang="add_network_interface">Add Network Interface</span>
                </h3>
                <button onclick="closeAddNetworkModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#64748b;">&times;</button>
            </div>
            
            <p style="color:#64748b;margin-bottom:25px;" data-lang="add_interface_desc">
                This will add a new network interface to your VPS. The new interface will be configured automatically.
            </p>
            
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button onclick="closeAddNetworkModal()" class="vps-btn" style="background:#e2e8f0;color:#475569;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;">
                    <span data-lang="cancel">Cancel</span>
                </button>
                <button onclick="addNetworkInterface()" class="vps-btn vps-btn-primary" style="padding:10px 20px;border:none;border-radius:8px;cursor:pointer;">
                    <i class="fas fa-plus"></i>
                    <span data-lang="add_interface">Add Interface</span>
                </button>
            </div>
        </div>
    </div>
</div>
