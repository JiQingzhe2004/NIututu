<?php
session_start();
require 'config.php';

// 检查用户是否已登录并且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// 获取上一条公告的 update_content 和 update_enabled
$stmt_latest = $pdo->prepare('SELECT update_content, update_enabled FROM announcements ORDER BY created_at DESC LIMIT 1');
$stmt_latest->execute();
$latest_announcement = $stmt_latest->fetch(PDO::FETCH_ASSOC);
$previous_update_content = $latest_announcement['update_content'] ?? ''; // 如果没有上一条，则为空
$previous_update_enabled = $latest_announcement['update_enabled'] ?? 0; // 如果没有，则为 0（假）

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginContent = $_POST['login_content'] ?? '';
    $indexContent = $_POST['index_content'] ?? '';

    if (!empty($loginContent) && !empty($indexContent)) {
        // 插入新公告，并带入上一条的 update_content 和 update_enabled
        $stmt = $pdo->prepare('INSERT INTO announcements (login_content, index_content, update_content, update_enabled) VALUES (?, ?, ?, ?)');
        $stmt->execute([$loginContent, $indexContent, $previous_update_content, $previous_update_enabled]);
        $message = '公告已更新';
    } else {
        $message = '公告内容不能为空';
    }
}

// 获取最新的公告内容
$stmt = $pdo->prepare('SELECT login_content, index_content FROM announcements ORDER BY created_at DESC LIMIT 1');
$stmt->execute();
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="/static/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/static/favicon.svg" />
    <link rel="shortcut icon" href="/static/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/static/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="牛图图传输" />
    <link rel="manifest" href="/static/site.webmanifest" />
    <title>管理公告</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1 class="mt-5">管理公告</h1>
    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="login_content" class="form-label">登录页面公告内容</label>
            <textarea class="form-control" id="login_content" name="login_content" rows="5" required><?php echo htmlspecialchars($announcement['login_content']); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="index_content" class="form-label">首页公告内容</label>
            <textarea class="form-control" id="index_content" name="index_content" rows="5" required><?php echo htmlspecialchars($announcement['index_content']); ?></textarea>
        </div>
        <!-- 隐藏的 update_enabled 字段（可选） -->
        <!--
        <input type="hidden" name="update_enabled" value="<?php echo htmlspecialchars($previous_update_enabled); ?>">
        -->
        <button type="submit" class="btn btn-primary">更新公告</button>
        <a href="index" class="btn btn-secondary">返回首页</a>
    </form>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>