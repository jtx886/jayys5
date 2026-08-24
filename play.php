<?php
require_once 'functions.php';
$user = require_login();
$theme_color = get_setting('theme_color', '#e50914');

$type = isset($_GET['type']) ? $_GET['type'] : 'movie';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$title = isset($_GET['title']) ? $_GET['title'] : '';
$season = isset($_GET['season']) ? intval($_GET['season']) : 1;
$episode = isset($_GET['episode']) ? intval($_GET['episode']) : 0;
$audio = isset($_GET['audio']) ? $_GET['audio'] : 'original';

if (!$id || !$title) {
    redirect('index.php');
}

// 获取影视详情
$detail = tmdb_api("/$type/$id");
$media_title = ($type == 'movie') ? $detail['title'] : $detail['name'];

// 获取播放源
$play_sources = db()->fetchAll("SELECT * FROM play_sources WHERE is_active = 1 ORDER BY sort_order ASC");
$default_source = count($play_sources) > 0 ? $play_sources[0] : null;

// 记录观看历史
db()->insert('watch_history', array(
    'user_id' => $user['id'],
    'media_id' => $id,
    'media_type' => $type,
    'title' => $media_title,
    'poster' => $detail['poster_path'],
    'season' => $type == 'tv' ? $season : null,
    'episode' => $type == 'tv' ? $episode : null,
    'watch_time' => 0
));

// 构建播放搜索URL（用于前端JS搜索）
$search_keyword = $media_title;
if ($type == 'tv' && $episode > 0) {
    $search_keyword = $media_title . ' 第' . $season . '季 第' . $episode . '集';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>正在播放：<?php echo e($media_title); ?> - Jay影视</title>
    <link rel="stylesheet" href="style.css">
    <style>
    :root { --primary-color: <?php echo $theme_color; ?>; }
    .player-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.9));
        padding: 40px 30px 20px;
        z-index: 50;
        color: white;
    }
    .player-info h2 {
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    .player-info p {
        color: #999;
        font-size: 14px;
    }
    .source-selector {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 100;
        display: flex;
        gap: 10px;
    }
    .source-btn {
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        font-size: 13px;
        cursor: pointer;
        transition: var(--transition);
        border: 1px solid rgba(255,255,255,0.2);
    }
    .source-btn:hover, .source-btn.active {
        background: var(--primary-color);
        border-color: var(--primary-color);
    }
    .searching-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: black;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 200;
        color: white;
    }
    .searching-text {
        margin-top: 20px;
        color: #999;
    }
    .manual-search {
        margin-top: 30px;
        text-align: center;
        padding: 20px;
        max-width: 500px;
    }
    .manual-search input {
        width: 100%;
        padding: 12px 15px;
        background: #333;
        border: 1px solid #444;
        border-radius: 5px;
        color: white;
        font-size: 14px;
        margin-bottom: 10px;
    }
    .search-results {
        margin-top: 20px;
        max-height: 300px;
        overflow-y: auto;
        text-align: left;
    }
    .search-result-item {
        padding: 12px 15px;
        background: #1f1f1f;
        border-radius: 5px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: var(--transition);
    }
    .search-result-item:hover {
        background: #333;
    }
    </style>
