<?php
// up_user.php

require 'config.php'; // 包含数据库连接配置

// 检查是否是AJAX请求
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');

    $uploadResults = []; // 存储每条用户上传结果
    $message = '';
    $messageType = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 获取多行用户数据
        $userData = trim($_POST['user_data'] ?? '');

        if (empty($userData)) {
            echo json_encode([
                'status' => 'error',
                'message' => '没有用户数据提交。',
            ]);
            exit;
        }

        // 按行分割数据，支持不同的换行符
        $lines = preg_split('/\r\n|\r|\n/', $userData);

        // 准备插入用户数据的 SQL 语句
        $stmt = $pdo->prepare('INSERT INTO users (username, name, password, role) VALUES (?, ?, ?, ?)');

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue; // 跳过空行
            }

            // 自动检测分隔符（逗号或制表符）
            if (strpos($line, "\t") !== false) {
                $parts = explode("\t", $line);
            } else {
                $parts = explode(',', $line);
            }

            if (count($parts) < 4) {
                $uploadResults[] = [
                    'status' => 'error',
                    'message' => "第 " . ($index + 1) . " 行格式错误。需要4个字段（用户名, 姓名, 密码, 角色）。",
                ];
                continue;
            }

            list($username, $name, $password, $role) = array_map('trim', $parts);

            // 验证数据
            if (empty($username) || empty($name) || empty($password)) {
                $uploadResults[] = [
                    'status' => 'error',
                    'message' => "第 " . ($index + 1) . " 行有空字段。",
                ];
                continue;
            }

            if (!in_array(strtolower($role), ['admin', 'user'])) {
                $role = 'user'; // 默认角色
            }

            // 哈希密码
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 插入数据
            try {
                $stmt->execute([$username, $name, $hashedPassword, $role]);
                $uploadResults[] = [
                    'status' => 'success',
                    'message' => "第 " . ($index + 1) . " 行用户 '<span class=\"username\">{$username}</span>'（姓名: <span class=\"name\">{$name}</span>，角色: <span class=\"role\">{$role}</span>）上传成功。",
                ];
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // 重复键错误
                    $uploadResults[] = [
                        'status' => 'error',
                        'message' => "第 " . ($index + 1) . " 行失败: 用户名 '<span class=\"username\">{$username}</span>'，姓名 '<span class=\"name\">{$name}</span>'，角色 '<span class=\"role\">{$role}</span>' 已存在。",
                    ];
                } else {
                    $uploadResults[] = [
                        'status' => 'error',
                        'message' => "第 " . ($index + 1) . " 行失败: " . htmlspecialchars($e->getMessage()),
                    ];
                }
            }
        }

        if (!empty($uploadResults)) {
            $message = "上传流程已经完成！";
            $messageType = 'success';
        }

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'results' => $uploadResults,
        ]);
        exit;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => '无效的请求方式。',
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>批量添加用户</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .instructions {
            white-space: pre-wrap;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin-bottom: 15px;
        }
        .username {
            color: #0d6efd; /* 蓝色 */
            font-weight: bold;
        }
        .name {
            color: #198754; /* 绿色 */
            font-weight: bold;
        }
        .role {
            color: #fd7e14; /* 橙色 */
            font-weight: bold;
        }
        #spinner-container {
            display: none;
            text-align: center;
            margin-bottom: 15px;
        }
        #upload-results {
            display: none;
        }
        @media (max-width: 768px) {
            .mt-mobile {
                margin-top: 5px; /* 根据需要调整上边距大小 */
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center">批量添加用户</h1>
        <div id="message-container"></div>
        <div id="spinner-container">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">加载中...</span>
            </div>
            <p>正在上传...</p>
        </div>
        <form id="upload-form">
            <div class="mb-3">
                <label for="user_data" class="form-label">用户信息</label>
                <div class="instructions">
用户名,姓名,密码,角色
例如：
john_doe,John Doe,secret123,user
jane_admin,Jane Admin,adminpass,admin
或
john_doe	John Doe	secret123	user
jane_admin	Jane Admin	adminpass	admin
均可，最好的方式是使用模板文件，每行一个用户，使用Excel编辑完之后，直接复制过来就好了。
                </div>
                <textarea class="form-control" id="user_data" name="user_data" rows="10" placeholder="请输入用户信息，每行一个用户，字段之间用逗号或制表符分隔。" required><?php echo isset($_POST['user_data']) ? htmlspecialchars($_POST['user_data']) : ''; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">上传并添加用户</button>
            <button type="button" class="btn btn-secondary" onclick="history.back();">返回上一页</button>
            <a href="用户模板.txt" class="btn btn-secondary">下载模板文件</a>
            <a href="upload_users.php" class="btn btn-secondary btn-info mt-mobile">换方式</a>
        </form>
        <div id="upload-results" class="mt-4">
            <h3>上传结果：</h3>
            <ul class="list-group" id="results-list">
            </ul>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('upload-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const userData = document.getElementById('user_data').value.trim();
            if (!userData) {
                showMessage('danger', '没有用户数据提交。');
                return;
            }

            const formData = new FormData();
            formData.append('user_data', userData);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'up_user.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onloadstart = function () {
                document.getElementById('spinner-container').style.display = 'block';
                // 清除之前的结果
                document.getElementById('results-list').innerHTML = '';
                document.getElementById('upload-results').style.display = 'none';
                document.getElementById('message-container').innerHTML = '';
            };

            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        showMessage('success', response.message);
                        displayResults(response.results);
                    } else {
                        showMessage('danger', response.message);
                    }
                } else {
                    showMessage('danger', '服务器错误。');
                }
                document.getElementById('spinner-container').style.display = 'none';
            };

            xhr.onerror = function () {
                showMessage('danger', '上传过程中发生错误。');
                document.getElementById('spinner-container').style.display = 'none';
            };

            xhr.send(formData);
        });

        function showMessage(type, message) {
            const messageContainer = document.getElementById('message-container');
            messageContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
                </div>
            `;
        }

        function displayResults(results) {
            const resultsList = document.getElementById('results-list');
            results.forEach(function (result) {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                // 保持背景为白色，不使用 success 或 danger 类
                if (result.status === 'success') {
                    // 仅给特定字段添加颜色
                    li.innerHTML = result.message;
                } else {
                    li.innerHTML = result.message;
                }
                resultsList.appendChild(li);
            });
            document.getElementById('upload-results').style.display = 'block';
        }
    </script>
</body>
</html>