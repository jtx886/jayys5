<?php
if ($page != 'announcements') return;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'publish') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        if ($title && $content) {
            // 先将其他公告设为非活跃
            db()->query("UPDATE announcements SET is_active = 0");
            db()->insert('announcements', array(
                'title' => $title,
                'content' => $content,
                'is_active' => 1
            ));
            $success_msg = '公告发布成功！';
        }
    } elseif ($action == 'toggle') {
        $id = intval($_POST['id']);
        $ann = db()->fetch("SELECT is_active FROM announcements WHERE id = ?", array($id));
        if ($ann) {
            if ($ann['is_active']) {
                db()->update('announcements', array('is_active' => 0), 'id = ?', array($id));
            } else {
                db()->query("UPDATE announcements SET is_active = 0");
                db()->update('announcements', array('is_active' => 1), 'id = ?', array($id));
            }
            $success_msg = '公告状态已更新';
        }
    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        db()->delete('announcements', 'id = ?', array($id));
        $success_msg = '公告已删除';
    }
}

$announcements = db()->fetchAll("SELECT * FROM announcements ORDER BY created_at DESC");
$current_announcement = get_active_announcement();
?>

<?php if ($page == 'announcements'): ?>
<div style="margin-bottom:30px;">
    <h3 style="margin-bottom:15px;">发布新公告</h3>
    <form method="POST" style="max-width:600px;">
        <input type="hidden" name="action" value="publish">
        <div class="form-group">
            <label class="form-label">公告标题</label>
            <input type="text" name="title" class="form-input" placeholder="输入公告标题" required>
        </div>
        <div class="form-group">
            <label class="form-label">公告内容</label>
            <textarea name="content" class="form-textarea" placeholder="输入公告内容" required style="min-height:150px;"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">发布公告</button>
        <p style="color:var(--text-muted);font-size:13px;margin-top:10px;">💡 新公告发布后，所有用户进入首页都会看到弹窗，除非勾选"不再提示"</p>
    </form>
</div>

<h3 style="margin-bottom:15px;">历史公告</h3>
<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>标题</th>
                <th>发布时间</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($announcements) > 0): ?>
            <?php foreach ($announcements as $a): ?>
            <tr>
                <td><?php echo $a['id']; ?></td>
                <td><?php echo e($a['title']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($a['created_at'])); ?></td>
                <td>
                    <?php if ($a['is_active']): ?>
                    <span class="badge badge-success">展示中</span>
                    <?php else: ?>
                    <span class="badge badge-danger">已关闭</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:8px;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <?php echo $a['is_active'] ? '关闭' : '启用'; ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除吗？');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">删除</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">暂无公告</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
