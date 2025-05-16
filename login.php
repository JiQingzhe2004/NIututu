<?php
// filepath: /f:/JiQingzhe/文件传输/login.php

// 设置时区为上海
date_default_timezone_set('Asia/Shanghai');

// 定义会话生命周期为1个月（2592000秒）
$cookie_lifetime = 30 * 24 * 60 * 60; // 30天

// 设置会话Cookie参数
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

// 启动会话
session_start();

require 'config.php'; // 包含数据库连接配置

// 获取最新的登录页面公告内容
$stmt = $pdo->query('SELECT login_content FROM announcements ORDER BY created_at DESC LIMIT 1');
$loginAnnouncement = $stmt->fetchColumn();

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 查询用户
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // 登录成功，将用户信息存储到会话中
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role'] // 确保包含角色信息
        ];

        // 跳转到文件管理页面
        header('Location: index.php');
        exit();
    } else {
        $error = '用户名或密码错误';
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
    <style>
        /* 调整公告的z-index以确保在最上层 */
        .announcement {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1050; /* Bootstrap 的z-index警告框是1040 */
            border-radius: 0;
        }
        body {
            padding-top: 80px; /* 为公告预留空间 */
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.5s, color 0.5s;
        }
        /* 登录页面 Light Theme 样式 */
        body.light-theme .btn {
            background-color: #0d6efd;
            color: #ffffff;
            width: 100%;
        }
        
        body.light-theme .announcement {
            color: #212529;
        }
        
        body.light-theme body {
            padding-top: 80px; /* 为公告预留空间 */
            background-color: #ffffff;
            color: #212529;
        }
        
        body.light-theme .login-container {
            margin-top: 40px;
            background-color: #ffffff;
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
        
        /* 登录页面 Dark Theme 样式 */
        body.dark-theme .btn {
            background-color: #375a7f;
            color: #f8f9fa;
            width: 100%;
        }
        body.dark-theme .announcement {
            border: none;
        }
        
        body.dark-theme body {
            padding-top: 80px; /* 为公告预留空间 */
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

        html.liget-theme {
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
        <link rel="stylesheet" href="css/animate.min.css"/>
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500"><g id="background-simple"><path d="M165.85,147.91c-8.23,4.9-16.79,9.36-25.13,14.24-38.39,22.46-70,52.59-73.87,98.74C64,294.68,74.66,334,104.38,353.55a68.9,68.9,0,0,0,24.12,9.9c12.13,2.47,25,1.5,37.35.79Z" style="fill:#540000"></path><path d="M344.07,99.85H224.88a141,141,0,0,0-21.49,16.47H367.2A164.61,164.61,0,0,0,344.07,99.85Z" style="fill:#540000"></path><path d="M338.5,96.75a136.64,136.64,0,0,0-27.16-11c-28.51-8-56.74-2.67-81.17,11Z" style="fill:#540000"></path><path d="M408.37,164.53a208.72,208.72,0,0,0-37.61-45.11H260.29V378.74a177,177,0,0,0,25.26,6.4c56.33,9,119.35-11.05,141.19-67.81C446.33,266.45,436.59,209.83,408.37,164.53Z" style="fill:#540000"></path><path d="M253.52,119.42H200.14q-3.31,3.27-6.44,6.73a104.36,104.36,0,0,1-21.08,17.55V363.9A212.15,212.15,0,0,1,230,369.59c8,1.91,15.72,4.45,23.48,7Z" style="fill:#540000"></path><g style="opacity:0.8"><path d="M165.85,147.91c-8.23,4.9-16.79,9.36-25.13,14.24-38.39,22.46-70,52.59-73.87,98.74C64,294.68,74.66,334,104.38,353.55a68.9,68.9,0,0,0,24.12,9.9c12.13,2.47,25,1.5,37.35.79Z" style="fill:#fff"></path><path d="M344.07,99.85H224.88a141,141,0,0,0-21.49,16.47H367.2A164.61,164.61,0,0,0,344.07,99.85Z" style="fill:#fff"></path><path d="M338.5,96.75a136.64,136.64,0,0,0-27.16-11c-28.51-8-56.74-2.67-81.17,11Z" style="fill:#fff"></path><path d="M408.37,164.53a208.72,208.72,0,0,0-37.61-45.11H260.29V378.74a177,177,0,0,0,25.26,6.4c56.33,9,119.35-11.05,141.19-67.81C446.33,266.45,436.59,209.83,408.37,164.53Z" style="fill:#fff"></path><path d="M253.52,119.42H200.14q-3.31,3.27-6.44,6.73a104.36,104.36,0,0,1-21.08,17.55V363.9A212.15,212.15,0,0,1,230,369.59c8,1.91,15.72,4.45,23.48,7Z" style="fill:#fff"></path></g></g><g id="Folders"><polygon points="214.32 128.27 199.77 128.27 199.77 131.44 199.77 150.1 199.77 153.28 230.63 153.28 230.63 131.44 215.35 131.44 214.32 128.27" style="fill:#540000"></polygon><polygon points="214.32 175.86 199.77 175.86 199.77 179.03 199.77 197.69 199.77 200.87 230.63 200.87 230.63 179.03 215.35 179.03 214.32 175.86" style="fill:#540000"></polygon><polygon points="214.32 223.45 199.77 223.45 199.77 226.62 199.77 245.28 199.77 248.46 230.63 248.46 230.63 226.62 215.35 226.62 214.32 223.45" style="fill:#540000"></polygon><polygon points="214.32 271.04 199.77 271.04 199.77 274.21 199.77 292.87 199.77 296.05 230.63 296.05 230.63 274.21 215.35 274.21 214.32 271.04" style="fill:#540000"></polygon><polygon points="214.32 318.63 199.77 318.63 199.77 321.81 199.77 340.46 199.77 343.64 230.63 343.64 230.63 321.81 215.35 321.81 214.32 318.63" style="fill:#540000"></polygon><polygon points="307.6 128.27 293.05 128.27 293.05 131.44 293.05 150.1 293.05 153.28 323.92 153.28 323.92 131.44 308.64 131.44 307.6 128.27" style="fill:#540000"></polygon><polygon points="368.13 131.44 367.09 128.27 352.54 128.27 352.54 131.44 352.54 150.1 352.54 153.28 383.4 153.28 383.4 131.44 368.13 131.44" style="fill:#540000"></polygon><polygon points="307.6 175.86 293.05 175.86 293.05 179.03 293.05 197.69 293.05 200.87 323.92 200.87 323.92 179.03 308.64 179.03 307.6 175.86" style="fill:#540000"></polygon><polygon points="367.09 223.45 352.54 223.45 352.54 226.62 352.54 245.28 352.54 248.46 383.4 248.46 383.4 226.62 368.13 226.62 367.09 223.45" style="fill:#540000"></polygon><polygon points="367.09 175.86 352.54 175.86 352.54 179.03 352.54 197.69 352.54 200.87 383.4 200.87 383.4 179.03 368.13 179.03 367.09 175.86" style="fill:#540000"></polygon><polygon points="307.6 223.45 293.05 223.45 293.05 226.62 293.05 245.28 293.05 248.46 323.92 248.46 323.92 226.62 308.64 226.62 307.6 223.45" style="fill:#540000"></polygon><polygon points="307.6 271.04 293.05 271.04 293.05 274.21 293.05 292.87 293.05 296.05 323.92 296.05 323.92 274.21 308.64 274.21 307.6 271.04" style="fill:#540000"></polygon><polygon points="367.09 271.04 352.54 271.04 352.54 274.21 352.54 292.87 352.54 296.05 383.4 296.05 383.4 274.21 368.13 274.21 367.09 271.04" style="fill:#540000"></polygon><polygon points="307.6 318.63 293.05 318.63 293.05 321.81 293.05 340.46 293.05 343.64 323.92 343.64 323.92 321.81 308.64 321.81 307.6 318.63" style="fill:#540000"></polygon><polygon points="367.09 318.63 352.54 318.63 352.54 321.81 352.54 340.46 352.54 343.64 383.4 343.64 383.4 321.81 368.13 321.81 367.09 318.63" style="fill:#540000"></polygon><g style="opacity:0.5"><polygon points="214.32 128.27 199.77 128.27 199.77 131.44 199.77 150.1 199.77 153.28 230.63 153.28 230.63 131.44 215.35 131.44 214.32 128.27" style="fill:#fff"></polygon><polygon points="214.32 175.86 199.77 175.86 199.77 179.03 199.77 197.69 199.77 200.87 230.63 200.87 230.63 179.03 215.35 179.03 214.32 175.86" style="fill:#fff"></polygon><polygon points="214.32 223.45 199.77 223.45 199.77 226.62 199.77 245.28 199.77 248.46 230.63 248.46 230.63 226.62 215.35 226.62 214.32 223.45" style="fill:#fff"></polygon><polygon points="214.32 271.04 199.77 271.04 199.77 274.21 199.77 292.87 199.77 296.05 230.63 296.05 230.63 274.21 215.35 274.21 214.32 271.04" style="fill:#fff"></polygon><polygon points="214.32 318.63 199.77 318.63 199.77 321.81 199.77 340.46 199.77 343.64 230.63 343.64 230.63 321.81 215.35 321.81 214.32 318.63" style="fill:#fff"></polygon><polygon points="307.6 128.27 293.05 128.27 293.05 131.44 293.05 150.1 293.05 153.28 323.92 153.28 323.92 131.44 308.64 131.44 307.6 128.27" style="fill:#fff"></polygon><polygon points="368.13 131.44 367.09 128.27 352.54 128.27 352.54 131.44 352.54 150.1 352.54 153.28 383.4 153.28 383.4 131.44 368.13 131.44" style="fill:#fff"></polygon><polygon points="307.6 175.86 293.05 175.86 293.05 179.03 293.05 197.69 293.05 200.87 323.92 200.87 323.92 179.03 308.64 179.03 307.6 175.86" style="fill:#fff"></polygon><polygon points="367.09 223.45 352.54 223.45 352.54 226.62 352.54 245.28 352.54 248.46 383.4 248.46 383.4 226.62 368.13 226.62 367.09 223.45" style="fill:#fff"></polygon><polygon points="367.09 175.86 352.54 175.86 352.54 179.03 352.54 197.69 352.54 200.87 383.4 200.87 383.4 179.03 368.13 179.03 367.09 175.86" style="fill:#fff"></polygon><polygon points="307.6 223.45 293.05 223.45 293.05 226.62 293.05 245.28 293.05 248.46 323.92 248.46 323.92 226.62 308.64 226.62 307.6 223.45" style="fill:#fff"></polygon><polygon points="307.6 271.04 293.05 271.04 293.05 274.21 293.05 292.87 293.05 296.05 323.92 296.05 323.92 274.21 308.64 274.21 307.6 271.04" style="fill:#fff"></polygon><polygon points="367.09 271.04 352.54 271.04 352.54 274.21 352.54 292.87 352.54 296.05 383.4 296.05 383.4 274.21 368.13 274.21 367.09 271.04" style="fill:#fff"></polygon><polygon points="307.6 318.63 293.05 318.63 293.05 321.81 293.05 340.46 293.05 343.64 323.92 343.64 323.92 321.81 308.64 321.81 307.6 318.63" style="fill:#fff"></polygon><polygon points="367.09 318.63 352.54 318.63 352.54 321.81 352.54 340.46 352.54 343.64 383.4 343.64 383.4 321.81 368.13 321.81 367.09 318.63" style="fill:#fff"></polygon></g></g><g id="Shadow"><ellipse cx="216.5" cy="421.5" rx="50.5" ry="10.5" style="fill:#540000"></ellipse><ellipse cx="216.5" cy="421.5" rx="50.5" ry="10.5" style="fill:#fff;opacity:0.5"></ellipse></g><g id="Character"><path d="M253.92,198.54s-.2-5.35-.2-10.91,2.88-6.79,4.73-6.79a12.78,12.78,0,0,1,2.68.2s1-8.85,7.62-10.29,11.53,3.91,13.79,2.06-5.35-1.65-2.06-4.32,12.77-2.89,13.8,7-5.77,14.41-9.06,15.23-7.83-5.15-12.15-4.94-3.7,5.15-6.38,7.21-4.74,1-4.74,3.7-1.23,4.12-4.53,4.32S253.92,198.54,253.92,198.54Z" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M284.43,182.62s3.21,1.48,7.4-1.23,1.47-8.87.74-9.12" style="fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round"></path><path d="M265.7,181.14s1.48-6.41,6.65-6.16,4.44,4.93,8.88,6.41" style="fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round"></path><path d="M263,185.16s-3-3-5.66-2.29-1.48,5.42-1.23,6.9-1.73,5.67-1.73,5.67" style="fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round"></path><polygon points="193.47 202.05 200.91 228.61 212.82 228.11 205.87 201.31 193.47 202.05" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></polygon><path d="M213.07,251.69l-3.23,1.73s-4-12.41-5.7-17.12-2.24-6.7-2.24-6.7l.08,0a4.58,4.58,0,0,0,2.17-5l-.57-2.53a1.58,1.58,0,0,0,2.44.7c.83-.54-.05-6-1.05-8s-4.31-.53-5.3,0a9.55,9.55,0,0,0-2,1.49l-.74-3s3.72-.25,4.47-.25,3.47-2.48,2.23-2.73-8.19-.25-9.93,1.24,1.24,7.45,1.74,8.69,3,13.9,4,17.37,3.48,29,5,31.27S217,260.62,217,260.62Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M198.52,218.05s3.49-3,6.63-2.26" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M199.57,221a8.13,8.13,0,0,1,5.75-2.44" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M239.51,219.75s-4.6,4.39-6.59,8.11-20.84,23.83-20.84,23.83l5.46,18.61,13.4-11.42s7.44-14.39,9.43-23.57S244.12,219.58,239.51,219.75Z" style="fill:#540000;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M280.69,224.69s-14.62-1.24-22.85-2.47-17.3-3.5-18.33-2.47-7.49,33.37-13.79,46.32c-8.34,17.15-14.08,26.24-14.08,26.24a33.57,33.57,0,0,1,18.16-1.67c10.29,1.85,26.31,12.79,26.31,12.79s7.7-16.57,15.93-33.24,12.56-41.59,12.56-43S281.92,224.48,280.69,224.69Z" style="fill:#540000;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M274.43,224.12c-5-.47-11.84-1.19-16.59-1.9-.7-.1-1.39-.22-2.09-.33a33.11,33.11,0,0,0-1.83,8.56c-.41,6,3.09,12.56,5.77,12.56s10.91-11.11,12.76-14.2A15.4,15.4,0,0,0,274.43,224.12Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M260.92,210.69s-2.05,10.5-3.7,14.82.2,11.94,2.26,12.15,10.3-10.5,10.3-10.5l2.88-11.94S262.16,212.34,260.92,210.69Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M282.56,225.13a7.15,7.15,0,0,1,5.21,3.72c2,3.48,21.84,36.48,21.84,36.48l-13.41,3.48-18.61-16.63s-4.46-5.71.5-15.88" style="fill:#540000;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M243.84,256.4s-4.71,4.22-5,5.21,2.48,5,3.23,5.21,1-2,.74-3-.74-2.49-.74-2.49l2.72-3.22S245.83,256.9,243.84,256.4Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M304.39,257.39A24.49,24.49,0,0,0,295,255.9c-5.21.25-16.13,3-23.08,3.23s-6.7.5-10.42-.5-11.41-6.94-13.15-6.94-6.95,8.68-6.2,9.67,6.94,5.71,8.68,6,5.46-2,5.46-2,18.61,2.73,27.3,3.73,19.36,2.23,23.57-.5S310.1,259.87,304.39,257.39Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><line x1="248.31" y1="254.66" x2="243.59" y2="262.11" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></line><line x1="251.78" y1="257.39" x2="246.32" y2="264.84" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></line><line x1="254.27" y1="260.62" x2="249.55" y2="266.33" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></line><polyline points="256.25 265.33 255.75 263.6 253.27 265.09" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></polyline><path d="M285.63,197.72s2.06-1.44,2.47,2.47-1,6.59-2.06,7.21-2.26-.42-2.26-.42Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M256,198.34s-3.29-4.33-5.14-.62-.42,8.44,2.06,10.29,4.94-.82,4.94-.82,5.35,10.91,9.88,11.32,13-2.88,16.06-9.26,2.67-16.68,3.08-20.59-3.91-8.85-11.94-8.85A15.18,15.18,0,0,0,263,185.16a2.24,2.24,0,0,1,.62,3.5c-1.65,2.27-3.7,3.71-4.32,5.15s0,2.88-.83,4.12S256,198.34,256,198.34Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M274.65,191.77c-.18,1.15-.76,2-1.29,1.94s-.83-1.09-.65-2.24.76-2,1.29-1.94S274.83,190.61,274.65,191.77Z" style="fill:#263238"></path><path d="M282.88,194c-.18,1.16-.76,2-1.29,1.94s-.83-1.08-.65-2.24.76-2,1.29-1.94S283.06,192.88,282.88,194Z" style="fill:#263238"></path><path d="M270.58,186.94s2.57-3,5.89-1.36" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M281.46,188s3.77-1.36,4.53,3.78" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M279.8,191.92s-1.36,2.57-1.06,3.32,4.08,1.66,2.87,2.72-4.23,2.27-5.14,1.66" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M268.47,200.07s1.06,3.63,5.89,4.38" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M213.42,290.64s-5.52,5.51-13.45,13.79S171.69,340.64,173.76,350s53.11,23.45,53.11,23.45l5.51-3.79s-30.69-22.76-32.76-27.94,24.49-22.07,24.49-22.07l29.66-19S240.49,278.22,213.42,290.64Z" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M250.66,276.84s6.55,11,5.86,24.14-5.86,24.83-18.62,24.49-11.72-19.66-7.58-29.32S235.14,273.74,250.66,276.84Z" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M232,283.39s-7.24,33.45-10.69,40-9.66,22.41-9.66,22.41l7.59,2.07s10.69-14.83,23.45-30.35,10.69-36.21,7.93-40.69S237.21,272.7,232,283.39Z" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M252.56,290s-5.69,23.79-13.45,33.62" style="fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round"></path><path d="M226.87,373.4s1,5.52-1,9.31-6.9,11.73-3.45,12.07,8.28-4.83,11.73-9,3.79-7.94,6.21-11-7.94-5.17-7.94-5.17L227.9,372Z" style="fill:#540000;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M240.32,374.78c.82-1.06.15-2-1.09-2.78a84.28,84.28,0,0,1-8.74,14.85c-3.61,4.69-6.94,6-9.29,6.15-.07,1,.23,1.69,1.18,1.78,3.45.35,8.28-4.83,11.73-9S237.9,377.88,240.32,374.78Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M211.69,345.81a32.11,32.11,0,0,1-12.07-4.14c-5.51-3.44-6.55-3.44-6.55,2.76s7.59,9.31,11.73,12.42,6.55,4.83,9.65,4.48,4.83-13.45,4.83-13.45S217.56,344.43,211.69,345.81Z" style="fill:#540000;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M193.07,344.43c0,6.21,7.59,9.31,11.73,12.42s6.51,4.79,9.57,4.48c-7.23-4.6-8-12-14.75-19.66C194.11,338.23,193.07,338.23,193.07,344.43Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M209.8,353.4a72,72,0,0,0-6.12-9.69,25.29,25.29,0,0,1-4.06-2c-5.51-3.44-6.55-3.44-6.55,2.76s7.59,9.31,11.73,12.42,6.55,4.83,9.65,4.48c.76-.08,1.43-.93,2-2.16A10.05,10.05,0,0,1,209.8,353.4Z" style="fill:none;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path></g><g id="Files"><path d="M106.91,236.65c0-.5.09-1,.14-1.49" style="fill:none;stroke:#263238;stroke-miterlimit:10"></path><path d="M107.39,232.19a146.19,146.19,0,0,1,290-.9" style="fill:none;stroke:#263238;stroke-miterlimit:10;stroke-dasharray:2.9833900928497314,2.9833900928497314"></path><path d="M397.6,232.78c.06.49.12,1,.17,1.49" style="fill:none;stroke:#263238;stroke-miterlimit:10"></path><path d="M126.69,135.28l-12.94,40.35L165,183.39l8.8-37.25S142.72,144.07,126.69,135.28Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M180,109.41s24.31,2.59,41.9-9.31c0,0,21.72,17.07,30,18.63,0,0-12.93,14-26.38,18.1C225.49,136.83,192.38,121.83,180,109.41Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M180,109.41A33,33,0,0,1,192,103.67a33,33,0,0,0,8.32,20S185.29,115.56,180,109.41Z" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M276,126.4s-.86,2.59,4.31,6.9c2.35,2,7.65,2.74,7.65,2.74l13.9-17.4S279.05,121.74,276,126.4Z" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M276,126.4a66.82,66.82,0,0,1,23.79-3.62,28.51,28.51,0,0,1,20.18,9.83l15-18.63S322,102.09,307.5,102.6,277,122.26,276,126.4Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><path d="M363.15,136.35s-9.31,24.49-29.65,27.94c0,0,20.34,24.14,31.72,26.9,0,0,23.11-10.35,29.32-21.38C394.54,169.81,374.19,158.08,363.15,136.35Z" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></path><polygon points="141.17 261.09 150.21 222.78 89.53 222.78 88.24 261.09 141.17 261.09" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></polygon><polygon points="96.34 208.59 96.15 252.59 141.55 257.2 144.97 210.42 96.34 208.59" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></polygon><polygon points="81.35 215.9 88.24 261.09 141.17 261.09 137.73 220.2 109.33 220.2 106.74 215.04 81.35 215.9" style="fill:#540000;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></polygon><polygon points="369.11 261.09 360.07 222.78 420.75 222.78 422.04 261.09 369.11 261.09" style="fill:#263238;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></polygon><polygon points="413.94 208.59 414.13 252.59 368.73 257.2 365.31 210.42 413.94 208.59" style="fill:#fff;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></polygon><polygon points="428.93 215.9 422.04 261.09 369.11 261.09 372.55 220.2 400.96 220.2 403.54 215.04 428.93 215.9" style="fill:#540000;stroke:#263238;stroke-linecap:round;stroke-linejoin:round"></polygon></g></svg>
                
            </div>
            <!-- 标题部分 -->
            <!--<div class="col-md-6 d-md-none">
                <h2 class="text-center d-md-none">文件管理系统！</h2>
            </div>-->
            <!-- 登录表单部分 -->
            <div class="col-md-6">
                <h1 class="text-center mb-4"> <img src="static/tutu.png" alt="logo" style="width: 20%;"> 登录文件传输</h1>
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="自己的工号" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="默认为123qwe" required>
                    </div>
                    <button type="submit" class="btn btn-primary">登录</button>
                </form>
            </div>
            <!-- 版权信息 -->
            <div class="text-center">
                <small>&copy; 2024-(<?php echo date("Y-m-d"); ?>) 吉庆喆.文件管理系统. 版权所有. V 3.5.6版本.</small>
            </div>
        </div>
    </div>

    <!-- 引入脚本 -->
    <script>
        function setThemeBasedOnTime() {
            const hour = new Date().getHours();
            const body = document.body;
            const html = document.documentElement;
            const table = document.getElementById('file-table'); // 确保表格有 id="file-table"
            const announcement = document.querySelector('.announcement'); // 公告区域
    
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
        
            // 规则：如果用户名包含“admin”，显示提示
            if (username.toLowerCase().includes('admin')) {
                adminAlert.textContent = '请注意：您正在使用管理员账户！'; // 设置提示内容
                adminAlert.style.display = 'block'; // 显示提示
            } else {
                adminAlert.style.display = 'none'; // 隐藏提示
            }
        });

        // 设置主题
        setThemeBasedOnTime();

        // 每小时检查一次以更新主题
        setInterval(setThemeBasedOnTime, 60 * 60 * 1000);
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>