<?php
// FILE: api/file_manager.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // 开发阶段允许所有来源，生产环境请限制
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 设置时区为上海
date_default_timezone_set('Asia/Shanghai');

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
$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => '用户不存在']);
    exit();
}

// 定义文件类型映射
$mimeTypeMapping = [
    'image' => 'image',
    'video' => 'video',
    'audio' => 'audio',
    'application/pdf' => 'document',
    'application/msword' => 'document',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
    'application/vnd.ms-excel' => 'document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'document',
    'application/zip' => 'archive',
    'application/x-rar-compressed' => 'archive',
    'text/plain' => 'text',
    // 添加更多常见的 MIME 类型映射
];

if (isset($_FILES['file'])) {
    $upload_dir = '../uploads/' . $userId . '/';
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
                // 映射 MIME 类型到常见类别
                $fileCategory = 'other'; // 默认类别
                foreach ($mimeTypeMapping as $key => $value) {
                    if (strpos($mimeType, $key) !== false) {
                        $fileCategory = $value;
                        break;
                    }
                }

                // 生成上传时间（服务器时间）
                $uploadTime = date('Y-m-d H:i:s');

                // 插入文件信息到数据库，使用 original_name 存储自定义名称
                try {
                    // 确保 $fileCategory 不超过 50 个字符（根据数据库定义调整）
                    $fileCategory = substr($fileCategory, 0, 50);

                    // 修改 SQL 语句，使用 original_name 存储自定义名称
                    $stmt = $pdo->prepare('INSERT INTO files (name, original_name, type, size, path, upload_time, user_id, access) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    // 传递参数
                    $stmt->execute([$uniqueName, $customName, $fileCategory, $filesize, $filepath, $uploadTime, $userId, $access]);
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