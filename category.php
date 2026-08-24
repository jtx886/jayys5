<?php
require_once 'functions.php';
$current_user = current_user();
$theme_color = get_setting('theme_color', '#e50914');

$type = isset($_GET['type']) ? $_GET['type'] : 'movie';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$genre = isset($_GET['genre']) ? $_GET['genre'] : '';

$type_names = array(
    'movie' => '电影',
    'tv' => '电视剧',
    'anime' => '动漫',
    'variety' => '综艺'
);

$current_type_name = isset($type_names[$type]) ? $type_names[$type] : '电影';

$params = array('page' => $page, 'language' => 'zh-CN');
if ($genre) {
    $params['with_genres'] = $genre;
}

switch ($type) {
    case 'movie':
        if ($genre) {
            $data = tmdb_api('/discover/movie', $params);
        } else {
            $data = tmdb_api('/movie/popular', $params);
        }
        $genres = tmdb_api('/genre/movie/list');
        break;
    case 'tv':
        if ($genre) {
            $data = tmdb_api('/discover/tv', $params);
        } else {
            $data = tmdb_api('/tv/popular', $params);
        }
        $genres = tmdb_api('/genre/tv/list');
        break;
    case 'anime':
        $params['with_genres'] = '16';
        $params['sort_by'] = 'popularity.desc';
        $data = tmdb_api('/discover/tv', $params);
        $genres = array('genres' => array());
        break;
    case 'variety':
        $params['with_genres'] = '10764';
        $params['sort_by'] = 'popularity.desc';
        $data = tmdb_api('/discover/tv', $params);
        $genres = array('genres' => array());
        break;
    default:
        $data = tmdb_api('/movie/popular', $params);
        $genres = tmdb_api('/genre/movie/list');
}

$results = isset($data['results']) ? $data['results'] : array();
$total_pages = isset($data['total_pages']) ? min($data['total_pages'], 20) : 1;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($current_type_name); ?> - Jay影视</title>
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
                <li><a href="category.php?type=movie" <?php echo $type == 'movie' ? 'class="active"' : ''; ?>>电影</a></li>
                <li><a href="category.php?type=tv" <?php echo $type == 'tv' ? 'class="active"' : ''; ?>>电视剧</a></li>
                <li><a href="category.php?type=anime" <?php echo $type == 'anime' ? 'class="active"' : ''; ?>>动漫</a></li>
                <li><a href="category.php?type=variety" <?php echo $type == 'variety' ? 'class="active"' : ''; ?>>综艺</a></li>
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
    
    <div class="main-content" style="padding-top: 100px;">
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title"><?php echo e($current_type_name); ?></h2>
            </div>
            
            <?php if (isset($genres['genres']) && count($genres['genres']) > 0): ?>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 25px;">
                <a href="?type=<?php echo $type; ?>" class="btn <?php echo !$genre ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">全部</a>
                <?php foreach ($genres['genres'] as $g): ?>
                <a href="?type=<?php echo $type; ?>&genre=<?php echo $g['id']; ?>" class="btn <?php echo $genre == $g['id'] ? 'btn-primary' : 'btn-secondary'; ?> btn-sm"><?php echo e($g['name']); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (count($results) > 0): ?>
            <div class="movie-row">
                <?php foreach ($results as $item): ?>
                <?php 
                $item_type = ($type == 'movie') ? 'movie' : 'tv';
                $title = isset($item['title']) ? $item['title'] : $item['name'];
                $date = isset($item['release_date']) ? $item['release_date'] : (isset($item['first_air_date']) ? $item['first_air_date'] : '');
                ?>
                <div class="movie-card" onclick="location.href='detail.php?type=<?php echo $item_type; ?>&id=<?php echo $item['id']; ?>'">
                    <div class="movie-poster" style="background-image: url('<?php echo tmdb_image($item['poster_path']); ?>');">
                        <div class="movie-poster-overlay">
                            <div class="movie-card-buttons">
                                <button class="card-btn primary" onclick="event.stopPropagation();location.href='detail.php?type=<?php echo $item_type; ?>&id=<?php echo $item['id']; ?>'">
                                    <span class="play-icon-sm"></span>
                                </button>
                                <?php if ($current_user): ?>
                                <button class="card-btn" onclick="event.stopPropagation();toggleFavorite(<?php echo $item['id']; ?>,'<?php echo $item_type; ?>','<?php echo addslashes($title); ?>','<?php echo $item['poster_path']; ?>')">
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
            
            <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 10px; margin-top: 40px;">
                <?php if ($page > 1): ?>
                <a href="?type=<?php echo $type; ?>&page=<?php echo $page-1; ?><?php echo $genre ? '&genre='.$genre : ''; ?>" class="btn btn-secondary">上一页</a>
                <?php endif; ?>
                <span style="padding: 10px 20px; color: var(--text-secondary);"><?php echo $page; ?> / <?php echo $total_pages; ?></span>
                <?php if ($page < $total_pages): ?>
                <a href="?type=<?php echo $type; ?>&page=<?php echo $page+1; ?><?php echo $genre ? '&genre='.$genre : ''; ?>" class="btn btn-primary">下一页</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🎬</div>
                <div class="empty-text">暂无内容</div>
            </div>
            <?php endif; ?>
        </section>
    </div>
    
    <script>
    window.addEventListener('scroll', function() {
        var navbar = document.getElementById('navbar');
        if (window.scrollY > 50) navbar.classList.add('scrolled');
        else navbar.classList.remove('scrolled');
    });
    
    var mobileMenuOpen = false;
    function toggleMobileMenu() {
        var navLinks = document.getElementById('navLinks');
        mobileMenuOpen = !mobileMenuOpen;
        navLinks.classList.toggle('mobile-open', mobileMenuOpen);
    }
    
    function doSearch() {
        var q = document.getElementById('searchInput').value.trim();
        if (q) location.href = 'search.php?q=' + encodeURIComponent(q);
    }
    
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
        xhr.send('action=toggle_favorite&media_id='+mediaId+'&media_type='+mediaType+'&title='+encodeURIComponent(title)+'&poster='+encodeURIComponent(poster));
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
