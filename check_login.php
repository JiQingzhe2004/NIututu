<?php
// 检查会话是否已启动
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查会话中是否存在用户信息
if (!isset($_SESSION['user'])) {
    // 未登录，重定向到登录页面
    header('Location: login.php');
    exit();
}

// 用户已登录，可以继续访问受保护的页面
?>