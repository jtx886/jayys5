<?php
if ($page != 'email') return;

$email_success = '';
$email_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_email'])) {
    $to = trim($_POST['to']);
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);
    
    if (empty($to) || empty($subject) || empty($content)) {
        $email_error = '请填写所有字段';
    } else {
        // 构建HTML邮件
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: "Microsoft YaHei", Arial, sans-serif; background: #141414; margin: 0; padding: 40px 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #1f1f1f; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
                .header { background: linear-gradient(135deg, ' . $theme_color . ' 0%, #b20710 100%); padding: 30px; text-align: center; }
                .header h1 { color: white; margin: 0; font-size: 24px; }
                .content { padding: 40px 30px; color: #d2d2d2; line-height: 1.8; }
                .footer { background: #0a0a0a; padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>JAY 影视 通知</h1>
                </div>
                <div class="content">
                    ' . nl2br(e($content)) . '
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' Jay影视 版权所有</p>
                    <p style="margin-top:5px;">此邮件为系统通知，请勿直接回复</p>
                </div>
            </div>
        </body>
        </html>';
        
        if (send_email($to, $subject, $html)) {
            $email_success = '邮件发送成功！';
        } else {
            $email_error = '邮件发送失败，请检查SMTP配置';
        }
    }
}

// 获取所有用户
$all_users = db()->fetchAll("SELECT id, username, email FROM users ORDER BY username ASC");
?>

<?php if ($page == 'email'): ?>
<?php if ($email_success): ?>
<div class="alert alert-success"><?php echo e($email_success); ?></div>
<?php endif; ?>
<?php if ($email_error): ?>
<div class="alert alert-danger"><?php echo e($email_error); ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;">
    <div>
        <div class="list-card">
            <h3>📧 发送邮件通知</h3>
            <form method="POST">
                <input type="hidden" name="send_email" value="1">
                <div class="form-group">
                    <label class="form-label">收件人邮箱</label>
                    <div style="display:flex;gap:10px;">
                        <select id="userSelect" class="form-select" style="flex:1;" onchange="document.getElementById('toInput').value=this.value">
                            <option value="">选择用户...</option>
                            <?php foreach ($all_users as $u): ?>
                            <option value="<?php echo e($u['email']); ?>"><?php echo e($u['username']); ?> (<?php echo e($u['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="email" name="to" id="toInput" class="form-input" placeholder="或直接输入邮箱地址" required style="margin-top:10px;">
                </div>
                <div class="form-group">
                    <label class="form-label">邮件主题</label>
                    <input type="text" name="subject" class="form-input" placeholder="输入邮件主题" required>
                </div>
                <div class="form-group">
                    <label class="form-label">邮件内容</label>
                    <textarea name="content" class="form-textarea" placeholder="输入邮件内容" required style="min-height:200px;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">发送邮件</button>
            </form>
        </div>
    </div>
    
    <div>
        <div class="list-card">
            <h3>💡 使用说明</h3>
            <ul style="color:var(--text-secondary);font-size:14px;line-height:2;">
                <li>可以通过下拉菜单快速选择用户邮箱</li>
                <li>也可以手动输入任意邮箱地址发送</li>
                <li>用户被封禁时会自动发送封禁通知邮件</li>
                <li>注册验证码邮件使用系统默认模板</li>
                <li>当前SMTP配置：<?php echo SMTP_HOST; ?>:<?php echo SMTP_PORT; ?></li>
                <li>发件人：<?php echo SMTP_FROM_NAME; ?></li>
            </ul>
        </div>
        
        <div class="list-card" style="margin-top:20px;">
            <h3>📊 统计</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                <div style="text-align:center;padding:15px;background:var(--bg-darker);border-radius:8px;">
                    <div style="font-size:2rem;font-weight:bold;color:var(--primary-color);"><?php echo count($all_users); ?></div>
                    <div style="color:var(--text-secondary);font-size:13px;">可发送用户</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
