<?php
// api/index.php

// 设置响应头为 JSON
header('Content-Type: application/json');

// 启动会话
session_start();

// 引入配置文件
require '../config.php';

// 获取请求方法和请求路径
$method = $_SERVER['REQUEST_METHOD'];
$request = trim($_SERVER['PATH_INFO'] ?? '', '/');

// 根据请求路径解析出操作和参数
$routes = explode('/', $request);
$action = $routes[0] ?? '';

// 路由请求到对应的处理逻辑
switch ($action) {
    case 'login':
        require 'login.php';
        break;
    case 'logout':
        require 'logout.php';
        break;
    case 'register':
        require 'register.php';
        break;
    case 'file_list':
        require 'file_list.php';
        break;
    case 'upload':
        require 'file_manager.php';
        break;
    case 'download':
        require 'download.php';
        break;
    case 'delete_file':
        require 'delete_file.php';
        break;
    case 'change_password':
        require 'change_password.php';
        break;
    // 可以继续添加其他功能的路由
    default:
        echo json_encode(['error' => '无效的 API 接口']);
        break;
}
?>