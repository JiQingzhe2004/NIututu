<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 启动会话并检查用户是否已登录
session_start();
require 'check_login.php';

// 包含数据库连接配置
require 'config.php';

$message = '';
$messageType = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 验证输入
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = "所有字段都是必填的。";
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = "新密码和确认密码不一致。";
        $messageType = 'danger';
    } else {
        // 获取当前用户的密码哈希
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user']['id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($currentPassword, $user['password'])) {
            // 更新密码并清空remember_token
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ?, remember_token = NULL WHERE id = ?');
            try {
                $stmt->execute([$newPasswordHash, $_SESSION['user']['id']]);
                // 清除本地 remember_token cookie
                setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                $message = "密码已成功更新，已清除免登录信息。";
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = "更新密码失败: " . $e->getMessage();
                $messageType = 'danger';
            }
        } else {
            $message = "当前密码不正确。";
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link href="favicon.ico" rel="icon">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="/static/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/static/favicon.svg" />
    <link rel="shortcut icon" href="/static/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/static/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="牛图图传输" />
    <link rel="manifest" href="/static/site.webmanifest" />
    <title>修改密码</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #222;
        }
        .container {
            max-width: 500px;
        }
        .card {
            margin-top: 50px;
            background-color: #fff;
            color: #222;
            border-color: #ddd;
        }
        /* 夜间模式样式 */
        body.night-mode {
            background-color: #181a1b !important;
            color: #eee !important;
        }
        body.night-mode .card {
            background-color: #23272b !important;
            color: #eee !important;
            border-color: #222 !important;
        }
        body.night-mode .form-control,
        body.night-mode .form-label {
            background-color: #23272b !important;
            color: #eee !important;
        }
        body.night-mode .btn-primary {
            background-color: #3b82f6 !important;
            border-color: #3b82f6 !important;
        }
        body.night-mode .btn-secondary {
            background-color: #374151 !important;
            border-color: #374151 !important;
        }
        body.night-mode .alert {
            background-color: #222 !important;
            color: #fbbf24 !important;
            border-color: #444 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-body">
                <h3 class="card-title text-center">修改密码</h3>

                <!-- 显示消息 -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
                    </div>
                <?php endif; ?>

                <!-- 修改密码表单 -->
                <form method="post" action="change_password.php">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">当前密码</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">新密码</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">确认新密码</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">保存更改</button>
                </form>
            </div>
        </div>
        <a href="index.php" class="btn btn-secondary w-100 mt-3">返回首页</a>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // 6:00-18:00为亮色，其余为暗色（夜间模式加body.night-mode类）
        (function () {
            var hour = new Date().getHours();
            if (hour < 6 || hour >= 18) {
                document.body.classList.add('night-mode');
            }
        })();
    </script>
</body>
</html>