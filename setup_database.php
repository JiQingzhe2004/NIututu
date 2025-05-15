<?php

// 读取配置文件并建立数据库连接
$config = json_decode(file_get_contents('config.json'), true);
$dbConfig = $config['db'];

$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 读取 SQL 文件内容
$sqlFile = 'up.SQL';
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    die("无法读取 SQL 文件: $sqlFile");
}

// 分割 SQL 语句
$sqlStatements = array_filter(array_map('trim', explode(';', $sql)));

try {
    $pdo->beginTransaction();
    foreach ($sqlStatements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    $pdo->commit();
    echo "数据库初始化成功！";
} catch (PDOException $e) {
    $pdo->rollBack();
    die("数据库初始化失败: " . $e->getMessage());
}
?>