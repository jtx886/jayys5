<?php
require_once 'functions.php';
$current_user = current_user();
$theme_color = get_setting('theme_color', '#e50914');

$type = isset($_GET['type']) ? $_GET['type'] : 'movie';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$season_num = isset($_GET['season']) ? intval($_GET['season']) : 1;

if (!$id) {
    redirect('index.php');
}

// 获取影视详情
$detail = tmdb_api("/$type/$id", array('append_to_response' => 'credits,external_ids'));
if (!$detail) {
    redirect('index.php');
}

$title = ($type == 'movie') ? $detail['title'] : $detail['name'];
$date = ($type == 'movie') ? $detail['release_date'] : $detail['first_air_date'];
$year = substr($date, 0, 4);

// 获取季信息（电视剧）
$seasons = array();
$episodes = array();
$current_season = null;
if ($type == 'tv' && isset($detail['seasons'])) {
    $seasons = $detail['seasons'];
    // 获取选中季的集数
    $season_data = tmdb_api("/tv/$id/season/$season_num");
    if ($season_data && isset($season_data['episodes'])) {
        $episodes = $season_data['episodes'];
        $current_season = $season_data;
    }
}

// 判断是否是国产影视（简单判断：语言为zh）
$is_chinese = isset($detail['original_language']) && $detail['original_language'] == 'zh';

// 获取演员表
$cast = array();
if (isset($detail['credits']['cast'])) {
    $cast = array_slice($detail['credits']['cast'], 0, 10);
}

// 检查是否已收藏
$is_favorited = false;
if ($current_user) {
    $fav = db()->fetch("SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?", 
        array($current_user['id'], $id, $type));
    $is_favorited = $fav ? true : false;
}

