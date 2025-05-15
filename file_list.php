<?php
session_start();
require 'check_login.php';
require 'config.php';

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// 获取文件列表
if ($userRole === 'admin') {
    // 管理员可以查看所有文件，并按当前用户的文件优先排序
    $stmt = $pdo->prepare('
        SELECT files.id, files.name, files.type, files.original_name, files.size, files.upload_time, files.path, files.access, users.name AS uploader_name
        FROM files
        JOIN users ON files.user_id = users.id
        ORDER BY (files.user_id = ?) DESC, files.upload_time DESC
    ');
    $stmt->execute([$userId]);
} else {
    // 普通用户只能查看公开的文件和自己的私密文件，并将自己的文件排在最前面
    $stmt = $pdo->prepare('
        SELECT files.id, files.name, files.type, files.original_name, files.size, files.upload_time, files.path, files.access, users.name AS uploader_name
        FROM files
        JOIN users ON files.user_id = users.id
        WHERE files.access = "public" OR files.user_id = ?
        ORDER BY (files.user_id = ?) DESC, files.upload_time DESC
    ');
    $stmt->execute([$userId, $userId]);
}

$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 以JSON格式返回文件列表
header('Content-Type: application/json');
echo json_encode($files);
?>