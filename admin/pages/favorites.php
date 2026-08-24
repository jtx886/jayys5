<?php
if ($page != 'favorites') return;

$selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$where = '';
$params = array();
if ($selected_user) {
    $where = 'WHERE f.user_id = ?';
    $params[] = $selected_user;
}

$favorites = db()->fetchAll("SELECT f.*, u.username FROM favorites f JOIN users u ON f.user_id = u.id $where ORDER BY f.created_at DESC LIMIT 200", $params);
$users = db()->fetchAll("SELECT id, username FROM users ORDER BY username ASC");
?>

<?php if ($page == 'favorites'): ?>
<div style="margin-bottom:20px;display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;">
        <input type="hidden" name="page" value="favorites">
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
    <span style="color:var(--text-secondary);">共 <?php echo count($favorites); ?> 条收藏</span>
</div>

<?php if (count($favorites) > 0): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;">
    <?php foreach ($favorites as $fav): ?>
    <div style="background:var(--bg-card);border-radius:8px;overflow:hidden;">
        <a href="../detail.php?type=<?php echo $fav['media_type']; ?>&id=<?php echo $fav['media_id']; ?>" target="_blank">
            <div style="aspect-ratio:2/3;background-size:cover;background-position:center;background-image:url('<?php echo tmdb_image($fav['poster']); ?>');"></div>
        </a>
        <div style="padding:10px;">
            <div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo e($fav['title']); ?>">
                <?php echo e($fav['title']); ?>
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                <?php echo e($fav['username']); ?> · <?php echo date('m-d', strtotime($fav['created_at'])); ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">❤️</div>
    <div class="empty-text">暂无收藏</div>
</div>
<?php endif; ?>
<?php endif; ?>
