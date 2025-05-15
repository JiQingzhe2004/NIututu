-- 1. 创建用户表
CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    INDEX (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 创建文件表
CREATE TABLE IF NOT EXISTS files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    type VARCHAR(255) NOT NULL,
    size BIGINT UNSIGNED NOT NULL,
    path VARCHAR(500) NOT NULL,
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT(11) UNSIGNED NOT NULL,
    access ENUM('public', 'private') DEFAULT 'private',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (type),
    INDEX (access)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 插入示例用户
INSERT INTO users (username, name, password, role) VALUES
('admin', '管理员', '$2y$10$C8d14PNIfOL3xFsntqcEjuQpg2oMMwTKcb7aVQo.V9uRjsqlxlMq2', 'admin');

-- 4. 创建公告表
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login_content TEXT NOT NULL,
    index_content TEXT NOT NULL,
    update_content TEXT,
    update_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入默认的登录界面和主页公告内容
INSERT INTO announcements (login_content, index_content) 
VALUES ('如没有账号，请联系管理员发放！', '除PHP文件外均可上传哦！！！');