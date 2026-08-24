<?php
require_once '../functions.php';
$admin = require_admin();
$theme_color = get_setting('theme_color', '#e50914');

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// 统计数据
$total_users = db()->fetch("SELECT COUNT(*) as cnt FROM users")['cnt'];
$total_feedbacks = db()->fetch("SELECT COUNT(*) as cnt FROM feedbacks")['cnt'];
$total_favorites = db()->fetch("SELECT COUNT(*) as cnt FROM favorites")['cnt'];
$total_history = db()->fetch("SELECT COUNT(*) as cnt FROM watch_history")['cnt'];
$banned_users = db()->fetch("SELECT COUNT(*) as cnt FROM users WHERE is_banned = 1")['cnt'];

// 最新注册用户
$new_users = db()->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// 最新反馈
$new_feedbacks = db()->fetchAll("SELECT f.*, u.username FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 5");

// 处理POST操作
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'update_theme') {
        $color = $_POST['theme_color'];
        update_setting('theme_color', $color);
        $theme_color = $color;
        $success_msg = '主题颜色更新成功！';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - Jay影视</title>
    <link rel="stylesheet" href="../style.css">
    <style>
    :root { --primary-color: <?php echo $theme_color; ?>; }
    .admin-sidebar .logo { font-size: 22px; }
    .stat-card.primary { border-left: 4px solid var(--primary-color); }
    .stat-card.success { border-left: 4px solid var(--success); }
    .stat-card.warning { border-left: 4px solid var(--warning); }
    .stat-card.danger { border-left: 4px solid var(--danger); }
    .list-card {
        background: var(--bg-card);
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
    }
    .list-card h3 {
        font-size: 1.1rem;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }
    .mini-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .mini-list-item:last-child { border-bottom: none; }
    .mini-user {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .mini-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        color: white;
    }
    .color-preview {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        border: 3px solid var(--border-color);
    }
    @media (max-width: 768px) {
        .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s; z-index: 1000; position: fixed; }
        .admin-sidebar.mobile-open { transform: translateX(0); }
        .admin-content { margin-left: 0; padding: 20px 15px; padding-top: 70px; }
        .mobile-toggle { display: flex !important; }
    }
    .mobile-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        width: 45px;
        height: 45px;
        background: var(--primary-color);
        border-radius: 8px;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    </style>
