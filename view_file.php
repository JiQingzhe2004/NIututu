<?php
// view_file.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'check_login.php';
require 'config.php';

// 获取文件ID
if (!isset($_GET['id'])) {
    die('缺少文件ID');
}

$fileId = intval($_GET['id']);

// 查询文件信息并获取上传者的名字
$stmt = $pdo->prepare('SELECT files.path, files.type, files.original_name, files.size, files.upload_time, users.name AS uploader_name 
                       FROM files 
                       JOIN users ON files.user_id = users.id 
                       WHERE files.id = ?');
$stmt->execute([$fileId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die('文件不存在');
}

$filepath = realpath(__DIR__ . '/' . $file['path']);
if (!$filepath || !file_exists($filepath)) {
    die('文件不存在');
}

// 获取文件扩展名
$fileExtension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));

// 定义支持的媒体类型（仅图片和视频）
$images = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
$videos = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'mkv'];
$audios = ['mp3', 'wav', 'ogg', 'flac', 'aac'];

// 格式化文件大小函数
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' Bytes';
    }
}
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
    <title>查看文件 - <?php echo htmlspecialchars($file['original_name']); ?></title>
    <!-- 使用本地 Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 100%;
        }
        .file-view {
            margin-top: 20px;
            text-align: center;
        }
        .file-name {
            word-break: break-all;
            font-size: 2rem;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .file-details {
            margin-top: 20px;
            font-size: 1.2rem;
            max-width: 800px; /* 设置最大宽度 */
            margin-left: auto;
            margin-right: auto;
        }
        .detail-item strong {
            display: inline-block;
            width: 120px; /* 设置标题的固定宽度 */
            text-align: right; /* 标题右对齐 */
            margin-right: 10px; /* 标题与数据之间的间距 */
        }
        .detail-item span {
            text-align: left; /* 数据左对齐 */
        }
        .media-content img,
        .media-content video {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            object-fit: contain;
        }
        .custom-player {
            padding: 20px;
            border-radius: 12px;
            background: linear-gradient(145deg, rgb(212 185 255), rgb(184 184 184));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        body.dark-theme .custom-player {
            background: linear-gradient(145deg, #21674d, #4a3770);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.05);
        }
        /* 自定义灯箱样式 */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
        }
        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        .lightbox-content img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }
        .lightbox-close {
            position: absolute;
            top: 10px;
            right: 25px;
            color: #fff;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-secondary {
            transition: background-color 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        /* 横向文件详情布局优化 */
        @media (min-width: 576px) {
            .file-details {
                display: flex;
                justify-content: center; /* 居中对齐 */
            }
            .file-details .detail-item {
                display: flex;
                align-items: center;
            }
            .file-details .detail-item strong {
                width: 120px; /* 设置标题的固定宽度 */
                flex-shrink: 0; /* 防止标题宽度缩小 */
                margin-right: 10px; /* 标题与数据之间的间距 */
                text-align: right; /* 标题右对齐 */
            }
            .file-details .detail-item span {
                text-align: left; /* 数据左对齐 */
            }
        }
        
        /* 全局背景色适配 */
        html.light-theme {
            background-color: #ffffff;
        }
        
        html.dark-theme {
            background-color: #212529;
        }
        
        /* 全局主题样式 */
        body.light-theme {
            background-color: #ffffff;
            color: #212529;
        }
        
        body.dark-theme {
            background-color: #212529;
            color: #f8f9fa;
        }
        
        /* Change Password 页面主题适配 */
        .change-password-container {
            padding: 20px;
            margin-top: 50px;
        }
        
        body.light-theme .change-password-container {
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        body.dark-theme .change-password-container {
            background-color: #343a40;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
        }
        
        /* 按钮样式适配 */
        body.light-theme .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        body.light-theme .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        
        body.dark-theme .btn-primary {
            background-color: #375a7f;
            border-color: #375a7f;
        }
        
        body.dark-theme .btn-primary:hover {
            background-color: #2e4a6f;
            border-color: #2c456c;
        }
        
        /* 关闭按钮颜色适配 */
        body.dark-theme .btn-close {
            filter: invert(1);
        }
        
        /* View File 页面主题适配 */
        
        body.light-theme .file-name {
            color: #212529;
        }
        
        body.dark-theme .file-name {
            color: #f8f9fa;
        }
        
        body.light-theme .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        body.dark-theme .btn-secondary {
            background-color: #5a6268;
            border-color: #5a6268;
        }
        
        body.light-theme .file-details strong {
            color: #212529;
        }
        
        body.dark-theme .file-details strong {
            color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="file-view">
            <div class="media-content mb-4">
                <?php
                if (in_array($fileExtension, $images)) {
                    echo '<img src="' . htmlspecialchars($file['path']) . '" alt="图片预览" class="img-fluid" id="previewImage" loading="lazy">';
                } elseif (in_array($fileExtension, $videos)) {
                    echo '<video controls class="img-fluid" loading="lazy">
                            <source src="' . htmlspecialchars($file['path']) . '" type="' . htmlspecialchars($file['type']) . '">
                            您的浏览器不支持视频标签。
                        </video>';
                } elseif (in_array($fileExtension, $audios)) {
                    echo '
                    <div class="custom-player">
                        <div id="waveform"></div>
                        <div class="wave-controls d-flex justify-content-between align-items-center mt-3">
                            <button id="playPause" class="btn btn-outline-primary rounded-circle" style="width: 48px; height: 48px;">
                                <svg id="playIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M6 4.5v7l6-3.5-6-3.5z"/>
                                </svg>
                                <svg id="pauseIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" style="display: none;">
                                    <path d="M5.5 3.5A.5.5 0 0 1 6 3h1a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5H6a.5.5 0 0 1-.5-.5v-9zm4 0A.5.5 0 0 1 10 3h1a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-9z"/>
                                </svg>
                            </button>
                            <span id="currentTime">00:00</span> / <span id="duration">00:00</span>
                            <input type="range" id="volume" min="0" max="1" step="0.01" value="1" style="width: 120px;">
                        </div>
                    </div>';
                } else {
                    echo '<p>无法预览此文件类型。</p>';
                }
                ?>
            </div>
            <hr>
            <div class="file-header d-flex justify-content-between align-items-center mb-4">
                <div class="file-name">
                    <?php echo htmlspecialchars($file['original_name']); ?>
                </div>
                <a href="<?php echo htmlspecialchars($file['path']); ?>" download="<?php echo htmlspecialchars($file['original_name']); ?>" class="btn btn-primary">下载</a>
                <button class="btn btn-secondary" onclick="window.close()">返回</button>
            </div>
            <hr>
            <div class="file-details">
                <div class="detail-item">
                    <strong>上传者：</strong>
                    <span><?php echo htmlspecialchars($file['uploader_name']); ?></span>
                </div>
                <div class="detail-item">
                    <strong>文件大小：</strong>
                    <span><?php echo htmlspecialchars(formatFileSize($file['size'])); ?></span>
                </div>
                <div class="detail-item">
                    <strong>上传时间：</strong>
                    <span><?php echo htmlspecialchars($file['upload_time']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 自定义灯箱 -->
    <?php if (in_array($fileExtension, $images)): ?>
    <div id="lightbox" class="lightbox">
        <span class="lightbox-close">&times;</span>
        <div class="lightbox-content">
            <img src="<?php echo htmlspecialchars($file['path']); ?>" alt="放大图片">
        </div>
    </div>
    <?php endif; ?>

    <!-- 使用本地 Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/wavesurfer.js"></script>
    <script>
        // 获取灯箱元素
        var lightbox = document.getElementById('lightbox');
        var previewImage = document.getElementById('previewImage');
        var closeBtn = document.getElementsByClassName('lightbox-close')[0];

        // 点击图片打开灯箱
        if (previewImage) {
            previewImage.onclick = function() {
                lightbox.style.display = 'flex';
            }
        }

        // 点击关闭按钮关闭灯箱
        if (closeBtn) {
            closeBtn.onclick = function() {
                lightbox.style.display = 'none';
            }
        }

        // 点击灯箱背景关闭灯箱
        if (lightbox) {
            lightbox.onclick = function(event) {
                if (event.target == lightbox) {
                    lightbox.style.display = 'none';
                }
            }
        }
    </script>
    <script>
        const wavesurfer = WaveSurfer.create({
            container: '#waveform',
            waveColor: '#dee2e6',
            progressColor: '#0d6efd',
            height: 80,
            barWidth: 2,
            responsive: true
        });

        wavesurfer.load('<?php echo htmlspecialchars($file['path']); ?>');

        const playBtn = document.getElementById('playPause');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');
        const currentTime = document.getElementById('currentTime');
        const duration = document.getElementById('duration');
        const volumeSlider = document.getElementById('volume');

        wavesurfer.on('ready', () => {
            duration.textContent = formatTime(wavesurfer.getDuration());
        });

        wavesurfer.on('audioprocess', () => {
            currentTime.textContent = formatTime(wavesurfer.getCurrentTime());
        });

        wavesurfer.on('seek', () => {
            currentTime.textContent = formatTime(wavesurfer.getCurrentTime());
        });

        playBtn.addEventListener('click', () => {
            wavesurfer.playPause();
            const isPlaying = wavesurfer.isPlaying();
            playIcon.style.display = isPlaying ? 'none' : 'inline';
            pauseIcon.style.display = isPlaying ? 'inline' : 'none';
        });

        volumeSlider.addEventListener('input', () => {
            wavesurfer.setVolume(volumeSlider.value);
        });

        function formatTime(sec) {
            const m = Math.floor(sec / 60).toString().padStart(2, '0');
            const s = Math.floor(sec % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        }
    </script>
    <script>
        // theme-switcher.js
        document.addEventListener('DOMContentLoaded', function() {
            function setThemeBasedOnTime() {
                const hour = new Date().getHours();
                const body = document.body;
                const html = document.documentElement;
                const tables = document.querySelectorAll('.table-dark');
                const announcement = document.querySelector('.announcement');
        
                if (hour >= 5 && hour < 17) {
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
        });
    </script>
</body>
</html>