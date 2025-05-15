<?php
// FILE: api/file_list.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // 开发阶段允许所有来源，生产环境请限制
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 引入配置和JWT帮助类
require '../config.php';
require '../jwt_helper.php';

// 确保请求方法为GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // 方法不允许
    echo json_encode(['error' => '仅支持 GET 请求']);
    exit();
}

// 获取 Authorization 头
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!$authHeader) {
    http_response_code(401);
    echo json_encode(['error' => '缺少 Authorization 头']);
    exit();
}

// 从头中提取 token
list($type, $token) = explode(' ', $authHeader, 2);
if (strcasecmp($type, 'Bearer') != 0 || !$token) {
    http_response_code(400);
    echo json_encode(['error' => '无效的 Authorization 头格式']);
    exit();
}

// 验证 token
$userId = JwtHelper::validateToken($token);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => '无效或过期的令牌']);
    exit();
}

// 获取用户角色
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => '用户不存在']);
    exit();
}

$userRole = $user['role'];

try {
    if ($userRole === 'admin') {
        // 管理员可以查看所有文件，并按当前用户的文件优先排序
        $stmt = $pdo->prepare('
            SELECT files.id, files.name, files.original_name, files.size, files.upload_time, files.path, users.name AS uploader_name, files.access
            FROM files
            JOIN users ON files.user_id = users.id
            ORDER BY (files.user_id = ?) DESC, files.upload_time DESC
        ');
        $stmt->execute([$userId]);
    } else {
        // 普通用户只能查看公开的文件和自己的私密文件，并将自己的文件排在最前面
        $stmt = $pdo->prepare('
            SELECT files.id, files.name, files.original_name, files.size, files.upload_time, files.path, users.name AS uploader_name, files.access
            FROM files
            JOIN users ON files.user_id = users.id
            WHERE files.access = "public" OR files.user_id = ?
            ORDER BY (files.user_id = ?) DESC, files.upload_time DESC
        ');
        $stmt->execute([$userId, $userId]);
    }

    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $files]);
} catch (Exception $e) {
    http_response_code(500); // 服务器错误
    echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
}
?>