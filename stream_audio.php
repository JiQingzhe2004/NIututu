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

            // 打开文件
            $fileSize = filesize($filePath);
            $fileHandle = fopen($filePath, 'rb');

            // 处理范围请求
            $range = $_SERVER['HTTP_RANGE'] ?? '';
            if ($range) {
                // 解析范围请求
                list($start, $end) = explode('-', substr($range, 6));
                $start = intval($start);
                $end = $end ? intval($end) : $fileSize - 1;

                // 设置部分内容响应头
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
                header('Content-Length: ' . ($end - $start + 1));

                // 跳转到指定位置
                fseek($fileHandle, $start);
            } else {
                // 设置完整内容响应头
                header('Content-Length: ' . $fileSize);
            }

            // 设置响应头
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . $originalName . '"');
            header('Cache-Control: max-age=3600'); // 缓存 1 小时
            header('Accept-Ranges: bytes');

            // 清除输出缓冲区，避免乱码
            if (ob_get_level()) {
                ob_end_clean();
            }

            // 读取文件并输出
            $bufferSize = 8192; // 每次读取 8KB
            while (!feof($fileHandle)) {
                echo fread($fileHandle, $bufferSize);
                flush();
            }

            fclose($fileHandle);
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