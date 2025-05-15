<?php
// cleanup.php

// 引入数据库配置
require 'config.php';

// 设置时区
date_default_timezone_set('Asia/Shanghai');

try {
    // 连接数据库
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // 查询所有文件记录
    $stmt = $pdo->query('SELECT id, path FROM files');
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $deletedCount = 0;

    foreach ($files as $file) {
        $filePath = $file['path'];
        
        // 检查文件是否存在
        if (!file_exists($filePath)) {
            // 删除数据库记录
            $deleteStmt = $pdo->prepare('DELETE FROM files WHERE id = ?');
            $deleteStmt->execute([$file['id']]);
            $deletedCount++;
        }
    }
    
    // 记录日志
    $logMessage = date('Y-m-d H:i:s') . " - 已删除 {$deletedCount} 条数据库记录。\n";
    file_put_contents('cleanup.log', $logMessage, FILE_APPEND);
    
} catch (PDOException $e) {
    // 记录错误日志
    $errorMessage = date('Y-m-d H:i:s') . " - 错误: " . $e->getMessage() . "\n";
    file_put_contents('cleanup_error.log', $errorMessage, FILE_APPEND);
    exit;
}
?>