<?php
session_start();
require 'check_login.php';
require 'config.php';

// 设置更新内容引导弹窗（关闭为 true，打开为 false）
// define('IS_DEV', true); // 关闭更新内容引导弹窗
define('IS_DEV', false); // 打开更新内容引导弹窗

// 获取最新的首页公告内容
$stmt = $pdo->query('SELECT index_content FROM announcements ORDER BY created_at DESC LIMIT 1');
$indexAnnouncement = $stmt->fetchColumn();

// 获取最新的更新内容和启用状态
$stmt = $pdo->query('SELECT update_content, update_enabled FROM announcements ORDER BY created_at DESC LIMIT 1');
$updateAnnouncement = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>文件管理系统</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 自定义样式 */
        .light-theme {
            --bg-color: #f8f9fa;
            --text-color: #212529;
            /* 添加其他变量 */
        }

        .dark-theme {
            --bg-color: #212529;
            --text-color: #f8f9fa;
            /* 添加其他变量 */
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            /*transition: background-color 0.5s, color 0.5s;*/
        }

        .btn-primary {
            background-color: var(--btn-primary-bg, #0d6efd);
            border-color: var(--btn-primary-border, #0d6efd);
        }

        .light-theme .btn-primary {
            --btn-primary-bg: #0d6efd;
            --btn-primary-border: #0d6efd;
        }

        .dark-theme .btn-primary {
            --btn-primary-bg: #375a7f;
            --btn-primary-border: #375a7f;
        }
        
        /* 美化滚动条 */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-color);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background-color: var(--text-color);
            border-radius: 10px;
            border: 2px solid var(--bg-color);
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }

        /* 文件预览区域样式 */

        /* Light Theme 下的文件预览样式 */
        body.light-theme #file-preview {
            background-color: #ffffff;
            color: #212529;
            padding: 20px;
            border-radius: 5px;
        }

        body.light-theme #file-preview h5 {
            color: #212529;
        }

        body.light-theme #preview-container .card {
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #dee2e6;
        }

        /* Dark Theme 下的文件预览样式 */
        body.dark-theme #file-preview {
            background-color: #343a40;
            color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }

        body.dark-theme #file-preview h5 {
            color: #f8f9fa;
        }

        body.dark-theme #preview-container .card {
            background-color: #495057;
            color: #f8f9fa;
            border: 1px solid #6c757d;
        }
        
        body.dark-theme .announcement {
            background-color: #495057;
            color: #f8f9fa;
            border: none;
        }
        /* 可根据需要为其他元素添加主题相关的样式 */
        h1 {
            margin-bottom: 30px;
        }
        .table thead th {
            background-color: #343a40;
            color: #fff;
        }
        .dark-theme .table thead th {
            background-color:rgb(91, 63, 63);
            color: #fff;
        }
        .btn {
            margin-right: 5px;
        }
        .alert {
            margin-top: 20px;
        }
        /* 上传按钮宽度为100% */
        #upload-form .btn {
            width: 100%;
        }
        /* 拖拽区域样式 */
        #drop-area {
            border: 2px dashed #6c757d;
            border-radius: 5px;
            padding: 20px;
            height: 200px;
            text-align: center;
            color: #6c757d;
            margin-bottom: 20px;
            transition: background-color 0.3s, border-color 0.3s;
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        #drop-area.highlight {
            border-color: #495057;
            background-color: #e9ecef;
            color: #495057;
        }
        /* 隐藏实际的文件输入 */
        #file-input {
            display: none;
        }
        /* 文件预览样式 */
        #file-preview {
            margin-top: 20px;
            display: none;
            border: 1px solid #ced4da;
            padding: 15px;
            border-radius: 5px;
            background-color: #ffffff;
        }
        #file-preview img {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
        }

        /* 为表格添加圆角 */
        .table-responsive .table {
            border-radius: 10px;
            overflow: hidden;
        }

        /* 访问权限切换按钮样式 */
        .toggle-access {
            color: #0d6efd;
            cursor: pointer;
        }

        /* 进度条遮罩样式 */
        #progress-container,
        #download-progress-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* 半透明黑色背景 */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999; /* 确保在最上层 */
            flex-direction: column; /* 垂直排列子元素 */
        }
        
        /* 进度条样式 */
        #progress-container .progress,
        #download-progress-container .progress {
            width: 50%; /* 可根据需要调整宽度 */
        }
        
        #progress-container .text-white,
        #download-progress-container .text-white {
            color: white;
        }

        .progress-overlay {
            width: 50%; /* 设置为适当的宽度，如 50% */
            max-width: 500px; /* 可选，限制最大宽度 */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-access:hover {
            color: #0b5ed7;
        }
        
        /* 调整公告的z-index以确保在最上层 */
        .announcement {
            position: fixed;
            top: 0;
            margin-top: 0px;
            left: 0;
            width: 100%;
            z-index: 1050; /* Bootstrap 的z-index警告框是1040 */
            border-radius: 0;
        }

        /* 隐藏关闭按钮，默认不显示 */
        .preview-card .btn-close {
            display: none;
            background-color: red;
            border-radius: 50%;
            padding: 0.5rem;
        }
        
        /* 当鼠标悬停在预览卡片上时显示关闭按钮 */
        .preview-card:hover .btn-close {
            display: block;
        }

        /* 可选：为编辑状态的输入框添加样式 */
        .editable-filename input {
            width: 100%;
        }
        #drop-area {
            background-color:rgba(0, 125, 251, 0.1);
        }
        :root {
            --scale-mobile: 0.85;
            --width-mobile: 117.65%; /* 1 / 0.85 ≈ 1.1765 */

            --scale-tablet: 1.2;
            --width-tablet: 83.33%; /* 1 / 1.2 ≈ 0.8333 */

            --scale-small-desktop: 1.1;
            --width-small-desktop: 90.91%; /* 1 / 1.1 ≈ 0.9091 */
        }

        /* Mobile Devices (Max Width: 375px) */
        @media (max-width: 376px) {
            body {
                transform: scale(var(--scale-mobile));
                transform-origin: top left;
                width: var(--width-mobile);
                overflow-x: hidden; /* Prevent horizontal scrollbar */
            }
        }

        /* Tablet Devices (Min Width: 768px and Max Width: 834px) */
        @media (min-width: 768px) and (max-width: 990px) {
            body {
                transform: scale(var(--scale-tablet));
                transform-origin: top left;
                width: var(--width-tablet);
                overflow-x: hidden; /* Prevent horizontal scrollbar */
            }

            /* Hide Specific Elements on Tablet */
            .update_name,
            table thead tr th:nth-child(2),
            table thead tr th:nth-child(5), /* Hide 5th column: Access Permission */
            table tbody tr td.file-size,
            table tbody tr td:nth-child(5) { /* Hide 5th column: Access Permission */
                display: none;
            }
        }
        /* 响应式隐藏列 */
        @media (max-width: 767.98px) {
            .file-size, .upload-time{
                display: none;
            }
            table thead tr th:nth-child(2),
            table thead tr th:nth-child(3),
            table thead tr th:nth-child(5), /* 隐藏第5列：访问权限 */
            table tbody tr td.file-size,
            table tbody tr td.upload-time,
            table tbody tr td:nth-child(5) { /* 隐藏第5列：访问权限 */
                display: none;
            }
            /* 进度条样式 */
            #progress-container .progress {
                width: 50%; /* 可根据需要调整宽度 */
            }
            #drop-area {
                height: 120px;
            }


            .hide-on-mobile {
                display: none !important;
            }
        }

        /* 文件名单元格样式 */
        .filename-cell {
            max-width: 150px; /* 根据需要调整最大宽度 */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* 用户标志圆点样式，完全居中 */
        .user-indicator {
            display: block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin: 0 auto;
        }
            
        .ts-user-indicator {
            display: block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
            
        .ts-user-indicator.owner {
            background-color: #0DCAF0;
        }
            
        .ts-user-indicator.other {
            background-color: gray; /* 其他用户的标志为灰色 */
        } 
        .user-indicator.owner {
            background-color: #0DCAF0;
        }     
        .user-indicator.other {
            background-color: gray; /* 其他用户的标志为灰色 */
        }
        .update-announcement {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1050;
            background-color: white;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
        }

        body.dark-theme .update-announcement {
            background-color: #343a40; /* 深色主题背景色 */
            border-color: #495057;     /* 深色主题边框色 */
            color: #f8f9fa;            /* 深色主题文字颜色 */
        }
        .update-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        .copyright {
            text-align: center;
            margin-top: 20px;
            color: #fff;
        }
        /* 固定提示信息容器在页面顶部 */
        #message-container {
            position: fixed;
            top: 60px; /* 距离顶部的距离，可以根据需要调整 */
            left: 50%;
            transform: translateX(-50%);
            width: auto;
            z-index: 9999; /* 确保在最上层 */
            pointer-events: none; /* 使其不阻挡下层元素的点击 */
        }

        /* 提示信息样式 */
        #message-container .alert {
            pointer-events: all; /* 使提示信息可交互（如关闭按钮） */
            min-width: 300px; /* 最小宽度，可根据需要调整 */
            max-width: 600px; /* 最大宽度，可根据需要调整 */
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.5); /* 添加更强的阴影效果 */
            border-radius: 10px; /* 圆角效果 */
            background-color: rgba(255, 255, 255, 0.66);
            backdrop-filter: blur(3px);
            color: rgb(255, 255, 255));
        }
        .ts {
            display: flex;
            align-items: center;
            gap: 10px; /* 减小间距，使圆点和文字更靠近 */
            justify-content: flex-start; 
            /* 内容靠左对齐 */
            font-size: 0.9rem;
            background-color:rgba(52, 52, 52, 0.13);
            border-radius: 50px;
            margin: 5px 0;
            padding: 10px 20px;
        }
    </style>
    <style>
        /* ...现有的样式... */

        /* 自定义右键菜单样式 */
        #contextMenu {
            display: none;
            position: absolute;
            background-color: var(--context-bg, white);
            border: 1px solid #ccc;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            width: 150px;
            border-radius: 5px;
            animation: fadeIn 0.3s ease forwards;
            opacity: 0;
            transform: scale(0.95);
        }

        /* 主题变量 */
        .light-theme {
            --context-bg: white;
            --context-text: #212529;
            --context-hover-bg: #f1f1f1;
        }

        .dark-theme {
            --context-bg: #343a40;
            --context-text: #f8f9fa;
            --context-hover-bg: #495057;
        }

        #contextMenu ul {
            list-style: none;
            margin: 0;
            padding: 5px 0;
        }

        #contextMenu li {
            padding: 6px 10px; /* 减少内边距 */
            cursor: pointer;
            color: var(--context-text);
            transition: background-color 0.2s, border-radius 0.2s;
            position: relative;
        }
        
        #contextMenu li:not(:last-child)::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 10%;
            width: 80%;
            height: 1px;
            background-color: var(--context-hover-bg);
        }
        
        #contextMenu li:hover {
            background-color: var(--context-hover-bg);
            border-radius: 5px; /* 添加圆角 */
        }

        /* 动画关键帧 */
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.95); }
        }

        /* 关闭时添加的类 */
        #contextMenu.fade-out {
            animation: fadeOut 0.2s ease forwards;
        }
    </style>
    <style>
        /* ...现有的样式... */

        /* 灯箱样式 */
        .lightbox {
            display: none; /* 初始隐藏 */
            position: fixed;
            z-index: 1001; /* 确保在最上层 */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8); /* 半透明背景 */
            animation: fadeIn 0.3s ease;
        }

        .lightbox-content {
            display: block;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
            margin: 0 auto;
            max-width: 80%;
            max-height: 80%;
            border-radius: 5px;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: var(--context-text, #fff);
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        @keyframes fadeIn {
            from {opacity: 0}
            to {opacity: 1}
        }

        @keyframes fadeOut {
            from {opacity: 1}
            to {opacity: 0}
        }
        /* 音频播放器样式 */
        .audio-player {
            position: fixed;
            bottom: 20px;
            right: 20px;
            max-width: 90%; /* 最大宽度为视口宽度的90% */
            background-color: rgba(var(--player-bg), 0.7);
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            backdrop-filter: blur(5px);
            color: var(--player-text);
            display: none;
        }

        .light-theme {
            --player-bg: 255, 255, 255;
            --player-text: #212529;
        }

        .dark-theme {
            --player-bg: 52, 58, 64;
            --player-text: #f8f9fa;
        }

        .player-header {
            padding: 10px;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 10px 10px 0 0;
            cursor: move;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .player-body {
            padding: 15px;
        }

        .controls {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #now-playing {
            font-weight: bold;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* 进度条整体样式 */
        #progress {
            -webkit-appearance: none; /* 去除默认样式 */
            appearance: none;
            width: 100%;
            height: 8px;
            background: #e0e0e0; /* 未走过的部分颜色 */
            border-radius: 4px;
            outline: none;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        #progress:hover {
            opacity: 1;
        }

        /* 进度条走过的部分样式 */
        #progress::-webkit-slider-runnable-track {
            height: 8px;
            background: linear-gradient(to right, #007bff, #00aaff); /* 走过的部分颜色 */
            border-radius: 4px;
        }

        #progress::-moz-range-track {
            height: 8px;
            background: linear-gradient(to right, #007bff, #00aaff); /* 走过的部分颜色 */
            border-radius: 4px;
        }

        /* 进度条滑块样式 */
        #progress::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            background: #007bff; /* 滑块颜色 */
            border-radius: 50%;
            cursor: pointer;
        }

        #progress::-moz-range-thumb {
            width: 16px;
            height: 16px;
            background: #007bff; /* 滑块颜色 */
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- 系统使用公告 -->
    <div class="alert alert-info alert-dismissible fade show text-center announcement animate__animated animate__fadeInDown" role="alert">
        <?php echo htmlspecialchars($indexAnnouncement); ?>
        <link rel="stylesheet" href="css/animate.min.css"/>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
    </div>
    <div class="container py-2">
        <div class="d-flex justify-content-center align-items-center mb-3">
            <img src="static/tutu.png" alt="logo" class="me-3" style="width: 50px;">
            <h1 class="text-center mb-0 d-none d-md-block" style="line-height: 50px;">牛图图内网传输</h1>
        </div>
        <hr>
        <!-- 显示欢迎信息和退出按钮 -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <img src="static/xiaomei.jpg" alt="logo" style="width: 25px; height: 25px; border-radius: 50%; border: 1px solid #000; margin-right: 5px; cursor: pointer;" onclick="showLightbox('static/xiaomei.jpg')">
                <span>欢迎，<?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                <!-- 添加加载中的图标，初始隐藏 -->
                <div id="upload-loading" class="spinner-border spinner-border-sm text-primary ms-1" role="status" style="display: none;">
                    <span class="visually-hidden">加载中...</span>
                </div>
            </div>
            <div>
                <!--刷新功能的图标-->
                <svg width="25" height="25" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="cursor: pointer;" onclick="location.reload();">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M4 2C4.55228 2 5 2.44772 5 3V5.10125C6.27009 3.80489 8.04052 3 10 3C13.0494 3 15.641 4.94932 16.6014 7.66675C16.7855 8.18747 16.5126 8.75879 15.9918 8.94284C15.4711 9.12689 14.8998 8.85396 14.7157 8.33325C14.0289 6.38991 12.1755 5 10 5C8.36507 5 6.91204 5.78502 5.99935 7H9C9.55228 7 10 7.44772 10 8C10 8.55228 9.55228 9 9 9H4C3.44772 9 3 8.55228 3 8V3C3 2.44772 3.44772 2 4 2ZM4.00817 11.0572C4.52888 10.8731 5.1002 11.146 5.28425 11.6668C5.97112 13.6101 7.82453 15 10 15C11.6349 15 13.088 14.215 14.0006 13L11 13C10.4477 13 10 12.5523 10 12C10 11.4477 10.4477 11 11 11H16C16.2652 11 16.5196 11.1054 16.7071 11.2929C16.8946 11.4804 17 11.7348 17 12V17C17 17.5523 16.5523 18 16 18C15.4477 18 15 17.5523 15 17V14.8987C13.7299 16.1951 11.9595 17 10 17C6.95059 17 4.35905 15.0507 3.39857 12.3332C3.21452 11.8125 3.48745 11.2412 4.00817 11.0572Z" fill="#4A5568"/>
                </svg>
                <!-- 搜索功能按钮 -->
                <button id="show-search-btn" class="btn btn-sm" title="搜索文件">
                    <svg t="1747516064572" class="icon bi bi-search" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="2824" width="25" height="25"><path d="M581.973333 846.933333a380.8 380.8 0 1 1 380.8-380.8A381.226667 381.226667 0 0 1 581.973333 846.933333z m0-688a307.2 307.2 0 1 0 307.2 307.2 307.413333 307.413333 0 0 0-307.2-307.2z" fill="#FA6302" p-id="2825"></path><path d="M146.56 938.666667a36.906667 36.906667 0 0 1-26.026667-64l192-190.933334a36.906667 36.906667 0 0 1 52.053334 52.266667l-192 192a37.333333 37.333333 0 0 1-26.026667 10.666667z" fill="#43D7B4" p-id="2826"></path><path d="M470.826667 274.773333m-49.066667 0a49.066667 49.066667 0 1 0 98.133333 0 49.066667 49.066667 0 1 0-98.133333 0Z" fill="#43D7B4" p-id="2827"></path><path d="M312.106667 684.8l-23.68 23.466667A388.693333 388.693333 0 0 0 341.333333 760.32l23.466667-23.253333a36.906667 36.906667 0 0 0-52.053333-52.266667z" fill="#425300" p-id="2828"></path></svg>
                </button>

                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                    <a href="admin" class="btn btn-sm btn-outline-secondary">管理用户</a>
                    <a href="manage_announcement" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">首页公告</a>
                    <a href="manage_update_content" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">更新提示</a>
                <?php endif; ?>
                <a href="change_password" class="btn btn-sm btn-outline-secondary">修改密码</a>
                <a href="logout" class="btn btn-sm btn-outline-secondary">退出登录</a>
            </div>
        </div>
        <!-- 右键菜单 -->
        <div id="contextMenu">
            <ul>
                <li id="refresh">刷新</li>
                <!-- 其他操作可以在这里添加 -->
                <!-- 彩蛋 -->
                <li id="showPhoto">宝宝</li>
            </ul>
        </div>

        <!-- 彩蛋 灯箱 (Lightbox) -->
        <div id="lightbox" class="lightbox">
            <span class="close">&times;</span>
            <img class="lightbox-content" src="static/tutu.png" alt="照片">
        </div>

        <!-- 拖拽上传区域 -->
        <div id="drop-area">
            <div class="hide-on-mobile"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-cloud-upload" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M4.406 1.342A5.53 5.53 0 0 1 8 0c2.69 0 4.923 2 5.166 4.579C14.758 4.804 16 6.137 16 7.773 16 9.569 14.502 11 12.687 11H10a.5.5 0 0 1 0-1h2.688C13.979 10 15 8.988 15 7.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 2.825 10.328 1 8 1a4.53 4.53 0 0 0-2.941 1.1c-.757.652-1.153 1.438-1.153 2.055v.448l-.445.049C2.064 4.805 1 5.952 1 7.318 1 8.785 2.23 10 3.781 10H6a.5.5 0 0 1 0 1H3.781C1.708 11 0 9.366 0 7.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383"/>
            <path fill-rule="evenodd" d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708z"/>
            </svg></div>
            <span class="d-none d-md-block">拖拽文件到此处上传，或点击选择文件。</span>
            <span class="d-block d-md-none">点击虚线框内任意位置即可选择文件。</span>
            
            <!-- 访问权限选择 -->
            <div class="mt-3">
                <label class="form-label me-3">文件访问权限：</label>
                <div class="form-check form-check-inline" onclick="event.stopPropagation()">
                    <input class="form-check-input" type="radio" name="access" id="public" value="public">
                    <label class="form-check-label" for="public">公开</label>
                </div>
                <div class="form-check form-check-inline" onclick="event.stopPropagation()">
                    <input class="form-check-input" type="radio" name="access" id="private" value="private" checked>
                    <label class="form-check-label" for="private">私密</label>
                </div>
            </div>
        </div>

        <!-- 文件上传表单 -->
        <div class="mb-4">
            <form id="upload-form" enctype="multipart/form-data">
                <input type="file" name="file[]" id="file-input" accept="*/*" multiple>
                <button type="submit" id="upload-button" class="btn btn-primary">上传文件
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16">
                  <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0"/>
                  <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>
                </svg></button>
            </form>
        </div>

        <!-- 文件预览 -->
        <div id="file-preview" style="display: none;">
            <h5>文件预览</h5>
            <div id="preview-container" class="row">
                <!-- 动态添加文件预览卡片 -->
            </div>
        </div>
        
        <!-- 上传进度条容器，初始隐藏 -->
        <div id="progress-container" style="display: none;">
            <div class="text-center mb-3 text-white">
                正在上传，请稍候...
            </div>
            <div class="progress mb-3" role="progressbar" aria-label="上传进度" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: 0%">0%</div>
            </div>
        </div>
        
        <!-- 下载进度条容器，初始隐藏 -->
        <div id="download-progress-container" style="display: none;">
            <div class="text-center mb-3 text-white">
                正在下载，请稍候...
            </div>
            <div class="progress mb-3" role="progressbar" aria-label="下载进度" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: 0%">0%</div>
            </div>
        </div>

        <!-- 提示信息 -->
        <div id="message-container"></div>
        <div class="ts d-md-none">
            <!-- 当前用户的圆点 -->
            <span class="ts-user-indicator owner"></span><?php echo htmlspecialchars($_SESSION['user']['name']); ?>上传
            <!-- 其他用户的圆点 -->
            <span class="ts-user-indicator other"></span>其他用户上传
        </div>

        <!-- 文件列表 -->
        <div class="table-responsive custom-rounded-table">
            <table id="main-table" class="table table-striped table-hover mb-0 table-dark">
                <thead>
                    <tr>
                        <th>文件名</th>
                        <th class="file-size">大小</th>
                        <th class="upload-time">上传时间</th>
                        <th class="uploader-name"><span class="d-none d-md-inline">上传者</span></th>
                        <th>访问权限</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="file-list">
                    <!-- 文件列表 -->
                </tbody>
            </table>
        </div>
        <!-- 版权信息 -->
        <?php $app_version = require __DIR__ . '/version.php'; ?>
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
    <!-- 悬浮音频播放器 -->
    <div id="audio-player" class="audio-player">
        <div class="player-header">
            <span>正在播放</span>
            <button class="btn-close" onclick="closeAudioPlayer()"></button>
        </div>
        <div class="player-body">
            <div id="now-playing">未选择音频文件</div>
            <audio id="audio-element" style="display: none;"></audio>
            <div class="controls">
                <button id="play-pause-btn" class="btn btn-sm btn-primary" onclick="togglePlayPause()">播</button>
                <input type="range" id="progress" value="0" class="form-range">
                <span id="current-time">00:00</span>|
                <span id="duration">00:00</span>
            </div>
            <div class="volume-control">
                <!-- 音量控制滑块 -->
                <input type="range" id="volume" min="0" max="1" step="0.1" value="1" 
                    class="form-range" oninput="setVolume(this.value)">
            </div>
        </div>
    </div>

    <!-- 更新弹窗 -->
    <style>
    #update-toast {
        display: none;
        position: fixed;
        right: 30px;
        bottom: 30px;
        z-index: 99999;
        min-width: 260px;
        max-width: 350px;
        background: rgba(255,255,255,0.97);
        color: #333;
        border-radius: 10px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        padding: 18px 24px 18px 18px;
        font-size: 15px;
        line-height: 1.7;
        border-left: 6px solid #f093fb;
        opacity: 0;
        transform: translateY(40px) scale(0.95);
        pointer-events: none;
        transition: opacity 0.3s cubic-bezier(.4,2,.6,1), transform 0.3s cubic-bezier(.4,2,.6,1);
    }
    #update-toast.toast-show {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }
    #update-toast.toast-hide {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
        pointer-events: none;
    }
    body.dark-theme #update-toast {
        background: rgba(34, 34, 34, 0.97) !important;
        color: #eee !important;
        border-left: 6px solid #f093fb !important;
    }
    </style>
    <div id="update-toast">
        <div style="font-weight:bold;margin-bottom:6px;">系统更新提示</div>
        <div id="update-toast-content"></div>
        <button id="update-toast-close" style="
            position:absolute;top:8px;right:12px;
            background:none;border:none;font-size:20px;line-height:1;cursor:pointer;color:#888;
        " title="关闭">&times;</button>
    </div>

    <!-- 引入脚本 -->
    <script>
        // 音频播放相关变量
        let currentAudio = null;
        let isDragging = false;
        let isSeeking = false;

        // 初始化播放器拖拽
        function initPlayerDrag() {
            const player = document.getElementById('audio-player');
            const header = player.querySelector('.player-header');
            
            let posX = 0, posY = 0, mouseX = 0, mouseY = 0;

            header.addEventListener('mousedown', dragMouseDown);

            function dragMouseDown(e) {
                e.preventDefault();
                isDragging = true;
                mouseX = e.clientX;
                mouseY = e.clientY;
                document.addEventListener('mousemove', elementDrag);
                document.addEventListener('mouseup', closeDragElement);
            }

            function elementDrag(e) {
                if (!isDragging) return;
                e.preventDefault();
                const dx = e.clientX - mouseX;
                const dy = e.clientY - mouseY;
                posX += dx;
                posY += dy;
                mouseX = e.clientX;
                mouseY = e.clientY;
                player.style.transform = `translate(${posX}px, ${posY}px)`;
            }

            function closeDragElement() {
                isDragging = false;
                document.removeEventListener('mousemove', elementDrag);
                document.removeEventListener('mouseup', closeDragElement);
            }
        }

        // 播放MP3文件
        window.playMP3 = function(fileId, fileName) {
        const player = document.getElementById('audio-player');
        const audioElement = document.getElementById('audio-element');
        const nowPlaying = document.getElementById('now-playing');

        // 停止当前播放
        if (currentAudio) {
            currentAudio.pause();
        }

        // 显示播放器
        player.style.display = 'block';
        nowPlaying.textContent = fileName;

        // 创建新的音频源
        audioElement.src = `stream_audio.php?id=${fileId}`;
        currentAudio = audioElement;
        audioElement.style.display = 'block';

        // 等待文件加载完成
        audioElement.oncanplaythrough = () => {
            audioElement.play().catch(error => {
                console.error('播放失败:', error);
                displayMessage('播放失败，请检查文件格式或权限。', 'danger');
            });
        };

        // 更新时长显示
        audioElement.onloadedmetadata = () => {
            document.getElementById('duration').textContent = formatTime(audioElement.duration);
        };

        // 进度更新
        audioElement.ontimeupdate = () => {
            if (!isSeeking) {
                const progress = document.getElementById('progress');
                progress.value = audioElement.currentTime / audioElement.duration * 100;
                document.getElementById('current-time').textContent = formatTime(audioElement.currentTime);
            }
        };

        // 进度条控制
        document.getElementById('progress').addEventListener('input', function() {
            isSeeking = true;
            audioElement.currentTime = this.value / 100 * audioElement.duration;
        });

        document.getElementById('progress').addEventListener('change', function() {
            isSeeking = false;
        });
    };

        function togglePlayPause() {
            const btn = document.getElementById('play-pause-btn');
            if (currentAudio.paused) {
                currentAudio.play();
                btn.textContent = '停';
            } else {
                currentAudio.pause();
                btn.textContent = '播';
            }
        }

        function setVolume(level) {
            if (currentAudio) {
                currentAudio.volume = level;
            }
        }

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            seconds = Math.floor(seconds % 60);
            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function closeAudioPlayer() {
            if (currentAudio) {
                currentAudio.pause();
                currentAudio = null;
            }
            document.getElementById('audio-player').style.display = 'none';
        }

        // 初始化播放器拖拽
        document.addEventListener('DOMContentLoaded', initPlayerDrag);
    </script>
        <script>
            const currentUser = <?php echo json_encode($_SESSION['user']['name']); ?>;
        </script>

    <?php if ($updateAnnouncement['update_enabled']): ?>
        <div class="update-overlay"></div>
        <div class="update-announcement alert alert-info text-center" role="alert">
            <h4>更新内容</h4>
            <div class="text-start"><?php echo $updateAnnouncement['update_content']; ?></div>
            <!-- 添加倒计时显示 -->
            <p>自动关闭倒计时: <span id="countdown">10</span> 秒</p>
            <!-- 修改关闭按钮，添加红色边框 -->
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭" style="border: 2px solid red;"></button>
            <!-- 版权信息 -->
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
        <script>
            // 设置倒计时初始值
            var timeLeft = 10;
            var countdownElement = document.getElementById('countdown');
            
            var countdownInterval = setInterval(function() {
                timeLeft--;
                if(timeLeft <= 0){
                    clearInterval(countdownInterval);
                    var updateAlert = document.querySelector('.update-announcement');
                    var updateOverlay = document.querySelector('.update-overlay');
                    if (updateAlert) {
                        updateAlert.classList.remove('show');
                        updateAlert.classList.add('hide');
                        updateAlert.remove();
                    }
                    if (updateOverlay) {
                        updateOverlay.remove();
                    }
                } else {
                    countdownElement.textContent = timeLeft;
                }
            }, 1000);
    
            // 监听公告关闭事件，移除背景色并清除倒计时
            document.addEventListener('DOMContentLoaded', function() {
                var updateAlert = document.querySelector('.update-announcement');
                var updateOverlay = document.querySelector('.update-overlay');
    
                if (updateAlert) {
                    updateAlert.addEventListener('closed.bs.alert', function () {
                        clearInterval(countdownInterval); // 清除定时器
                        if (updateOverlay) {
                            updateOverlay.remove();
                        }
                    });
                }
            });
        </script>
    <?php endif; ?>

    <!-- 搜索功能 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('show-search-btn');
        btn && btn.addEventListener('click', function() {
            // 如果已存在搜索框则不重复加载
            if(document.getElementById('search-modal-bg')) return;
            fetch('sousuo.php')
                .then(r=>r.text())
                .then(html=>{
                    document.body.insertAdjacentHTML('beforeend', html);
    
                    // 关闭逻辑：支持按钮、遮罩、ESC
                    const modalBg = document.getElementById('search-modal-bg');
                    const closeBtn = document.getElementById('close-search');
                    const form = document.getElementById('search-form');
                    const input = document.getElementById('search-keyword');
                    const resultBox = document.getElementById('search-result');
    
                    function closeSearchModal() {
                        if (modalBg && modalBg.parentNode) {
                            modalBg.parentNode.removeChild(modalBg);
                        }
                    }
                    if (closeBtn) closeBtn.onclick = closeSearchModal;
                    if (modalBg) modalBg.onclick = function(e) {
                        if(e.target === modalBg) closeSearchModal();
                    };
                    document.addEventListener('keydown', function(e){
                        if(e.key === 'Escape') closeSearchModal();
                    });
    
                    // 搜索
                    let lastKeyword = '';
                    if (form && input && resultBox) {
                        form.onsubmit = e => e.preventDefault();
                        input.oninput = function() {
                            const kw = input.value.trim();
                            if(kw === lastKeyword) return;
                            lastKeyword = kw;
                            resultBox.innerHTML = '<div class="text-center text-muted py-3">搜索中...</div>';
                            fetch('sousuo.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'keyword=' + encodeURIComponent(kw)
                            })
                            .then(r=>r.text())
                            .then(html=>{ resultBox.innerHTML = html; });
                        };
                        input.focus();
                    }
                });
        });
    });
    </script>
    
    <!-- 引入脚本 -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <!-- 彩蛋 -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const showPhoto = document.getElementById('showPhoto');
            const lightbox = document.getElementById('lightbox');
            const closeBtn = document.querySelector('.lightbox .close');
        
            // 显示灯箱
            if (showPhoto) {
                showPhoto.addEventListener('click', () => {
                    lightbox.style.display = 'block';
                });
            }
        
            // 关闭灯箱
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    lightbox.style.display = 'none';
                });
            }
        
            // 点击灯箱背景关闭
            if (lightbox) {
                lightbox.addEventListener('click', (e) => {
                    if (e.target === lightbox) {
                        lightbox.style.display = 'none';
                    }
                });
            }
        });
                // 修改 showLightbox 函数，添加关闭事件监听
                function showLightbox(imageSrc) {
                    const lightbox = document.getElementById('lightbox');
                    const lightboxImage = document.querySelector('.lightbox-content');
                    const closeBtn = document.querySelector('.lightbox .close');
                    
                    // 设置图片源
                    lightboxImage.src = imageSrc;
                    
                    // 显示灯箱
                    lightbox.style.display = 'block';
                    
                    // 确保关闭按钮的事件监听器已设置
                    closeBtn.onclick = () => {
                        lightbox.style.display = 'none';
                    };
                    
                    // 点击灯箱背景关闭
                    lightbox.onclick = (e) => {
                        if (e.target === lightbox) {
                            lightbox.style.display = 'none';
                        }
                    };
                }
    </script>
    <script>
        // 刷新
        function refreshFileList() {
            // 显示加载图标
            const uploadLoading = document.getElementById('upload-loading');
            uploadLoading.style.display = 'inline-block';
            
            // 调用已有的加载文件列表函数
            loadFileList();
            
            // 显示成功消息并隐藏加载图标
            setTimeout(() => {
                displayMessage('数据刷新成功！', 'success');
                uploadLoading.style.display = 'none';
            }, 500);
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const contextMenu = document.getElementById('contextMenu');
            const refreshOption = document.getElementById('refresh');

            // 显示右键菜单的函数
            function showContextMenu(x, y) {
                contextMenu.style.top = `${y}px`;
                contextMenu.style.left = `${x}px`;
                contextMenu.classList.remove('fade-out');
                contextMenu.style.display = 'block';
                requestAnimationFrame(() => {
                    contextMenu.style.opacity = '1';
                    contextMenu.style.transform = 'scale(1)';
                });
            }

            // 隐藏右键菜单的函数
            function hideContextMenu() {
                contextMenu.classList.add('fade-out');
                // 等待动画结束后隐藏菜单
                contextMenu.addEventListener('animationend', () => {
                    contextMenu.style.display = 'none';
                    contextMenu.classList.remove('fade-out');
                }, { once: true });
            }

            // 监听右键菜单事件
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                showContextMenu(e.clientX, e.clientY);
            });

            // 点击页面其他地方隐藏右键菜单
            document.addEventListener('click', () => {
                hideContextMenu();
            });

            // 刷新功能
            refreshOption.addEventListener('click', () => {
                location.reload();
            });

            // 其他操作的事件监听器可以在这里添加
        });
    </script>
    <script>
        function downloadFile(fileId, fileName) {
            const downloadProgressContainer = document.getElementById('download-progress-container');
            const progressBar = downloadProgressContainer.querySelector('.progress-bar');
        
            // 显示下载进度条
            downloadProgressContainer.style.display = 'flex';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
        
            // 构建下载请求的 URL
            const url = `download.php?id=${encodeURIComponent(fileId)}`;
        
            fetch(url, {
                method: 'GET',
                credentials: 'include' // 确保包含会话信息
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('网络响应失败');
                }
        
                const contentLength = response.headers.get('Content-Length');
                if (!contentLength) {
                    throw new Error('无法获取文件大小，无法显示下载进度');
                }
        
                const total = parseInt(contentLength, 10);
                let loaded = 0;
        
                // 创建一个 reader 来读取数据流
                const reader = response.body.getReader();
                const chunks = [];
        
                function read() {
                    return reader.read().then(({ done, value }) => {
                        if (done) {
                            return;
                        }
                        chunks.push(value);
                        loaded += value.length;
        
                        // 更新进度条
                        const percentComplete = Math.round((loaded / total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        progressBar.textContent = percentComplete + '%';
        
                        return read();
                    });
                }
        
                return read().then(() => {
                    // 创建 Blob 并生成下载链接
                    const blob = new Blob(chunks);
                    const downloadUrl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = downloadUrl;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(downloadUrl);
                });
            })
            .catch(error => {
                displayMessage('下载失败：' + error.message, 'danger');
            })
            .finally(() => {
                // 隐藏下载进度条
                downloadProgressContainer.style.display = 'none';
            });
        }
    </script>
    <script>
        setTimeout(function() {
            var alertElement = document.querySelector('.alert');
            if (alertElement) {
                var alert = new bootstrap.Alert(alertElement);
                alert.close();
            }
        }, 5000);
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const uploadForm = document.getElementById('upload-form');
        const fileListTable = document.getElementById('file-list');
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('file-input');
        const uploadButton = document.getElementById('upload-button');
        const messageContainer = document.getElementById('upload-message');
        const filePreview = document.getElementById('file-preview');
        const previewContainer = document.getElementById('preview-container');
        let selectedFiles = []; // 存储 { file: File, customName: string }
    
        // 加载文件列表
        loadFileList();
    
        // 点击拖拽区域触发文件选择
        dropArea.addEventListener('click', () => {
            fileInput.click();
        });
    
        // 文件选择
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                handleFiles(fileInput.files);
            }
        });
        // 函数：更新上传按钮的状态
        function updateUploadButton() {
            if (fileInput.files.length > 0) {
                uploadButton.disabled = false;
                uploadButton.classList.remove('btn-secondary');
                uploadButton.classList.add('btn-primary');
            } else {
                uploadButton.disabled = true;
                uploadButton.classList.remove('btn-primary');
                uploadButton.classList.add('btn-secondary');
            }
        }
    
        // 替换 uploadForm 的提交事件处理
        uploadForm.addEventListener('submit', function (event) {
            event.preventDefault();
    
            if (selectedFiles.length === 0) {
                displayMessage('请先选择一个文件。', 'danger');
                return;
            }
    
            const formData = new FormData();
            selectedFiles.forEach((item, index) => {
                formData.append('file[]', item.file);
                formData.append('customName[]', item.customName);
            });
    
            // 获取选定的访问权限
            const access = document.querySelector('input[name="access"]:checked').value;
            formData.append('access', access);
    
            // 显示进度条和遮罩
            const progressContainer = document.getElementById('progress-container');
            const progressBar = progressContainer.querySelector('.progress-bar');
            progressContainer.style.display = 'flex';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
    
            // 显示加载图标
            const uploadLoading = document.getElementById('upload-loading');
            uploadLoading.style.display = 'inline-block';
    
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'file_manager.php', true);
            xhr.withCredentials = true;
    
            // 上传进度事件
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressBar.textContent = percentComplete + '%';
                }
            });
    
            // 请求完成事件
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    // 隐藏进度条和遮罩
                    progressContainer.style.display = 'none';
                    uploadLoading.style.display = 'none';
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            let allSuccess = true;
                            data.forEach(response => {
                                if (response.success) {
                                    displayMessage('文件上传成功！', 'success');
                                } else {
                                    displayMessage('文件上传失败：' + response.error, 'danger');
                                    allSuccess = false;
                                }
                            });
                            if (allSuccess) {
                                // 重新加载文件列表
                                loadFileList();
                                // 重置表单和预览
                                uploadForm.reset();
                                resetPreview();
                            }
                        } catch (err) {
                            displayMessage('解析服务器响应失败。', 'danger');
                        }
                    } else {
                        displayMessage('文件上传失败，请稍后重试。', 'danger');
                    }
                }
            };
    
            xhr.send(formData);
        });
    
    //20241217更新：添加图片和视频的查看功能
    // 将 viewFile 绑定到 window 对象，确保全局可访问
    window.viewFile = function(fileId) {
        //console.log(`查看文件ID: ${fileId}`);
        window.open(`view_file?id=${fileId}`, '_blank');
    };
    
    // 加载文件列表
    function loadFileList() {
    fetch('file_list.php', {
    method: 'GET',
    credentials: 'include' // 确保请求包含会话信息
    })
    .then(response => response.json())
    .then(data => {
    fileListTable.innerHTML = '';
        if (!Array.isArray(data) || data.length === 0) {
            fileListTable.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <img src="static/tutuxiao.png" alt="暂无文件" style="width: 80px; opacity: 0.7; margin-bottom: 12px;">
                            <div style="font-size: 1.2rem; color: #888;">
                                暂无文件，快来上传文件吧！
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        data.forEach(file => {
            // 判断是否为图片或视频类型，添加查看按钮
            const viewButton = (file.type && (file.type.startsWith('image/') || file.type.startsWith('video/'))) ?
                `<button class="btn btn-info btn-sm me-1" onclick="viewFile(${file.id})">查看</button>` : '';
            
            // 判断是否为音频类型，添加播放按钮
            const playButton = (file.type && file.type.startsWith('audio/')) ?
                `<button class="btn btn-warning btn-sm me-1" onclick="playMP3(${file.id}, '${escapeHtml(file.original_name)}')">播放</button>` : '';
            const accessBadge = file.access === 'public' ?
                `<span class="badge bg-success">公开</span>` :
                `<span class="badge bg-secondary">私密</span>`;
            
            const accessToggle = `
                <button type="button" class="btn btn-sm btn-link toggle-access" data-file-id="${file.id}" data-current-access="${file.access}">
                    ${file.access.toLowerCase().trim() === 'private' ?
                        `<!-- 私密文件锁定图标 -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2M5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
                        </svg>
                        ` :
                        `<!-- 公开文件解锁图标 -->
                        <svg xmlns="http://www.w3.org/2000/svg width="16" height="16" fill="currentColor" class="bi bi-unlock" viewBox="0 0 16 16">
                            <path d="M11 1a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5V3a3 3 0 0 1 6 0v4a.5.5 0 0 1-1 0V3a2 2 0 0 0-2-2M3 8a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1z"/>
                        </svg>`
                    }
                </button>
            `;
            
            // 声明并初始化 isCurrentUser
            const isCurrentUser = file.uploader_name.trim().toLowerCase() === currentUser.trim().toLowerCase();
            
            const row = `
                <tr>
                    <td class="filename-cell" title="${escapeHtml(file.original_name)}">${escapeHtml(file.original_name)}</td>
                    <td class="file-size">${formatFileSize(file.size)}</td>
                    <td class="upload-time">${escapeHtml(file.upload_time)}</td>
                    <td class="uploader-name">
                        <span class="d-none d-md-inline-block ${isCurrentUser ? 'text-white bg-info rounded px-2 py-1' : ''}">
                            ${escapeHtml(file.uploader_name)}
                        </span>
                        <span class="user-indicator d-block d-md-none ${isCurrentUser ? 'owner' : 'other'}"></span>
                    </td>
                    <td>
                        ${accessBadge}
                        ${accessToggle}
                    </td>
                    <td>
                        ${viewButton} <!-- 添加查看按钮 -->
                        ${playButton} <!-- 添加播放按钮 -->
                        <button class="btn btn-success btn-sm" onclick="downloadFile(${file.id}, '${escapeHtml(file.original_name)}')">
                            下载
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteFile(${file.id})">删除</button>
                    </td>
                </tr>
            `;
            fileListTable.innerHTML += row;
        });
        
                    // 初始化切换访问权限按钮的事件
                    document.querySelectorAll('.toggle-access').forEach(button => {
                        button.addEventListener('click', function(event) {
                            const fileId = this.getAttribute('data-file-id');
                            const currentAccess = this.getAttribute('data-current-access');
                            const newAccess = currentAccess === 'public' ? 'private' : 'public';
                            
                            if (confirm(`确定要将文件权限从 "${currentAccess === 'public' ? '公开' : '私密'}" 更改为 "${newAccess === 'public' ? '公开' : '私密'}" 吗？`)) {
                                // 发送请求更改访问权限
                                fetch('change_access.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    credentials: 'include',
                                    body: JSON.stringify({ file_id: fileId, access: newAccess })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        displayMessage('文件访问权限已更新。', 'success');
                                        loadFileList();
                                    } else {
                                        displayMessage('更新失败：' + data.error, 'danger');
                                    }
                                })
                                .catch(error => {
                                    console.error('更新访问权限失败:', error);
                                    displayMessage('更新失败，请稍后重试。', 'danger');
                                });
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('加载文件列表失败:', error);
                    displayMessage('加载文件列表失败，请刷新页面重试。', 'danger');
                });
        }
        
        // 添加查看文件的函数
        function viewFile(fileId) {
            console.log(`查看文件ID: ${fileId}`); // 调试信息
            window.open(`view_file?id=${fileId}`, '_blank');
        }
    
        // 前端删除文件的函数
        window.deleteFile = function (fileId) {
            if (confirm('确定要删除这个文件吗？')) {
                fetch('delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    credentials: 'include', // 确保请求包含会话信息
                    body: `id=${encodeURIComponent(fileId)}`
                })
                .then(response => response.text())
                .then(data => {
                    // 显示提示信息
                    displayMessage(data, 'success');
                    // 重新加载文件列表
                    loadFileList();
                })
                .catch(error => {
                    console.error('删除失败:', error);
                    displayMessage('删除失败，请稍后重试。', 'danger');
                });
            }
        };
    
        // 防止文件被误拖拽到浏览器窗口
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            document.addEventListener(eventName, preventDefaults, false);
        });
    
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
    
        // 高亮拖拽区域
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'), false);
        });
    
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'), false);
        });
    
        // 处理拖拽文件
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
        
            if (files.length > 0) {
                handleFiles(files);
            }
        }
        
        function handleFiles(files) {
            Array.from(files).forEach(file => {
                // 检查文件是否已存在
                if (!selectedFiles.some(f => f.file.name === file.name && f.file.size === file.size)) {
                    // 创建一个对象并同时添加到 selectedFiles 和传递给 handleFile
                    const item = { file: file, customName: file.name };
                    selectedFiles.push(item);
                    handleFile(item);
                } else {
                    displayMessage(`文件 "${file.name}" 已被选中。`, 'warning');
                }
            });
        }
    
        // 处理选中文件并显示预览
        function handleFile(item) {
            const file = item.file;
            const customName = item.customName;
            // 阻止上传 .php 文件
            const fileName = file.name.toLowerCase();
            if (fileName.endsWith('.php')) {
                displayMessage('不允许上传 PHP 文件。', 'warning');
                return;
            }
    
            const maxSize = 300 * 1024 * 1024; // 300MB
            if (file.size > maxSize) {
                displayMessage('文件大小超过限制 (最大300MB)。', 'warning');
                return;
            }
    
            // 创建预览卡片并添加 preview-card 类
            const previewCard = document.createElement('div');
            previewCard.className = 'col-md-4 mb-3 preview-card'; // 添加 preview-card 类
            previewCard.setAttribute('data-file-name', file.name);
    
            const card = document.createElement('div');
            card.className = 'card position-relative';
    
            // 关闭按钮
            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn-close position-absolute top-0 end-0 m-2';
            closeButton.setAttribute('aria-label', '关闭');
    
            closeButton.addEventListener('click', () => {
                // 从 selectedFiles 中移除文件
                selectedFiles = selectedFiles.filter(f => f.file !== file);
                // 移除预览卡片
                previewCard.remove();
                // 如果没有文件，隐藏预览区域
                if (selectedFiles.length === 0) {
                    filePreview.style.display = 'none';
                }
            });
    
            card.appendChild(closeButton);
    
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body';
    
            // 可编辑的文件名
            const title = document.createElement('h5');
            title.className = 'card-title editable-filename';
            title.textContent = customName;
            title.style.cursor = 'pointer';
            
            title.addEventListener('click', () => {
                const input = document.createElement('input');
                input.type = 'text';
                input.value = item.customName;
                input.className = 'form-control';
                input.style.width = '80%';
            
                // 替换标题为输入框
                cardBody.replaceChild(input, title);
                input.focus();
            
                // 保存新名称
                let hasSaved = false;
                const saveName = () => {
                    if (hasSaved) return;
                    hasSaved = true;
                    let newName = input.value.trim() || file.name;
                    const originalExtension = file.name.substring(file.name.lastIndexOf('.'));
                    if (!newName.includes('.')) {
                        newName += originalExtension;
                    }
                    item.customName = newName;
                    title.textContent = newName;
                    if (cardBody.contains(input)) {
                        cardBody.replaceChild(title, input);
                    }
                };
            
                // 监听 Enter 键和失去焦点
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        saveName();
                    }
                });
            
                input.addEventListener('blur', saveName);
            });
            
            // 在文件名下方添加提示文字
            const hint = document.createElement('p');
            hint.className = 'text-muted small mb-1';
            hint.textContent = '点击文件名可自定义编辑';
            
            // 将标题和提示文字添加到卡片主体
            cardBody.appendChild(title);
            cardBody.appendChild(hint);
    
            const type = document.createElement('p');
            type.className = 'card-text';
            type.innerHTML = `<strong>类型：</strong> ${escapeHtml(file.type) || 'N/A'}`;
    
            const size = document.createElement('p');
            size.className = 'card-text';
            size.innerHTML = `<strong>大小：</strong> ${formatFileSize(file.size)}`;
    
            cardBody.appendChild(title);
            cardBody.appendChild(type);
            cardBody.appendChild(size);
    
            // 如果是图片类型，添加图片预览
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'card-img-top';
                img.alt = '预览图片';
                img.style.maxHeight = '200px';
                img.style.objectFit = 'cover';
    
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                }
                reader.readAsDataURL(file);
    
                card.appendChild(img);
            }
    
            card.appendChild(cardBody);
            previewCard.appendChild(card);
            previewContainer.appendChild(previewCard);
    
            // 显示文件预览区域
            filePreview.style.display = 'block';
        }
    
        // 重置文件预览
        function resetPreview() {
            selectedFiles = [];
            previewContainer.innerHTML = '';
            filePreview.style.display = 'none';
            fileInput.value = '';
        }
    
        // 转义 HTML 特殊字符，防止 XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    
        // 格式化文件大小
        function formatFileSize(bytes) {
            bytes = Number(bytes);
            if (isNaN(bytes)) return '未知大小';
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    
        // 显示提示信息
        function displayMessage(message, type) {
            const messageContainer = document.getElementById('message-container');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
            `;
            messageContainer.appendChild(alertDiv);
    
            // 自动移除提示信息 after 3 seconds
            setTimeout(() => {
                alertDiv.classList.remove('show');
                alertDiv.classList.add('hide');
                alertDiv.remove();
            }, 3000);
        }
    });
    </script>
    <script>
    <?php if (!defined('IS_DEV') || !IS_DEV): ?>
    //localStorage.removeItem('update-toast-version'); // 测试环境下每次刷新都弹出
    // 仅生产环境下启用弹窗
    const APP_VERSION = <?php echo json_encode($app_version); ?>;
    const key = 'update-toast-version';
    const lastVersion = localStorage.getItem(key);

    document.addEventListener('DOMContentLoaded', function() {
        if (lastVersion !== APP_VERSION) {
            const toast = document.getElementById('update-toast');
            const content = document.getElementById('update-toast-content');
            const closeBtn = document.getElementById('update-toast-close');
            content.innerHTML = `
                版本已更新为 <b>V${APP_VERSION}</b>
                <span id="view-update-log" style="
                    display: inline-block;
                    color: #fff;
                    background: linear-gradient(90deg,#f093fb,#f5576c);
                    border-radius: 16px;
                    padding: 3px 16px;
                    margin-left: 8px;
                    cursor: pointer;
                    font-weight: 500;
                    box-shadow: 0 2px 8px rgba(240,147,251,0.12);
                    transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
                    text-decoration: none;
                "
                onmouseover="this.style.background='linear-gradient(90deg,#f5576c,#f093fb)';this.style.transform='scale(1.06)';"
                onmouseout="this.style.background='linear-gradient(90deg,#f093fb,#f5576c)';this.style.transform='scale(1)';"
                >点击查看更新</span>
                <div style="margin-top:12px;">
                    <div id="update-toast-progress" style="
                        width:100%;height:6px;background:#eee;border-radius:3px;overflow:hidden;">
                        <div id="update-toast-bar" style="
                            width:100%;height:100%;background:linear-gradient(90deg,#f093fb,#f5576c);transition:width 0.2s;"></div>
                    </div>
                </div>
            `;
            toast.style.display = 'block';
            setTimeout(() => toast.classList.add('toast-show'), 10);

            // 点击“点击查看更新”跳转
            document.getElementById('view-update-log').onclick = function() {
                localStorage.setItem(key, APP_VERSION);
                toast.classList.remove('toast-show');
                toast.classList.add('toast-hide');
                setTimeout(() => {
                    toast.style.display = 'none';
                    toast.classList.remove('toast-hide');
                    window.location.href = 'version_log';
                }, 300);
            };

            // 关闭按钮
            closeBtn.onclick = function() {
                toast.classList.remove('toast-show');
                toast.classList.add('toast-hide');
                localStorage.setItem(key, APP_VERSION);
                clearInterval(interval);
                setTimeout(() => {
                    toast.style.display = 'none';
                    toast.classList.remove('toast-hide');
                }, 300);
            };

            // 5秒进度条倒计时并同步关闭弹窗
            const bar = document.getElementById('update-toast-bar');
            let progress = 100;
            const interval = setInterval(() => {
                progress -= 1; // 每步1%，100步
                if (progress <= 0) {
                    progress = 0;
                    bar.style.width = progress + '%';
                    clearInterval(interval);
                    toast.classList.remove('toast-show');
                    toast.classList.add('toast-hide');
                    localStorage.setItem(key, APP_VERSION);
                    setTimeout(() => {
                        toast.style.display = 'none';
                        toast.classList.remove('toast-hide');
                    }, 300);
                } else {
                    bar.style.width = progress + '%';
                }
            }, 50);
        }
    });
    <?php endif; ?>
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function setThemeBasedOnTime() {
            // 当前登录用户的名字
            const currentUser = <?php echo json_encode($_SESSION['user']['name']); ?>;
            // 欢迎信息（深蓝色背景，加大字体）
            console.log(
                '%c🎉 欢迎 "%s"',
                'color: #fff; background: #223a5e; padding: 4px 12px; border-radius: 6px; font-size: 15px;',
                currentUser
            );
            const hour = new Date().getHours();
            // 当前时间（深紫色背景，默认字体大小）
            console.log(
                '%c⏰ 当前时间: ' + hour + ' 点',
                'color: #fff; background: #3a235e; padding: 4px 12px; border-radius: 6px;'
            );
            const body = document.body;
            const table = document.getElementById('main-table'); // 使用 ID 选择表格
    
            if (hour >= 5 && hour < 17) {
                // 日间主题（深绿色背景，默认字体大小）
                console.log(
                    '%c🌞 当前主题：日间',
                    'color: #fff; background: #225e3a; font-weight: bold; border-radius: 4px; padding: 2px 10px;'
                );
                body.classList.add('light-theme');
                body.classList.remove('dark-theme');
                if (table) {
                    table.classList.remove('table-dark');
                }
            } else {
                // 夜间主题（深棕色背景，默认字体大小）
                console.log(
                    '%c🌙 当前主题：夜间',
                    'color: #fff; background: #5e3a22; font-weight: bold; border-radius: 4px; padding: 2px 10px;'
                );
                body.classList.add('dark-theme');
                body.classList.remove('light-theme');
                if (table) {
                    table.classList.add('table-dark');
                }
            }
        }
    
        // 控制台显示页面加载时长和版权
        window.addEventListener('load', function() {
            const startTime = performance.timing.navigationStart;
            const endTime = Date.now();
            const loadTime = endTime - startTime;
            // 加载时长（深青色背景，默认字体大小）
            console.log(
                '%c🚀 页面加载时长: ' + loadTime + ' ms',
                'color: #fff; background: #225e5e; padding: 4px 12px; border-radius: 6px;'
            );
            // 版权（深红色背景，加大字体）
            console.log(
                '%c© 2024-%s 吉庆喆.牛图图传输. 版权所有.',
                'color: #fff; background: #5e2222; padding: 4px 12px; border-radius: 6px; font-size: 15px;',
                new Date().getFullYear()
            );
        });
    
        // 设置主题
        setThemeBasedOnTime();
    
        // 每小时检查一次以更新主题
        setInterval(setThemeBasedOnTime, 10 * 60 * 1000);
    });
    </script>
</body>
</html>