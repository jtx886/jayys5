<?php
require_once 'functions.php';

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

header('Content-Type: application/json');

switch ($action) {
    case 'toggle_favorite':
        $user = require_login();
        $media_id = intval($_POST['media_id']);
        $media_type = $_POST['media_type'];
        $title = $_POST['title'];
        $poster = $_POST['poster'];
        
        $existing = db()->fetch("SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?", 
            array($user['id'], $media_id, $media_type));
        
        if ($existing) {
            db()->delete('favorites', 'id = ?', array($existing['id']));
            echo json_encode(array('success' => true, 'message' => '已取消收藏'));
        } else {
            db()->insert('favorites', array(
                'user_id' => $user['id'],
                'media_id' => $media_id,
                'media_type' => $media_type,
                'title' => $title,
                'poster' => $poster
            ));
            echo json_encode(array('success' => true, 'message' => '收藏成功'));
        }
        break;
        
    case 'dismiss_announcement':
        $user = require_login();
        $announcement_id = intval($_POST['announcement_id']);
        
        $existing = has_dismissed_announcement($user['id'], $announcement_id);
        if (!$existing) {
            db()->insert('announcement_dismissals', array(
                'user_id' => $user['id'],
                'announcement_id' => $announcement_id
            ));
        }
        echo json_encode(array('success' => true));
        break;
        
    case 'add_history':
        $user = require_login();
        $media_id = intval($_POST['media_id']);
        $media_type = $_POST['media_type'];
        $title = $_POST['title'];
        $poster = $_POST['poster'];
        $season = isset($_POST['season']) ? intval($_POST['season']) : null;
        $episode = isset($_POST['episode']) ? intval($_POST['episode']) : null;
        $watch_time = isset($_POST['watch_time']) ? intval($_POST['watch_time']) : 0;
        
        // 先删除同一影视的历史记录
        db()->delete('watch_history', 'user_id = ? AND media_id = ? AND media_type = ?' . 
            ($season ? ' AND season = ?' : ' AND season IS NULL') . 
            ($episode ? ' AND episode = ?' : ' AND episode IS NULL'), 
            $season && $episode ? array($user['id'], $media_id, $media_type, $season, $episode) : 
            ($season ? array($user['id'], $media_id, $media_type, $season) : array($user['id'], $media_id, $media_type)));
        
        db()->insert('watch_history', array(
            'user_id' => $user['id'],
            'media_id' => $media_id,
            'media_type' => $media_type,
            'title' => $title,
            'poster' => $poster,
            'season' => $season,
            'episode' => $episode,
            'watch_time' => $watch_time
        ));
        echo json_encode(array('success' => true));
        break;
        
    case 'delete_history':
        $user = require_login();
        $id = intval($_POST['id']);
        db()->delete('watch_history', 'id = ? AND user_id = ?', array($id, $user['id']));
        echo json_encode(array('success' => true));
        break;
        
    case 'check_favorite':
        $user = current_user();
        if (!$user) {
            echo json_encode(array('favorited' => false));
            exit;
        }
        $media_id = intval($_GET['media_id']);
        $media_type = $_GET['media_type'];
        $existing = db()->fetch("SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?", 
            array($user['id'], $media_id, $media_type));
        echo json_encode(array('favorited' => $existing ? true : false));
        break;
        
    case 'like_feedback':
        $user = require_login();
        $feedback_id = intval($_POST['feedback_id']);
        
        $existing = db()->fetch("SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?", 
            array($feedback_id, $user['id']));
        
        if ($existing) {
            db()->delete('feedback_likes', 'id = ?', array($existing['id']));
            db()->query("UPDATE feedbacks SET likes = likes - 1 WHERE id = ?", array($feedback_id));
            echo json_encode(array('success' => true, 'liked' => false));
        } else {
            db()->insert('feedback_likes', array(
                'feedback_id' => $feedback_id,
                'user_id' => $user['id']
            ));
            db()->query("UPDATE feedbacks SET likes = likes + 1 WHERE id = ?", array($feedback_id));
            echo json_encode(array('success' => true, 'liked' => true));
        }
        break;
        
    case 'update_avatar':
        $user = require_login();
        $avatar = $_POST['avatar'];
        
        if (empty($avatar)) {
            echo json_encode(array('success' => false, 'message' => '请选择头像'));
            exit;
        }
        
        db()->update('users', array('avatar' => $avatar), 'id = ?', array($user['id']));
        echo json_encode(array('success' => true, 'message' => '头像更新成功'));
        break;
        
    case 'remove_favorite':
        $user = require_login();
        $id = intval($_POST['id']);
        db()->delete('favorites', 'id = ? AND user_id = ?', array($id, $user['id']));
        echo json_encode(array('success' => true, 'message' => '已取消收藏'));
        break;
        
    case 'submit_feedback':
        $user = require_login();
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        if (empty($title) || empty($content)) {
            echo json_encode(array('success' => false, 'message' => '请填写标题和内容'));
            exit;
        }
        
        db()->insert('feedbacks', array(
            'user_id' => $user['id'],
            'title' => $title,
            'content' => $content
        ));
        echo json_encode(array('success' => true, 'message' => '反馈提交成功'));
        break;
        
    case 'submit_reply':
        $user = require_login();
        $feedback_id = intval($_POST['feedback_id']);
        $content = trim($_POST['content']);
        
        if (empty($content)) {
            echo json_encode(array('success' => false, 'message' => '请输入回复内容'));
            exit;
        }
        
        db()->insert('feedback_replies', array(
            'feedback_id' => $feedback_id,
            'user_id' => $user['id'],
            'content' => $content
        ));
        echo json_encode(array('success' => true, 'message' => '回复成功'));
        break;
        
    default:
        echo json_encode(array('success' => false, 'message' => '未知操作'));
}
?>
