<?php
// filepath: /f:/JiQingzhe/文件传输/paiban.php

// 设置时区为上海
date_default_timezone_set('Asia/Shanghai');

// 启用错误报告（开发环境中使用，生产环境请关闭）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 确保已启动会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否已登录
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// 读取配置文件并建立数据库连接
require 'config.php';

$user = $_SESSION['user'];
$userId = $user['id'];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // 添加排班
            $patient_name = $_POST['patient_name'];
            $doctor_name = $_POST['doctor_name'];
            $date = $_POST['date'];
            $time = $_POST['time'];

            // 插入数据库
            $stmt = $pdo->prepare('INSERT INTO Schedules (user_id, patient_name, doctor_name, date, time) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $patient_name, $doctor_name, $date, $time]);

            header('Location: paiban.php');
            exit();
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            // 删除排班
            $id = $_POST['id'];
            $stmt = $pdo->prepare('DELETE FROM Schedules WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            header('Location: paiban.php');
            exit();
        }
    }
}

// 获取所有排班
$stmt = $pdo->prepare('SELECT * FROM Schedules WHERE user_id = ? ORDER BY date, time');
$stmt->execute([$userId]);
$schedules = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>患者排班表</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">患者排班表</h2>

    <!-- 添加排班表单 -->
    <div class="card mb-4">
        <div class="card-header">添加排班</div>
        <div class="card-body">
            <form method="POST" action="paiban.php">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="patient_name">患者姓名</label>
                        <input type="text" class="form-control" id="patient_name" name="patient_name" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="doctor_name">医生姓名</label>
                        <input type="text" class="form-control" id="doctor_name" name="doctor_name" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="date">日期</label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="time">时间</label>
                        <input type="time" class="form-control" id="time" name="time" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">添加</button>
            </form>
        </div>
    </div>

    <!-- 排班表 -->
    <div class="card">
        <div class="card-header">当前排班</div>
        <div class="card-body">
            <button onclick="window.print()" class="btn btn-success mb-3 no-print">打印排班表</button>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>患者姓名</th>
                    <th>医生姓名</th>
                    <th>日期</th>
                    <th>时间</th>
                    <th class="no-print">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($schedules) > 0): ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= htmlspecialchars($schedule['patient_name']) ?></td>
                            <td><?= htmlspecialchars($schedule['doctor_name']) ?></td>
                            <td><?= htmlspecialchars($schedule['date']) ?></td>
                            <td><?= htmlspecialchars($schedule['time']) ?></td>
                            <td class="no-print">
                                <!-- 删除按钮 -->
                                <form method="POST" action="paiban.php" onsubmit="return confirm('确定要删除这条排班吗?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $schedule['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">暂无排班记录。</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 引入Bootstrap JS和依赖 -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>