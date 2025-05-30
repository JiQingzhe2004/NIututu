<?php
// login.php

// 设置时区为上海
date_default_timezone_set('Asia/Shanghai');

// 统一设置"记住我"天数
$remember_days = 30;
$remember_seconds = $remember_days * 24 * 60 * 60;

// 定义会话生命周期为1个月
$cookie_lifetime = $remember_seconds;

// 设置会话Cookie参数（与config.php中的设置保持一致）
session_set_cookie_params([
    'lifetime' => $cookie_lifetime,
    'path' => '/',
    'domain' => '', // 根据需要设置，例如 '.yourdomain.com'
    'secure' => isset($_SERVER['HTTPS']), // 如果使用HTTPS，则设置为true
    'httponly' => true, // 防止JavaScript访问会话Cookie
    'samesite' => 'Lax' // 或 'Strict'/'None'，根据需求选择
]);

// 设置服务器端会话数据的生命周期
ini_set('session.gc_maxlifetime', $cookie_lifetime);

// 启动会话（与config.php中的设置保持一致）
session_start([
    'cookie_lifetime' => $cookie_lifetime,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'sid_length' => 48,
    'sid_bits_per_character' => 6
]);

require 'config.php'; // 包含数据库连接配置

// 创建审计日志函数
function logAction($pdo, $userId, $action, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $pdo->prepare('
        INSERT INTO audit_logs 
        (user_id, action, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $userId,
        $action,
        $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        $ip,
        $userAgent
    ]);
}

// 获取设备指纹
function getDeviceFingerprint() {
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    
    return md5($agent . $ip . $accept . $language);
}

// 获取最新的登录页面公告内容
$stmt = $pdo->query('SELECT login_content FROM announcements ORDER BY created_at DESC LIMIT 1');
$loginAnnouncement = $stmt->fetchColumn();

