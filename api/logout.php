<?php
// FILE: api/logout.php

header('Content-Type: application/json');
session_start();

// 销毁会话
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 返回成功消息
echo json_encode(['success' => true, 'message' => '已成功退出登录']);
?>