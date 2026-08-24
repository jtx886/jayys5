<?php
require_once 'functions.php';
$current_user = current_user();
$theme_color = get_setting('theme_color', '#e50914');

// 处理提交反馈
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback']) && $current_user) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    if (!empty($title) && !empty($content)) {
        db()->insert('feedbacks', array(
            'user_id' => $current_user['id'],
            'title' => $title,
            'content' => $content
        ));
        header("Location: feedback.php?success=1");
        exit;
    }
}

// 获取反馈列表
$feedbacks = db()->fetchAll("SELECT f.*, u.username, u.is_admin as user_is_admin, u.avatar as user_avatar 
    FROM feedbacks f 
    JOIN users u ON f.user_id = u.id 
    ORDER BY f.created_at DESC");

// 为每个反馈获取回复
foreach ($feedbacks as &$fb) {
    $fb['replies'] = db()->fetchAll("SELECT r.*, u.username, u.is_admin as user_is_admin, u.avatar as user_avatar 
        FROM feedback_replies r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.feedback_id = ? 
        ORDER BY u.is_admin DESC, r.created_at ASC", array($fb['id']));
    
    // 检查当前用户是否已点赞
    if ($current_user) {
        $liked = db()->fetch("SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?", 
            array($fb['id'], $current_user['id']));
        $fb['liked_by_me'] = $liked ? true : false;
    } else {
        $fb['liked_by_me'] = false;
    }
}
unset($fb);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户反馈 - Jay影视</title>
    <link rel="stylesheet" href="style.css">
    <style>
    :root { --primary-color: <?php echo $theme_color; ?>; }
    .user-initial {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        font-weight: bold;
    }
    .user-initial.admin-initial {
        background: linear-gradient(135deg, var(--primary-color), #b20710);
    }
    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
    
    <nav class="navbar scrolled" id="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">
                <span class="logo-icon">J</span>
                Jay影视
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">首页</a></li>
                <li><a href="category.php?type=movie">电影</a></li>
                <li><a href="category.php?type=tv">电视剧</a></li>
                <li><a href="category.php?type=anime">动漫</a></li>
                <li><a href="category.php?type=variety">综艺</a></li>
                <li><a href="feedback.php" class="active">反馈</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <div class="search-box">
                <div class="search-icon"></div>
                <input type="text" id="searchInput" placeholder="搜索影视..." onkeypress="if(event.key==='Enter')doSearch()">
            </div>
            <?php if ($current_user): ?>
            <div class="nav-user">
                <div class="user-avatar">
                    <?php if ($current_user['avatar']): ?>
                        <img src="<?php echo e($current_user['avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <?php echo mb_substr($current_user['username'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div class="user-dropdown">
                    <a href="profile.php">个人中心</a>
                    <?php if ($current_user['is_admin']): ?><a href="admin/">管理后台</a><?php endif; ?>
                    <a href="logout.php">退出登录</a>
                </div>
            </div>
            <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm" style="padding:8px 20px;">登录</a>
            <?php endif; ?>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()"><span></span><span></span><span></span></div>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="feedback-page">
            <div class="feedback-header">
                <div>
                    <h1 style="font-size:2rem;margin-bottom:8px;">💬 用户反馈</h1>
                    <p style="color:var(--text-secondary);">有问题或建议？欢迎告诉我们！</p>
                </div>
                <?php if ($current_user): ?>
                <button class="btn btn-primary" onclick="showFeedbackModal()">
                    ✏️ 提交反馈
                </button>
                <?php else: ?>
                <a href="login.php" class="btn btn-primary">登录后反馈</a>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">反馈提交成功！</div>
            <?php endif; ?>
            
            <div class="feedback-list">
                <?php if (count($feedbacks) > 0): ?>
                <?php foreach ($feedbacks as $fb): ?>
                <div class="feedback-card">
                    <div class="feedback-top">
                        <div class="feedback-avatar <?php echo $fb['user_is_admin'] ? 'admin-avatar' : ''; ?>">
                            <?php if ($fb['user_avatar'] && strpos($fb['user_avatar'], 'gradient') !== 0): ?>
                                <img src="<?php echo e($fb['user_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <?php echo mb_substr($fb['username'], 0, 1); ?>
                            <?php endif; ?>
                        </div>
                        <div class="feedback-user-info">
                            <div class="feedback-username">
                                <?php echo e($fb['username']); ?>
                                <?php if ($fb['user_is_admin']): ?>
                                <span class="dev-badge">开发者</span>
                                <?php endif; ?>
                            </div>
                            <div class="feedback-time"><?php echo time_ago($fb['created_at']); ?></div>
                        </div>
                    </div>
                    <div class="feedback-title"><?php echo e($fb['title']); ?></div>
                    <div class="feedback-content"><?php echo nl2br(e($fb['content'])); ?></div>
                    <div class="feedback-actions">
                        <span class="feedback-action <?php echo $fb['liked_by_me'] ? 'liked' : ''; ?>" 
                              onclick="<?php echo $current_user ? "toggleLike({$fb['id']}, this)" : "location.href='login.php'"; ?>">
                            <span class="like-icon"></span>
                            <span class="like-count"><?php echo $fb['likes']; ?></span>
                        </span>
                        <span class="feedback-action" onclick="toggleReplyForm(<?php echo $fb['id']; ?>)">
                            💬 回复 (<?php echo count($fb['replies']); ?>)
                        </span>
                    </div>
                    
                    <?php if (count($fb['replies']) > 0): ?>
                    <div class="replies-section">
                        <?php 
                        $replies = $fb['replies'];
                        $show_replies = array_slice($replies, 0, 3);
                        $hidden_count = count($replies) - 3;
                        ?>
                        <div class="replies-container" id="replies-<?php echo $fb['id']; ?>">
                            <?php foreach ($show_replies as $idx => $reply): ?>
                            <div class="reply-item">
                                <div class="reply-avatar <?php echo $reply['user_is_admin'] ? 'admin-avatar' : ''; ?>">
                                    <?php if ($reply['user_avatar'] && strpos($reply['user_avatar'], 'gradient') !== 0): ?>
                                        <img src="<?php echo e($reply['user_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                    <?php else: ?>
                                        <?php echo mb_substr($reply['username'], 0, 1); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="reply-content">
                                    <div class="reply-username">
                                        <?php echo e($reply['username']); ?>
                                        <?php if ($reply['user_is_admin']): ?>
                                        <span class="dev-badge">开发者</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reply-text"><?php echo nl2br(e($reply['content'])); ?></div>
                                    <div class="reply-time"><?php echo time_ago($reply['created_at']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if ($hidden_count > 0): ?>
                            <div class="hidden-replies" id="hidden-<?php echo $fb['id']; ?>" style="display:none;">
                                <?php foreach (array_slice($replies, 3) as $reply): ?>
                                <div class="reply-item">
                                    <div class="reply-avatar <?php echo $reply['user_is_admin'] ? 'admin-avatar' : ''; ?>">
                                        <?php if ($reply['user_avatar'] && strpos($reply['user_avatar'], 'gradient') !== 0): ?>
                                            <img src="<?php echo e($reply['user_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <?php echo mb_substr($reply['username'], 0, 1); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reply-content">
                                        <div class="reply-username">
                                            <?php echo e($reply['username']); ?>
                                            <?php if ($reply['user_is_admin']): ?>
                                            <span class="dev-badge">开发者</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="reply-text"><?php echo nl2br(e($reply['content'])); ?></div>
                                        <div class="reply-time"><?php echo time_ago($reply['created_at']); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <span class="expand-replies" onclick="expandReplies(<?php echo $fb['id']; ?>, <?php echo $hidden_count; ?>)" id="expand-<?php echo $fb['id']; ?>">
                                展开全部 <?php echo count($replies); ?> 条回复
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 回复表单 -->
                    <div id="reply-form-<?php echo $fb['id']; ?>" style="display:none;margin-top:15px;">
                        <?php if ($current_user): ?>
                        <div class="reply-form">
                            <input type="text" class="reply-input" id="reply-input-<?php echo $fb['id']; ?>" placeholder="写下你的回复...">
                            <button class="btn btn-primary btn-sm" onclick="submitReply(<?php echo $fb['id']; ?>)">发送</button>
                        </div>
                        <?php else: ?>
                        <p style="color:var(--text-secondary);text-align:center;padding:10px;">
                            <a href="login.php" style="color:var(--primary-color);">登录</a>后才能回复
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <div class="empty-text">暂无反馈，成为第一个提建议的人吧！</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 提交反馈弹窗 -->
    <div class="modal-overlay" id="feedbackModal" style="display:none;">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">提交反馈</div>
                <div class="modal-close" onclick="closeFeedbackModal()">×</div>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">标题</label>
                        <input type="text" name="title" class="form-input" placeholder="简要描述问题或建议" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">详细内容</label>
                        <textarea name="content" class="form-textarea" placeholder="请详细描述您遇到的问题或建议..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeFeedbackModal()">取消</button>
                    <button type="submit" name="submit_feedback" class="btn btn-primary">提交</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showFeedbackModal() {
        document.getElementById('feedbackModal').style.display = 'flex';
    }
    function closeFeedbackModal() {
        document.getElementById('feedbackModal').style.display = 'none';
    }
    
    function toggleReplyForm(id) {
        var form = document.getElementById('reply-form-' + id);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            document.getElementById('reply-input-' + id).focus();
        }
    }
    
    function expandReplies(id, count) {
        document.getElementById('hidden-' + id).style.display = 'block';
        document.getElementById('expand-' + id).style.display = 'none';
    }
    
    function toggleLike(id, el) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var res = JSON.parse(xhr.responseText);
            if (res.success) {
                el.classList.toggle('liked', res.liked);
                var countEl = el.querySelector('.like-count');
                countEl.textContent = parseInt(countEl.textContent) + (res.liked ? 1 : -1);
            }
        };
        xhr.send('action=like_feedback&feedback_id=' + id);
    }
    
    function submitReply(id) {
        var input = document.getElementById('reply-input-' + id);
        var content = input.value.trim();
        if (!content) { showToast('请输入回复内容', 'error'); return; }
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var res = JSON.parse(xhr.responseText);
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) setTimeout(function(){location.reload();}, 800);
        };
        xhr.send('action=submit_reply&feedback_id=' + id + '&content=' + encodeURIComponent(content));
    }
    
    function doSearch() {
        var q = document.getElementById('searchInput').value.trim();
        if (q) location.href = 'search.php?q=' + encodeURIComponent(q);
    }
    
    var mobileMenuOpen = false;
    function toggleMobileMenu() {
        mobileMenuOpen = !mobileMenuOpen;
        document.getElementById('navLinks').classList.toggle('mobile-open', mobileMenuOpen);
    }
    
    function showToast(message, type) {
        var container = document.getElementById('toastContainer');
        var toast = document.createElement('div');
        toast.className = 'toast '+(type||'');
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(function(){
            toast.style.opacity='0';toast.style.transform='translateX(100%)';
            setTimeout(function(){toast.remove();},300);
        },2500);
    }
    </script>
</body>
</html>
