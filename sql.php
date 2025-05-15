<?php
// cleanup.php

// 引入数据库配置
require 'config.php';

// 设置时区
date_default_timezone_set('Asia/Shanghai');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_log') {
    // 读取日志文件并返回内容
    $logFile = 'execution.log';
    if (file_exists($logFile)) {
        echo nl2br(file_get_contents($logFile));
    } else {
        echo "日志文件不存在。";
    }
    exit;
}

try {
    // 连接数据库
    $pdo = new PDO($dsn, $db_user, $db_password, $options);
    
    // 运行 up.SQL 文件中的代码
    $sql = file_get_contents('up.SQL');
    $pdo->exec($sql);
    
    // 记录执行日志
    file_put_contents('execution.log', date('Y-m-d H:i:s') . " - 执行 up.SQL 文件中的代码。\n", FILE_APPEND);
    
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
    file_put_contents('execution.log', $logMessage, FILE_APPEND);
    
} catch (PDOException $e) {
    // 记录错误日志
    $errorMessage = date('Y-m-d H:i:s') . " - 错误: " . $e->getMessage() . "\n";
    file_put_contents('execution.log', $errorMessage, FILE_APPEND);
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>SQL 执行记录</title>
    <style>
        #log {
            white-space: pre-wrap;
            border: 1px solid #ccc;
            padding: 10px;
            height: 300px;
            overflow-y: scroll;
        }
    </style>
</head>
<body>
    <h1>SQL 执行记录</h1>
    <div id="log">加载中...</div>

    <script>
        function fetchLog() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '?action=get_log', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('log').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        // 每5秒钟获取一次日志内容
        setInterval(fetchLog, 5000);
        // 页面加载时立即获取一次日志内容
        fetchLog();
    </script>
</body>
</html>