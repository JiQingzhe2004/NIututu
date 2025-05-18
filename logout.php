<?php
session_start();

// 清除数据库中的 remember_token
if (isset($_SESSION['user'])) {
    require 'config.php';
    $stmt = $pdo->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
}

// 清除浏览器 remember_token cookie
setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);

session_destroy();
header('Location: login');
exit();
?>