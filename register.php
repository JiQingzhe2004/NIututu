<?php
session_start();
require 'config.php'; // 包含数据库连接配置

// 检查用户是否已登录并且是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo '您没有权限访问此页面。';
    exit();
}

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $role = $_POST['role']; // 'admin' 或 'user'

    // 简单的输入验证
    if (empty($username) || empty($password) || empty($name) || empty($role)) {
        $error = '请填写所有必填字段。';
    } elseif (!in_array($role, ['admin', 'user'])) {
        $error = '无效的用户角色。';
    } else {
        // 检查用户名是否已存在
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = '用户名已存在，请选择另一个用户名。';
        } else {
            // 哈希密码
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 插入新用户
            $stmt = $pdo->prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)');
            if ($stmt->execute([$username, $hashedPassword, $name, $role])) {
                $success = '用户注册成功。';
            } else {
                $error = '用户注册失败，请稍后再试。';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>用户注册 - 文件管理系统</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 自定义样式 */
        .btn {
            width: 100%;
        }
        .register-container {
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="container register-container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="text-center mb-4">用户注册</h2>
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($success)) : ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">姓名</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">角色</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">请选择角色</option>
                            <option value="admin">管理员</option>
                            <option value="user">普通用户</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">注册用户</button>
                    <button type="button" class="btn btn-secondary mt-3" onclick="history.back();">返回上一页</button>
                    <a href="upload_users.php" class="btn btn-secondary mt-3" style="background-color: red;">上传用户</a>
                </form>
            </div>
        </div>
    </div>

    <!-- 引入脚本 -->
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>