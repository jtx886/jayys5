<?php
require_once 'config.php';
require_once 'db.php';

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// 测试数据库连接
try {
    $test_conn = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch(PDOException $e) {
    $error = "数据库连接失败: " . $e->getMessage() . "<br>请先修改config.php中的数据库配置信息";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 2) {
    try {
        // 创建数据库
        $test_conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $test_conn->exec("USE " . DB_NAME);
        
        // 读取SQL文件
        $sql = file_get_contents('database.sql');
        $sql = preg_replace('/^CREATE DATABASE.*?;/ms', '', $sql);
        $sql = preg_replace('/^USE.*?;/ms', '', $sql);
        
        // 执行SQL
        $test_conn->exec($sql);
        
        // 创建管理员账号 密码101113
        $admin_password = password_hash('101113', PASSWORD_DEFAULT);
        $check_admin = $test_conn->query("SELECT id FROM users WHERE email = 'admin@jaymovie.com'")->fetch();
        if (!$check_admin) {
            $stmt = $test_conn->prepare("INSERT INTO users (email, username, password, is_admin) VALUES (?, ?, ?, 1)");
            $stmt->execute(array('admin@jaymovie.com', '杰同学', $admin_password));
        }
        
        // 插入默认播放源
        $check_source = $test_conn->query("SELECT id FROM play_sources WHERE url = 'https://api.yyzy-tv.vip/inc/apijson.php'")->fetch();
        if (!$check_source) {
            $stmt = $test_conn->prepare("INSERT INTO play_sources (name, url, parser_url) VALUES (?, ?, ?)");
            $stmt->execute(array('永久资源', 'https://api.yyzy-tv.vip/inc/apijson.php', 'https://svip.ffzyplay.com/?url='));
        }
        
        // 插入默认设置
        $check_setting = $test_conn->query("SELECT id FROM settings WHERE setting_key = 'theme_color'")->fetch();
        if (!$check_setting) {
            $stmt = $test_conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute(array('theme_color', '#e50914'));
            $stmt->execute(array('tmdb_api_key', TMDB_API_KEY));
            $stmt->execute(array('site_name', 'Jay影视'));
        }
        
        $success = "安装成功！管理员账号：杰同学，密码：101113";
        $step = 3;
    } catch(PDOException $e) {
        $error = "安装失败: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装 - Jay影视</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', sans-serif; background: #141414; color: white; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-container { background: #1f1f1f; padding: 40px; border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        .logo { text-align: center; font-size: 36px; font-weight: bold; color: #e50914; margin-bottom: 30px; }
        h1 { font-size: 24px; margin-bottom: 20px; text-align: center; }
        .step { display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; }
        .step-item { width: 35px; height: 35px; border-radius: 50%; background: #333; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .step-item.active { background: #e50914; }
        .step-item.done { background: #46d369; }
        .info-item { background: #2d2d2d; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
        .info-label { color: #999; font-size: 13px; }
        .info-value { font-size: 15px; margin-top: 5px; word-break: break-all; }
        .btn { display: inline-block; padding: 12px 30px; background: #e50914; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; text-decoration: none; text-align: center; }
        .btn:hover { background: #f40612; }
        .btn-secondary { background: #444; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: rgba(229,9,20,0.1); border: 1px solid #e50914; color: #ff6b6b; }
        .alert-success { background: rgba(70,211,105,0.1); border: 1px solid #46d369; color: #46d369; }
        .alert-warning { background: rgba(255,165,0,0.1); border: 1px solid #ffa500; color: #ffa500; }
        .text-center { text-align: center; }
        .mt-20 { margin-top: 20px; }
        .text-muted { color: #999; font-size: 14px; margin-top: 15px; line-height: 1.8; }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">🎬 Jay影视</div>
        
        <div class="step">
            <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'done' : 'active') : ''; ?>">1</div>
            <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'done' : 'active') : ''; ?>">2</div>
            <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
        <h1>环境检测</h1>
        
        <div class="info-item">
            <div class="info-label">PHP版本</div>
            <div class="info-value"><?php echo phpversion(); ?> <?php echo version_compare(phpversion(), '5.6', '>=') ? '✅' : '❌ (需要5.6+)'; ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">数据库主机</div>
            <div class="info-value"><?php echo DB_HOST; ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">数据库用户</div>
            <div class="info-value"><?php echo DB_USER; ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">数据库名称</div>
            <div class="info-value"><?php echo DB_NAME; ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">SMTP发件服务器</div>
            <div class="info-value"><?php echo SMTP_HOST; ?>:<?php echo SMTP_PORT; ?></div>
        </div>
        
        <p class="text-muted">
            ⚠️ 如果数据库信息不正确，请先修改 <strong>config.php</strong> 文件中的数据库配置。<br>
            InfinityFree免费主机通常数据库地址是 <strong>sqlxxx.epizy.com</strong> 之类的，不是localhost。
        </p>
        
        <div class="text-center mt-20">
            <?php if (!$error): ?>
            <a href="?step=2" class="btn">下一步：开始安装</a>
            <?php else: ?>
            <p class="text-muted" style="color:#ff6b6b;">请先修正数据库配置后刷新页面</p>
            <?php endif; ?>
        </div>
        
        <?php elseif ($step == 2): ?>
        <h1>确认安装</h1>
        
        <div class="alert alert-warning">
            即将创建数据库表和初始数据，包括：
            <ul style="margin:10px 0 0 20px;line-height:2;">
                <li>用户表、播放源表、收藏表等9个数据表</li>
                <li>管理员账号：<strong>杰同学</strong> (admin@jaymovie.com)</li>
                <li>管理员密码：<strong>101113</strong></li>
                <li>默认播放源配置</li>
            </ul>
        </div>
        
        <form method="POST" class="text-center mt-20">
            <button type="submit" class="btn">确认安装</button>
            <a href="?step=1" class="btn btn-secondary" style="margin-left:10px;">返回上一步</a>
        </form>
        
        <?php elseif ($step == 3): ?>
        <h1>安装完成！</h1>
        
        <div class="alert alert-success">
            🎉 Jay影视安装成功！
        </div>
        
        <div class="info-item">
            <div class="info-label">网站首页</div>
            <div class="info-value"><a href="index.php" style="color:#e50914;">点击进入首页</a></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">管理员后台</div>
            <div class="info-value"><a href="admin/" style="color:#e50914;">点击进入管理后台</a></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">管理员账号</div>
            <div class="info-value">用户名：杰同学 / 密码：101113</div>
        </div>
        
        <p class="text-muted">
            💡 提示：<br>
            1. 登录后可以在管理后台修改主题颜色、管理用户等<br>
            2. 注册功能需要SMTP发邮件，请确认config.php中的SMTP配置正确<br>
            3. 建议安装完成后删除 install.php 文件
        </p>
        
        <div class="text-center mt-20">
            <a href="index.php" class="btn">进入网站</a>
            <a href="admin/" class="btn btn-secondary" style="margin-left:10px;">进入后台</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
