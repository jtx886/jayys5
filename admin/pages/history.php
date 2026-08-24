<?php
if ($page != 'history') return;

$selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$where = '';
$params = array();
if ($selected_user) {
    $where = 'WHERE h.user_id = ?';
    $params[] = $selected_user;
}

$history = db()->fetchAll("SELECT h.*, u.username FROM watch_history h JOIN users u ON h.user_id = u.id $where ORDER BY h.watched_at DESC LIMIT 200", $params);
$users = db()->fetchAll("SELECT id, username FROM users ORDER BY username ASC");
?>

<?php if ($page == 'history'): ?>
<div style="margin-bottom:20px;display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;">
        <input type="hidden" name="page" value="history">
        <label style="color:var(--text-secondary);">选择用户：</label>
        <select name="user_id" class="form-select" style="width:200px;" onchange="this.form.submit()">
            <option value="0">全部用户</option>
            <?php foreach ($users as $u): ?>
            <option value="<?php echo $u['id']; ?>" <?php echo $selected_user == $u['id'] ? 'selected' : ''; ?>>
                <?php echo e($u['username']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
    <span style="color:var(--text-secondary);">共 <?php echo count($history); ?> 条记录</span>
</div>

<?php if (count($history) > 0): ?>
<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>用户</th>
                <th>影视</th>
                <th>季/集</th>
                <th>观看时间</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $h): ?>
            <tr>
                <td><?php echo e($h['username']); ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:40px;height:60px;background-size:cover;background-position:center;border-radius:4px;background-image:url('<?php echo tmdb_image($h['poster'], 'w200'); ?>');"></div>
                        <div>
                            <div><?php echo e($h['title']); ?></div>
                            <small style="color:var(--text-muted);"><?php echo $h['media_type'] == 'movie' ? '电影' : '电视剧'; ?></small>
                        </div>
                    </div>
                </td>
                <td>
                    <?php if ($h['season']): ?>
                    第<?php echo $h['season']; ?>季<?php if ($h['episode']) echo ' 第' . $h['episode'] . '集'; ?>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </td>
                <td><?php echo date('Y-m-d H:i', strtotime($h['watched_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">📺</div>
    <div class="empty-text">暂无观看记录</div>
</div>
<?php endif; ?>
<?php endif; ?>
