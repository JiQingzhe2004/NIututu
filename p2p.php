<?php
// filepath: /f:/JiQingzhe/文件传输/p2p.php

// 设置时区为上海
date_default_timezone_set('Asia/Shanghai');

// 启用错误报告（开发环境中使用，生产环境请关闭）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 读取配置文件并建立数据库连接
require 'config.php';

// 确保已启动会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$userId = $user['id'];

// 处理AJAX请求获取新消息
if (isset($_GET['action']) && $_GET['action'] === 'fetch_messages' && isset($_GET['peer'])) {
    $peer_id_ajax = intval($_GET['peer']);
    $last_message_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    if ($peer_id_ajax > 0) {
        if ($last_message_id > 0) {
            $stmt = $pdo->prepare('
                SELECT m.*, u.name AS sender_name
                FROM Messages m
                JOIN users u ON m.sender_id = u.id
                WHERE ((m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?))
                   AND m.id > ?
                ORDER BY m.timestamp ASC
            ');
            $stmt->execute([$userId, $peer_id_ajax, $peer_id_ajax, $userId, $last_message_id]);
        } else {
            $stmt = $pdo->prepare('
                SELECT m.*, u.name AS sender_name
                FROM Messages m
                JOIN users u ON m.sender_id = u.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.timestamp ASC
            ');
            $stmt->execute([$userId, $peer_id_ajax, $peer_id_ajax, $userId]);
        }
        $messages_ajax = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($messages_ajax);
    }
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'send' && isset($_POST['receiver_id']) && isset($_POST['message'])) {
            // 发送消息
            $receiver_id = intval($_POST['receiver_id']);
            $message = trim($_POST['message']);

            if ($receiver_id == $userId) {
                $error = '无法向自己发送消息。';
            } elseif (!empty($message)) {
                // 插入消息到数据库
                $stmt = $pdo->prepare('INSERT INTO Messages (sender_id, receiver_id, content, timestamp) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$userId, $receiver_id, $message]);

                header('Location: p2p.php?peer=' . $receiver_id);
                exit();
            } else {
                $error = '消息内容不能为空。';
            }
        }
    }
}

// 获取所有用户（排除当前用户）
$stmt = $pdo->prepare('SELECT id, name FROM users WHERE id != ? ORDER BY name');
$stmt->execute([$userId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 确定聊天对象
$peer_id = isset($_GET['peer']) ? intval($_GET['peer']) : (count($users) > 0 ? $users[0]['id'] : 0);

// 获取聊天对象的用户名
$peer = [];
if ($peer_id > 0) {
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ?');
    $stmt->execute([$peer_id]);
    $peer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$peer) {
        $peer_id = 0;
    }
}

// 获取消息
if ($peer_id > 0) {
    $stmt = $pdo->prepare('
        SELECT m.*, u.name AS sender_name
        FROM Messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.timestamp ASC
    ');
    $stmt->execute([$userId, $peer_id, $peer_id, $userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $messages = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>点对点文字传输</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
        body {
            background-color: #f5f5f5;
            color: #000000;
            transition: background-color 0.3s, color 0.3s;
        }
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 80vh;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #ffffff;
        }
        @media (min-width: 768px) {
            .chat-container {
                flex-direction: row;
            }
        }
        .user-list {
            width: 100%;
            border-bottom: 1px solid #dee2e6;
            overflow-y: auto;
        }
        @media (min-width: 768px) {
            .user-list {
                width: 25%;
                border-right: 1px solid #dee2e6;
                border-bottom: none;
            }
        }
        .user-list .list-group-item {
            cursor: pointer;
        }
        .user-list .active {
            background-color: #007bff;
            border-color: #007bff;
        }
        .user-list .active a {
            color: #fff;
        }
        .chat-window {
            width: 100%;
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 768px) {
            .chat-window {
                width: 75%;
            }
        }
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            background-color: #ffffff;
        }
        .chat-box {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f5f5f5;
        }
        .chat-box {
            overflow-y: scroll;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none;  /* IE 10+ */
        }
        
        .chat-box::-webkit-scrollbar { /* WebKit */
            width: 0;
            height: 0;
        }
        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .message.sent {
            align-items: flex-end;
        }
        .message.received {
            align-items: flex-start;
        }
        .message .content {
            max-width: 60%;
            padding: 10px 15px;
            border-radius: 20px;
            position: relative;
            background-color: #f1f0f0;
            color: #000000;
            word-wrap: break-word;
        }
        .message.sent .content {
            background-color: #007bff;
            color: #ffffff;
        }
        .message .timestamp {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        .chat-footer {
            padding: 15px;
            border-top: 1px solid #dee2e6;
            background-color: #ffffff;
        }
        .chat-footer textarea {
            resize: none;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">点对点文字传输</h2>

    <div class="chat-container">
        <!-- 用户列表 -->
        <div class="user-list">
            <div class="card">
                <div class="card-header">用户列表</div>
                <div class="card-body p-0">
                    <?php if (count($users) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($users as $u): ?>
                                <li class="list-group-item <?= $u['id'] == $peer_id ? 'active' : '' ?>">
                                    <a href="p2p.php?peer=<?= htmlspecialchars($u['id']) ?>" class="<?= $u['id'] == $peer_id ? 'text-white' : '' ?>">
                                        <?= htmlspecialchars($u['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="p-3">暂无其他用户。</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 聊天窗口 -->
        <div class="chat-window">
            <?php if ($peer_id > 0 && $peer): ?>
                <div class="chat-header">
                    <h5>与 <?= htmlspecialchars($peer['name']) ?> 的聊天</h5>
                </div>
                <div class="chat-box" id="chat-box" data-last-id="<?= count($messages) > 0 ? end($messages)['id'] : 0 ?>">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php
                                $isSent = $msg['sender_id'] == $userId;
                                $messageClass = $isSent ? 'sent' : 'received';
                            ?>
                            <div class="message <?= $messageClass ?>">
                                <div class="content">
                                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                                </div>
                                <div class="timestamp">
                                    <?= date('Y-m-d H:i', strtotime($msg['timestamp'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>暂无消息。</p>
                    <?php endif; ?>
                </div>
                <div class="chat-footer">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="p2p.php?peer=<?= htmlspecialchars($peer_id) ?>">
                        <input type="hidden" name="action" value="send">
                        <input type="hidden" name="receiver_id" value="<?= htmlspecialchars($peer_id) ?>">
                        <div class="form-group">
                            <textarea class="form-control" id="message" name="message" rows="2" placeholder="输入消息..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">发送</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="chat-header">
                    <h5>请选择一个用户进行聊天</h5>
                </div>
                <div class="chat-box d-flex align-items-center justify-content-center">
                    <p>请选择左侧的用户开始聊天。</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 引入Bootstrap JS和依赖 -->
<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
    // 函数：转义HTML以防止XSS
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // 自动滚动到最新消息
    function scrollToBottom() {
        var chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    window.onload = function() {
        scrollToBottom();
    };

    <?php if ($peer_id > 0 && $peer): ?>
    // 定时获取新消息
    setInterval(function() {
        var chatBox = $('#chat-box');
        var lastId = chatBox.data('last-id');

        // 检查用户是否已滚动到接近底部
        var isAtBottom = chatBox[0].scrollHeight - chatBox.scrollTop() - chatBox.outerHeight() < 100;

        $.ajax({
            url: 'p2p.php',
            method: 'GET',
            data: { action: 'fetch_messages', peer: <?= json_encode($peer_id) ?>, last_id: lastId },
            dataType: 'json',
            success: function(data) {
                if (data.length > 0) {
                    data.forEach(function(msg) {
                        var messageClass = msg.sender_id == <?= json_encode($userId) ?> ? 'sent' : 'received';
                        var messageHtml = `
                            <div class="message ${messageClass}">
                                <div class="content">
                                    ${escapeHtml(msg.content).replace(/\n/g, '<br>')}
                                </div>
                                <div class="timestamp">
                                    ${formatTimestamp(msg.timestamp)}
                                </div>
                            </div>
                        `;
                        chatBox.append(messageHtml);
                        // 更新最后一条消息的ID
                        chatBox.data('last-id', msg.id);
                    });

                    // 如果用户原本处于底部，则自动滚动
                    if (isAtBottom) {
                        scrollToBottom();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    }, 3000); // 每3秒请求一次

    // 函数：格式化时间戳
    function formatTimestamp(ts) {
        var date = new Date(ts);
        var year = date.getFullYear();
        var month = ('0' + (date.getMonth()+1)).slice(-2);
        var day = ('0' + date.getDate()).slice(-2);
        var hours = ('0' + date.getHours()).slice(-2);
        var minutes = ('0' + date.getMinutes()).slice(-2);
        return `${year}-${month}-${day} ${hours}:${minutes}`;
    }
    <?php endif; ?>
</script>
</body>
</html>