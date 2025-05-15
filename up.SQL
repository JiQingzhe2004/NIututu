-- 表的结构 `announcements`

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_content` text NOT NULL,
  `index_content` text NOT NULL,
  `update_content` text,
  `update_enabled` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcements_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `announcements` (`id`, `login_content`, `index_content`, `update_content`, `update_enabled`, `created_at`) VALUES
(1, '如没有账号，请联系管理员发放！', '除PHP文件外均可上传哦！！！', '<p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">1、更改了部分样式布局；</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">2、顶部提示更改为可修改；</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">3、用户的新增；</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">4、上传增加</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 194, 102);\">进度条提示</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">；</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">5、下载通过自定义</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 235, 204);\">逻辑</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">，</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(102, 185, 102);\">避免泄露文件位置</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">。</span></p><p><br></p>', 0, '2024-12-09 17:56:49');

-- 表的结构 `files`

CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` bigint(20) NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `upload_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `access` enum('public','private') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_files_upload_time` (`upload_time`),
  KEY `idx_files_user_id_upload_time` (`user_id`, `upload_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 表的结构 `Messages`

CREATE TABLE `Messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `idx_messages_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 表的结构 `users`

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('admin','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_created_at` (`created_at`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, name, password, role) VALUES
('admin', '管理员', '$2y$10$C8d14PNIfOL3xFsntqcEjuQpg2oMMwTKcb7aVQo.V9uRjsqlxlMq2', 'admin');

-- 外键约束
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;