// 获取默认播放源
$play_source = db()->fetch("SELECT * FROM play_sources WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?> - Jay影视</title>
    <link rel="stylesheet" href="style.css">
    <style>
    :root { --primary-color: <?php echo $theme_color; ?>; }
    .season-tabs { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 25px; padding: 0 4%; }
    .season-tab { padding: 10px 20px; background: var(--bg-card); border-radius: 6px; cursor: pointer; transition: var(--transition); font-size: 14px; }
    .season-tab:hover, .season-tab.active { background: var(--primary-color); color: white; }
    .audio-selector { display: flex; gap: 10px; }
    .audio-option { padding: 8px 18px; background: var(--bg-card); border-radius: 20px; cursor: pointer; font-size: 14px; transition: var(--transition); border: 2px solid transparent; }
    .audio-option:hover, .audio-option.active { border-color: var(--primary-color); background: var(--bg-card-hover); }
    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
    
    <nav class="navbar" id="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">
                <span class="logo-icon">J</span>
                Jay影视
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">首页</a></li>
                <li><a href="category.php?type=movie" <?php echo $type=='movie'?'class="active"':''; ?>>电影</a></li>
                <li><a href="category.php?type=tv" <?php echo $type=='tv'?'class="active"':''; ?>>电视剧</a></li>
                <li><a href="category.php?type=anime">动漫</a></li>
                <li><a href="category.php?type=variety">综艺</a></li>
                <li><a href="feedback.php">反馈</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <div class="search-box">
                <div class="search-icon"></div>
                <input type="text" id="searchInput" placeholder="搜索影视..." onkeypress="if(event.key==='Enter')doSearch()">
            </div>
            <?php if ($current_user): ?>
            <div class="nav-user">
                <div class="user-avatar">
                    <?php if ($current_user['avatar']): ?>
                        <img src="<?php echo e($current_user['avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <?php echo mb_substr($current_user['username'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div class="user-dropdown">
                    <a href="profile.php">个人中心</a>
                    <?php if ($current_user['is_admin']): ?><a href="admin/">管理后台</a><?php endif; ?>
                    <a href="logout.php">退出登录</a>
                </div>
            </div>
            <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm" style="padding:8px 20px;">登录</a>
            <?php endif; ?>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()"><span></span><span></span><span></span></div>
        </div>
    </nav>
    
    <div class="detail-page">
        <div class="detail-hero">
            <div class="detail-bg" style="background-image: url('<?php echo tmdb_image($detail['backdrop_path'], 'original'); ?>');"></div>
            <div class="detail-overlay"></div>
            <div class="detail-content">
                <h1 class="detail-title"><?php echo e($title); ?></h1>
                <div class="detail-meta">
                    <span class="detail-score">★ <?php echo number_format($detail['vote_average'], 1); ?>分</span>
                    <span><?php echo e($year); ?></span>
                    <?php if ($type == 'movie' && isset($detail['runtime'])): ?>
                    <span><?php echo $detail['runtime']; ?>分钟</span>
                    <?php endif; ?>
                    <?php if (isset($detail['genres'])): ?>
                    <span><?php echo e(implode(' / ', array_slice(array_map(function($g){return $g['name'];}, $detail['genres']), 0, 3))); ?></span>
                    <?php endif; ?>
                </div>
                <div class="detail-actions">
                    <?php if ($type == 'movie'): ?>
                    <button class="btn btn-play btn-lg" onclick="playMovie()">
                        <span class="play-icon"></span>
                        播放
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-info btn-lg" onclick="toggleFavorite()">
                        <span class="heart-icon <?php echo $is_favorited ? 'active' : ''; ?>" id="favIcon"></span>
                        <span id="favText"><?php echo $is_favorited ? '已收藏' : '收藏'; ?></span>
                    </button>
                </div>
                <p class="detail-overview"><?php echo e($detail['overview'] ? $detail['overview'] : '暂无简介'); ?></p>
                <div class="detail-extra">
                    <?php if (count($cast) > 0): ?>
                    <div>
                        <span class="detail-extra-label">演员：</span>
                        <?php echo e(implode('、', array_slice(array_map(function($c){return $c['name'];}, $cast), 0, 5))); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($detail['production_countries']) && count($detail['production_countries']) > 0): ?>
                    <div>
                        <span class="detail-extra-label">国家/地区：</span>
                        <?php echo e($detail['production_countries'][0]['name']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($type == 'tv' && count($seasons) > 0): ?>
        <!-- 季选择 -->
        <div class="selector-row" style="margin-top: 30px;">
            <div class="selector">
                <span class="selector-label">季：</span>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php foreach ($seasons as $s): ?>
                    <?php if ($s['season_number'] > 0): ?>
                    <a href="?type=<?php echo $type; ?>&id=<?php echo $id; ?>&season=<?php echo $s['season_number']; ?>" 
                       class="btn <?php echo $s['season_number']==$season_num?'btn-primary':'btn-secondary'; ?> btn-sm">
                        第<?php echo $s['season_number']; ?>季
                    </a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php if (!$is_chinese): ?>
        <!-- 配音选择（非国产） -->
        <div class="selector-row">
            <div class="selector">
                <span class="selector-label">配音：</span>
                <div class="audio-selector">
                    <div class="audio-option active" data-audio="original" onclick="selectAudio(this)">原声</div>
                    <div class="audio-option" data-audio="mandarin" onclick="selectAudio(this)">普通话</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 季详情 -->
        <?php if ($current_season): ?>
        <div style="padding: 0 4%; margin-bottom: 20px;">
            <?php if ($current_season['overview']): ?>
            <p style="color: var(--text-secondary); margin-bottom: 15px;"><?php echo e($current_season['overview']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 集数列表 -->
        <div class="episodes-section">
            <h3 style="margin-bottom: 20px; font-size: 1.3rem;">剧集列表</h3>
            <div class="episodes-grid">
                <?php foreach ($episodes as $ep): ?>
                <div class="episode-card" onclick="playEpisode(<?php echo $ep['episode_number']; ?>)">
                    <div class="episode-thumb" style="background-image: url('<?php echo tmdb_image($ep['still_path'], 'w300'); ?>');">
                        <span class="episode-number">EP<?php echo $ep['episode_number']; ?></span>
                        <div class="episode-play-overlay">
                            <span class="play-icon"></span>
                        </div>
                    </div>
                    <div class="episode-info">
                        <div class="episode-title">第<?php echo $ep['episode_number']; ?>集 <?php echo e($ep['name']); ?></div>
                        <div class="episode-desc"><?php echo e($ep['overview'] ? mb_substr($ep['overview'], 0, 80) . '...' : '暂无简介'); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="height: 50px;"></div>
    </div>
    
    <script>
    var mediaId = <?php echo $id; ?>;
    var mediaType = '<?php echo $type; ?>';
    var title = <?php echo json_encode($title); ?>;
    var poster = '<?php echo $detail['poster_path']; ?>';
    var currentSeason = <?php echo $season_num; ?>;
    var currentAudio = 'original';
    var playSourceUrl = '<?php echo $play_source ? e($play_source['url']) : ''; ?>';
    var parserUrl = '<?php echo $play_source ? e($play_source['parser_url']) : 'https://svip.ffzyplay.com/?url='; ?>';
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) document.getElementById('navbar').classList.add('scrolled');
        else document.getElementById('navbar').classList.remove('scrolled');
    });
    
    var mobileMenuOpen = false;
    function toggleMobileMenu() {
        mobileMenuOpen = !mobileMenuOpen;
        document.getElementById('navLinks').classList.toggle('mobile-open', mobileMenuOpen);
    }
    
    function doSearch() {
        var q = document.getElementById('searchInput').value.trim();
        if (q) location.href = 'search.php?q=' + encodeURIComponent(q);
    }
    
    function selectAudio(el) {
        document.querySelectorAll('.audio-option').forEach(function(o){o.classList.remove('active')});
        el.classList.add('active');
        currentAudio = el.dataset.audio;
    }
    
    <?php if (!$current_user): ?>
    function checkLogin() {
        location.href = 'login.php?msg=login_required';
        return false;
    }
    <?php else: ?>
    function checkLogin() { return true; }
    <?php endif; ?>
    
    function playMovie() {
        if (!checkLogin()) return;
        var searchTitle = encodeURIComponent(title);
        var playUrl = 'play.php?type=movie&id=' + mediaId + '&title=' + searchTitle + '&audio=' + currentAudio;
        location.href = playUrl;
    }
    
    function playEpisode(ep) {
        if (!checkLogin()) return;
        var searchTitle = encodeURIComponent(title);
        var playUrl = 'play.php?type=tv&id=' + mediaId + '&season=' + currentSeason + '&episode=' + ep + '&title=' + searchTitle + '&audio=' + currentAudio;
        location.href = playUrl;
    }
    
    function toggleFavorite() {
        <?php if (!$current_user): ?>
        location.href = 'login.php?msg=login_required';
        return;
        <?php endif; ?>
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var res = JSON.parse(xhr.responseText);
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                var favIcon = document.getElementById('favIcon');
                var favText = document.getElementById('favText');
                if (res.message === '收藏成功') {
                    favIcon.classList.add('active');
                    favText.textContent = '已收藏';
                } else {
                    favIcon.classList.remove('active');
                    favText.textContent = '收藏';
                }
            }
        };
        xhr.send('action=toggle_favorite&media_id=' + mediaId + '&media_type=' + mediaType + '&title=' + encodeURIComponent(title) + '&poster=' + encodeURIComponent(poster));
    }
    
    function showToast(message, type) {
        var container = document.getElementById('toastContainer');
        var toast = document.createElement('div');
        toast.className = 'toast ' + (type||'');
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(function(){
            toast.style.opacity='0';toast.style.transform='translateX(100%)';
            setTimeout(function(){toast.remove();},300);
        },2500);
    }
    </script>
</body>
</html>
