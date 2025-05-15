<?php
// FILE: api/download.php

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

// 获取用户信息
$stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => '用户不存在']);
    exit();
}

// 检查文件ID
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => '无效的请求']);
    exit();
}

$fileId = intval($_GET['id']);

// 获取文件信息
$stmt = $pdo->prepare('SELECT * FROM files WHERE id = ?');
$stmt->execute([$fileId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    echo json_encode(['error' => '文件不存在或无权限访问']);
    exit();
}

// 检查文件访问权限
if ($file['access'] === 'private' && $file['user_id'] !== $userId && $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => '无权限访问此文件']);
    exit();
}

$filePath = $file['path'];
$originalName = $file['original_name'];

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => '文件不存在']);
    exit();
}

// 获取文件 MIME 类型
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// 设置响应头
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($originalName) . '"');
header('Content-Length: ' . filesize($filePath));

// 清除输出缓冲区，避免乱码
if (ob_get_level()) {
    ob_end_clean();
}

// 读取文件并输出
readfile($filePath);
exit();
?>