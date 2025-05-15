<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 启动会话并检查用户是否已登录
session_start();
require 'check_login.php';

// 包含数据库连接配置
require 'config.php';

// 数据库连接
$config = json_decode(file_get_contents('config.json'), true);
$dbConfig = $config['db'];

$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

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
            // 更新密码
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            try {
                $stmt->execute([$newPasswordHash, $_SESSION['user']['id']]);
                $message = "密码已成功更新。";
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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>修改密码</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 自定义样式 */
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 500px;
        }
        .card {
            margin-top: 50px;
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
</body>
</html>