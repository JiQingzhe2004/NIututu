<?php
// log.php - 审计日志查看器

// 启用错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 设置时区为上海
date_default_timezone_set('Asia/Shanghai');

// 启动会话
session_start();

// 检查用户是否已登录且具有管理员权限
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'config.php'; // 包含数据库连接配置

// 创建审计日志函数
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

// 处理搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = 'WHERE (al.action LIKE ? OR al.details LIKE ? OR al.ip_address LIKE ? OR u.username LIKE ?)';
    $searchTerm = "%$search%";
    $searchParams = array_fill(0, 4, $searchTerm);
}

// 分页设置
$perPage = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// 获取日志总数
$totalSql = "SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id $searchCondition";
$totalStmt = $pdo->prepare($totalSql);

try {
    if (!empty($search)) {
        $totalStmt->execute($searchParams);
    } else {
        $totalStmt->execute();
    }
    $totalLogs = $totalStmt->fetchColumn();
} catch (PDOException $e) {
    die("查询总数失败: " . $e->getMessage());
}

// 获取日志数据 - 修改为使用bindValue明确指定参数类型
$sql = "
    SELECT 
        al.*,
        u.username,
        u.name AS user_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $searchCondition
    ORDER BY al.created_at DESC 
    LIMIT ?, ?
";

$stmt = $pdo->prepare($sql);

try {
    // 绑定参数 - 特别注意LIMIT参数需要明确指定为整数类型
    $paramIndex = 1;
    
    // 绑定搜索参数(如果有)
    if (!empty($search)) {
        foreach ($searchParams as $param) {
            $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
        }
    }
    
    // 绑定LIMIT参数 - 必须使用PDO::PARAM_INT
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
    
    // 执行查询
    $stmt->execute();
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("查询日志失败: " . $e->getMessage());
}

// 计算总页数
$totalPages = max(1, ceil($totalLogs / $perPage));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>审计日志</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <style>
        body {
            padding-top: 20px;
            background-color: #f8f9fa;
        }
        .table-responsive {
            margin-top: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        /* 修改后的列宽样式 */
        .col-id {
            width: 60px;
        }
        .col-user {
            width: 120px;
        }
        .col-action {
            width: 100px;
        }
        .col-details {
            width: 200px; /* 减小详情列宽度 */
            white-space: normal !important; /* 允许换行 */
            word-break: break-word; /* 长单词换行 */
        }
        .col-ip {
            width: 120px;
        }
        .col-device {
            width: 250px; /* 增加设备信息列宽度 */
            white-space: normal !important; /* 允许换行 */
            word-break: break-word; /* 长单词换行 */
        }
        .col-time {
            width: 150px;
        }
        /* 搜索框容器 */
        .search-container {
            margin-bottom: 25px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* 搜索表单 */
        .search-form {
            display: flex;
            align-items: center;
        }

        /* 输入框样式 */
        .search-input {
            flex: 1;
            padding: 12px 20px;
            font-size: 16px;
            border: 2px solid #dee2e6;
            border-radius: 30px 0 0 30px;
            transition: all 0.3s;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .search-input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            outline: none;
        }

        /* 搜索按钮 */
        .search-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, #0b5ed7 0%, #0a58ca 100%);
            transform: translateY(-1px);
        }

        /* 清除按钮 */
        .clear-btn {
            margin-left: 10px;
            padding: 12px 15px;
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clear-btn:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: translateY(-1px);
        }

        /* 图标样式 */
        .bi {
            font-size: 1.1rem;
        }
                
        /* 其他样式保持不变 */
        .search-box {
            margin-bottom: 20px;
        }
        .badge {
            font-size: 0.9em;
        }
        .action-login {
            background-color: #28a745;
        }
        .action-logout {
            background-color: #dc3545;
        }
        .action-auto_login {
            background-color: #17a2b8;
        }
        .action-other {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
    <!-- 添加搜索表单 -->
    <div class="search-container">
        <form method="get" class="search-form">
            <input 
                type="text" 
                class="form-control search-input" 
                name="search" 
                placeholder="搜索操作、详情、IP或用户名..." 
                value="<?= htmlspecialchars($search) ?>"
                aria-label="搜索日志"
            >
            <button type="submit" class="search-btn">
                <i class="bi bi-search"></i>
                <span>搜索</span>
            </button>
            <?php if (!empty($search)): ?>
                <a href="log.php" class="clear-btn">
                    <i class="bi bi-x-circle"></i>
                    <span>清除</span>
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- 显示搜索结果信息 -->
    <?php if (!empty($search)): ?>
    <div class="alert alert-info mb-4">
        搜索 "<strong><?= htmlspecialchars($search) ?></strong>" 共找到 <?= $totalLogs ?> 条记录
        <a href="log.php" class="float-end">显示全部记录</a>
    </div>
    <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-user">用户</th>
                        <th class="col-action">操作</th>
                        <th class="col-details">详情</th>
                        <th class="col-ip">IP地址</th>
                        <th class="col-device">设备信息</th>
                        <th class="col-time">时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="col-id"><?= htmlspecialchars($log['id']) ?></td>
                        <td class="col-user">
                            <?php if ($log['username']): ?>
                                <?= htmlspecialchars($log['username']) ?>
                                <?php if ($log['user_name']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($log['user_name']) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">已删除用户</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-action">
                            <?php 
                            $badgeClass = 'action-other';
                            if (strpos($log['action'], 'login') !== false) {
                                $badgeClass = 'action-login';
                            } elseif (strpos($log['action'], 'logout') !== false) {
                                $badgeClass = 'action-logout';
                            } elseif (strpos($log['action'], 'auto_login') !== false) {
                                $badgeClass = 'action-auto_login';
                            }
                            ?>
                            <span class="badge rounded-pill <?= $badgeClass ?>">
                                <?= htmlspecialchars($log['action']) ?>
                            </span>
                        </td>
                        <td class="col-details">
                            <?= htmlspecialchars($log['details']) ?>
                        </td>
                        <td class="col-ip"><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td class="col-device">
                            <?= htmlspecialchars($log['user_agent']) ?>
                        </td>
                        <td class="col-time"><?= htmlspecialchars($log['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php 
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
                } ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // 工具提示初始化
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>