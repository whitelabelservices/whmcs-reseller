{* VPS Rebuild Template - White Label *}
<div class="vps-rebuild-area">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        .vps-rebuild-area {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
            font-weight: 500;
        }
        
        .vps-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .vps-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
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
        
        .vps-back-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .vps-back-btn:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
        }
        
        .vps-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #f39c12;
        }
        
        .vps-warning h4 {
            color: #856404;
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        
        .vps-warning p {
            color: #856404;
            margin: 0;
            line-height: 1.6;
        }
        
        .vps-templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .vps-template-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .vps-template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .vps-template-card.selected {
            border-color: #667eea;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }
        
        .vps-template-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px 10px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .vps-template-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            background: var(--os-color);
            flex-shrink: 0;
        }
        
        .vps-template-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: right;
            flex: 0 0 auto;
        }
        
        .vps-template-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 5px 0;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }
        
        .vps-template-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0;
            line-height: 1.2;
        }
        
        .vps-versions-list {
            margin-bottom: 20px;
        }
        
        .vps-version-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            border: 2px solid #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .vps-version-item:hover {
            border-color: #667eea;
            background: #f0f3ff;
        }
        
        .vps-version-item.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .vps-version-radio {
            margin-right: 12px;
            width: 18px;
            height: 18px;
        }
        
        .vps-version-info {
            flex: 1;
        }
        
        .vps-version-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 3px;
        }
        
        .vps-version-details {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .vps-rebuild-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.5;
            pointer-events: none;
        }
        
        .vps-rebuild-btn.enabled {
            opacity: 1;
            pointer-events: auto;
        }
        
        .vps-rebuild-btn.enabled:hover {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff9a56 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        
        .vps-rebuild-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .vps-error-alert {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .vps-templates-grid {
                grid-template-columns: 1fr;
            }
            
            .vps-header h1 {
                font-size: 2rem;
            }
            
            .vps-back-btn {
                position: static;
                margin-top: 20px;
                align-self: flex-start;
            }
        }
    </style>

    <div class="vps-container">
        <div class="vps-header">
            <h1><i class="fas fa-hammer"></i> Rebuild VPS</h1>
            <div class="subtitle">{$domain} - Select New Operating System</div>
            <a href="clientarea.php?action=productdetails&id={$serviceid}" class="vps-back-btn">
                <i class="fas fa-arrow-left"></i> Back to VPS
            </a>
        </div>

        {if isset($smarty.get.error)}
            <div class="vps-error-alert">
                <i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> {$smarty.get.error|urldecode}
            </div>
        {/if}

        <div class="vps-warning">
            <h4><i class="fas fa-exclamation-triangle"></i> Important Warning</h4>
            <p>Rebuilding your VPS will completely wipe all data and reinstall the selected operating system. This action cannot be undone. Please ensure you have backed up any important data before proceeding.</p>
        </div>

        <form method="post" action="clientarea.php?action=productdetails&id={$serviceid}" id="rebuildForm">
            <input type="hidden" name="token" value="{$token}">
            <input type="hidden" name="customAction" value="rebuild">
            <input type="hidden" name="selectedTemplate" id="selectedTemplate" value="">

            <div class="vps-templates-grid">
                {if $templates && count($templates) > 0}
                    {foreach from=$templates key=osGroup item=template}
                        <div class="vps-template-card" data-os="{$osGroup}">
                            <div class="vps-template-header">
                                <div class="vps-template-icon" style="--os-color: {$template.color};">
                                    <i class="{$template.icon}"></i>
                                </div>
                                <div class="vps-template-info">
                                    <h3 class="vps-template-title">{$template.name}</h3>
                                    <div class="vps-template-subtitle">{count($template.versions)} versions available</div>
                                </div>
                            </div>
                            
                            <div class="vps-versions-list">
                                {foreach from=$template.versions item=version}
                                    <div class="vps-version-item" data-template-id="{$version.id}" data-os-group="{$osGroup}">
                                        <input type="radio" name="template_version" value="{$version.id}" class="vps-version-radio" id="template_{$version.id}">
                                        <div class="vps-version-info">
                                            <div class="vps-version-name">{$version.name}</div>
                                            <div class="vps-version-details">
                                                <i class="fas fa-hdd"></i> {$version.size_gb} GB
                                                {if $version.price > 0}
                                                    • <i class="fas fa-dollar-sign"></i> ${$version.price}
                                                {else}
                                                    • <i class="fas fa-check text-success"></i> Free
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                            
                            <button type="button" class="vps-rebuild-btn" data-os="{$osGroup}">
                                <i class="fas fa-hammer"></i> Rebuild with {$template.name}
                            </button>
                        </div>
                    {/foreach}
                {else}
                    <div class="vps-error-alert" style="grid-column: 1 / -1;">
                        <i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> No templates available for rebuild. Please ensure your VPS is properly provisioned and try again.
                        <br><br>
                        <a href="clientarea.php?action=productdetails&id={$serviceid}" class="vps-btn btn-secondary" style="margin-top: 10px; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Back to VPS Management
                        </a>
                    </div>
                {/if}
            </div>
        </form>
    </div>

    <script>
        var selectedTemplate = '';
        var selectedOSGroup = '';
        
        document.addEventListener('DOMContentLoaded', function() {
            // Version seçimi
            var versionItems = document.querySelectorAll('.vps-version-item');
            var rebuildButtons = document.querySelectorAll('.vps-rebuild-btn');
            var templateCards = document.querySelectorAll('.vps-template-card');
            
            // Version item click handler
            versionItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    var templateId = this.getAttribute('data-template-id');
                    var osGroup = this.getAttribute('data-os-group');
                    var radio = this.querySelector('.vps-version-radio');
                    
                    // Tüm seçimleri temizle
                    clearAllSelections();
                    
                    // Bu seçimi aktif et
                    radio.checked = true;
                    this.classList.add('selected');
                    
                    // OS kartını seçili yap
                    var osCard = document.querySelector('.vps-template-card[data-os="' + osGroup + '"]');
                    if (osCard) {
                        osCard.classList.add('selected');
                    }
                    
                    // Bu OS grubunun rebuild butonunu aktif et
                    var rebuildBtn = document.querySelector('.vps-rebuild-btn[data-os="' + osGroup + '"]');
                    if (rebuildBtn) {
                        rebuildBtn.classList.add('enabled');
                    }
                    
                    selectedTemplate = templateId;
                    selectedOSGroup = osGroup;
                    
                    console.log('Selected template:', templateId, 'OS Group:', osGroup);
                });
            });
            
            // Rebuild button click handler
            rebuildButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var osGroup = this.getAttribute('data-os');
                    
                    if (selectedOSGroup === osGroup && selectedTemplate) {
                        // Onay dialogu
                        var confirmed = confirm(
                            'Are you sure you want to rebuild your VPS with the selected operating system?\\n\\n' +
                            'WARNING: This will permanently delete all data on your VPS!\\n\\n' +
                            'This action cannot be undone.'
                        );
                        
                        if (confirmed) {
                            document.getElementById('selectedTemplate').value = selectedTemplate;
                            document.getElementById('rebuildForm').submit();
                        }
                    }
                });
            });
            
            function clearAllSelections() {
                // Tüm radio button'ları temizle
                var radios = document.querySelectorAll('.vps-version-radio');
                radios.forEach(function(radio) {
                    radio.checked = false;
                });
                
                // Tüm seçili class'ları temizle
                var selectedItems = document.querySelectorAll('.selected');
                selectedItems.forEach(function(item) {
                    item.classList.remove('selected');
                });
                
                // Tüm rebuild butonlarını deaktif et
                rebuildButtons.forEach(function(btn) {
                    btn.classList.remove('enabled');
                });
                
                selectedTemplate = '';
                selectedOSGroup = '';
            }
        });
    </script>
</div> 