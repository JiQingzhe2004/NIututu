<?php
// FILE: api/delete_file.php

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
if (!isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['error' => '无效的请求']);
    exit();
}

$fileId = intval($_POST['id']);
$userRole = strtolower($user['role']); // 转换为小写

try {
    if ($userRole === 'admin') {
        // 管理员删除逻辑
        deleteFileAsAdmin($pdo, $fileId, $userId);
    } else {
        // 普通用户删除逻辑
        deleteFileAsUser($pdo, $fileId, $userId);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
}

/**
 * 管理员删除文件的函数
 *
 * @param PDO $pdo 数据库连接对象
 * @param int $fileId 文件ID
 * @param int $userId 当前用户ID
 */
function deleteFileAsAdmin($pdo, $fileId, $userId) {
    try {
        // 查询文件
        $stmt = $pdo->prepare('SELECT * FROM files WHERE id = ?');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if ($file) {
            $filePath = $file['path'];
            if (file_exists($filePath)) {
                // 开始事务
                $pdo->beginTransaction();

                if (unlink($filePath)) {
                    // 从数据库中删除记录
                    $stmt = $pdo->prepare('DELETE FROM files WHERE id = ?');
                    $stmt->execute([$fileId]);

                    // 提交事务
                    $pdo->commit();

                    echo json_encode(['success' => true, 'message' => '文件已删除']);
                    
                    // 添加日志记录
                    error_log("File ID $fileId deleted by Admin User ID $userId");
                } else {
                    // 回滚事务
                    $pdo->rollBack();

                    http_response_code(500);
                    echo json_encode(['error' => '删除文件时发生错误']);
                    error_log("Error deleting file at path: $filePath by Admin User ID $userId");
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => '文件不存在']);
                error_log("File not found at path: $filePath for File ID $fileId");
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => '文件不存在']);
            error_log("File ID $fileId not found");
        }
    } catch (PDOException $e) {
        // 回滚事务
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        echo json_encode(['error' => '删除过程中发生数据库错误']);
        error_log("Database error while deleting file ID $fileId: " . $e->getMessage());
    }
}

/**
 * 普通用户删除文件的函数
 *
 * @param PDO $pdo 数据库连接对象
 * @param int $fileId 文件ID
 * @param int $userId 当前用户ID
 */
function deleteFileAsUser($pdo, $fileId, $userId) {
    try {
        // 查询文件，确保是当前用户上传的
        $stmt = $pdo->prepare('SELECT * FROM files WHERE id = ? AND user_id = ?');
        $stmt->execute([$fileId, $userId]);
        $file = $stmt->fetch();

        if ($file) {
            $filePath = $file['path'];
            if (file_exists($filePath)) {
                // 开始事务
                $pdo->beginTransaction();

                if (unlink($filePath)) {
                    // 从数据库中删除记录
                    $stmt = $pdo->prepare('DELETE FROM files WHERE id = ?');
                    $stmt->execute([$fileId]);

                    // 提交事务
                    $pdo->commit();

                    echo json_encode(['success' => true, 'message' => '文件已删除']);
                    
                    // 添加日志记录
                    error_log("File ID $fileId deleted by User ID $userId");
                } else {
                    // 回滚事务
                    $pdo->rollBack();

                    http_response_code(500);
                    echo json_encode(['error' => '删除文件时发生错误']);
                    error_log("Error deleting file at path: $filePath by User ID $userId");
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => '文件不存在']);
                error_log("File not found at path: $filePath for File ID $fileId");
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => '文件不存在或无权限删除']);
            error_log("File ID $fileId not found or User ID $userId lacks permission");
        }
    } catch (PDOException $e) {
        // 回滚事务
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        echo json_encode(['error' => '删除过程中发生数据库错误']);
        error_log("Database error while deleting file ID $fileId: " . $e->getMessage());
    }
}
?>