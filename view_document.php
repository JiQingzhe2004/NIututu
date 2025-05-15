<?php
// filepath: /f:/JiQingzhe/文件传输/view_document.php

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

// 定义支持的Office文件类型
$office = ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];

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

// 转换 Office 文件为 PDF
$isOfficeFile = in_array($fileExtension, $office);
$pdfPath = '';
if ($isOfficeFile) {
    $pdfPath = $filepath . '.pdf';
    if (!file_exists($pdfPath)) {
        // 转换函数
        function convertToPDF($inputPath, $outputPath) {
            // 使用 LibreOffice 命令行工具进行转换
            $command = "soffice --headless --convert-to pdf --outdir " . escapeshellarg(dirname($outputPath)) . " " . escapeshellarg($inputPath);
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                return false;
            }
            
            return file_exists($outputPath);
        }

        if (!convertToPDF($filepath, $pdfPath)) {
            die('文件转换失败');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>查看文档 - <?php echo htmlspecialchars($file['original_name']); ?></title>
    <!-- 使用本地 Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- 集成 PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.7.107/pdf.min.js"></script>
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
        /* PDF 查看器样式 */
        #pdf-viewer {
            width: 100%;
            height: 80vh;
            border: 1px solid #ccc;
            margin-top: 20px;
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
        
        /* View Document 页面主题适配 */
        
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
            <?php if ($isOfficeFile && file_exists($pdfPath)): ?>
                <canvas id="pdf-viewer"></canvas>
            <?php else: ?>
                <p>无法预览此文件类型。</p>
            <?php endif; ?>
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

    <!-- 使用本地 Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($isOfficeFile && file_exists($pdfPath)): ?>
        // PDF.js 初始化代码
        const url = '<?php echo htmlspecialchars(str_replace('\\', '/', $pdfPath)); ?>';

        const pdfViewer = document.getElementById('pdf-viewer');

        const loadingTask = pdfjsLib.getDocument(url);
        loadingTask.promise.then(function(pdf) {
            // 获取第一页
            pdf.getPage(1).then(function(page) {
                const scale = 1.5;
                const viewport = page.getViewport({ scale: scale });

                // 准备canvas使用PDF页面尺寸
                const context = pdfViewer.getContext('2d');
                pdfViewer.height = viewport.height;
                pdfViewer.width = viewport.width;

                // 渲染PDF页面到canvas
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                page.render(renderContext);
            });
        }, function (reason) {
            console.error(reason);
            alert('无法加载PDF文件');
        });
        <?php endif; ?>
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
        });
    </script>
</body>
</html>