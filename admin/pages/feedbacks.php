<?php
if ($page != 'feedbacks') return;

// 处理回复
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reply') {
    $feedback_id = intval($_POST['feedback_id']);
    $content = trim($_POST['content']);
    if ($feedback_id && $content) {
        db()->insert('feedback_replies', array(
            'feedback_id' => $feedback_id,
            'user_id' => $admin['id'],
            'content' => $content
        ));
        $success_msg = '回复已发布';
    }
}

// 获取反馈列表
$feedbacks = db()->fetchAll("SELECT f.*, u.username, u.email FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");

foreach ($feedbacks as &$fb) {
    $fb['replies'] = db()->fetchAll("SELECT r.*, u.username, u.is_admin FROM feedback_replies r JOIN users u ON r.user_id = u.id WHERE r.feedback_id = ? ORDER BY u.is_admin DESC, r.created_at ASC", array($fb['id']));
}
unset($fb);
?>

<?php if ($page == 'feedbacks'): ?>
<div class="feedback-list">
    <?php if (count($feedbacks) > 0): ?>
    <?php foreach ($feedbacks as $fb): ?>
    <div class="feedback-card" style="margin-bottom:20px;">
        <div class="feedback-top">
            <div class="feedback-avatar <?php echo $fb['user_id'] == $admin['id'] ? 'admin-avatar' : ''; ?>">
                <?php echo mb_substr($fb['username'], 0, 1); ?>
            </div>
            <div class="feedback-user-info">
                <div class="feedback-username">
                    <?php echo e($fb['username']); ?>
                    <span style="color:var(--text-muted);font-size:13px;font-weight:normal;"><?php echo e($fb['email']); ?></span>
                </div>
                <div class="feedback-time"><?php echo date('Y-m-d H:i', strtotime($fb['created_at'])); ?> · 👍 <?php echo $fb['likes']; ?></div>
            </div>
        </div>
        <div class="feedback-title" style="font-size:1.1rem;"><?php echo e($fb['title']); ?></div>
        <div class="feedback-content"><?php echo nl2br(e($fb['content'])); ?></div>
        
        <?php if (count($fb['replies']) > 0): ?>
        <div class="replies-section">
            <?php foreach ($fb['replies'] as $reply): ?>
            <div class="reply-item">
                <div class="reply-avatar <?php echo $reply['is_admin'] ? 'admin-avatar' : ''; ?>">
                    <?php echo mb_substr($reply['username'], 0, 1); ?>
                </div>
                <div class="reply-content">
                    <div class="reply-username">
                        <?php echo e($reply['username']); ?>
                        <?php if ($reply['is_admin']): ?><span class="dev-badge">开发者</span><?php endif; ?>
                    </div>
                    <div class="reply-text"><?php echo nl2br(e($reply['content'])); ?></div>
                    <div class="reply-time"><?php echo date('Y-m-d H:i', strtotime($reply['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top:15px;">
            <details>
                <summary style="cursor:pointer;color:var(--primary-color);font-size:14px;">回复此反馈</summary>
                <form method="POST" style="margin-top:15px;">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="feedback_id" value="<?php echo $fb['id']; ?>">
                    <div class="form-group">
                        <textarea name="content" class="form-textarea" placeholder="输入回复内容..." required style="min-height:80px;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">发送回复</button>
                </form>
            </details>
        </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">💬</div>
        <div class="empty-text">暂无反馈</div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
