<?php
require_once 'functions.php';
$current_user = current_user();
$theme_color = get_setting('theme_color', '#e50914');

// 获取TMDB数据
$trending_movies = tmdb_api('/trending/movie/week', array('page' => 1));
$trending_tv = tmdb_api('/trending/tv/week', array('page' => 1));
$popular_movies = tmdb_api('/movie/popular', array('page' => 1));
$popular_tv = tmdb_api('/tv/popular', array('page' => 1));
$chinese_movies = tmdb_api('/discover/movie', array('with_original_language' => 'zh', 'sort_by' => 'popularity.desc', 'page' => 1));
$anime = tmdb_api('/discover/tv', array('with_genres' => '16', 'sort_by' => 'popularity.desc', 'page' => 1));
$variety = tmdb_api('/discover/tv', array('with_genres' => '10764', 'sort_by' => 'popularity.desc', 'page' => 1));

$featured = null;
if ($trending_movies && isset($trending_movies['results']) && count($trending_movies['results']) > 0) {
    $featured = $trending_movies['results'][0];
}

// 检查公告
$announcement = get_active_announcement();
$show_announcement = false;
if ($announcement) {
    if (!$current_user || !has_dismissed_announcement($current_user['id'], $announcement['id'])) {
        $show_announcement = true;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jay影视 - 海量影视免费看</title>
    <link rel="stylesheet" href="style.css">
    <style>
    :root {
        --primary-color: <?php echo $theme_color; ?>;
        --primary-hover: <?php echo $theme_color; ?>;
    }
    </style>
</head>
<body>
    <!-- Toast容器 -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- 公告弹窗 -->
    <?php if ($show_announcement): ?>
    <div class="modal-overlay" id="announcementModal">
        <div class="modal announcement-modal">
            <div class="announcement-banner">
                <div class="announcement-banner-icon">📢</div>
                <h2><?php echo e($announcement['title']); ?></h2>
            </div>
            <div class="announcement-body">
                <div class="announcement-content">
                    <?php echo nl2br(e($announcement['content'])); ?>
                </div>
                <label class="announcement-checkbox">
                    <div class="checkbox-custom" id="dismissCheck"></div>
                    <span>不再提示此公告</span>
                </label>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeAnnouncement()">我知道了</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 导航栏 -->
    <nav class="navbar" id="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">
                <span class="logo-icon">J</span>
                Jay影视
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" class="active">首页</a></li>
                <li><a href="category.php?type=movie">电影</a></li>
                <li><a href="category.php?type=tv">电视剧</a></li>
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
    
    <!-- 主要内容 -->
    <div class="main-content">
        <?php if ($featured): ?>
        <!-- Hero区域 -->
        <section class="hero">
            <div class="hero-bg" style="background-image: url('<?php echo tmdb_image($featured['backdrop_path'], 'original'); ?>');"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 class="hero-title"><?php echo e($featured['title'] ? $featured['title'] : $featured['name']); ?></h1>
                <div class="hero-meta">
                    <span class="hero-score">★ <?php echo number_format($featured['vote_average'], 1); ?>分</span>
                    <span><?php echo e($featured['release_date'] ? substr($featured['release_date'], 0, 4) : substr($featured['first_air_date'], 0, 4)); ?></span>
                </div>
                <p class="hero-desc"><?php echo e($featured['overview']); ?></p>
                <div class="hero-buttons">
                    <a href="detail.php?type=movie&id=<?php echo $featured['id']; ?>" class="btn btn-play">
                        <span class="play-icon"></span>
                        播放
                    </a>
                    <a href="detail.php?type=movie&id=<?php echo $featured['id']; ?>" class="btn btn-info">
                        <span class="info-icon">i</span>
                        详情
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- 热门电影 -->
        <?php if ($popular_movies && isset($popular_movies['results']) && count($popular_movies['results']) > 0): ?>
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">🔥 热门电影</h2>
            </div>
            <div class="movie-row">
                <?php foreach (array_slice($popular_movies['results'], 0, 12) as $movie): ?>
                <div class="movie-card" onclick="location.href='detail.php?type=movie&id=<?php echo $movie['id']; ?>'">
                    <div class="movie-poster" style="background-image: url('<?php echo tmdb_image($movie['poster_path']); ?>');">
                        <div class="movie-poster-overlay">
                            <div class="movie-card-buttons">
                                <button class="card-btn primary" onclick="event.stopPropagation();location.href='detail.php?type=movie&id=<?php echo $movie['id']; ?>'">
                                    <span class="play-icon-sm"></span>
                                </button>
                                <?php if ($current_user): ?>
                                <button class="card-btn" onclick="event.stopPropagation();toggleFavorite(<?php echo $movie['id']; ?>,'movie','<?php echo addslashes($movie['title']); ?>','<?php echo $movie['poster_path']; ?>')">
                                    <span class="plus-icon"></span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="movie-info">
                        <div class="movie-title" title="<?php echo e($movie['title']); ?>"><?php echo e($movie['title']); ?></div>
                        <div class="movie-meta">
                            <span class="movie-score">★ <?php echo number_format($movie['vote_average'], 1); ?></span>
                            <span><?php echo e(substr($movie['release_date'], 0, 4)); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- 热门电视剧 -->
        <?php if ($popular_tv && isset($popular_tv['results']) && count($popular_tv['results']) > 0): ?>
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">📺 热门电视剧</h2>
            </div>
            <div class="movie-row">
                <?php foreach (array_slice($popular_tv['results'], 0, 12) as $tv): ?>
                <div class="movie-card" onclick="location.href='detail.php?type=tv&id=<?php echo $tv['id']; ?>'">
                    <div class="movie-poster" style="background-image: url('<?php echo tmdb_image($tv['poster_path']); ?>');">
                        <div class="movie-poster-overlay">
                            <div class="movie-card-buttons">
                                <button class="card-btn primary" onclick="event.stopPropagation();location.href='detail.php?type=tv&id=<?php echo $tv['id']; ?>'">
                                    <span class="play-icon-sm"></span>
                                </button>
                                <?php if ($current_user): ?>
                                <button class="card-btn" onclick="event.stopPropagation();toggleFavorite(<?php echo $tv['id']; ?>,'tv','<?php echo addslashes($tv['name']); ?>','<?php echo $tv['poster_path']; ?>')">
                                    <span class="plus-icon"></span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="movie-info">
                        <div class="movie-title" title="<?php echo e($tv['name']); ?>"><?php echo e($tv['name']); ?></div>
                        <div class="movie-meta">
                            <span class="movie-score">★ <?php echo number_format($tv['vote_average'], 1); ?></span>
                            <span><?php echo e(substr($tv['first_air_date'], 0, 4)); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- 动漫 -->
        <?php if ($anime && isset($anime['results']) && count($anime['results']) > 0): ?>
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">🎬 动漫精选</h2>
            </div>
            <div class="movie-row">
                <?php foreach (array_slice($anime['results'], 0, 12) as $item): ?>
                <div class="movie-card" onclick="location.href='detail.php?type=tv&id=<?php echo $item['id']; ?>'">
                    <div class="movie-poster" style="background-image: url('<?php echo tmdb_image($item['poster_path']); ?>');">
                        <div class="movie-poster-overlay">
                            <div class="movie-card-buttons">
                                <button class="card-btn primary" onclick="event.stopPropagation();location.href='detail.php?type=tv&id=<?php echo $item['id']; ?>'">
                                    <span class="play-icon-sm"></span>
                                </button>
                                <?php if ($current_user): ?>
                                <button class="card-btn" onclick="event.stopPropagation();toggleFavorite(<?php echo $item['id']; ?>,'tv','<?php echo addslashes($item['name']); ?>','<?php echo $item['poster_path']; ?>')">
                                    <span class="plus-icon"></span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="movie-info">
                        <div class="movie-title" title="<?php echo e($item['name']); ?>"><?php echo e($item['name']); ?></div>
                        <div class="movie-meta">
                            <span class="movie-score">★ <?php echo number_format($item['vote_average'], 1); ?></span>
                            <span><?php echo e(substr($item['first_air_date'], 0, 4)); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- 综艺 -->
        <?php if ($variety && isset($variety['results']) && count($variety['results']) > 0): ?>
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">🎤 综艺娱乐</h2>
            </div>
            <div class="movie-row">
                <?php foreach (array_slice($variety['results'], 0, 12) as $item): ?>
                <div class="movie-card" onclick="location.href='detail.php?type=tv&id=<?php echo $item['id']; ?>'">
                    <div class="movie-poster" style="background-image: url('<?php echo tmdb_image($item['poster_path']); ?>');">
                        <div class="movie-poster-overlay">
                            <div class="movie-card-buttons">
                                <button class="card-btn primary" onclick="event.stopPropagation();location.href='detail.php?type=tv&id=<?php echo $item['id']; ?>'">
                                    <span class="play-icon-sm"></span>
                                </button>
                                <?php if ($current_user): ?>
                                <button class="card-btn" onclick="event.stopPropagation();toggleFavorite(<?php echo $item['id']; ?>,'tv','<?php echo addslashes($item['name']); ?>','<?php echo $item['poster_path']; ?>')">
                                    <span class="plus-icon"></span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="movie-info">
                        <div class="movie-title" title="<?php echo e($item['name']); ?>"><?php echo e($item['name']); ?></div>
                        <div class="movie-meta">
                            <span class="movie-score">★ <?php echo number_format($item['vote_average'], 1); ?></span>
                            <span><?php echo e(substr($item['first_air_date'], 0, 4)); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
    
    <div style="height: 50px;"></div>
    
    <script>
    // 导航栏滚动效果
    window.addEventListener('scroll', function() {
        var navbar = document.getElementById('navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // 移动端菜单
    var mobileMenuOpen = false;
    function toggleMobileMenu() {
        var navLinks = document.getElementById('navLinks');
        mobileMenuOpen = !mobileMenuOpen;
        if (mobileMenuOpen) {
            navLinks.classList.add('mobile-open');
        } else {
            navLinks.classList.remove('mobile-open');
        }
    }
    
    // 搜索
    function doSearch() {
        var q = document.getElementById('searchInput').value.trim();
        if (q) {
            location.href = 'search.php?q=' + encodeURIComponent(q);
        }
    }
    
    // 公告
    <?php if ($show_announcement && $current_user): ?>
    var dismissChecked = false;
    document.getElementById('dismissCheck').addEventListener('click', function() {
        dismissChecked = !dismissChecked;
        this.classList.toggle('checked', dismissChecked);
    });
    
    function closeAnnouncement() {
        document.getElementById('announcementModal').style.display = 'none';
        if (dismissChecked) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=dismiss_announcement&announcement_id=<?php echo $announcement['id']; ?>');
        }
    }
    <?php elseif ($show_announcement): ?>
    function closeAnnouncement() {
        document.getElementById('announcementModal').style.display = 'none';
    }
    <?php endif; ?>
    
    // 收藏
    function toggleFavorite(mediaId, mediaType, title, poster) {
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
        };
        xhr.send('action=toggle_favorite&media_id=' + mediaId + '&media_type=' + mediaType + '&title=' + encodeURIComponent(title) + '&poster=' + encodeURIComponent(poster));
    }
    
    // Toast提示
    function showToast(message, type) {
        var container = document.getElementById('toastContainer');
        var toast = document.createElement('div');
        toast.className = 'toast ' + (type || '');
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function() { toast.remove(); }, 300);
        }, 2500);
    }
    </script>
</body>
</html>
