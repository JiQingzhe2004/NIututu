<?php
// FILE: api/register.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // 开发阶段允许所有来源，生产环境请限制
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 引入配置和JWT帮助类
require '../config.php';
require '../jwt_helper.php';

// 确保请求方法为POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 方法不允许
    echo json_encode(['error' => '仅支持 POST 请求']);
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

// 检查是否为管理员
if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => '您没有权限执行此操作']);
    exit();
}

// 获取输入数据（支持JSON和表单数据）
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// 提取并验证输入字段
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$name = trim($input['name'] ?? '');
$role = $input['role'] ?? '';

if (empty($username) || empty($password) || empty($name) || empty($role)) {
    http_response_code(400);
    echo json_encode(['error' => '请填写所有必填字段']);
    exit();
}

if (!in_array($role, ['admin', 'user'])) {
    http_response_code(400);
    echo json_encode(['error' => '无效的用户角色']);
    exit();
}

try {
    // 检查用户名是否已存在
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(409); // 冲突
        echo json_encode(['error' => '用户名已存在，请选择另一个用户名']);
        exit();
    }

    // 哈希密码
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 插入新用户
    $stmt = $pdo->prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)');
    if ($stmt->execute([$username, $hashedPassword, $name, $role])) {
        echo json_encode(['success' => true, 'message' => '用户注册成功']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => '用户注册失败，请稍后再试']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
}
?>