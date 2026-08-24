<?php
if ($page != 'users') return;

// 处理用户操作
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $user_id = intval($_POST['user_id']);
    
    if ($action == 'ban') {
        $ban_days = intval($_POST['ban_days']);
        $ban_reason = trim($_POST['ban_reason']);
        $ban_until = $ban_days > 0 ? date('Y-m-d H:i:s', time() + $ban_days * 86400) : null;
        
        db()->update('users', array(
            'is_banned' => 1,
            'ban_until' => $ban_until,
            'ban_reason' => $ban_reason
        ), 'id = ?', array($user_id));
        
        // 发送封禁邮件
        $banned_user = db()->fetch("SELECT * FROM users WHERE id = ?", array($user_id));
        if ($banned_user) {
            send_ban_email($banned_user['email'], $banned_user['username'], $ban_reason, $ban_until);
        }
        
        $success_msg = '用户已封禁';
    } elseif ($action == 'unban') {
        db()->update('users', array(
            'is_banned' => 0,
            'ban_until' => null,
            'ban_reason' => null
        ), 'id = ?', array($user_id));
        $success_msg = '用户已解封';
    } elseif ($action == 'toggle_admin') {
        $target_user = db()->fetch("SELECT is_admin FROM users WHERE id = ?", array($user_id));
        if ($target_user && $user_id != $admin['id']) {
            $new_admin = $target_user['is_admin'] ? 0 : 1;
            db()->update('users', array('is_admin' => $new_admin), 'id = ?', array($user_id));
            $success_msg = $new_admin ? '已设为管理员' : '已取消管理员';
        }
    }
}

// 获取用户列表
$users = db()->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 100");
?>

<?php if ($page == 'users'): ?>
<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>用户</th>
                <th>邮箱</th>
                <th>状态</th>
                <th>注册时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:14px;">
                            <?php if ($u['avatar'] && strpos($u['avatar'], 'gradient') !== 0): ?>
                                <img src="<?php echo e($u['avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <?php echo mb_substr($u['username'], 0, 1); ?>
                            <?php endif; ?>
                        </div>
                        <?php echo e($u['username']); ?>
                        <?php if ($u['is_admin']): ?><span class="badge badge-admin">管理员</span><?php endif; ?>
                    </div>
                </td>
                <td><?php echo e($u['email']); ?></td>
                <td>
                    <?php if ($u['is_banned']): ?>
                        <span class="badge badge-danger">已封禁</span>
                        <?php if ($u['ban_until']): ?>
                        <br><small style="color:var(--text-muted);">至 <?php echo date('m-d H:i', strtotime($u['ban_until'])); ?></small>
                        <?php else: ?>
                        <br><small style="color:var(--text-muted);">永久封禁</small>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-success">正常</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                <td>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if ($u['id'] != $admin['id']): ?>
                            <?php if (!$u['is_banned']): ?>
                            <button class="btn btn-danger btn-sm" onclick="showBanModal(<?php echo $u['id']; ?>, '<?php echo addslashes($u['username']); ?>')">封禁</button>
                            <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="unban">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">解封</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_admin">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('确定要<?php echo $u['is_admin']?'取消':'设为'; ?>管理员吗？')">
                                    <?php echo $u['is_admin']?'取消管理员':'设为管理员'; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color:var(--text-muted);">当前账号</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 封禁弹窗 -->
<div class="modal-overlay" id="banModal" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">封禁用户</div>
            <div class="modal-close" onclick="closeBanModal()">×</div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ban">
            <input type="hidden" name="user_id" id="banUserId">
            <div class="modal-body">
                <p style="margin-bottom:20px;color:var(--text-secondary);">确定要封禁用户 <strong id="banUsername" style="color:white;"></strong> 吗？</p>
                <div class="form-group">
                    <label class="form-label">封禁时长（天），填0为永久封禁</label>
                    <input type="number" name="ban_days" class="form-input" value="7" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">封禁原因</label>
                    <textarea name="ban_reason" class="form-textarea" placeholder="请填写封禁原因（将发送给用户）" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeBanModal()">取消</button>
                <button type="submit" class="btn btn-danger">确认封禁</button>
            </div>
        </form>
    </div>
</div>

<script>
function showBanModal(id, username) {
    document.getElementById('banUserId').value = id;
    document.getElementById('banUsername').textContent = username;
    document.getElementById('banModal').style.display = 'flex';
}
function closeBanModal() {
    document.getElementById('banModal').style.display = 'none';
}
</script>
<?php endif; ?>
