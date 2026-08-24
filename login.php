<?php
require_once 'functions.php';

$user = current_user();
if ($user) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = '请填写邮箱和密码';
    } else {
        $user = db()->fetch("SELECT * FROM users WHERE email = ?", array($email));
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_banned']) {
                if ($user['ban_until'] && strtotime($user['ban_until']) < time()) {
                    db()->update('users', array('is_banned' => 0, 'ban_until' => null, 'ban_reason' => null), 'id = ?', array($user['id']));
                } else {
                    $banUntil = $user['ban_until'] ? date('Y-m-d H:i:s', strtotime($user['ban_until'])) : '永久';
                    $error = "账号已被封禁，原因：{$user['ban_reason']}，解封时间：{$banUntil}";
                }
            }
            if (!$error) {
                $_SESSION['user_id'] = $user['id'];
                $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
                unset($_SESSION['redirect_url']);
                redirect($redirect);
            }
        } else {
            $error = '邮箱或密码错误';
        }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'login_required') {
        $error = '需要登录才可以观看哦，如没有账号请注册！';
    } elseif ($_GET['msg'] == 'banned') {
        $error = '您的账号已被封禁';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - Jay影视</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-logo">
                <a href="index.php" class="logo">
                    <span class="logo-icon">J</span>
                    Jay影视
                </a>
            </div>
            <h1 class="auth-title">登录</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <input type="email" name="email" class="form-input" placeholder="邮箱" required value="<?php echo e(isset($_POST['email']) ? $_POST['email'] : ''); ?>">
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-input" placeholder="密码" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">登录</button>
                </div>
            </form>
            
            <div class="auth-footer">
                <p>还没有账号？<a href="register.php">立即注册</a></p>
                <p style="margin-top: 15px; font-size: 14px;"><a href="index.php">← 返回首页</a></p>
            </div>
        </div>
    </div>
</body>
</html>
