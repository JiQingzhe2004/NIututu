<?php
// config.php

require_once __DIR__ . '/vendor/autoload.php'; // 引入 Composer 自动加载

// 加载 .env 文件
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// 会话配置
session_start([
    'cookie_lifetime' => 0,            // 会话在浏览器关闭后失效
    'cookie_secure' => false,          // 如果使用 HTTPS，设置为 true
    'cookie_httponly' => true,         // 防止 JavaScript 访问会话 cookie
    'use_strict_mode' => true,         // 启用严格模式，防止会话劫持
    'use_only_cookies' => true,        // 仅使用 cookie 保存会话 ID
    'sid_length' => 48,                // 增加会话 ID 的长度
    'sid_bits_per_character' => 6,     // 增加会话 ID 的复杂度
]);

// 只从 .env 读取数据库配置
$host = $_ENV['DB_HOST'] ?? null;
$database = $_ENV['DB_DATABASE'] ?? null;
$db_user = $_ENV['DB_USERNAME'] ?? null;
$db_password = $_ENV['DB_PASSWORD'] ?? null;

if (!$host || !$database || !$db_user || !$db_password) {
    die('数据库配置不完整，请检查 .env 文件。');
}

$dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    error_log('数据库连接失败: ' . $e->getMessage());
    die('数据库连接失败。');
}
?>