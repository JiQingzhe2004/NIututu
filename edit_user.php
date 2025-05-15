<?php
// 确保已启动会话
session_start();
require 'check_login.php';
require 'config.php';

// 获取当前用户信息
$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? 0;
$userRole = strtolower($user['role'] ?? ''); // 转换为小写

// 初始化变量
$errors = [];
$success = '';

// 仅管理员可以编辑角色
$isAdminEditing = false;
$existingUsername = '';
$existingName = '';
$existingRole = '';

// 添加调试信息
error_log("当前用户ID: " . $userId);
error_log("当前用户角色: " . $userRole);

if (isset($_GET['id'])) {
    $editUserId = intval($_GET['id']);
    error_log("编辑的用户ID: " . $editUserId);
    if ($userRole === 'admin') {
        $isAdminEditing = true;
        try {
            $stmt = $pdo->prepare('SELECT username, name, role FROM users WHERE id = ?');
            $stmt->execute([$editUserId]);
            $editUserData = $stmt->fetch();

            if (!$editUserData) {
                $errors[] = '用户不存在。';
                error_log("用户ID {$editUserId} 不存在。");
            } else {
                $existingUsername = htmlspecialchars($editUserData['username']);
                $existingName = htmlspecialchars($editUserData['name']);
                $existingRole = htmlspecialchars($editUserData['role']);
                error_log("获取到的用户名: " . $existingUsername);
                error_log("获取到的姓名: " . $existingName);
                error_log("获取到的角色: " . $existingRole);
            }
        } catch (PDOException $e) {
            $errors[] = '数据库错误: ' . htmlspecialchars($e->getMessage());
            error_log("数据库错误: " . $e->getMessage());
        }
    } else {
        $errors[] = '无权限编辑其他用户。';
        error_log("用户ID {$userId} 没有权限编辑其他用户。");
    }
} else {
    // 编辑当前用户信息
    try {
        $stmt = $pdo->prepare('SELECT username, name FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();

        if ($userData) {
            $existingUsername = htmlspecialchars($userData['username']);
            $existingName = htmlspecialchars($userData['name']);
            error_log("获取当前用户用户名: " . $existingUsername);
            error_log("获取当前用户姓名: " . $existingName);
        } else {
            $errors[] = '用户不存在。';
            error_log("当前用户ID {$userId} 不存在。");
        }
    } catch (PDOException $e) {
        $errors[] = '数据库错误: ' . htmlspecialchars($e->getMessage());
        error_log("数据库错误: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理表单提交

    // 获取并验证输入
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    // 验证用户名
    if (empty($username)) {
        $errors[] = '用户名不能为空。';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = '用户名长度应在3到50个字符之间。';
    }

    // 验证姓名
    if (empty($name)) {
        $errors[] = '姓名不能为空。';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = '姓名长度应在2到100个字符之间。';
    }

    // 验证密码（如果用户要修改密码）
    $updatePassword = false;
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = '密码长度应至少为6个字符。';
        } elseif ($password !== $confirm_password) {
            $errors[] = '密码和确认密码不匹配。';
        } else {
            $updatePassword = true;
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    // 验证角色（仅管理员）
    if ($isAdminEditing) {
        $allowedRoles = ['admin', 'user']; // 根据需要调整角色
        if (!in_array($role, $allowedRoles)) {
            $errors[] = '无效的角色选择。';
        }
    }

    // 检查是否有任何更改
    $hasChanges = false;

    if ($username !== $existingUsername) {
        $hasChanges = true;
    }

    if ($name !== $existingName) {
        $hasChanges = true;
    }

    if ($updatePassword) {
        $hasChanges = true;
    }

    if ($isAdminEditing && isset($role) && $role !== $existingRole) {
        $hasChanges = true;
    }

    if (!$hasChanges) {
        $success = '用户信息并未更改。';
        error_log("用户信息未发生变化。");
    } else {
        // 如果有错误，不执行更新
        if (empty($errors)) {
            try {
                // 检查用户名是否已被其他用户使用
                if ($isAdminEditing) {
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                    $stmt->execute([$username, $editUserId]);
                    $currentId = $editUserId;
                } else {
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                    $stmt->execute([$username, $userId]);
                    $currentId = $userId;
                }

                if ($stmt->fetch()) {
                    $errors[] = '该用户名已被其他用户使用。';
                    error_log("用户名 '{$username}' 已被其他用户使用。");
                } else {
                    // 准备更新语句
                    if ($updatePassword && $isAdminEditing) {
                        $stmt = $pdo->prepare('UPDATE users SET username = ?, name = ?, password = ?, role = ? WHERE id = ?');
                        $stmt->execute([$username, $name, $hashedPassword, $role, $editUserId]);
                        error_log("用户ID {$editUserId} 的用户名、姓名、密码和角色已更新。");
                    } elseif ($updatePassword) {
                        $stmt = $pdo->prepare('UPDATE users SET username = ?, name = ?, password = ? WHERE id = ?');
                        $stmt->execute([$username, $name, $hashedPassword, $currentId]);
                        error_log("用户ID {$currentId} 的用户名、姓名和密码已更新。");
                    } elseif ($isAdminEditing) {
                        $stmt = $pdo->prepare('UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?');
                        $stmt->execute([$username, $name, $role, $editUserId]);
                        error_log("用户ID {$editUserId} 的用户名、姓名和角色已更新。");
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET username = ?, name = ? WHERE id = ?');
                        $stmt->execute([$username, $name, $currentId]);
                        error_log("用户ID {$currentId} 的用户名和姓名已更新。");
                    }

                    // 更新会话中的用户信息（仅当前用户）
                    if (!$isAdminEditing || $currentId === $userId) {
                        $_SESSION['user']['username'] = $username;
                        $_SESSION['user']['name'] = $name;
                        error_log("会话中用户信息已更新为用户名: {$username}, 姓名: {$name}");
                    }

                    $success = '用户信息已成功更新。';
                }
            } catch (PDOException $e) {
                $errors[] = '数据库错误: ' . htmlspecialchars($e->getMessage());
                error_log("数据库错误: " . $e->getMessage());
            }
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
    <title>编辑用户信息</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // 自动关闭警告消息框
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    // 使用 Bootstrap 的关闭方法
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 3000); // 3秒后关闭
            });
        });
    </script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">编辑用户信息</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($errors) && ($isAdminEditing || !$isAdminEditing)): ?>
            <form action="edit_user.php<?php echo $isAdminEditing ? '?id=' . urlencode($editUserId) : ''; ?>" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">用户名:</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? $existingUsername); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">姓名:</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? $existingName); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">新密码 (留空则不更改):</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">确认新密码:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password">
                </div>
                
                <?php
                    // 确定当前角色
                    $currentRole = isset($role) && !empty($role) ? $role : $existingRole;
                    error_log("当前角色: " . $currentRole);
                ?>

                <?php if ($isAdminEditing): ?>
                    <div class="mb-3">
                        <label for="role" class="form-label">角色:</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">选择角色</option>
                            <option value="admin" <?php echo ($currentRole === 'admin') ? 'selected' : ''; ?>>管理员</option>
                            <option value="user" <?php echo ($currentRole === 'user') ? 'selected' : ''; ?>>用户</option>
                            <!-- 添加更多角色选项 -->
                        </select>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">保存更改</button>
                <button type="button" class="btn btn-secondary" onclick="history.back();">返回</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>