</head>
<body class="player-page">
    <div class="player-container">
        <a href="detail.php?type=<?php echo $type; ?>&id=<?php echo $id; ?><?php echo $type=='tv'?'&season='.$season:''; ?>" class="player-back">
            <span class="back-icon"></span>
            返回详情
        </a>
        
        <div class="source-selector" id="sourceSelector">
            <?php foreach ($play_sources as $idx => $src): ?>
            <button class="source-btn <?php echo $idx===0?'active':''; ?>" 
                    onclick="switchSource(<?php echo $idx; ?>, '<?php echo addslashes($src['url']); ?>', '<?php echo addslashes($src['parser_url']); ?>')">
                <?php echo e($src['name']); ?>
            </button>
            <?php endforeach; ?>
        </div>
        
        <div id="searchingOverlay" class="searching-overlay">
            <div class="loading" style="width:50px;height:50px;border-width:4px;"></div>
            <div class="searching-text">正在搜索片源...</div>
            <div class="manual-search">
                <p style="margin-bottom:15px; color:#999;">如果长时间未加载，请手动搜索或选择其他播放源</p>
                <input type="text" id="manualSearchInput" value="<?php echo e($search_keyword); ?>" placeholder="输入影视名称搜索">
                <button class="btn btn-primary" onclick="manualSearch()">搜索</button>
                <div class="search-results" id="searchResults"></div>
            </div>
        </div>
        
        <iframe class="player-iframe" id="playerIframe" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
        
        <div class="player-info">
            <h2>
                <?php echo e($media_title); ?>
                <?php if ($type == 'tv'): ?>
                - 第<?php echo $season; ?>季 第<?php echo $episode; ?>集
                <?php endif; ?>
                <?php if ($audio == 'mandarin'): ?>
                [普通话配音]
                <?php endif; ?>
            </h2>
            <p>如果无法播放，请尝试切换播放源或手动搜索</p>
        </div>
    </div>
    
    <script>
    var playSources = [
        <?php 
        $first = true;
        foreach ($play_sources as $src) {
            if (!$first) echo ',';
            echo "{url:'".addslashes($src['url'])."',parser:'".addslashes($src['parser_url'])."',name:'".addslashes($src['name'])."'}";
            $first = false;
        }
        ?>
    ];
    var currentSourceIdx = 0;
    var searchKeyword = <?php echo json_encode($search_keyword); ?>;
    var audioType = '<?php echo $audio; ?>';
    
    function switchSource(idx, url, parser) {
        currentSourceIdx = idx;
        document.querySelectorAll('.source-btn').forEach(function(b,i){
            b.classList.toggle('active', i===idx);
        });
        searchAndPlay(url, parser);
    }
    
    function searchAndPlay(apiUrl, parserUrl) {
        document.getElementById('searchingOverlay').style.display = 'flex';
        document.getElementById('playerIframe').src = '';
        
        var searchUrl = apiUrl + '?wd=' + encodeURIComponent(searchKeyword + (audioType==='mandarin' ? ' 普通话' : ''));
        
        fetch(searchUrl)
            .then(function(r){return r.text();})
            .then(function(text){
                try {
                    var data = JSON.parse(text);
                    var videoUrl = findVideoUrl(data);
                    if (videoUrl) {
                        playVideo(parserUrl, videoUrl);
                    } else {
                        showSearchResults(data, apiUrl, parserUrl);
                    }
                } catch(e) {
                    showSearchResults(null, apiUrl, parserUrl);
                }
            })
            .catch(function(){
                showSearchResults(null, apiUrl, parserUrl);
            });
    }
    
    function findVideoUrl(data) {
        if (!data) return null;
        if (data.list && data.list.length > 0) {
            var item = data.list[0];
            if (item.vod_play_url) {
                var urls = item.vod_play_url.split('#');
                if (urls.length > 0) {
                    var firstUrl = urls[0].split('$');
                    if (firstUrl.length > 1) {
                        return firstUrl[1];
                    }
                }
            }
        }
        return null;
    }
    
    function showSearchResults(data, apiUrl, parserUrl) {
        var resultsEl = document.getElementById('searchResults');
        resultsEl.innerHTML = '';
        
        if (data && data.list && data.list.length > 0) {
            data.list.slice(0, 10).forEach(function(item){
                var div = document.createElement('div');
                div.className = 'search-result-item';
                div.textContent = item.vod_name;
                div.onclick = function(){
                    if (item.vod_play_url) {
                        var urls = item.vod_play_url.split('#');
                        var targetUrl = null;
                        
                        // 电视剧尝试匹配集数
                        <?php if ($type == 'tv'): ?>
                        var targetEp = <?php echo $episode; ?>;
                        for (var i = 0; i < urls.length; i++) {
                            var parts = urls[i].split('$');
                            if (parts.length > 1) {
                                if (parts[0].indexOf('第' + targetEp + '集') !== -1 || 
                                    parts[0].indexOf(targetEp + '$') !== -1 ||
                                    i === targetEp - 1) {
                                    targetUrl = parts[1];
                                    break;
                                }
                            }
                        }
                        <?php endif; ?>
                        
                        if (!targetUrl && urls[0]) {
                            var firstParts = urls[0].split('$');
                            if (firstParts.length > 1) targetUrl = firstParts[1];
                        }
                        
                        if (targetUrl) {
                            playVideo(parserUrl, targetUrl);
                        }
                    }
                };
                resultsEl.appendChild(div);
            });
        } else {
            resultsEl.innerHTML = '<p style="color:#999;text-align:center;padding:20px;">自动搜索失败，请手动输入关键词搜索</p>';
        }
    }
    
    function manualSearch() {
        var keyword = document.getElementById('manualSearchInput').value.trim();
        if (!keyword) return;
        searchKeyword = keyword;
        var src = playSources[currentSourceIdx];
        searchAndPlay(src.url, src.parser);
    }
    
    function playVideo(parserUrl, videoUrl) {
        document.getElementById('searchingOverlay').style.display = 'none';
        var fullParserUrl = parserUrl + encodeURIComponent(videoUrl);
        document.getElementById('playerIframe').src = fullParserUrl;
    }
    
    // 开始播放
    window.onload = function() {
        if (playSources.length > 0) {
            setTimeout(function(){
                searchAndPlay(playSources[0].url, playSources[0].parser);
            }, 500);
        }
    };
    
    // 回车搜索
    document.getElementById('manualSearchInput').addEventListener('keypress', function(e){
        if (e.key === 'Enter') manualSearch();
    });
    </script>
</body>
</html>
