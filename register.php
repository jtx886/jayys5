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
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $code = trim($_POST['code']);
    
    if (empty($email) || empty($username) || empty($password) || empty($confirm_password) || empty($code)) {
        $error = '请填写所有必填项';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } elseif (strlen($username) < 2 || strlen($username) > 20) {
        $error = '用户名长度需在2-20个字符之间';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少6位';
    } elseif ($password != $confirm_password) {
        $error = '两次密码输入不一致';
    } else {
        $existing = db()->fetch("SELECT id FROM users WHERE email = ?", array($email));
        if ($existing) {
            $error = '该邮箱已被注册';
        } else {
            $verify = db()->fetch("SELECT * FROM email_verifications WHERE email = ? AND code = ? AND type = 'register' AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1", array($email, $code));
            if (!$verify) {
                $error = '验证码错误或已过期';
            } else {
                db()->update('email_verifications', array('used' => 1), 'id = ?', array($verify['id']));
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_id = db()->insert('users', array(
                    'email' => $email,
                    'username' => $username,
                    'password' => $hashed_password
                ));
                
                $_SESSION['user_id'] = $user_id;
                $success = '注册成功！正在跳转...';
                header("refresh:2;url=index.php");
            }
        }
    }
}

if (isset($_GET['send_code']) && $_GET['send_code'] == '1') {
    $email = trim($_GET['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array('success' => false, 'message' => '请输入有效的邮箱地址'));
        exit;
    }
    
    $existing = db()->fetch("SELECT id FROM users WHERE email = ?", array($email));
    if ($existing) {
        echo json_encode(array('success' => false, 'message' => '该邮箱已被注册'));
        exit;
    }
    
    $code = generate_code();
    $expires = date('Y-m-d H:i:s', time() + 600);
    
    db()->insert('email_verifications', array(
        'email' => $email,
        'code' => $code,
        'type' => 'register',
        'expires_at' => $expires
    ));
    
    if (send_verification_email($email, $code)) {
        echo json_encode(array('success' => true, 'message' => '验证码已发送'));
    } else {
        echo json_encode(array('success' => false, 'message' => '发送失败，请稍后重试'));
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - Jay影视</title>
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
            <h1 class="auth-title">注册</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <input type="email" name="email" id="email" class="form-input" placeholder="邮箱" required value="<?php echo e(isset($_POST['email']) ? $_POST['email'] : ''); ?>">
                </div>
                <div class="form-group">
                    <input type="text" name="username" class="form-input" placeholder="用户名（2-20字符）" required value="<?php echo e(isset($_POST['username']) ? $_POST['username'] : ''); ?>">
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-input" placeholder="密码（至少6位）" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" class="form-input" placeholder="确认密码" required>
                </div>
                <div class="form-group">
                    <div class="code-row">
                        <input type="text" name="code" class="form-input code-input" placeholder="验证码" required maxlength="6">
                        <button type="button" class="btn-code" id="sendCodeBtn" onclick="sendCode()">获取验证码</button>
                    </div>
                    <div id="codeMsg"></div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">注册</button>
                </div>
            </form>
            
            <div class="auth-footer">
                <p>已有账号？<a href="login.php">立即登录</a></p>
                <p style="margin-top: 15px; font-size: 14px;"><a href="index.php">← 返回首页</a></p>
            </div>
        </div>
    </div>
    
    <script>
    var countdown = 0;
    var timer = null;
    
    function sendCode() {
        if (countdown > 0) return;
        
        var email = document.getElementById('email').value;
        var msgEl = document.getElementById('codeMsg');
        
        if (!email) {
            msgEl.innerHTML = '<div class="form-error">请先输入邮箱</div>';
            return;
        }
        
        var btn = document.getElementById('sendCodeBtn');
        btn.disabled = true;
        btn.textContent = '发送中...';
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'register.php?send_code=1&email=' + encodeURIComponent(email), true);
        xhr.onload = function() {
            var res = JSON.parse(xhr.responseText);
            if (res.success) {
                msgEl.innerHTML = '<div class="form-success">' + res.message + '</div>';
                countdown = 60;
                timer = setInterval(function() {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(timer);
                        btn.disabled = false;
                        btn.textContent = '获取验证码';
                    } else {
                        btn.textContent = countdown + '秒后重发';
                    }
                }, 1000);
            } else {
                msgEl.innerHTML = '<div class="form-error">' + res.message + '</div>';
                btn.disabled = false;
                btn.textContent = '获取验证码';
            }
        };
        xhr.onerror = function() {
            msgEl.innerHTML = '<div class="form-error">网络错误，请重试</div>';
            btn.disabled = false;
            btn.textContent = '获取验证码';
        };
        xhr.send();
    }
    </script>
</body>
</html>
