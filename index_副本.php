<?php
session_start();
require 'check_login.php';
require 'config.php';
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
    <link href="favicon.ico" rel="icon">
    <link href="favicon.ico" rel="icon">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>文件管理系统</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 自定义样式 */
        body {
            background-color: #f8f9fa;
        }
        h1 {
            margin-bottom: 30px;
        }
        .table thead th {
            background-color: #343a40;
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

        /* 响应式隐藏列 */
        @media (max-width: 767.98px) {
            .file-size, .upload-time, .uploader-name {
                display: none;
            }
            table thead tr th:nth-child(2),
            table thead tr th:nth-child(3),
            table thead tr th:nth-child(4),
            table thead tr th:nth-child(5), /* 隐藏第5列：访问权限 */
            table tbody tr td.file-size,
            table tbody tr td.upload-time,
            table tbody tr td.uploader-name,
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
        }

        /* 文件名单元格样式 */
        .filename-cell {
            max-width: 150px; /* 根据需要调整最大宽度 */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        /* 批量删除按钮样式 */
        #bulk-delete-btn {
            /* 根据需要自定义样式 */
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
    <div class="container py-4">
        <h1 class="text-center">内网文件管理系统</h1>

        <!-- 显示欢迎信息和退出按钮 -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <span>欢迎，<?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                <!-- 添加加载中的图标，初始隐藏 -->
                <div id="upload-loading" class="spinner-border spinner-border-sm text-primary ms-1" role="status" style="display: none;">
                    <span class="visually-hidden">加载中...</span>
                </div>
            </div>
            <div>
                <!-- 批量删除按钮 -->
                <div class="mb-3">
                    <button id="bulk-delete-btn" class="btn btn-danger" disabled>批量删除</button>
                </div>
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-sm btn-outline-secondary">管理用户</a>
                    <a href="manage_announcement.php" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">首页公告</a>
                    <a href="manage_update_content.php" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">更新提示</a>
                <?php endif; ?>
                <a href="change_password.php" class="btn btn-sm btn-outline-secondary">修改密码</a>
                <a href="logout.php" class="btn btn-sm btn-outline-secondary">退出登录</a>
            </div>
        </div>

        <!-- 拖拽上传区域 -->
        <div id="drop-area">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-cloud-upload" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M4.406 1.342A5.53 5.53 0 0 1 8 0c2.69 0 4.923 2 5.166 4.579C14.758 4.804 16 6.137 16 7.773 16 9.569 14.502 11 12.687 11H10a.5.5 0 0 1 0-1h2.688C13.979 10 15 8.988 15 7.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 2.825 10.328 1 8 1a4.53 4.53 0 0 0-2.941 1.1c-.757.652-1.153 1.438-1.153 2.055v.448l-.445.049C2.064 4.805 1 5.952 1 7.318 1 8.785 2.23 10 3.781 10H6a.5.5 0 0 1 0 1H3.781C1.708 11 0 9.366 0 7.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383"/>
            <path fill-rule="evenodd" d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708z"/>
            </svg>
            <span>拖拽文件到此处上传，或点击选择文件。</span>
            
            <!-- 访问权限选择 -->
            <div class="mt-3">
                <label class="form-label me-3">文件访问权限：</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="access" id="public" value="public">
                    <label class="form-check-label" for="public">公开</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="access" id="private" value="private" checked>
                    <label class="form-check-label" for="private">私密</label>
                </div>
            </div>
        </div>

        <!-- 文件上传表单 -->
        <div class="mb-4">
            <form id="upload-form" enctype="multipart/form-data">
                <input type="file" name="file[]" id="file-input" accept="*/*" multiple>
                <button type="submit" class="btn btn-primary">上传文件</button>
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

        <!-- 文件列表 -->
        <div class="table-responsive custom-rounded-table">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="select-all">
                        </th>
                        <th>文件名</th>
                        <th class="file-size">大小</th>
                        <th class="upload-time">上传时间</th>
                        <th class="uploader-name">上传者</th>
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
        <div class="text-center mt-3">
            <small>&copy; 2024-<?php echo date("Y"); ?> 吉庆喆.文件管理系统. 版权所有.</small>
        </div>
    </div>
        <script>
        // 现有代码...
    
        // 选择全部复选框功能
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.file-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            toggleBulkDeleteButton();
        });
    
        // 监听每个复选框的变化以控制“批量删除”按钮
        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('file-checkbox')) {
                toggleBulkDeleteButton();
                // 如果有未选中的复选框，取消“选择全部”复选框
                const allCheckboxes = document.querySelectorAll('.file-checkbox');
                const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                document.getElementById('select-all').checked = allChecked;
            }
        });
    
        // 控制“批量删除”按钮的启用状态
        function toggleBulkDeleteButton() {
            const anyChecked = document.querySelectorAll('.file-checkbox:checked').length > 0;
            document.getElementById('bulk-delete-btn').disabled = !anyChecked;
        }
    
        // 处理批量删除操作
        document.getElementById('bulk-delete-btn').addEventListener('click', function() {
            const selectedCheckboxes = document.querySelectorAll('.file-checkbox:checked');
            if (selectedCheckboxes.length === 0) return;
    
            const fileIds = Array.from(selectedCheckboxes).map(cb => cb.getAttribute('data-file-id'));
    
            if (confirm(`确定要删除选中的 ${fileIds.length} 个文件吗？`)) {
                fetch('bulk_delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ file_ids: fileIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessage('选中文件已成功删除。', 'success');
                        loadFileList();
                    } else {
                        displayMessage('删除失败：' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('批量删除失败:', error);
                    displayMessage('删除失败，请稍后重试。', 'danger');
                });
            }
        });
    
        // 现有代码...
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
            <p>自动关闭倒计时: <span id="countdown">60</span> 秒</p>
            <!-- 修改关闭按钮，添加红色边框 -->
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭" style="border: 2px solid red;"></button>
            <!-- 版权信息 -->
            <div class="text-center mt-3">
                <small>&copy; 2024-<?php echo date("Y"); ?> 吉庆喆.文件管理系统. 版权所有.</small>
            </div>
        </div>
        <script>
            // 设置倒计时初始值
            var timeLeft = 60;
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
    
    <!-- 引入脚本 -->
    <script src="js/bootstrap.bundle.min.js"></script>
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
        const currentUser = "<?php echo $_SESSION['username']; ?>";
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const uploadForm = document.getElementById('upload-form');
        const fileListTable = document.getElementById('file-list');
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('file-input');
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
            console.log(`查看文件ID: ${fileId}`);
            window.open(`view_file.php?id=${fileId}`, '_blank');
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
                    data.forEach(file => {
                        console.log(`文件ID: ${file.id}, 类型: ${file.type}`); // 调试信息
        
                        // 判断是否为图片或视频类型，添加查看按钮
                        const viewButton = (file.type && (file.type.startsWith('image/') || file.type.startsWith('video/'))) ?
                            `<button class="btn btn-info btn-sm me-1" onclick="viewFile(${file.id})">查看</button>` : '';
                        
                        console.log(`生成的查看按钮: ${viewButton}`); // 调试信息
        
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
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-unlock" viewBox="0 0 16 16">
                                        <path d="M11 1a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5V3a3 3 0 0 1 6 0v4a.5.5 0 0 1-1 0V3a2 2 0 0 0-2-2M3 8a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1z"/>
                                    </svg>`
                                }
                            </button>
                        `;
                
                        // 声明并初始化 isCurrentUser
                        const isCurrentUser = file.uploader_name.trim().toLowerCase() === currentUser.trim().toLowerCase();
                        console.log(`文件上传者: ${file.uploader_name}, 是否当前用户: ${isCurrentUser}`);
                        
                        const row = `
                            <tr>
                                <td>
                                    <input type="checkbox" class="file-checkbox" data-file-id="${file.id}">
                                </td>
                                <td class="filename-cell" title="${escapeHtml(file.original_name)}">${escapeHtml(file.original_name)}</td>
                                <td class="file-size">${formatFileSize(file.size)}</td>
                                <td class="upload-time">${escapeHtml(file.upload_time)}</td>
                                <td class="uploader-name">
                                    <span class="${isCurrentUser ? 'text-white bg-info rounded p-1' : ''}">
                                        ${escapeHtml(file.uploader_name)}
                                    </span>
                                </td>
                                <td>
                                    ${accessBadge}
                                    ${accessToggle}
                                </td>
                                <td>
                                    ${viewButton} <!-- 添加查看按钮 -->
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
            window.open(`view_file.php?id=${fileId}`, '_blank');
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
    
            console.log('处理文件:', file.name);
            // 阻止上传 .php 文件
            const fileName = file.name.toLowerCase();
            if (fileName.endsWith('.php')) {
                displayMessage('不允许上传 PHP 文件。', 'warning');
                return;
            }
    
            const maxSize = 500 * 1024 * 1024; // 500MB
            if (file.size > maxSize) {
                displayMessage('文件大小超过限制 (最大500MB)。', 'warning');
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
                const saveName = () => {
                    let newName = input.value.trim() || file.name;
            
                    // 获取原始文件的扩展名（包含点号）
                    const originalExtension = file.name.substring(file.name.lastIndexOf('.'));
            
                    // 检查新名称是否包含扩展名
                    if (!newName.includes('.')) {
                        // 如果没有扩展名，自动添加原始扩展名
                        newName += originalExtension;
                    }
            
                    item.customName = newName;
                    title.textContent = newName;
                    cardBody.replaceChild(title, input);
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
</body>
</html>