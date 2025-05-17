<?php
// 设置时区为上海
date_default_timezone_set('Asia/Shanghai');

// 启用错误报告（开发环境中使用，生产环境请关闭）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 确保已启动会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否已登录
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit();
}

// 读取配置文件并建立数据库连接
require 'config.php';

$user = $_SESSION['user'];
$userId = $user['id'];

// 设置响应头为 JSON
header('Content-Type: application/json');

// 定义文件类型映射
$mimeTypeMapping = [
    'image' => 'image',
    'video' => 'video',
    'audio' => 'audio',
    'audio/mpeg' => 'audio',
    'application/pdf' => 'document',
    'application/msword' => 'document',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
    'application/vnd.ms-excel' => 'document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'document',
    'application/zip' => 'archive',
    'application/x-rar-compressed' => 'archive',
    'text/plain' => 'text',
    'text/markdown' => 'text', // 新增：支持 Markdown 文件
    'application/octet-stream' => 'binary', // 新增：处理通用二进制文件
    // 添加更多常见的 MIME 类型映射
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $upload_dir = 'uploads/' . $userId . '/';
    // 确保上传目录存在
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(['success' => false, 'error' => '创建上传目录失败']);
            exit();
        }
    }

    $files = $_FILES['file'];
    $customNames = isset($_POST['customName']) ? $_POST['customName'] : [];
    $accesses = isset($_POST['access']) ? $_POST['access'] : 'private';
    $responses = [];

    for ($i = 0; $i < count($files['name']); $i++) {
        // 获取自定义文件名，如果未设置则使用原始文件名
        $customName = isset($customNames[$i]) && !empty(trim($customNames[$i])) ? trim($customNames[$i]) : basename($files['name'][$i]);
        
        // 规范化自定义文件名，移除不安全的字符（允许Unicode字母）
        $customName = preg_replace('/[^\p{L}0-9_\-.\s]/u', '_', $customName);

        // 检查数据库中是否已存在相同 original_name 的记录
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM files WHERE user_id = ? AND original_name = ?');
        $checkStmt->execute([$userId, $customName]);
        if ($checkStmt->fetchColumn() > 0) {
                        $responses[] = ['success' => false, 'error' => "文件名 '{$customName}' 已存在，请换名称。"];
            continue;
        }

        $mimeType = $files['type'][$i];
        $filesize = intval($files['size'][$i]); // 确保是整数

        // 获取访问权限，默认为 'private'
        $access = in_array($accesses, ['public', 'private']) ? $accesses : 'private';

        // 阻止上传 .php 文件
        $fileExt = strtolower(pathinfo($customName, PATHINFO_EXTENSION));
        if ($fileExt === 'php') {
            $responses[] = ['success' => false, 'error' => '不允许上传 PHP 文件。'];
            continue;
        }

        // 文件大小限制
        $maxFileSize = 300 * 1024 * 1024; // 300MB
        if ($filesize > $maxFileSize) {
            $responses[] = ['success' => false, 'error' => '文件大小超过限制 (最大300MB)。'];
            continue;
        }

        // 检查上传过程中是否有错误
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => '上传的文件超过了php.ini中upload_max_filesize选项限制的值。',
                UPLOAD_ERR_FORM_SIZE  => '上传的文件超过了表单中MAX_FILE_SIZE选项指定的值。',
                UPLOAD_ERR_PARTIAL    => '文件仅被部分上传。',
                UPLOAD_ERR_NO_FILE    => '没有文件被上传。',
                UPLOAD_ERR_NO_TMP_DIR => '缺少临时文件夹。',
                UPLOAD_ERR_CANT_WRITE => '无法将文件写入磁盘。',
                UPLOAD_ERR_EXTENSION  => '由于扩展程序中断文件上传。',
            ];
            $error = isset($errorMessages[$files['error'][$i]]) ? $errorMessages[$files['error'][$i]] : '未知的上传错误。';
            $responses[] = ['success' => false, 'error' => '文件上传错误: ' . $error];
            continue;
        }

        // 在文件名前面加上时间戳，确保唯一性
        $timestamp = time();
        $uniqueName = $timestamp . '_' . $customName;
        $filepath = $upload_dir . $uniqueName;

        // 检查文件是否已存在（虽然添加了时间戳，重复概率极低）
        if (file_exists($filepath)) {
            $responses[] = ['success' => false, 'error' => '文件已存在'];
            continue;
        } else {
            if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                // 保留完整的 MIME 类型
                $fileMimeType = $mimeType; // 例如 "image/png"

                // 生成上传时间（服务器时间）
                $uploadTime = date('Y-m-d H:i:s');

                // 插入文件信息到数据库
                try {
                    // 确保 $fileMimeType 不超过 255 个字符（根据数据库定义调整）
                    $fileMimeType = substr($fileMimeType, 0, 255);

                    // 修改 SQL 语句，使用 original_name 存储自定义名称
                    $stmt = $pdo->prepare('INSERT INTO files (name, original_name, type, size, path, upload_time, user_id, access) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    // 传递参数
                    $stmt->execute([$uniqueName, $customName, $fileMimeType, $filesize, $filepath, $uploadTime, $userId, $access]);
                    $responses[] = ['success' => true];
                } catch (PDOException $e) {
                    // 删除已上传的文件
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                    // 记录错误到日志文件
                    error_log("数据库错误: " . $e->getMessage(), 3, 'error_log.txt');
                    $responses[] = ['success' => false, 'error' => '数据库错误，请稍后重试。'];
                    continue;
                }
            } else {
                // 获取最后一个错误
                $error = error_get_last();
                $errorMessage = isset($error['message']) ? $error['message'] : '未知的错误。';
                $responses[] = ['success' => false, 'error' => '文件上传失败: ' . $errorMessage];
                continue;
            }
        }
    }

    echo json_encode($responses);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '请求方法不被允许']);
    exit();
}
?>