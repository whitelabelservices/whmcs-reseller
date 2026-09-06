<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    <title>VNC Console — <?= htmlspecialchars($session['vm_name'] ?? 'VM') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 100%; height: 100%; overflow: hidden; background: #0b1020; color: #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .console-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            height: 48px;
            padding: 0 16px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .console-title { font-size: 14px; font-weight: 600; }
        .console-status { font-size: 12px; color: #94a3b8; max-width: 50%; text-align: right; word-break: break-word; }
        .console-status.error { color: #f87171; }
        .console-status.connected { color: #4ade80; }
        .console-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .console-btn {
            border: 1px solid rgba(148, 163, 184, 0.25);
            background: rgba(30, 41, 59, 0.9);
            color: #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
        }
        .console-btn:hover { background: #334155; }
        #screen {
            width: 100%;
            height: calc(100% - 48px);
            background: #000;
        }
        .console-loading {
            position: absolute;
            inset: 48px 0 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(11, 16, 32, 0.85);
            z-index: 10;
        }
        .console-loading.hidden { display: none; }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(129, 140, 248, 0.2);
            border-top-color: #818cf8;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="console-toolbar">
        <div class="console-title">
            <?= htmlspecialchars($session['vm_name'] ?? 'VM') ?> (<?= (int) ($session['vmid'] ?? 0) ?>)
        </div>
        <div id="status" class="console-status">Connecting...</div>
        <div class="console-actions">
            <button class="console-btn" type="button" id="btn-ctrl-alt-del">Ctrl+Alt+Del</button>
            <button class="console-btn" type="button" id="btn-reconnect">Reconnect</button>
        </div>
    </div>
    <div id="loading" class="console-loading"><div class="spinner"></div></div>
    <div id="screen"></div>

    <script type="module">
        const cfg = <?= $consoleConfig ?>;
        const statusEl = document.getElementById('status');
        const loadingEl = document.getElementById('loading');
        let rfb = null;
        let connectTimer = null;

        function setStatus(text, type) {
            statusEl.textContent = text;
            statusEl.className = 'console-status' + (type ? ' ' + type : '');
        }

        function buildWsUrl() {
             
            const url = new URL('ajax/console_ws.php', window.location.href);
            url.searchParams.set('serviceid', String(cfg.serviceId));
            url.protocol = url.protocol === 'https:' ? 'wss:' : 'ws:';
            return url.href;
        }

        function sendVncCredentials() {
            if (!rfb || !cfg.vncPassword) {
                return;
            }
            rfb.sendCredentials({ password: cfg.vncPassword });
        }

        async function loadRfb() {
            const sources = [
                cfg.novncBase + '/core/rfb.js',
                'https://cdn.jsdelivr.net/npm/@novnc/novnc@1.4.0/core/rfb.js',
            ];
            let lastError = null;
            for (const src of sources) {
                try {
                    const mod = await import(src);
                    return mod.default;
                } catch (e) {
                    lastError = e;
                }
            }
            throw lastError || new Error('Could not load noVNC library');
        }

        function disconnectConsole() {
            if (connectTimer) {
                clearTimeout(connectTimer);
                connectTimer = null;
            }
            if (rfb) {
                try { rfb.disconnect(); } catch (e) {}
                rfb = null;
            }
        }

        async function connectConsole() {
            disconnectConsole();
            loadingEl.classList.remove('hidden');
            setStatus('Connecting...', '');

            try {
                const RFB = await loadRfb();
                const wsUrl = buildWsUrl();

                connectTimer = setTimeout(function() {
                    setStatus('WebSocket timeout — check port ' + cfg.port + ' access', 'error');
                    loadingEl.classList.add('hidden');
                    try { if (rfb) rfb.disconnect(); } catch (e) {}
                }, 20000);

                rfb = new RFB(document.getElementById('screen'), wsUrl, {
                    wsProtocols: ['binary'],
                    shared: true,
                    credentials: cfg.vncPassword ? { password: cfg.vncPassword } : undefined,
                });

                rfb.scaleViewport = true;
                rfb.resizeSession = false;
                rfb.clipViewport = false;
                rfb.background = '#000000';

                rfb.addEventListener('connect', function() {
                    if (connectTimer) clearTimeout(connectTimer);
                    loadingEl.classList.add('hidden');
                    setStatus('Connected', 'connected');
                });

                rfb.addEventListener('disconnect', function(e) {
                    if (connectTimer) clearTimeout(connectTimer);
                    loadingEl.classList.add('hidden');
                    const detail = e.detail || {};
                    if (detail.clean) {
                        setStatus('Disconnected', 'error');
                    } else {
                        setStatus('Connection failed (code ' + (detail.code || '1006') + '). WebSocket relay may need server configuration.', 'error');
                    }
                });

                rfb.addEventListener('credentialsrequired', function() {
                    sendVncCredentials();
                });

                rfb.addEventListener('securityfailure', function(e) {
                    if (connectTimer) clearTimeout(connectTimer);
                    loadingEl.classList.add('hidden');
                    setStatus('Security failure: ' + ((e.detail || {}).reason || 'unknown'), 'error');
                });
            } catch (err) {
                if (connectTimer) clearTimeout(connectTimer);
                loadingEl.classList.add('hidden');
                setStatus('Error: ' + (err.message || 'Could not start console'), 'error');
            }
        }

        document.getElementById('btn-reconnect').addEventListener('click', function() {
            location.reload();
        });

        document.getElementById('btn-ctrl-alt-del').addEventListener('click', function() {
            if (rfb) rfb.sendCtrlAltDel();
        });

        connectConsole();
    </script>
</body>
</html>
