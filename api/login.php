<?php
// api/login.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '仅支持 POST 请求']);
    exit;
}

// 获取请求数据
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// 验证用户
$stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // 设置会话
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ];
    echo json_encode(['success' => true, 'message' => '登录成功']);
} else {
    echo json_encode(['success' => false, 'error' => '用户名或密码错误']);
}
?>