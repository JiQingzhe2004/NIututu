<?php
// cleanup.php

require 'config.php';

date_default_timezone_set('Asia/Shanghai');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'get_log') {
        $logFile = __DIR__ . 'execution.log';
        if (file_exists($logFile)) {
            echo file_get_contents($logFile);
        } else {
            echo "日志文件不存在。";
        }
        exit;
    } elseif ($_GET['action'] === 'init_db') {
        try {
            $sqlFile = __DIR__ . 'up.SQL';
            if (!file_exists($sqlFile)) {
                throw new Exception('SQL文件不存在');
            }
            
            $sql = file_get_contents($sqlFile);
            if ($sql === false || trim($sql) === '') {
                throw new Exception('SQL文件为空或读取失败');
            }
            
            $pdo->exec($sql);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    exit;
}

// 定义要检查的所有表
$tables = ['announcements', 'files', 'Messages', 'users', 'remember_tokens', 'audit_logs'];
$tableStatus = [];
$hasError = false;
$allMissing = true;

try {
    // 添加2秒延迟
    sleep(2);
    
    // 检测日志
    $detectLog = date('Y-m-d H:i:s') . " - 正在检测表状态: ";
    $detectLogArr = [];

    foreach ($tables as $table) {
        try {
            $checkStmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            $exists = ($checkStmt && $checkStmt->rowCount() > 0);
            $tableStatus[$table] = $exists;
            $detectLogArr[] = "{$table}:" . ($exists ? "存在" : "不存在");
            if ($exists) {
                $allMissing = false;
            }
        } catch (PDOException $e) {
            $tableStatus[$table] = false;
            $hasError = true;
            $detectLogArr[] = "{$table}:检测出错";
            file_put_contents('execution.log', date('Y-m-d H:i:s') . " - 检查表 {$table} 时出错: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    $detectLog .= implode(', ', $detectLogArr) . "\n";
    file_put_contents('execution.log', $detectLog, FILE_APPEND);

    if (!$hasError) {
        $allExist = !in_array(false, $tableStatus, true);
        if ($allExist) {
            $msg = date('Y-m-d H:i:s') . " - 数据库已初始化。\n";
            file_put_contents('execution.log', $msg, FILE_APPEND);
        }
    }

} catch (PDOException $e) {
    $errorMessage = date('Y-m-d H:i:s') . " - 错误: " . $e->getMessage() . "\n";
    file_put_contents('execution.log', $errorMessage, FILE_APPEND);
    $hasError = true;
    $tableStatus = array_fill_keys($tables, false);
}
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="/static/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/static/favicon.svg" />
    <link rel="shortcut icon" href="/static/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/static/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="牛图图传输" />
    <link rel="manifest" href="/static/site.webmanifest" />
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <title>SQL 执行记录</title>
    <style>
        /* 保持原有样式不变 */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; background: #f5f7fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a202c; padding: 20px; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); padding: 32px; }
        h1 { font-size: 24px; font-weight: 600; color: #2d3748; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
        .table-status { width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 14px; }
        .table-status th { background: #f8fafc; padding: 12px 16px; text-align: left; font-weight: 600; color: #4a5568; border-bottom: 2px solid #e2e8f0; }
        .table-status td { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; }
        .table-status tr:hover { background: #f8fafc; }
        .ok { color: #047857; display: inline-flex; align-items: center; font-size: 14px; font-weight: 500; }
        .ok:before { content: "●"; margin-right: 6px; }
        .fail { color: #dc2626; display: inline-flex; align-items: center; font-size: 14px; font-weight: 500; }
        .fail:before { content: "●"; margin-right: 6px; }
        #log { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; height: 300px; overflow-y: auto; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; line-height: 1.6; color: #4a5568; }
        #log::-webkit-scrollbar { width: 6px; height: 6px; }
        #log::-webkit-scrollbar-track { background: #f1f5f9; }
        #log::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        #log::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        @keyframes pulse { 0% { opacity: 0.4; } 50% { opacity: 0.8; } 100% { opacity: 0.4; } }
        @keyframes spin { 0% { transform: translate(-50%, -50%) rotate(0deg);} 100% { transform: translate(-50%, -50%) rotate(360deg);} }
        @keyframes skeleton { 0% { background-color: #e2e8f0; } 50% { background-color: #f1f5f9; } 100% { background-color: #e2e8f0; } }
        .skeleton { animation: skeleton 1.2s infinite ease-in-out; border-radius: 4px; background-color: #e2e8f0; }
        .skeleton-row td { height: 40px; padding: 0 16px; }
        .skeleton-block { display: inline-block; height: 18px; width: 100px; margin: 0 auto; }
        .skeleton-log { width: 100%; height: 18px; margin-bottom: 8px; }
        .loading { z-index: 1; background: #e2e8f0; color: transparent !important; animation: pulse 1.5s infinite; border-radius: 4px; user-select: none; min-height: 18px; position: relative; }
        .loading::after { content: ''; display: inline-block; width: 18px; height: 18px; border: 3px solid #cbd5e1; border-top: 3px solid #3b82f6; border-radius: 50%; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); animation: spin 1s linear infinite; }
        .init-btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: background 0.2s; margin: 20px 0; }
        .init-btn:hover { background: #2563eb; }
        .init-btn:disabled { background: #94a3b8; cursor: not-allowed; }
        .text-center { text-align: center; }
        @media (max-width: 768px) {
            .container { padding: 20px; margin: 0; }
            h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>数据库初始化状态</h1>
        <?php if ($allMissing): ?>
            <div class="text-center">
                <p style="margin-bottom: 16px;">检测到数据库未初始化</p>
                <button class="init-btn" onclick="initDatabase()">初始化数据库</button>
            </div>
        <?php endif; ?>
        <table class="table-status">
            <thead>
                <tr>
                    <th>数据表</th>
                    <th>状态</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <!-- 检查中时用JS动态填充 -->
            </tbody>
        </table>
        <div id="log"></div>
    </div>
    <script>
        // 骨架屏动画
        function showSkeleton() {
            const tables = <?php echo json_encode($tables); ?>;
            let html = '';
            for (let t of tables) {
                html += `<tr class="skeleton-row">
                    <td><span class="skeleton skeleton-block" style="width:80px;"></span></td>
                    <td><span class="skeleton skeleton-block"></span></td>
                </tr>`;
            }
            document.getElementById('table-body').innerHTML = html;

            let logHtml = '';
            for (let i = 0; i < 8; i++) {
                logHtml += `<div class="skeleton skeleton-log"></div>`;
            }
            document.getElementById('log').innerHTML = logHtml;
        }

        // 检查结束后填充真实内容
        function showResult() {
            fetchLog();
        }

        function fetchLog() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '?action=get_log', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('log').innerText = xhr.responseText;
                } else {
                    document.getElementById('log').innerText = '无法加载日志。';
                }
            };
            xhr.onerror = function() {
                document.getElementById('log').innerText = '日志请求出错。';
            };
            xhr.send();
        }

        function initDatabase() {
            const btn = document.querySelector('.init-btn');
            if (btn) btn.disabled = true;
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '?action=init_db', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert('初始化失败：' + response.error);
                        if (btn) btn.disabled = false;
                    }
                }
            };
            xhr.send();
        }

        // 页面加载时先显示骨架屏2秒，再显示真实内容
        showSkeleton();
        setTimeout(() => {
            document.getElementById('table-body').innerHTML = 
                `<?php
                ob_start();
                foreach ($tables as $table): ?>
                <tr>
                    <td><?php echo htmlspecialchars($table); ?></td>
                    <td>
                        <?php if (!empty($tableStatus[$table])): ?>
                            <span class="ok">已完成初始化</span>
                        <?php else: ?>
                            <span class="fail">未初始化</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach;
                echo trim(ob_get_clean());
                ?>`;
            showResult();
        }, 2000);
    </script>
</body>
</html>