</head>
<body>
    <div class="mobile-toggle" onclick="document.querySelector('.admin-sidebar').classList.toggle('mobile-open')">☰</div>
    
    <div class="admin-page">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <a href="index.php" class="logo">
                    <span class="logo-icon">J</span>
                    Jay影视
                </a>
                <p style="color:var(--text-muted);font-size:12px;margin-top:8px;">管理后台</p>
            </div>
            <ul class="admin-menu">
                <li><a href="index.php?page=dashboard" class="<?php echo $page=='dashboard'?'active':''; ?>">
                    📊 仪表盘
                </a></li>
                <li><a href="index.php?page=users" class="<?php echo $page=='users'?'active':''; ?>">
                    👥 用户管理
                </a></li>
                <li><a href="index.php?page=sources" class="<?php echo $page=='sources'?'active':''; ?>">
                    🎬 播放源管理
                </a></li>
                <li><a href="index.php?page=announcements" class="<?php echo $page=='announcements'?'active':''; ?>">
                    📢 公告管理
                </a></li>
                <li><a href="index.php?page=feedbacks" class="<?php echo $page=='feedbacks'?'active':''; ?>">
                    💬 反馈管理
                </a></li>
                <li><a href="index.php?page=history" class="<?php echo $page=='history'?'active':''; ?>">
                    📺 观看历史
                </a></li>
                <li><a href="index.php?page=favorites" class="<?php echo $page=='favorites'?'active':''; ?>">
                    ❤️ 用户收藏
                </a></li>
                <li><a href="index.php?page=email" class="<?php echo $page=='email'?'active':''; ?>">
                    📧 邮件通知
                </a></li>
                <li><a href="index.php?page=settings" class="<?php echo $page=='settings'?'active':''; ?>">
                    ⚙️ 网站设置
                </a></li>
                <li><a href="../index.php">
                    🏠 返回网站
                </a></li>
                <li><a href="../logout.php">
                    🚪 退出登录
                </a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1 class="admin-title">
                    <?php
                    $page_titles = array(
                        'dashboard' => '仪表盘',
                        'users' => '用户管理',
                        'sources' => '播放源管理',
                        'announcements' => '公告管理',
                        'feedbacks' => '反馈管理',
                        'history' => '观看历史',
                        'favorites' => '用户收藏',
                        'email' => '邮件通知',
                        'settings' => '网站设置'
                    );
                    echo isset($page_titles[$page]) ? $page_titles[$page] : '仪表盘';
                    ?>
                </h1>
                <div style="display:flex;align-items:center;gap:15px;">
                    <span style="color:var(--text-secondary);">
                        <?php echo e($admin['username']); ?>
                        <span class="dev-badge">开发者</span>
                    </span>
                </div>
            </div>
            
            <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo e($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo e($error_msg); ?></div>
            <?php endif; ?>
            
            <?php if ($page == 'dashboard'): ?>
            <div class="dashboard-grid">
                <div class="stat-card primary">
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">总用户数</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $total_feedbacks; ?></div>
                    <div class="stat-label">反馈总数</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $total_history; ?></div>
                    <div class="stat-label">观看记录</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value"><?php echo $banned_users; ?></div>
                    <div class="stat-label">封禁用户</div>
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">
                <div class="list-card">
                    <h3>🆕 最新注册用户</h3>
                    <?php foreach ($new_users as $u): ?>
                    <div class="mini-list-item">
                        <div class="mini-user">
                            <div class="mini-avatar">
                                <?php if ($u['avatar'] && strpos($u['avatar'], 'http') === 0): ?>
                                    <img src="<?php echo e($u['avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                <?php else: ?>
                                    <?php echo mb_substr($u['username'], 0, 1); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight:500;">
                                    <?php echo e($u['username']); ?>
                                    <?php if ($u['is_admin']): ?><span class="badge badge-admin">管理员</span><?php endif; ?>
                                </div>
                                <div style="font-size:12px;color:var(--text-muted);"><?php echo e($u['email']); ?></div>
                            </div>
                        </div>
                        <span style="font-size:12px;color:var(--text-muted);"><?php echo time_ago($u['created_at']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="list-card">
                    <h3>💬 最新反馈</h3>
                    <?php foreach ($new_feedbacks as $fb): ?>
                    <div class="mini-list-item">
                        <div>
                            <div style="font-weight:500;"><?php echo e($fb['title']); ?></div>
                            <div style="font-size:12px;color:var(--text-muted);">
                                <?php echo e($fb['username']); ?> · <?php echo time_ago($fb['created_at']); ?>
                            </div>
                        </div>
                        <a href="index.php?page=feedbacks" class="btn btn-secondary btn-sm">查看</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php elseif ($page == 'users'): ?>
            <?php include 'pages/users.php'; ?>
            
            <?php elseif ($page == 'sources'): ?>
            <?php include 'pages/sources.php'; ?>
            
            <?php elseif ($page == 'announcements'): ?>
            <?php include 'pages/announcements.php'; ?>
            
            <?php elseif ($page == 'feedbacks'): ?>
            <?php include 'pages/feedbacks.php'; ?>
            
            <?php elseif ($page == 'history'): ?>
            <?php include 'pages/history.php'; ?>
            
            <?php elseif ($page == 'favorites'): ?>
            <?php include 'pages/favorites.php'; ?>
            
            <?php elseif ($page == 'email'): ?>
            <?php include 'pages/email.php'; ?>
            
            <?php elseif ($page == 'settings'): ?>
            <?php include 'pages/settings.php'; ?>
            
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    document.addEventListener('click', function(e) {
        var sidebar = document.querySelector('.admin-sidebar');
        var toggle = document.querySelector('.mobile-toggle');
        if (window.innerWidth <= 768 && sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('mobile-open');
        }
    });
    </script>
</body>
</html>
