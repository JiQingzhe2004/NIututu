<?php
// config.php

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

// 读取配置文件路径
$configFile = __DIR__ . '/config.json';

// 检查配置文件是否存在
if (!file_exists($configFile)) {
    die('配置文件不存在。');
}

// 读取并解析配置文件
$config = json_decode(file_get_contents($configFile), true);

// 检查数据库配置是否存在
if (!isset($config['db'])) {
    die('数据库配置不存在。');
}

$dbConfig = $config['db'];

$host = $dbConfig['host'];
$database = $dbConfig['database'];
$db_user = $dbConfig['username'];       // 重命名变量
$db_password = $dbConfig['password'];   // 重命名变量

$dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $db_user, $db_password, $options); // 使用新变量名
} catch (PDOException $e) {
    error_log('数据库连接失败: ' . $e->getMessage());
    die('数据库连接失败。');
}
?>