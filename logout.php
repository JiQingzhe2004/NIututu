<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 启动会话
session_start();

// 包含数据库配置
require 'config.php';

// 记录审计日志函数
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

if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    $username = $_SESSION['user']['username'];
    $currentToken = $_COOKIE['remember_token'] ?? null;
    
    try {
        // 1. 记录注销审计日志
        logAction($pdo, $userId, 'logout', [
            'username' => $username,
            'logout_type' => 'manual',
            'device_specific' => true
        ]);
        
        // 2. 只删除当前设备的remember_token（如果存在）
        if ($currentToken) {
            $stmt = $pdo->prepare('
                DELETE FROM remember_tokens 
                WHERE user_id = ? AND token = ?
            ');
            $stmt->execute([$userId, $currentToken]);
        }
        
        // 3. 清除当前会话数据
        $_SESSION = array();
        
        // 4. 删除会话cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // 5. 清除当前设备的remember_token cookie
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
    } catch (Exception $e) {
        error_log("注销过程中发生错误: " . $e->getMessage());
    }
}

// 销毁会话
session_destroy();

// 重定向到登录页面
header('Location: login.php');
exit();
?>