// 自动登录（多设备支持版记住我功能）
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $current_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $device_fingerprint = getDeviceFingerprint();
    
    // 查询token有效性（包含设备验证）
    $stmt = $pdo->prepare('
        SELECT u.*, t.token, t.expires_at, t.device_info 
        FROM users u
        JOIN remember_tokens t ON u.id = t.user_id
        WHERE t.token = ? 
        AND t.expires_at > NOW()
        AND JSON_EXTRACT(t.device_info, "$.fingerprint") = ?
        LIMIT 1
    ');
    $stmt->execute([$_COOKIE['remember_token'], $device_fingerprint]);
    $result = $stmt->fetch();
    
    if ($result) {
        // 验证通过，创建会话
        $_SESSION['user'] = [
            'id' => $result['id'],
            'username' => $result['username'],
            'name' => $result['name'],
            'role' => $result['role'],
            'current_device_fingerprint' => $device_fingerprint
        ];

        // 记录自动登录审计日志
        logAction($pdo, $result['id'], 'auto_login', [
            'username' => $result['username'],
            'token' => substr($_COOKIE['remember_token'], 0, 8).'...',
            'device' => json_decode($result['device_info'], true)
        ]);
        
        // 更新token最后使用时间和设备信息
        $stmt = $pdo->prepare('
            UPDATE remember_tokens 
            SET last_used = NOW(),
                device_info = JSON_SET(
                    device_info,
                    "$.last_ip", ?,
                    "$.last_agent", ?
                )
            WHERE token = ?
        ');
        $stmt->execute([$current_ip, $current_agent, $_COOKIE['remember_token']]);
        
        // 10%的几率更换token（增强安全性）
        if (rand(1, 10) === 1) {
            $new_token = bin2hex(random_bytes(32));
            $new_expires = date('Y-m-d H:i:s', time() + $remember_seconds);
            
            $stmt = $pdo->prepare('
                UPDATE remember_tokens 
                SET token = ?, 
                    expires_at = ?, 
                    device_info = JSON_SET(
                        device_info,
                        "$.fingerprint", ?,
                        "$.last_update", NOW()
                    )
                WHERE token = ?
            ');
            $stmt->execute([
                $new_token, 
                $new_expires,
                $device_fingerprint,
                $_COOKIE['remember_token']
            ]);
            
            setcookie('remember_token', $new_token, [
                'expires' => time() + $remember_seconds,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        header('Location: index');
        exit();
    } else {
        // 无效token则清除cookie
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 查询用户
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // 获取设备信息
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $device_fingerprint = getDeviceFingerprint();
        
        // 登录成功，创建会话
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role'],
            'current_device_fingerprint' => $device_fingerprint
        ];

        // 记录登录审计日志
        logAction($pdo, $user['id'], 'login', [
            'username' => $user['username'],
            'remember_me' => !empty($_POST['remember']),
            'device' => [
                'ip' => $ip_address,
                'agent' => substr($user_agent, 0, 100),
                'fingerprint' => $device_fingerprint
            ]
        ]);

        // 记住我功能
        if (!empty($_POST['remember'])) {
            $token = bin2hex(random_bytes(32));
            $token_expires = date('Y-m-d H:i:s', time() + $remember_seconds);
            
            // 设备详细信息
            $device_info = [
                'agent' => $user_agent,
                'ip' => $ip_address,
                'device' => $_POST['device_name'] ?? '未知设备',
                'fingerprint' => $device_fingerprint,
                'first_login' => date('Y-m-d H:i:s')
            ];
            
            // 为当前设备创建新token记录
            $stmt = $pdo->prepare('
                INSERT INTO remember_tokens 
                (user_id, token, expires_at, device_info, created_at, last_used)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ');
            $stmt->execute([
                $user['id'],
                $token,
                $token_expires,
                json_encode($device_info, JSON_UNESCAPED_UNICODE)
            ]);
            
            // 设置cookie
            setcookie('remember_token', $token, [
                'expires' => time() + $remember_seconds,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        header('Location: index');
        exit();
    } else {
        $error = '用户名或密码错误';
        // 记录失败的登录尝试
        logAction($pdo, 0, 'login_failed', [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
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
    <title>登录 - 文件管理系统</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/animate.min.css"/>
    <style>
        .announcement {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1050;
            border-radius: 0;
        }
        body {
            padding-top: 80px;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.5s, color 0.5s;
        }
        body.light-theme .btn {
            background-color: #0d6efd;
            color: #ffffff;
            width: 100%;
        }
        body.light-theme .announcement {
            color: #212529;
        }
        body.light-theme body {
            padding-top: 80px;
            background-color: #ffffff;
            color: #212529;
        }
        body.light-theme .login-container {
            margin-top: 40px;
            color: #212529;
            padding: 20px;
            border-radius: 5px;
        }
        body.light-theme .login-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        body.light-theme .copyright {
            text-align: center;
            margin-top: 20px;
            color: #212529;
        }
        body.dark-theme .btn {
            background-color: #375a7f;
            color: #f8f9fa;
            width: 100%;
        }
        body.dark-theme .announcement {
            border: none;
        }
        body.dark-theme body {
            padding-top: 80px;
            background-color: #212529;
            color: #f8f9fa;
        }
        body.dark-theme .login-container {
            margin-top: 40px;
            background-color: #212529;
            color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        body.dark-theme .login-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        body.dark-theme .copyright {
            text-align: center;
            margin-top: 20px;
            color: #f8f9fa;
        }
        html.light-theme {
            background-color: #f8f9fa;
        }
        html.dark-theme {
            background-color: #212529;
        }
    </style>
</head>
<body>
    <!-- 系统使用公告 -->
    <div class="alert alert-info alert-dismissible fade show text-center announcement animate__animated animate__fadeInDown" role="alert">
        <?php echo htmlspecialchars($loginAnnouncement); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
    </div>
    <script>
        document.querySelector('.btn-close').addEventListener('click', function() {
            var announcement = document.querySelector('.announcement');
            announcement.classList.remove('animate__fadeInDown');
            announcement.classList.add('animate__fadeOutUp');
            announcement.addEventListener('animationend', function() {
                announcement.style.display = 'none';
            });
        });
    </script>

    <div id="adminAlert" class="text-center mt-2" style="display: none; color: red; font-size: 14px;"></div>

    <div class="container py-4 login-container">
        <div class="row justify-content-center align-items-center">
            <!-- 图片部分 -->
            <div class="col-md-6 mb-4 mb-md-0 d-none d-md-block">
                <img src="static/zhanshi.svg" alt="展示" class="login-image">
            </div>
            <!-- 登录表单部分 -->
            <div class="col-md-6">
                <h1 class="text-center mb-4"> <img src="static/tutu.png" alt="logo" style="width: 20%;"> 登录文件传输</h1>
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="自己的工号"
                               required autofocus
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="默认为123qwe"
                               required
                               value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember"
                            <?php if (!empty($_POST['remember'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="remember">记住我（<?php echo $remember_days; ?>天免登录）</label>
                    </div>
                    <div id="deviceNameContainer" class="mb-3" style="display: none;">
                        <label for="device_name" class="form-label">设备名称（可选）</label>
                        <input type="text" class="form-control" id="device_name" name="device_name" placeholder="例如：我的手机">
                    </div>
                    <button type="submit" class="btn btn-primary">登录</button>
                </form>
            </div>
            <?php $app_version = require __DIR__ . '/version.php'; ?>
            <!-- 版权信息 -->
            <div class="text-center mt-4">
                <small style="color:#888;">
                    &copy; 2024-<?php echo date("Y"); ?>
                    吉庆喆.文件管理系统. 版权所有.
                    <a href="version_log" style="text-decoration:none;">
                        <span style="background: linear-gradient(90deg,#f093fb,#f5576c); color:#fff; border-radius: 4px; padding: 2px 8px; margin-left: 8px; cursor:pointer;">
                            V <?php echo $app_version; ?>
                        </span>
                    </a>
                    <span style="margin-left:8px; color:#5e3a22; font-weight:bold;">
                        <?php echo date("Y-m-d H:i"); ?>
                    </span>
                </small>
            </div>
        </div>
    </div>

    <!-- 引入脚本 -->
    <script>
        // 当记住我复选框状态变化时显示/隐藏设备名称输入框
        document.getElementById('remember').addEventListener('change', function() {
            document.getElementById('deviceNameContainer').style.display = this.checked ? 'block' : 'none';
        });

        function setThemeBasedOnTime() {
            const hour = new Date().getHours();
            const body = document.body;
            const html = document.documentElement;
            const table = document.getElementById('file-table');
            const announcement = document.querySelector('.announcement');
    
            if (hour >= 6 && hour < 17) {
                body.classList.add('light-theme');
                body.classList.remove('dark-theme');
                html.classList.add('light-theme');
                html.classList.remove('dark-theme');
                if (table) {
                    table.classList.remove('table-dark');
                }
                if (announcement) {
                    announcement.classList.remove('bg-dark', 'text-white');
                    announcement.classList.add('bg-light', 'text-dark');
                }
            } else {
                body.classList.add('dark-theme');
                body.classList.remove('light-theme');
                html.classList.add('dark-theme');
                html.classList.remove('light-theme');
                if (table) {
                    table.classList.add('table-dark');
                }
                if (announcement) {
                    announcement.classList.remove('bg-light', 'text-dark');
                    announcement.classList.add('bg-dark', 'text-white');
                }
            }
        }
        
        document.getElementById('username').addEventListener('input', function (event) {
            const username = event.target.value;
            const adminAlert = document.getElementById('adminAlert');
            if (username.toLowerCase().includes('admin')) {
                adminAlert.textContent = '请注意：您正在使用管理员账户！';
                adminAlert.style.display = 'block';
            } else {
                adminAlert.style.display = 'none';
            }
        });

        setThemeBasedOnTime();
        setInterval(setThemeBasedOnTime, 60 * 60 * 1000);

        // 登录失败后自动聚焦用户名
        <?php if (isset($error)) : ?>
        document.getElementById('username').focus();
        <?php endif; ?>
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>