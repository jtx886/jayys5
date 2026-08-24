<?php
require_once 'db.php';

function current_user() {
    if (isset($_SESSION['user_id'])) {
        return db()->fetch("SELECT * FROM users WHERE id = ?", array($_SESSION['user_id']));
    }
    return null;
}

function require_login() {
    $user = current_user();
    if (!$user) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php?msg=login_required");
        exit;
    }
    if ($user['is_banned']) {
        if ($user['ban_until'] AND strtotime($user['ban_until']) < time()) {
            db()->update('users', array('is_banned' => 0, 'ban_until' => null, 'ban_reason' => null), 'id = ?', array($user['id']));
        } else {
            session_destroy();
            header("Location: login.php?msg=banned");
            exit;
        }
    }
    return $user;
}

function require_admin() {
    $user = require_login();
    if (!$user['is_admin']) {
        header("Location: index.php");
        exit;
    }
    return $user;
}

function get_setting($key, $default = '') {
    $setting = db()->fetch("SELECT setting_value FROM settings WHERE setting_key = ?", array($key));
    return $setting ? $setting['setting_value'] : $default;
}

function update_setting($key, $value) {
    $existing = db()->fetch("SELECT id FROM settings WHERE setting_key = ?", array($key));
    if ($existing) {
        db()->update('settings', array('setting_value' => $value), 'setting_key = ?', array($key));
    } else {
        db()->insert('settings', array('setting_key' => $key, 'setting_value' => $value));
    }
}

function send_email($to, $subject, $htmlBody) {
    require_once 'smtp.php';
    $smtp = new SMTP();
    return $smtp->send($to, $subject, $htmlBody);
}

function generate_code($length = 6) {
    $chars = '0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function send_verification_email($email, $code, $type = 'register') {
    $subject = 'Jay影视 - 邮箱验证码';
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:"Microsoft YaHei",Arial,sans-serif;background:#141414;margin:0;padding:40px 20px}.container{max-width:500px;margin:0 auto;background:#1f1f1f;border-radius:12px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.5)}.header{background:linear-gradient(135deg,#e50914 0%,#b20710 100%);padding:30px;text-align:center}.header h1{color:white;margin:0;font-size:28px;font-weight:bold}.content{padding:40px 30px}.greeting{color:#fff;font-size:18px;margin-bottom:20px}.code-box{background:#2d2d2d;border:2px dashed #e50914;border-radius:10px;padding:25px;text-align:center;margin:30px 0}.code{font-size:42px;font-weight:bold;color:#e50914;letter-spacing:10px;font-family:"Courier New",monospace}.desc{color:#999;font-size:14px;line-height:1.8;margin-top:20px}.footer{background:#0a0a0a;padding:20px;text-align:center;color:#666;font-size:12px}.warning{color:#ff6b6b;font-size:13px;margin-top:15px;padding:10px;background:rgba(229,9,20,0.1);border-radius:6px}</style></head><body><div class="container"><div class="header"><h1>JAY 影视</h1></div><div class="content"><p class="greeting">您好！</p><p style="color:#ccc;font-size:15px;">您的邮箱验证码是：</p><div class="code-box"><div class="code">' . $code . '</div></div><p class="desc">验证码有效期为10分钟，请尽快完成验证。<br>如果这不是您的操作，请忽略此邮件。</p><div class="warning">⚠️ 请勿将验证码泄露给他人！</div></div><div class="footer"><p>© ' . date('Y') . ' Jay影视 版权所有</p></div></div></body></html>';
    return send_email($email, $subject, $html);
}

function send_ban_email($email, $username, $reason, $banUntil) {
    $subject = 'Jay影视 - 账号封禁通知';
    $banUntilStr = $banUntil ? date('Y-m-d H:i:s', strtotime($banUntil)) : '永久';
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:"Microsoft YaHei",Arial,sans-serif;background:#141414;margin:0;padding:40px 20px}.container{max-width:500px;margin:0 auto;background:#1f1f1f;border-radius:12px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.5)}.header{background:linear-gradient(135deg,#e50914 0%,#b20710 100%);padding:30px;text-align:center}.header h1{color:white;margin:0;font-size:28px}.content{padding:40px 30px}.warning-icon{text-align:center;font-size:60px;margin-bottom:20px}.title{color:#fff;font-size:22px;text-align:center;margin-bottom:25px;font-weight:bold}.info-box{background:#2d2d2d;border-radius:10px;padding:20px;margin-bottom:20px}.info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #3d3d3d}.info-row:last-child{border-bottom:none}.info-label{color:#999}.info-value{color:#fff;font-weight:bold}.reason{color:#ff6b6b}.footer{background:#0a0a0a;padding:20px;text-align:center;color:#666;font-size:12px}</style></head><body><div class="container"><div class="header"><h1>JAY 影视</h1></div><div class="content"><div class="warning-icon">🚫</div><div class="title">账号封禁通知</div><p style="color:#ccc;text-align:center;margin-bottom:25px;">尊敬的 ' . htmlspecialchars($username) . '，您的账号已被封禁</p><div class="info-box"><div class="info-row"><span class="info-label">封禁原因</span><span class="info-value reason">' . htmlspecialchars($reason) . '</span></div><div class="info-row"><span class="info-label">解封时间</span><span class="info-value">' . $banUntilStr . '</span></div></div><p style="color:#999;font-size:14px;text-align:center;">如有疑问，请联系管理员。</p></div><div class="footer"><p>© ' . date('Y') . ' Jay影视 版权所有</p></div></div></body></html>';
    return send_email($email, $subject, $html);
}

function tmdb_api($endpoint, $params = array()) {
    $params['api_key'] = TMDB_API_KEY;
    $params['language'] = 'zh-CN';
    $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . TMDB_ACCESS_TOKEN,
        'Accept: application/json'
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function tmdb_image($path, $size = 'w500') {
    if (!$path) return '';
    return TMDB_IMAGE_BASE . '/' . $size . $path;
}

function time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d > 30) return $datetime;
    if ($diff->d > 0) return $diff->d . '天前';
    if ($diff->h > 0) return $diff->h . '小时前';
    if ($diff->i > 0) return $diff->i . '分钟前';
    return '刚刚';
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function get_active_announcement() {
    return db()->fetch("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
}

function has_dismissed_announcement($user_id, $announcement_id) {
    return db()->fetch("SELECT id FROM announcement_dismissals WHERE user_id = ? AND announcement_id = ?", array($user_id, $announcement_id));
}

function render_avatar($user, $size_class = '') {
    $avatar = isset($user['avatar']) ? $user['avatar'] : null;
    $username = isset($user['username']) ? $user['username'] : 'U';
    $is_admin = isset($user['is_admin']) AND $user['is_admin'];
    $initial = mb_substr($username, 0, 1);
    
    $class = $size_class ? $size_class : 'user-avatar';
    $html = '<div class="' . $class . '"';
    
    if ($avatar AND strpos($avatar, 'http') === 0) {
        $html .= '><img src="' . e($avatar) . '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">';
    } elseif ($avatar AND strpos($avatar, 'gradient') !== false) {
        $html .= ' style="background:' . e($avatar) . ';">' . e($initial);
    } else {
        $gradient = $is_admin ? 'linear-gradient(135deg, #e50914, #b20710)' : 'linear-gradient(135deg, #667eea, #764ba2)';
        $html .= ' style="background:' . $gradient . ';">' . e($initial);
    }
    
    $html .= '</div>';
    return $html;
}
