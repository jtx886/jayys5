<?php
require_once 'functions.php';
$user = require_login();
$theme_color = get_setting('theme_color', '#e50914');

// 获取收藏列表
$favorites = db()->fetchAll("SELECT * FROM favorites WHERE user_id = ? ORDER BY created_at DESC", array($user['id']));

// 获取观看历史
$history = db()->fetchAll("SELECT * FROM watch_history WHERE user_id = ? ORDER BY watched_at DESC LIMIT 50", array($user['id']));

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'favorites';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - Jay影视</title>
    <link rel="stylesheet" href="style.css">
    <style>
    :root { --primary-color: <?php echo $theme_color; ?>; }
    .avatar-upload-label {
        cursor: pointer;
        display: inline-block;
    }
    #avatarInput { display: none; }
    .avatar-presets {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 15px;
    }
    .avatar-preset {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        cursor: pointer;
        border: 3px solid transparent;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 20px;
    }
    .avatar-preset:hover, .avatar-preset.selected {
        border-color: var(--primary-color);
        transform: scale(1.1);
    }
    .history-item {
        display: flex;
        gap: 15px;
        background: var(--bg-card);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 12px;
        transition: var(--transition);
    }
    .history-item:hover {
        background: var(--bg-card-hover);
    }
    .history-poster {
        width: 80px;
        aspect-ratio: 2/3;
        border-radius: 6px;
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
    }
    .history-info { flex: 1; }
    .history-title { font-weight: 600; margin-bottom: 5px; }
    .history-meta { font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; }
    .history-time { font-size: 12px; color: var(--text-muted); }
    .delete-btn {
        background: transparent;
        color: var(--text-muted);
        padding: 8px;
        border-radius: 50%;
        transition: var(--transition);
        align-self: flex-start;
    }
    .delete-btn:hover {
        background: rgba(229,9,20,0.1);
        color: var(--danger);
    }
    .delete-icon {
        width: 18px;
        height: 18px;
        position: relative;
    }
    .delete-icon::before, .delete-icon::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 2px;
        background: currentColor;
        top: 50%;
        left: 0;
    }
    .delete-icon::before { transform: rotate(45deg); }
    .delete-icon::after { transform: rotate(-45deg); }
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
            <div class="search-box">
                <div class="search-icon"></div>
                <input type="text" id="searchInput" placeholder="搜索影视..." onkeypress="if(event.key==='Enter')doSearch()">
            </div>
            <div class="nav-user">
                <div class="user-avatar">
                    <?php if ($user['avatar']): ?>
                        <img src="<?php echo e($user['avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <?php echo mb_substr($user['username'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div class="user-dropdown">
                    <a href="profile.php" class="active">个人中心</a>
                    <?php if ($user['is_admin']): ?><a href="admin/">管理后台</a><?php endif; ?>
                    <a href="logout.php">退出登录</a>
                </div>
            </div>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()"><span></span><span></span><span></span></div>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="profile-page">
            <div class="profile-header">
                <label class="avatar-upload-label" for="avatarInput" onclick="event.preventDefault();showAvatarModal();">
                    <div class="profile-avatar" id="profileAvatar">
                        <?php if ($user['avatar']): ?>
                            <img src="<?php echo e($user['avatar']); ?>" id="avatarImg">
                        <?php else: ?>
                            <span id="avatarText"><?php echo mb_substr($user['username'], 0, 1); ?></span>
                        <?php endif; ?>
                        <div class="avatar-edit">更换头像</div>
                    </div>
                </label>
                <div class="profile-info">
                    <h1><?php echo e($user['username']); ?></h1>
                    <div class="profile-email"><?php echo e($user['email']); ?></div>
                    <div style="margin-top:10px;">
                        <?php if ($user['is_admin']): ?>
                        <span class="badge badge-admin">管理员</span>
                        <?php endif; ?>
                        <span style="color:var(--text-muted);font-size:13px;margin-left:10px;">注册时间：<?php echo date('Y-m-d', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="tabs" style="padding:0;border-bottom:1px solid var(--border-color);">
                <div class="tab <?php echo $tab=='favorites'?'active':''; ?>" onclick="switchTab('favorites')">
                    ❤️ 我的收藏 (<?php echo count($favorites); ?>)
                </div>
                <div class="tab <?php echo $tab=='history'?'active':''; ?>" onclick="switchTab('history')">
                    📺 观看历史 (<?php echo count($history); ?>)
                </div>
            </div>
            
            <!-- 收藏列表 -->
            <div id="favoritesTab" style="<?php echo $tab!='favorites'?'display:none;':''; ?>">
                <?php if (count($favorites) > 0): ?>
                <div class="movie-row" style="margin-top:20px;">
                    <?php foreach ($favorites as $fav): ?>
                    <div class="movie-card" onclick="location.href='detail.php?type=<?php echo $fav['media_type']; ?>&id=<?php echo $fav['media_id']; ?>'">
                        <div class="movie-poster" style="background-image: url('<?php echo tmdb_image($fav['poster']); ?>');">
                            <div class="movie-poster-overlay">
                                <div class="movie-card-buttons">
                                    <button class="card-btn primary" onclick="event.stopPropagation();location.href='detail.php?type=<?php echo $fav['media_type']; ?>&id=<?php echo $fav['media_id']; ?>'">
                                        <span class="play-icon-sm"></span>
                                    </button>
                                    <button class="card-btn" style="background:var(--danger);border-color:var(--danger);" onclick="event.stopPropagation();removeFavorite(<?php echo $fav['id']; ?>)">
                                        <span class="delete-icon" style="width:14px;height:14px;"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="movie-info">
                            <div class="movie-title"><?php echo e($fav['title']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">❤️</div>
                    <div class="empty-text">还没有收藏任何影视</div>
                    <a href="index.php" class="btn btn-primary">去发现好片</a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 观看历史 -->
            <div id="historyTab" style="<?php echo $tab!='history'?'display:none;':''; ?>margin-top:20px;">
                <?php if (count($history) > 0): ?>
                <?php foreach ($history as $h): ?>
                <div class="history-item" id="history-<?php echo $h['id']; ?>">
                    <div class="history-poster" style="background-image:url('<?php echo tmdb_image($h['poster'], 'w200'); ?>');"
                         onclick="location.href='detail.php?type=<?php echo $h['media_type']; ?>&id=<?php echo $h['media_id']; ?><?php echo $h['season']?'&season='.$h['season']:''; ?>'"
                         style="cursor:pointer;"></div>
                    <div class="history-info" onclick="location.href='detail.php?type=<?php echo $h['media_type']; ?>&id=<?php echo $h['media_id']; ?><?php echo $h['season']?'&season='.$h['season']:''; ?>'" style="cursor:pointer;">
                        <div class="history-title"><?php echo e($h['title']); ?></div>
                        <div class="history-meta">
                            <?php if ($h['season']): ?>第<?php echo $h['season']; ?>季<?php endif; ?>
                            <?php if ($h['episode']): ?> 第<?php echo $h['episode']; ?>集<?php endif; ?>
                        </div>
                        <div class="history-time"><?php echo time_ago($h['watched_at']); ?></div>
                    </div>
                    <button class="delete-btn" onclick="deleteHistory(<?php echo $h['id']; ?>)">
                        <span class="delete-icon"></span>
                    </button>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📺</div>
                    <div class="empty-text">还没有观看记录</div>
                    <a href="index.php" class="btn btn-primary">去看片</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 头像选择弹窗 -->
    <div class="modal-overlay" id="avatarModal" style="display:none;">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">选择头像</div>
                <div class="modal-close" onclick="closeAvatarModal()">×</div>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-secondary);margin-bottom:15px;font-size:14px;">选择一个预设头像，或输入图片URL</p>
                <input type="text" id="customAvatarUrl" class="form-input" placeholder="输入图片URL（可选）" style="margin-bottom:15px;">
                <div class="avatar-presets" id="avatarPresets">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeAvatarModal()">取消</button>
                <button class="btn btn-primary" onclick="saveAvatar()">保存</button>
            </div>
        </div>
    </div>
    
    <script>
    var selectedAvatar = null;
    
    var avatarColors = [
        'linear-gradient(135deg,#667eea,#764ba2)',
        'linear-gradient(135deg,#f093fb,#f5576c)',
        'linear-gradient(135deg,#4facfe,#00f2fe)',
        'linear-gradient(135deg,#43e97b,#38f9d7)',
        'linear-gradient(135deg,#fa709a,#fee140)',
        'linear-gradient(135deg,#e50914,#b20710)',
        'linear-gradient(135deg,#30cfd0,#330867)',
        'linear-gradient(135deg,#a8edea,#fed6e3)'
    ];
    
    function initAvatarPresets() {
        var container = document.getElementById('avatarPresets');
        var initials = '<?php echo mb_substr($user['username'], 0, 1); ?>';
        avatarColors.forEach(function(color, idx) {
            var div = document.createElement('div');
            div.className = 'avatar-preset';
            div.style.background = color;
            div.textContent = initials;
            div.onclick = function() {
                document.querySelectorAll('.avatar-preset').forEach(function(p){p.classList.remove('selected')});
                div.classList.add('selected');
                selectedAvatar = {color: color, url: null};
                document.getElementById('customAvatarUrl').value = '';
            };
            container.appendChild(div);
        });
    }
    
    function showAvatarModal() {
        document.getElementById('avatarModal').style.display = 'flex';
        selectedAvatar = null;
        initAvatarPresets();
    }
    
    function closeAvatarModal() {
        document.getElementById('avatarModal').style.display = 'none';
    }
    
    function saveAvatar() {
        var customUrl = document.getElementById('customAvatarUrl').value.trim();
        var avatarValue = customUrl ? customUrl : (selectedAvatar ? selectedAvatar.color : null);
        
        if (!avatarValue) {
            showToast('请选择一个头像', 'error');
            return;
        }
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var res = JSON.parse(xhr.responseText);
            if (res.success) {
                showToast(res.message, 'success');
                setTimeout(function(){location.reload();}, 1000);
            } else {
                showToast(res.message, 'error');
            }
        };
        xhr.send('action=update_avatar&avatar=' + encodeURIComponent(avatarValue));
    }
    
    document.getElementById('customAvatarUrl').addEventListener('input', function(){
        if (this.value.trim()) {
            document.querySelectorAll('.avatar-preset').forEach(function(p){p.classList.remove('selected')});
            selectedAvatar = {url: this.value.trim(), color: null};
        }
    });
    
    function switchTab(tab) {
        document.querySelectorAll('.tab').forEach(function(t){t.classList.remove('active')});
        document.getElementById('favoritesTab').style.display = tab==='favorites'?'block':'none';
        document.getElementById('historyTab').style.display = tab==='history'?'block':'none';
        event.target.classList.add('active');
        history.replaceState(null, null, '?tab=' + tab);
    }
    
    function removeFavorite(id) {
        if (!confirm('确定要取消收藏吗？')) return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var res = JSON.parse(xhr.responseText);
            showToast(res.message, res.success?'success':'error');
            if (res.success) setTimeout(function(){location.reload();}, 800);
        };
        xhr.send('action=remove_favorite&id=' + id);
    }
    
    function deleteHistory(id) {
        if (!confirm('确定要删除这条观看记录吗？')) return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var res = JSON.parse(xhr.responseText);
            showToast(res.message, res.success?'success':'error');
            if (res.success) {
                var el = document.getElementById('history-' + id);
                if (el) el.remove();
            }
        };
        xhr.send('action=delete_history&id=' + id);
    }
    
    function doSearch() {
        var q = document.getElementById('searchInput').value.trim();
        if (q) location.href = 'search.php?q=' + encodeURIComponent(q);
    }
    
    var mobileMenuOpen = false;
    function toggleMobileMenu() {
        mobileMenuOpen = !mobileMenuOpen;
        document.getElementById('navLinks').classList.toggle('mobile-open', mobileMenuOpen);
    }
    
    function showToast(message, type) {
        var container = document.getElementById('toastContainer');
        var toast = document.createElement('div');
        toast.className = 'toast '+(type||'');
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
