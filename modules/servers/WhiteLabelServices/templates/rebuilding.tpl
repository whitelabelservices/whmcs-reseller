 
<div class="vps-rebuilding-area">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        .vps-rebuilding-area {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
            font-weight: 500;
        }
        
        .vps-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .vps-header {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .vps-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        
        .vps-header .subtitle {
            opacity: 0.9;
            margin-top: 10px;
            font-size: 1.1rem;
            font-weight: 400;
        }
        
        .vps-rebuilding-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .vps-rebuilding-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3rem;
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(255, 107, 107, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
        }
        
        .vps-rebuilding-title {
            font-size: 2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .vps-rebuilding-subtitle {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .vps-progress-container {
            margin: 30px 0;
        }
        
        .vps-progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .vps-progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .vps-progress-text {
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
        }
        
        .vps-status-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .vps-status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .vps-status-item:last-child {
            border-bottom: none;
        }
        
        .vps-status-label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .vps-status-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .vps-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .vps-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .vps-loading-dots {
            display: inline-flex;
            gap: 4px;
            margin-left: 10px;
        }
        
        .vps-loading-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ff6b6b;
            animation: loadingDots 1.4s infinite ease-in-out;
        }
        
        .vps-loading-dot:nth-child(1) { animation-delay: -0.32s; }
        .vps-loading-dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes loadingDots {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        @media (max-width: 768px) {
            .vps-header h1 {
                font-size: 2rem;
            }
            
            .vps-rebuilding-card {
                padding: 30px 20px;
            }
            
            .vps-actions {
                flex-direction: column;
            }
        }
    </style>

    <div class="vps-container">
        <div class="vps-header">
            <h1><i class="fas fa-hammer"></i> Rebuilding VPS</h1>
            <div class="subtitle">{$domain} - Please wait while we rebuild your VPS</div>
        </div>

        <div class="vps-rebuilding-card">
            <div class="vps-rebuilding-icon">
                <i class="fas fa-cogs fa-spin"></i>
            </div>
            
            <h2 class="vps-rebuilding-title">Rebuilding in Progress</h2>
            <p class="vps-rebuilding-subtitle">
                Your VPS is being rebuilt with the new operating system. This process typically takes 5-15 minutes.
                <span class="vps-loading-dots">
                    <span class="vps-loading-dot"></span>
                    <span class="vps-loading-dot"></span>
                    <span class="vps-loading-dot"></span>
                </span>
            </p>
            
            <div class="vps-progress-container">
                <div class="vps-progress-bar">
                    <div class="vps-progress-fill" id="progressFill" style="width: 10%;"></div>
                </div>
                <div class="vps-progress-text" id="progressText">Initializing rebuild process...</div>
            </div>
            
            <div class="vps-status-info">
                <div class="vps-status-item">
                    <span class="vps-status-label">Current Status:</span>
                    <span class="vps-status-value" id="currentStatus">Rebuilding</span>
                </div>
                <div class="vps-status-item">
                    <span class="vps-status-label">Started:</span>
                    <span class="vps-status-value">{$smarty.now|date_format:"%H:%M:%S"}</span>
                </div>
                <div class="vps-status-item">
                    <span class="vps-status-label">Next Check:</span>
                    <span class="vps-status-value">
                        <span id="countdown">20</span> seconds
                    </span>
                </div>
            </div>
            
            <div class="vps-actions">
                <a href="clientarea.php?action=productdetails&id={$serviceid}" class="vps-btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to VPS
                </a>
            </div>
        </div>
    </div>

    <script>
        var serviceId = {$serviceid};
        var checkInterval;
        var countdownInterval;
        var countdownSeconds = 20;
        var progressValue = 10;
        var checkCount = 0;
        
        var progressMessages = [
            "Initializing rebuild process...",
            "Preparing new operating system...",
            "Installing system files...",
            "Configuring network settings...",
            "Setting up services...",
            "Finalizing installation...",
            "Almost complete..."
        ];
        
        document.addEventListener('DOMContentLoaded', function() {
            startRebuildMonitoring();
        });
        
        function startRebuildMonitoring() {
            checkVMStatus();
            checkInterval = setInterval(checkVMStatus, 20000);
            startCountdown();
        }
        
        function checkVMStatus() {
            console.log('Checking VM status...');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'clientarea.php?action=productdetails&id=' + serviceId + '&ajax=1', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data.success && data.vmInfo) {
                                handleStatusUpdate(data.vmInfo);
                            }
                        } catch (e) {
                            console.log('Error parsing response:', e);
                        }
                    }
                    countdownSeconds = 20;
                    startCountdown();
                }
            };
            
            xhr.send('customAction=getVMData');
            updateProgress();
        }
        
        function handleStatusUpdate(vmInfo) {
            var status = vmInfo.vm_status;
            document.getElementById('currentStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
            
            if (status === 'running' || status === 'stopped') {
                showSuccess();
                setTimeout(function() {
                    window.location.href = 'clientarea.php?action=productdetails&id=' + serviceId;
                }, 3000);
            } else if (status === 'error') {
                showError();
            }
        }
        
        function updateProgress() {
            checkCount++;
            progressValue = Math.min(10 + (checkCount * 15), 90);
            document.getElementById('progressFill').style.width = progressValue + '%';
            
            var messageIndex = Math.min(Math.floor(checkCount / 2), progressMessages.length - 1);
            document.getElementById('progressText').textContent = progressMessages[messageIndex];
        }
        
        function startCountdown() {
            clearInterval(countdownInterval);
            countdownInterval = setInterval(function() {
                countdownSeconds--;
                document.getElementById('countdown').textContent = countdownSeconds;
                if (countdownSeconds <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }
        
        function showSuccess() {
            clearInterval(checkInterval);
            clearInterval(countdownInterval);
            
            document.getElementById('progressFill').style.width = '100%';
            document.getElementById('progressText').textContent = 'Rebuild completed successfully!';
            document.getElementById('currentStatus').textContent = 'Completed';
            
            var icon = document.querySelector('.vps-rebuilding-icon i');
            icon.className = 'fas fa-check';
            icon.style.animation = 'none';
            
            document.querySelector('.vps-rebuilding-title').textContent = 'Rebuild Completed!';
            document.querySelector('.vps-rebuilding-subtitle').innerHTML = 
                'Your VPS has been successfully rebuilt. Redirecting to VPS management...';
        }
        
        function showError() {
            clearInterval(checkInterval);
            clearInterval(countdownInterval);
            
            document.getElementById('progressText').textContent = 'Rebuild failed. Please try again.';
            document.getElementById('currentStatus').textContent = 'Error';
            
            var icon = document.querySelector('.vps-rebuilding-icon i');
            icon.className = 'fas fa-exclamation-triangle';
            icon.style.animation = 'none';
            
            document.querySelector('.vps-rebuilding-title').textContent = 'Rebuild Failed';
            document.querySelector('.vps-rebuilding-subtitle').innerHTML = 
                'An error occurred during the rebuild process. Please try again.';
        }
        
        window.addEventListener('beforeunload', function() {
            clearInterval(checkInterval);
            clearInterval(countdownInterval);
        });
    </script>
</div> 