<?php
// change_access.php

// 确保已启动会话
session_start();

// 设置响应头为 JSON
header('Content-Type: application/json');

// 检查用户是否已登录
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit();
}

// 读取配置文件并建立数据库连接
require 'config.php';

$user = $_SESSION['user'];
$userId = $user['id'];

// 获取输入数据
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['file_id']) || !isset($input['access'])) {
    echo json_encode(['success' => false, 'error' => '无效的请求参数']);
    exit();
}

$fileId = intval($input['file_id']);
$newAccess = in_array($input['access'], ['public', 'private']) ? $input['access'] : 'private';

try {
    // 检查文件是否属于当前用户
    $stmt = $pdo->prepare('SELECT * FROM files WHERE id = ? AND user_id = ?');
    $stmt->execute([$fileId, $userId]);
    $file = $stmt->fetch();

    if (!$file) {
        echo json_encode(['success' => false, 'error' => '文件不存在或您无权修改此文件']);
        exit();
    }

    // 更新访问权限
    $updateStmt = $pdo->prepare('UPDATE files SET access = ? WHERE id = ?');
    $updateStmt->execute([$newAccess, $fileId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => '数据库错误: ' . $e->getMessage()]);
}
?>