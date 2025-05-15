<?php
// filepath: /f:/JiQingzhe/文件传输/bulk_delete.php
session_start();
require 'check_login.php';
require 'config.php';

header('Content-Type: application/json');

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['file_ids']) || !is_array($data['file_ids'])) {
    echo json_encode(['success' => false, 'error' => '无效的请求参数。']);
    exit;
}

$fileIds = array_map('intval', $data['file_ids']); // 确保文件ID为整数
$placeholders = rtrim(str_repeat('?,', count($fileIds)), ',');

try {
    // 开启事务
    $pdo->beginTransaction();

    // 删除数据库记录
    $stmt = $pdo->prepare("DELETE FROM files WHERE id IN ($placeholders) AND uploader_id = ?");
    $params = $fileIds;
    $params[] = $_SESSION['user']['id']; // 确保用户只能删除自己上传的文件
    $stmt->execute($params);

    // 提交事务
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => '服务器错误，请稍后重试。']);
}
?>