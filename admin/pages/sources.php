<?php
if ($page != 'sources') return;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $name = trim($_POST['name']);
        $url = trim($_POST['url']);
        $parser = trim($_POST['parser_url']);
        if ($name && $url) {
            db()->insert('play_sources', array(
                'name' => $name,
                'url' => $url,
                'parser_url' => $parser ? $parser : 'https://svip.ffzyplay.com/?url='
            ));
            $success_msg = '播放源添加成功';
        }
    } elseif ($action == 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $url = trim($_POST['url']);
        $parser = trim($_POST['parser_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        db()->update('play_sources', array(
            'name' => $name,
            'url' => $url,
            'parser_url' => $parser,
            'is_active' => $is_active
        ), 'id = ?', array($id));
        $success_msg = '播放源更新成功';
    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        db()->delete('play_sources', 'id = ?', array($id));
        $success_msg = '播放源已删除';
    }
}

$sources = db()->fetchAll("SELECT * FROM play_sources ORDER BY sort_order ASC, id ASC");
?>

<?php if ($page == 'sources'): ?>
<div style="margin-bottom:20px;">
    <button class="btn btn-primary" onclick="showAddModal()">+ 添加播放源</button>
</div>

<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>名称</th>
                <th>API地址</th>
                <th>解析地址</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sources as $s): ?>
            <tr>
                <td><?php echo $s['id']; ?></td>
                <td><?php echo e($s['name']); ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo e($s['url']); ?>"><?php echo e($s['url']); ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo e($s['parser_url']); ?>"><?php echo e($s['parser_url']); ?></td>
                <td>
                    <?php if ($s['is_active']): ?>
                    <span class="badge badge-success">启用</span>
                    <?php else: ?>
                    <span class="badge badge-danger">禁用</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-secondary btn-sm" onclick='showEditModal(<?php echo json_encode($s); ?>)'>编辑</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除吗？');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">删除</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 添加/编辑弹窗 -->
<div class="modal-overlay" id="sourceModal" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="sourceModalTitle">添加播放源</div>
            <div class="modal-close" onclick="closeSourceModal()">×</div>
        </div>
        <form method="POST" id="sourceForm">
            <input type="hidden" name="action" id="sourceAction" value="add">
            <input type="hidden" name="id" id="sourceId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">播放源名称</label>
                    <input type="text" name="name" id="sourceName" class="form-input" placeholder="例如：永久资源" required>
                </div>
                <div class="form-group">
                    <label class="form-label">API地址</label>
                    <input type="text" name="url" id="sourceUrl" class="form-input" placeholder="https://example.com/api.php" required>
                </div>
                <div class="form-group">
                    <label class="form-label">解析播放器地址</label>
                    <input type="text" name="parser_url" id="sourceParser" class="form-input" value="https://svip.ffzyplay.com/?url=">
                </div>
                <div class="form-group" id="activeGroup">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="is_active" id="sourceActive" checked>
                        <span>启用此播放源</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSourceModal()">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('sourceModalTitle').textContent = '添加播放源';
    document.getElementById('sourceAction').value = 'add';
    document.getElementById('sourceId').value = '';
    document.getElementById('sourceName').value = '';
    document.getElementById('sourceUrl').value = '';
    document.getElementById('sourceParser').value = 'https://svip.ffzyplay.com/?url=';
    document.getElementById('sourceActive').checked = true;
    document.getElementById('activeGroup').style.display = 'block';
    document.getElementById('sourceModal').style.display = 'flex';
}
function showEditModal(source) {
    document.getElementById('sourceModalTitle').textContent = '编辑播放源';
    document.getElementById('sourceAction').value = 'edit';
    document.getElementById('sourceId').value = source.id;
    document.getElementById('sourceName').value = source.name;
    document.getElementById('sourceUrl').value = source.url;
    document.getElementById('sourceParser').value = source.parser_url;
    document.getElementById('sourceActive').checked = source.is_active == 1;
    document.getElementById('sourceModal').style.display = 'flex';
}
function closeSourceModal() {
    document.getElementById('sourceModal').style.display = 'none';
}
</script>
<?php endif; ?>
