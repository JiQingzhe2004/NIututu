document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('upload-form');
    const fileListTable = document.getElementById('file-list');
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('file-input');
    const filePreview = document.getElementById('file-preview');
    const previewName = document.getElementById('preview-name');
    const previewType = document.getElementById('preview-type');
    const previewSize = document.getElementById('preview-size');
    const previewImage = document.getElementById('preview-image');
    let selectedFile = null;

    // 加载文件列表
    loadFileList();

    // 点击拖拽区域触发文件选择
    dropArea.addEventListener('click', () => {
        fileInput.click();
    });

    // 文件选择
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            handleFile(fileInput.files[0]);
        }
    });

    // 文件上传表单提交
    uploadForm.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!selectedFile) {
            displayMessage('请先选择一个文件。', 'danger');
            return;
        }

        const formData = new FormData();
        formData.append('file', selectedFile);

        fetch('file_manager.php', {
            method: 'POST',
            body: formData,
            credentials: 'include' // 确保请求包含会话信息
        })
            .then(response => response.text())
            .then(data => {
                // 显示提示信息
                displayMessage(data, 'success');
                // 重新加载文件列表
                loadFileList();
                // 重置表单和预览
                uploadForm.reset();
                resetPreview();
            })
            .catch(error => {
                console.error('文件上传失败:', error);
                displayMessage('文件上传失败，请稍后重试。', 'danger');
            });
    });

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
                    const row = `
                        <tr>
                            <td>${escapeHtml(file.original_name)}</td>
                            <td class="file-size">${formatFileSize(file.size)}</td>
                            <td class="upload-time">${escapeHtml(file.upload_time)}</td>
                            <td class="uploader-name">${escapeHtml(file.uploader_name)}</td>
                            <td>
                                <a href="${escapeHtml(file.path)}" class="btn btn-success btn-sm" download>下载</a>
                                <button class="btn btn-danger btn-sm" onclick="deleteFile(${file.id})">删除</button>
                            </td>
                        </tr>
                    `;
                    fileListTable.innerHTML += row;
                });
            })
            .catch(error => {
                console.error('加载文件列表失败:', error);
                displayMessage('加载文件列表失败，请刷新页面重试。', 'danger');
            });
    }

    // 删除文件
    window.deleteFile = function (fileId) {
        if (confirm('确定要删除这个文件吗？')) {
            fetch(`delete_file.php?id=${fileId}`, {
                method: 'GET',
                credentials: 'include' // 确保请求包含会话信息
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
            if (files.length > 1) {
                displayMessage('一次只能上传一个文件。', 'warning');
                return;
            }

            handleFile(files[0]);
        }
    }

    // 处理选中文件并显示预览
    function handleFile(file) {
        // 阻止上传 .php 文件
        const fileName = file.name.toLowerCase();
        if (fileName.endsWith('.php')) {
            displayMessage('不允许上传 PHP 文件。', 'warning');
            resetPreview();
            return;
        }

        const maxSize = 300 * 1024 * 1024; // 300MB
        if (file.size > maxSize) {
            displayMessage('文件大小超过限制 (最大300MB)。', 'warning');
            resetPreview();
            return;
        }

        selectedFile = file;
        previewName.textContent = file.name;
        previewType.textContent = file.type || 'N/A';
        previewSize.textContent = formatFileSize(file.size);

        // 如果是图片类型，显示预览
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            previewImage.style.display = 'none';
        }

        filePreview.style.display = 'block';
    }

    // 重置文件预览
    function resetPreview() {
        selectedFile = null;
        previewName.textContent = '';
        previewType.textContent = '';
        previewSize.textContent = '';
        previewImage.src = '#';
        previewImage.style.display = 'none';
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

        // 自动移除提示信息 after 5 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            alertDiv.classList.add('hide');
            alertDiv.remove();
        }, 5000);
    }
});