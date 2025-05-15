<?php
session_start();
require 'check_login.php';
require 'config.php';

if (isset($_GET['id'])) {
    $fileId = intval($_GET['id']);

    // 获取文件信息
    $stmt = $pdo->prepare('SELECT * FROM files WHERE id = ?');
    $stmt->execute([$fileId]);

    $file = $stmt->fetch();

    if ($file) {
        $filePath = $file['path'];
        $originalName = $file['original_name'];

        if (file_exists($filePath)) {
            // 获取文件 MIME 类型
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            // 设置响应头
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filePath));

            // 清除输出缓冲区，避免乱码
            if (ob_get_level()) {
                ob_end_clean();
            }

            // 读取文件并输出
            readfile($filePath);
            exit;
        } else {
            http_response_code(404);
            echo '文件不存在';
        }
    } else {
        http_response_code(404);
        echo '文件不存在或无权限访问';
    }
} else {
    http_response_code(400);
    echo '无效的请求';
}
?>