<?php
 




require_once __DIR__ . '/includes/wls_bootstrap.php';

$whmcsRoot = wls_find_whmcs_root_dir(__DIR__);
if ($whmcsRoot === null || !is_file($whmcsRoot . DIRECTORY_SEPARATOR . 'init.php')) {
    die('WHMCS init.php not found');
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

$t = [
    'news' => $lang === 'tr' ? 'Haberler & Duyurular' : 'News & Announcements',
    'no_news' => $lang === 'tr' ? 'Haber bulunamadı' : 'No news available',
    'loading' => $lang === 'tr' ? 'Yükleniyor...' : 'Loading...',
    'read_more' => $lang === 'tr' ? 'Devamını Oku' : 'Read More',
    'published' => $lang === 'tr' ? 'Yayın Tarihi' : 'Published',
    'back' => $lang === 'tr' ? 'Listeye Dön' : 'Back to List',
];

$extraStyles = '
    .news-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        margin-bottom: 20px;
        overflow: hidden;
        transition: all 0.3s;
    }
    .news-card:hover {
        transform: translateY(-5px);
        border-color: rgba(96, 165, 250, 0.3);
    }
    .news-header {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.3), rgba(16, 185, 129, 0.3));
        padding: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .news-header h3 { margin: 0; font-size: 1.1rem; color: #e2e8f0; }
    .news-header .date { font-size: 0.85rem; color: #64748b; margin-top: 5px; }
    .news-body { padding: 20px; }
    .news-body p { color: #94a3b8; line-height: 1.7; margin: 0 0 15px; }
    .btn-read-more {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    .btn-read-more:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3); }
    .news-detail { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 30px; }
    .news-detail h2 { margin: 0 0 15px; color: #e2e8f0; }
    .news-detail .meta { color: #64748b; font-size: 0.9rem; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .news-detail .content { color: #94a3b8; line-height: 1.8; }
    .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
    .empty-state i { font-size: 3rem; margin-bottom: 15px; color: #475569; }
    
     
    @media (max-width: 768px) {
        .news-card { margin-bottom: 15px; }
        .news-header { padding: 15px; }
        .news-header h3 { font-size: 1rem; }
        .news-body { padding: 15px; }
        .news-body p { font-size: 0.9rem; }
        .btn-read-more { padding: 6px 14px; font-size: 0.85rem; }
        .news-detail { padding: 20px; }
        .news-detail h2 { font-size: 1.2rem; }
        .news-detail .content { font-size: 0.9rem; }
    }
';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php wls_render_head('WLS ' . $t['news'], $extraStyles); ?>
</head>
<body>
    <?php wls_render_sidebar('news', $lang); ?>
    
    <main class="wls-main">
        <div class="wls-page-header">
            <h1><i class="fas fa-newspaper"></i> <?= $t['news'] ?></h1>
        </div>
        
        <div id="newsList">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p><?= $t['loading'] ?></p>
            </div>
        </div>
        
        <div id="newsDetail" style="display: none;"></div>
    </main>

<script>
var lang = <?= json_encode($t) ?>;

$(document).ready(function() {
    loadNews();
});

function loadNews() {
    $("#newsList").show();
    $("#newsDetail").hide();
    
    $.post("ajax/notifications.php", {action: "news"}, function(d) {
        if(d.status == "success" && d.data && d.data.news && d.data.news.length > 0) {
            var html = '';
            d.data.news.forEach(function(n) {
                html += '<div class="news-card">';
                html += '<div class="news-header">';
                html += '<h3><i class="fas fa-bullhorn"></i> ' + (n.title || 'News') + '</h3>';
                html += '<div class="date"><i class="fas fa-calendar"></i> ' + (n.date || n.created_at || '') + '</div>';
                html += '</div>';
                html += '<div class="news-body">';
                var excerpt = (n.body || n.content || '').substring(0, 200);
                if((n.body || n.content || '').length > 200) excerpt += '...';
                html += '<p>' + excerpt + '</p>';
                html += '<button class="btn-read-more" onclick="viewNews(' + n.id + ')"><i class="fas fa-arrow-right"></i> ' + lang.read_more + '</button>';
                html += '</div>';
                html += '</div>';
            });
            $("#newsList").html(html);
        } else {
            $("#newsList").html('<div class="empty-state"><i class="fas fa-newspaper"></i><p>' + lang.no_news + '</p></div>');
        }
    }, "json").fail(function() {
        $("#newsList").html('<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading news</p></div>');
    });
}

function viewNews(id) {
    $.post("ajax/notifications.php", {action: "news_detail", id: id}, function(d) {
        if(d.status == "success" && d.data) {
            var n = d.data.news || d.data;
            var html = '<button class="wls-btn wls-btn-secondary" onclick="loadNews()" style="margin-bottom:20px;"><i class="fas fa-arrow-left"></i> ' + lang.back + '</button>';
            html += '<div class="news-detail">';
            html += '<h2>' + (n.title || 'News') + '</h2>';
            html += '<div class="meta"><i class="fas fa-calendar"></i> ' + lang.published + ': ' + (n.date || n.created_at || '') + '</div>';
            html += '<div class="content">' + (n.body || n.content || '') + '</div>';
            html += '</div>';
            
            $("#newsList").hide();
            $("#newsDetail").html(html).show();
        }
    }, "json");
}
</script>

</body>
</html>
