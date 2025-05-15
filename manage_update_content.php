<?php
session_start();
require 'config.php';

// 检查用户是否已登录并是管理员
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateContent = $_POST['update_content'] ?? '';
    $updateEnabled = isset($_POST['update_enabled']) ? 1 : 0;

    if (!empty($updateContent)) {
        try {
            // 检查是否已有记录
            $stmt = $pdo->query('SELECT COUNT(*) FROM announcements');
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                // 更新现有记录
                $stmt = $pdo->prepare('UPDATE announcements SET update_content = ?, update_enabled = ? ORDER BY created_at DESC LIMIT 1');
            } else {
                // 插入新记录
                $stmt = $pdo->prepare('INSERT INTO announcements (update_content, update_enabled) VALUES (?, ?)');
            }

            $stmt->execute([$updateContent, $updateEnabled]);
            $message = '更新内容已保存。';
        } catch (PDOException $e) {
            error_log('数据库错误: ' . $e->getMessage());
            $message = '保存更新内容时发生错误。';
        }
    } else {
        $message = '更新内容不能为空。';
    }
}

// 获取最新的更新内容和启用状态
try {
    $stmt = $pdo->query('SELECT update_content, update_enabled FROM announcements ORDER BY created_at DESC LIMIT 1');
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC) ?? ['update_content' => '', 'update_enabled' => 0];
} catch (PDOException $e) {
    error_log('数据库错误: ' . $e->getMessage());
    $announcement = ['update_content' => '', 'update_enabled' => 0];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link href="favicon.ico" rel="icon">
    <title>管理更新内容</title>
    <!-- 引入 Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- 引入 Quill 样式 -->
    <link href="css/quill.snow.css" rel="stylesheet">
    <!-- 自定义样式，添加带背景色文字的内边距和圆角 -->
    <style>
    /* 自定义编辑器内带背景色文字的样式 */
    #editor span[style*="background-color"] {
        padding: 2px !important;
        border-radius: 5px !important;
    }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mt-5">管理更新内容</h1>
    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="editor" class="form-label">更新内容</label>
            <!-- Quill 编辑器容器 -->
            <div id="editor"><?php echo $announcement['update_content']; ?></div>
            <!-- 隐藏的 textarea，用于提交数据 -->
            <textarea name="update_content" id="update_content" style="display: none;"></textarea>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="update_enabled" name="update_enabled" <?php echo $announcement['update_enabled'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="update_enabled">启用更新内容提示</label>
        </div>
        <button type="submit" class="btn btn-primary">保存</button>
    </form>

    <div class="mt-5">
        <h2>当前更新内容</h2>
        <div class="border p-3">
            <?php echo $announcement['update_content']; ?>
        </div>
    </div>
</div>

<!-- 引入 Quill JS -->
<script src="js/quill.js"></script>
<script>
    // 自定义工具栏选项，包含所有工具
    var toolbarOptions = [
        ['bold', 'italic', 'underline', 'strike'],        // 粗体、斜体、下划线、删除线
        ['blockquote', 'code-block'],                     // 引用、代码块

        [{'header': 1}, {'header': 2}],                   // 标题，键值对的形式
        [{'list': 'ordered'}, {'list': 'bullet'}],        // 列表
        [{'script': 'sub'}, {'script': 'super'}],         // 上标、下标
        [{'indent': '-1'}, {'indent': '+1'}],             // 缩进
        [{'direction': 'rtl'}],                           // 文字方向

        [{'size': ['small', false, 'large', 'huge']}],    // 字体大小
        [{'header': [1, 2, 3, 4, 5, 6, false]}],          // 标题等级

        [{'color': []}, {'background': []}],              // 颜色选择器
        [{'font': []}],                                   // 字体
        [{'align': []}],                                  // 对齐方式

        ['link', 'image', 'video', 'formula'],            // 链接、图片、视频、公式

        ['clean']                                         // 清除格式
    ];

    // 初始化 Quill 编辑器，设置自定义工具栏
    var quill = new Quill('#editor', {
        modules: {
            toolbar: toolbarOptions
        },
        theme: 'snow'
    });

    // 将编辑器内容同步到隐藏的 textarea 中
    var form = document.querySelector('form');
    form.onsubmit = function() {
        var updateContent = document.querySelector('textarea[name=update_content]');
        updateContent.value = quill.root.innerHTML;
    };

    // 设置工具栏按钮的提示文本为中文
    var tooltips = {
        'bold': '加粗',
        'italic': '斜体',
        'underline': '下划线',
        'strike': '删除线',
        'blockquote': '引用',
        'code-block': '代码块',
        'header': '标题',
        'list': '列表',
        'script': '脚本',
        'indent': '缩进',
        'direction': '方向',
        'size': '字体大小',
        'color': '颜色',
        'background': '背景色',
        'font': '字体',
        'align': '对齐',
        'link': '链接',
        'image': '图片',
        'video': '视频',
        'formula': '公式',
        'clean': '清除格式'
    };

    // 为每个工具栏按钮添加中文提示
    document.querySelectorAll('.ql-toolbar button').forEach(function(button) {
        var classes = Array.from(button.classList);
        var tooltipSet = false;

        classes.forEach(function(cls) {
            if (cls.startsWith('ql-')) {
                var format = cls.replace('ql-', '').split('-')[0];
                if (tooltips[format]) {
                    button.setAttribute('title', tooltips[format]);
                    tooltipSet = true;
                }
            }
        });

        // 处理特殊情况，如 ql-header-*、ql-size-* 等
        if (!tooltipSet) {
            if (button.classList.contains('ql-header')) {
                button.setAttribute('title', '标题');
            } else if (button.classList.contains('ql-size')) {
                button.setAttribute('title', '字体大小');
            }
        }
    });

    // 监听文本变化事件，为带背景色的文字添加内边距和圆角
    quill.on('text-change', function(delta, oldDelta, source) {
        if (source === 'user') {
            const spans = quill.root.querySelectorAll('span[style*="background-color"]');
            spans.forEach(function(span) {
                // 获取现有的样式
                let style = span.getAttribute('style') || '';
                // 添加内边距和圆角
                if (!style.includes('padding')) {
                    style += ' padding: 2px;';
                }
                if (!style.includes('border-radius')) {
                    style += ' border-radius: 5px;';
                }
                span.setAttribute('style', style);
            });
        }
    });
</script>
<!-- 引入 Bootstrap JS -->
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>