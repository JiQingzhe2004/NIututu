<?php
session_start();
require 'check_login.php';
require 'config.php';

// 添加调试日志（开发阶段使用，生产环境请移除）
error_log("Delete File Request: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $fileId = intval($_POST['id']);
    $userId = $_SESSION['user']['id'];
    $userRole = strtolower($_SESSION['user']['role']); // 转换为小写

    // 调试日志
    error_log("User ID: $userId, User Role: $userRole, File ID: $fileId");

    if ($userRole === 'admin') {
        // 管理员删除逻辑
        deleteFileAsAdmin($pdo, $fileId, $userId);
    } else {
        // 普通用户删除逻辑
        deleteFileAsUser($pdo, $fileId, $userId);
    }
} else {
    echo '无效的请求';
    error_log("Invalid request method or missing file ID");
}

/**
 * 管理员删除文件的函数
 *
 * @param PDO $pdo 数据库连接对象
 * @param int $fileId 文件ID
 * @param int $userId 当前用户ID
 */
function deleteFileAsAdmin($pdo, $fileId, $userId) {
    try {
        // 查询文件
        $stmt = $pdo->prepare('SELECT * FROM files WHERE id = ?');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if ($file) {
            $filePath = $file['path'];
            if (file_exists($filePath)) {
                // 开始事务
                $pdo->beginTransaction();

                if (unlink($filePath)) {
                    // 从数据库中删除记录
                    $stmt = $pdo->prepare('DELETE FROM files WHERE id = ?');
                    $stmt->execute([$fileId]);

                    // 提交事务
                    $pdo->commit();

                    echo '文件已删除';
                    
                    // 添加日志记录
                    error_log("File ID $fileId deleted by Admin User ID $userId");
                } else {
                    // 回滚事务
                    $pdo->rollBack();

                    echo '删除文件时发生错误';
                    error_log("Error deleting file at path: $filePath by Admin User ID $userId");
                }
            } else {
                echo '文件不存在';
                error_log("File not found at path: $filePath for File ID $fileId");
            }
        } else {
            echo '文件不存在';
            error_log("File ID $fileId not found");
        }
    } catch (PDOException $e) {
        // 回滚事务
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo '删除过程中发生数据库错误';
        error_log("Database error while deleting file ID $fileId: " . $e->getMessage());
    }
}

/**
 * 普通用户删除文件的函数
 *
 * @param PDO $pdo 数据库连接对象
 * @param int $fileId 文件ID
 * @param int $userId 当前用户ID
 */
function deleteFileAsUser($pdo, $fileId, $userId) {
    try {
        // 查询文件，确保是当前用户上传的
        $stmt = $pdo->prepare('SELECT * FROM files WHERE id = ? AND user_id = ?');
        $stmt->execute([$fileId, $userId]);
        $file = $stmt->fetch();

        if ($file) {
            $filePath = $file['path'];
            if (file_exists($filePath)) {
                // 开始事务
                $pdo->beginTransaction();

                if (unlink($filePath)) {
                    // 从数据库中删除记录
                    $stmt = $pdo->prepare('DELETE FROM files WHERE id = ?');
                    $stmt->execute([$fileId]);

                    // 提交事务
                    $pdo->commit();

                    echo '文件已删除';
                    
                    // 添加日志记录
                    error_log("File ID $fileId deleted by User ID $userId");
                } else {
                    // 回滚事务
                    $pdo->rollBack();

                    echo '删除文件时发生错误';
                    error_log("Error deleting file at path: $filePath by User ID $userId");
                }
            } else {
                echo '文件不存在';
                error_log("File not found at path: $filePath for File ID $fileId");
            }
        } else {
            echo '您无权限删除';
            error_log("File ID $fileId not found or User ID $userId lacks permission");
        }
    } catch (PDOException $e) {
        // 回滚事务
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo '删除过程中发生数据库错误';
        error_log("Database error while deleting file ID $fileId: " . $e->getMessage());
    }
}
?>