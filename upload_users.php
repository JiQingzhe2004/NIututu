<?php
// 手动引用 PHPExcel 库
require 'PHPExcel/Classes/PHPExcel.php';
require 'PHPExcel/Classes/PHPExcel/IOFactory.php';
require 'config.php'; // 包含数据库连接配置

$message = '';
$messageType = '';
$uploadResults = []; // 用于存储每个用户的上传结果

// 检查是否有文件上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];

    // 读取 .xlsx 文件
    $spreadsheet = PHPExcel_IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // 数据库连接
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

    // 准备插入用户数据的 SQL 语句
    $stmt = $pdo->prepare('INSERT INTO users (username, name, password, role) VALUES (?, ?, ?, ?)');

    // 遍历每一行数据并插入到数据库中
    foreach ($rows as $row) {
        // 假设 .xlsx 文件的每一行包含以下列：用户名、姓名、密码、角色
        $username = $row[0];
        $name = $row[1];
        $password = password_hash($row[2], PASSWORD_DEFAULT); // 对密码进行哈希处理
        $role = $row[3];

        // 检查角色是否有效
        if (!in_array($role, ['admin', 'user'])) {
            $role = 'user'; // 默认角色
        }

        // 插入数据到数据库
        try {
            $stmt->execute([$username, $name, $password, $role]);
            $uploadResults[] = "用户 '{$username}' 上传成功。";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $uploadResults[] = "插入用户失败: 用户名 '{$username}' 已存在。";
            } else {
                $uploadResults[] = "插入用户失败: 用户名 '{$username}' 错误信息: " . $e->getMessage();
            }
        }
    }

    if (empty($message)) {
        $message = "用户批量添加完成！";
        $messageType = 'success';
    }
} else {
    $message = "请上传一个 .xlsx 文件。";
    $messageType = 'info';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link href="favicon.ico" rel="icon">
    <meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="/static/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/static/favicon.svg" />
<link rel="shortcut icon" href="/static/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/static/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="牛图图传输" />
<link rel="manifest" href="/static/site.webmanifest" />
    <title>批量添加用户</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
        <style>
            @media (max-width: 768px) {
                .mt-mobile {
                    margin-top: 5px; /* 根据需要调整上边距大小 */
                }
            }
        </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center">批量添加用户</h1>
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <form action="upload_users.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="file" class="form-label">选择 .xlsx 文件</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">上传并添加用户</button>
            <button type="button" class="btn btn-secondary" onclick="history.back();">返回上一页</button>
            <a href="批量创建用户模板.xlsx" class="btn btn-secondary">下载模板文件</a>
            <a href="up_user.php" class="btn btn-secondary btn-info mt-mobile">换方式</a>
        </form>
        <?php if (!empty($uploadResults)): ?>
            <div class="mt-4">
                <h3>上传结果：</h3>
                <ul class="list-group">
                    <?php foreach ($uploadResults as $result): ?>
                        <li class="list-group-item"><?php echo $result; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>