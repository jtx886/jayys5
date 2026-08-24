<?php
if ($page != 'settings') return;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_theme'])) {
    $color = $_POST['theme_color'];
    if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
        update_setting('theme_color', $color);
        $theme_color = $color;
        $success_msg = '主题颜色已更新！刷新页面生效。';
    } else {
        $error_msg = '请输入有效的颜色值';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tmdb'])) {
    $api_key = trim($_POST['tmdb_api_key']);
    update_setting('tmdb_api_key', $api_key);
    $success_msg = 'TMDB API设置已更新！';
}
?>

<?php if ($page == 'settings'): ?>
<div style="max-width:600px;">
    <div class="list-card">
        <h3>🎨 主题颜色设置</h3>
        <form method="POST">
            <input type="hidden" name="update_theme" value="1">
            <div class="form-group">
                <label class="form-label">网站主题色</label>
                <div style="display:flex;gap:15px;align-items:center;">
                    <input type="color" name="theme_color" value="<?php echo $theme_color; ?>" class="color-picker" style="width:80px;height:50px;">
                    <input type="text" name="theme_color_text" value="<?php echo $theme_color; ?>" class="form-input" style="width:150px;" onchange="document.querySelector('[name=theme_color]').value=this.value">
                    <div class="color-preview" id="colorPreview" style="background:<?php echo $theme_color; ?>;"></div>
                </div>
                <p style="color:var(--text-muted);font-size:13px;margin-top:10px;">选择颜色后点击保存，全站主题色将更新</p>
            </div>
            
            <div style="margin-top:20px;">
                <p style="margin-bottom:10px;color:var(--text-secondary);font-size:14px;">预设颜色：</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <?php 
                    $presets = array('#e50914' => 'Netflix红', '#1db954' => 'Spotify绿', '#3b5998' => 'Facebook蓝', '#e60023' => 'Pinterest红', '#ff6b00' => '橙色', '#9933ff' => '紫色', '#00a8e8' => '天蓝', '#f5c518' => 'IMDb黄');
                    foreach ($presets as $c => $name):
                    ?>
                    <button type="button" onclick="setColor('<?php echo $c; ?>')" style="width:40px;height:40px;border-radius:8px;background:<?php echo $c; ?>;border:3px solid <?php echo $theme_color == $c ? 'white' : 'transparent'; ?>;cursor:pointer;" title="<?php echo $name; ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top:25px;">保存颜色设置</button>
        </form>
    </div>
    
    <div class="list-card" style="margin-top:20px;">
        <h3>⚙️ API设置</h3>
        <form method="POST">
            <input type="hidden" name="update_tmdb" value="1">
            <div class="form-group">
                <label class="form-label">TMDB API Key</label>
                <input type="text" name="tmdb_api_key" class="form-input" value="<?php echo e(get_setting('tmdb_api_key', TMDB_API_KEY)); ?>">
                <p style="color:var(--text-muted);font-size:13px;margin-top:8px;">用于获取影视数据，当前已使用默认配置</p>
            </div>
            <button type="submit" class="btn btn-secondary">保存API设置</button>
        </form>
    </div>
    
    <div class="list-card" style="margin-top:20px;">
        <h3>ℹ️ 系统信息</h3>
        <table class="admin-table" style="background:transparent;">
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid var(--border-color);color:var(--text-secondary);width:150px;">PHP版本</td>
                <td style="padding:10px 0;border-bottom:1px solid var(--border-color);"><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid var(--border-color);color:var(--text-secondary);">服务器软件</td>
                <td style="padding:10px 0;border-bottom:1px solid var(--border-color);"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
            </tr>
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid var(--border-color);color:var(--text-secondary);">站点路径</td>
                <td style="padding:10px 0;border-bottom:1px solid var(--border-color);font-size:12px;"><?php echo SITE_PATH; ?></td>
            </tr>
        </table>
    </div>
</div>

<script>
function setColor(color) {
    document.querySelector('[name=theme_color]').value = color;
    document.querySelector('[name=theme_color_text]').value = color;
    document.getElementById('colorPreview').style.background = color;
}

document.querySelector('[name=theme_color]').addEventListener('input', function() {
    document.querySelector('[name=theme_color_text]').value = this.value;
    document.getElementById('colorPreview').style.background = this.value;
});
</script>
<?php endif; ?>
