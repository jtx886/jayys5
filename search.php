<?php
require_once 'functions.php';
$current_user = current_user();
$theme_color = get_setting('theme_color', '#e50914');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = array();

if ($q) {
    $data = tmdb_api('/search/multi', array('query' => $q, 'page' => 1, 'language' => 'zh-CN'));
    if ($data && isset($data['results'])) {
        $results = array_filter($data['results'], function($item) {
            return in_array($item['media_type'], array('movie', 'tv'));
        });
        $results = array_values($results);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>搜索 - <?php echo e($q); ?> - Jay影视</title>
    <link rel="stylesheet" href="style.css">
    <style>
    :root { --primary-color: <?php echo $theme_color; ?>; }
    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
    
    <nav class="navbar scrolled" id="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">
                <span class="logo-icon">J</span>
                Jay影视
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">首页</a></li>
                <li><a href="category.php?type=movie">电影</a></li>
                <li><a href="category.php?type=tv">电视剧</a></li>
                <li><a href="category.php?type=anime">动漫</a></li>
                <li><a href="category.php?type=variety">综艺</a></li>
                <li><a href="feedback.php">反馈</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <div class="search-box" style="display:flex;">
                <div class="search-icon"></div>
                <input type="text" id="searchInput" placeholder="搜索影视..." value="<?php echo e($q); ?>" onkeypress="if(event.key==='Enter')doSearch()">
            </div>
            <?php if ($current_user): ?>
            <div class="nav-user">
                <div class="user-avatar">
                    <?php if ($current_user['avatar']): ?>
                        <img src="<?php echo e($current_user['avatar']); ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <?php echo mb_substr($current_user['username'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div class="user-dropdown">
                    <a href="profile.php">个人中心</a>
                    <?php if ($current_user['is_admin']): ?>
                    <a href="admin/">管理后台</a>
                    <?php endif; ?>
                    <a href="logout.php">退出登录</a>
                </div>
            </div>
            <?php else: ?>
            <div class="nav-user">
                <a href="login.php" class="btn btn-primary btn-sm" style="padding:8px 20px;">登录</a>
            </div>
            <?php endif; ?>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    
    <div class="main-content" style="padding-top: 100px;">
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">搜索: <?php echo e($q); ?></h2>
            </div>
            
            <?php if ($q && count($results) > 0): ?>
            <div class="movie-row">
                <?php foreach ($results as $item): ?>
                <?php 
                $title = isset($item['title']) ? $item['title'] : $item['name'];
                $date = isset($item['release_date']) ? $item['release_date'] : (isset($item['first_air_date']) ? $item['first_air_date'] : '');
                ?>
                <div class="movie-card" onclick="location.href='detail.php?type=<?php echo $item['media_type']; ?>&id=<?php echo $item['id']; ?>'">
                    <div class="movie-poster" style="background-image: url('<?php echo tmdb_image($item['poster_path']); ?>');">
                        <div class="movie-poster-overlay">
                            <div class="movie-card-buttons">
                                <button class="card-btn primary" onclick="event.stopPropagation();location.href='detail.php?type=<?php echo $item['media_type']; ?>&id=<?php echo $item['id']; ?>'">
                                    <span class="play-icon-sm"></span>
                                </button>
                                <?php if ($current_user): ?>
                                <button class="card-btn" onclick="event.stopPropagation();toggleFavorite(<?php echo $item['id']; ?>,'<?php echo $item['media_type']; ?>','<?php echo addslashes($title); ?>','<?php echo $item['poster_path']; ?>')">
                                    <span class="plus-icon"></span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="movie-info">
                        <div class="movie-title" title="<?php echo e($title); ?>"><?php echo e($title); ?></div>
                        <div class="movie-meta">
                            <span class="movie-score">★ <?php echo number_format($item['vote_average'], 1); ?></span>
                            <span><?php echo e(substr($date, 0, 4)); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif ($q): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <div class="empty-text">没有找到相关结果</div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🎬</div>
                <div class="empty-text">请输入关键词搜索</div>
            </div>
            <?php endif; ?>
        </section>
    </div>
    
    <script>
    function doSearch() {
        var q = document.getElementById('searchInput').value.trim();
        if (q) location.href = 'search.php?q=' + encodeURIComponent(q);
    }
    var mobileMenuOpen = false;
    function toggleMobileMenu() {
        document.getElementById('navLinks').classList.toggle('mobile-open');
    }
    function toggleFavorite(mediaId, mediaType, title, poster) {
        <?php if (!$current_user): ?>location.href='login.php?msg=login_required';return;<?php endif; ?>
        var xhr=new XMLHttpRequest();xhr.open('POST','api.php',true);
        xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        xhr.onload=function(){var res=JSON.parse(xhr.responseText);showToast(res.message,res.success?'success':'error');};
        xhr.send('action=toggle_favorite&media_id='+mediaId+'&media_type='+mediaType+'&title='+encodeURIComponent(title)+'&poster='+encodeURIComponent(poster));
    }
    function showToast(msg,type){var c=document.getElementById('toastContainer'),t=document.createElement('div');t.className='toast '+(type||'');t.textContent=msg;c.appendChild(t);setTimeout(function(){t.style.opacity='0';t.style.transform='translateX(100%)';setTimeout(function(){t.remove()},300)},2500)}
    </script>
</body>
</html>
