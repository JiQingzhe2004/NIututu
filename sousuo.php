<?php
// filepath: /f:/JiQingzhe/文件传输/file_list.php
session_start();
require 'check_login.php';
require 'config.php';

// 设置响应的内容类型为JSON
header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// 获取搜索参数（如果存在）
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($search !== '') {
        // 处理搜索请求
        if ($userRole === 'admin') {
            // 管理员可以搜索所有文件
            $stmt = $pdo->prepare('
                SELECT files.id, files.original_name
                FROM files
                JOIN users ON files.user_id = users.id
                WHERE files.original_name LIKE ?
                ORDER BY files.upload_time DESC
                LIMIT 10
            ');
            $stmt->execute(["%$search%"]);
        } else {
            // 普通用户只能搜索公开的文件和自己的私密文件
            $stmt = $pdo->prepare('
                SELECT files.id, files.original_name
                FROM files
                JOIN users ON files.user_id = users.id
                WHERE (files.access = "public" OR files.user_id = ?)
                  AND files.original_name LIKE ?
                ORDER BY files.upload_time DESC
                LIMIT 10
            ');
            $stmt->execute([$userId, "%$search%"]);
        }
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results);
        exit();
    }

    // 获取完整文件列表
    if ($userRole === 'admin') {
        // 管理员可以查看所有文件，并按上传时间降序排序
        $stmt = $pdo->prepare('
            SELECT files.id, files.name, files.type, files.original_name, files.size, 
                   files.upload_time, files.path, files.access, users.name AS uploader_name
            FROM files
            JOIN users ON files.user_id = users.id
            ORDER BY files.upload_time DESC
        ');
        $stmt->execute([$userId]);
    } else {
        // 普通用户只能查看公开的文件和自己的私密文件，并按上传时间降序排序
        $stmt = $pdo->prepare('
            SELECT files.id, files.name, files.type, files.original_name, files.size, 
                   files.upload_time, files.path, files.access, users.name AS uploader_name
            FROM files
            JOIN users ON files.user_id = users.id
            WHERE files.access = "public" OR files.user_id = ?
            ORDER BY files.upload_time DESC
        ');
        $stmt->execute([$userId]);
    }

    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 返回完整的文件列表
    echo json_encode($files);
} catch (Exception $e) {
    // 发生错误时返回500状态码和错误信息
    http_response_code(500);
    echo json_encode(['error' => '服务器错误']);
}
?>