
-- 表的结构 `announcements`

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `login_content` text NOT NULL,
  `index_content` text NOT NULL,
  `update_content` text,
  `update_enabled` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `announcements` (`id`, `login_content`, `index_content`, `update_content`, `update_enabled`, `created_at`) VALUES
(1, '如没有账号，请联系管理员发放！', '除PHP文件外均可上传哦！！！', '<p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">1、更改了部分样式布局；</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">2、顶部提示更改为可修改；</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">3、用户的新增；</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">4、上传增加</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 194, 102);\">进度条提示</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">；</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">5、下载通过自定义</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 235, 204);\">逻辑</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">，</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(102, 185, 102);\">避免泄露文件位置</span><span style=\"color: rgb(5, 81, 96); background-color: rgb(255, 255, 255);\">。</span></p><p><br></p>', 0, '2024-12-09 17:56:49');

-- 表的结构 `files`

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` bigint(20) NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `upload_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `access` enum('public','private') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 表的结构 `Messages`

CREATE TABLE `Messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 表的结构 `users`

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('admin','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, name, password, role) VALUES
('admin', '管理员', '$2y$10$C8d14PNIfOL3xFsntqcEjuQpg2oMMwTKcb7aVQo.V9uRjsqlxlMq2', 'admin');

-- 表的索引 `announcements`

ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

-- 表的索引 `files`

ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

-- 表的索引 `Messages`

ALTER TABLE `Messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

-- 表的索引 `users`

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

-- 使用表AUTO_INCREMENT `announcements`

ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

-- 使用表AUTO_INCREMENT `files`

ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

-- 使用表AUTO_INCREMENT `Messages`

ALTER TABLE `Messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

-- 使用表AUTO_INCREMENT `users`

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=358;

ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
