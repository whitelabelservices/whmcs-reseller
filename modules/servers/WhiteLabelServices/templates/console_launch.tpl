<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Opening console...</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }
        .box { text-align: center; padding: 2rem; }
        .spinner {
            width: 36px;
            height: 36px;
            margin: 0 auto 1rem;
            border: 3px solid rgba(129, 140, 248, 0.25);
            border-top-color: #818cf8;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .error { color: #f87171; max-width: 420px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner" id="spinner"></div>
        <div id="message">Opening console...</div>
    </div>
    <script>
    (function () {
        var cfg = <?= $launchConfig ?>;
        var message = document.getElementById('message');
        var spinner = document.getElementById('spinner');

        function fail(text) {
            spinner.style.display = 'none';
            message.className = 'error';
            message.textContent = text;
        }

        if (!cfg.ticket || !cfg.consoleUrl) {
            fail('Invalid console session.');
            return;
        }

        try {
            var secure = window.location.protocol === 'https:';
            var cookieBase = 'path=/; SameSite=Lax' + (secure ? '; Secure' : '');
            var host = (window.location.hostname || '').toLowerCase();
            var pveHost = (cfg.pveHost || '').toLowerCase();
            var sameHost = host === pveHost || host.endsWith('.' + pveHost);

            if (sameHost) {
                document.cookie = 'PVEAuthCookie=' + cfg.ticket + '; ' + cookieBase;
                window.location.replace(cfg.consoleUrl);
                return;
            }

            if (cfg.bridgeUrl) {
                window.location.replace(cfg.bridgeUrl);
                return;
            }

            document.cookie = 'PVEAuthCookie=' + cfg.ticket + '; ' + cookieBase;
            window.location.replace(cfg.consoleUrl);
        } catch (e) {
            fail('Could not start console: ' + (e.message || 'unknown error'));
        }
    })();
    </script>
</body>
</html>
