<?php
session_start();
require 'check_login.php';

// 检查当前用户是否为管理员
if ($_SESSION['user']['role'] !== 'admin') {
    // 非管理员用户重定向到首页或显示错误信息
    header('Location: index.php');
    exit;
}
require 'config.php'; // 包含数据库连接配置

// 处理删除用户请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_selected'])) {
        $deleteUserIds = $_POST['user_ids'] ?? [];
        if (empty($deleteUserIds)) {
            $message = "请选择要删除的用户。";
            $messageType = 'warning';
        } else {
            // 移除当前登录用户的ID，防止删除自己
            $deleteUserIds = array_diff($deleteUserIds, [$_SESSION['user']['id']]);
            if (empty($deleteUserIds)) {
                $message = "您不能删除当前登录的账号。";
                $messageType = 'danger';
            } else {
                // 使用事务处理批量删除
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    foreach ($deleteUserIds as $deleteUserId) {
                        $stmt->execute([$deleteUserId]);
                    }
                    $pdo->commit();
                    $message = "选中的用户已成功删除。";
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $message = "批量删除用户失败: " . $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }
    }
}

// 如果通过GET请求删除单个用户
if (isset($_GET['delete'])) {
    $deleteUserId = intval($_GET['delete']);
    if ($deleteUserId === $_SESSION['user']['id']) {
        $message = "您不能删除当前登录的账号。";
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        try {
            $stmt->execute([$deleteUserId]);
            $message = "用户已成功删除。";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = "删除用户失败: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// 获取所有用户
$stmt = $pdo->prepare('SELECT id, username, name, role FROM users');
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="/static/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/static/favicon.svg" />
<link rel="shortcut icon" href="/static/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/static/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="牛图图传输" />
<link rel="manifest" href="/static/site.webmanifest" />
    <title>用户管理</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 管理页面 Light Theme 样式 */
        body.light-theme .username {
            color: #0d6efd; /* 蓝色 */
            font-weight: bold;
        }
        
        body.light-theme .name {
            color: #198754; /* 绿色 */
            font-weight: bold;
        }
        
        body.light-theme .role {
            color: #fd7e14; /* 橙色 */
            font-weight: bold;
        }
        
        /* 管理页面 Dark Theme 样式 */
        body.dark-theme .username {
            color: #5bc0de; /* 浅蓝色 */
            font-weight: bold;
        }
        
        body.dark-theme .name {
            color: #71c671; /* 浅绿色 */
            font-weight: bold;
        }
        
        body.dark-theme .role {
            color: #f0ad4e; /* 浅橙色 */
            font-weight: bold;
        }
        

        html.dark-theme {
            background-color: #212529;
        }
        
        body.dark-theme {
            background-color: #212529;
        }

        .dark-theme .container {
            background-color: #212529;
            color: #f8f9fa;
        }
        
        .light-theme .table thead th {
            background-color:rgb(207, 207, 207);
            color: #000;
        }
        
        .dark-theme .table thead th {
            background-color:rgb(91, 63, 63);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center">用户管理</h1>
        <!-- 显示欢迎信息和退出按钮 -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span>欢迎，<?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="history.back();">返回上一页</button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">返回首页</a>
                <a href="change_password.php" class="btn btn-sm btn-outline-secondary">修改密码</a>
                <a href="logout.php" class="btn btn-sm btn-outline-secondary">退出登录</a>
            </div>
        </div>

        <!-- 显示消息 -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 添加和删除用户按钮 -->
        <div class="mb-3 d-flex justify-content-between">
            <a href="register.php" class="btn btn-success">添加新用户</a>
            <button type="submit" form="delete-form" name="delete_selected" class="btn btn-danger" onclick="return confirm('确定要删除选中的用户吗？');">删除选中用户</button>
        </div>

        <!-- 用户列表表格 -->
        <form id="delete-form" method="POST" action="admin.php">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-dark">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>姓名</th>
                            <th>角色</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?php if ($user['id'] !== $_SESSION['user']['id']): ?>
                                            <input type="checkbox" name="user_ids[]" value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="role">管理员</span>
                                        <?php else: ?>
                                            <span class="role">用户</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">编辑</a>
                                        <?php if ($user['id'] !== $_SESSION['user']['id']): ?>
                                            <a href="admin.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除用户 <?php echo htmlspecialchars($user['username']); ?> 吗？');">删除</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">暂无用户数据。</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
        <script>
    function setThemeBasedOnTime() {
        const hour = new Date().getHours();
        const body = document.body;
        const html = document.documentElement;
        const tables = document.querySelectorAll('.table-dark'); // 选择所有具有 table-dark 类的表格
        const announcement = document.querySelector('.announcement'); // 公告区域

        if (hour >= 6 && hour < 18) {
            body.classList.add('light-theme');
            body.classList.remove('dark-theme');
            html.classList.add('light-theme');
            html.classList.remove('dark-theme');
            tables.forEach(table => table.classList.remove('table-dark'));
            if (announcement) {
                announcement.classList.remove('bg-dark', 'text-white');
                announcement.classList.add('bg-light', 'text-dark');
            }
        } else {
            body.classList.add('dark-theme');
            body.classList.remove('light-theme');
            html.classList.add('dark-theme');
            html.classList.remove('light-theme');
            tables.forEach(table => table.classList.add('table-dark'));
            if (announcement) {
                announcement.classList.remove('bg-light', 'text-dark');
                announcement.classList.add('bg-dark', 'text-white');
            }
        }
    }
        
            // 设置主题
            setThemeBasedOnTime();
        
            // 每小时检查一次以更新主题
            setInterval(setThemeBasedOnTime, 60 * 60 * 1000);
        </script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // 全选/取消全选功能
        document.getElementById('select-all').addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
            checkboxes.forEach(cb => cb.checked = e.target.checked);
        });
    </script>
</body>
</html>