<?php
require 'PHPExcel/Classes/PHPExcel.php';
require 'PHPExcel/Classes/PHPExcel/IOFactory.php';
require 'config.php';

$message = '';
$messageType = '';
$uploadResults = [];
$parsedUsers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['parse'])) {
        // 解析文件
        if (isset($_FILES['file']) && $_FILES['file']['tmp_name']) {
            $file = $_FILES['file']['tmp_name'];
            $spreadsheet = PHPExcel_IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // 跳过第一行（表头）
            foreach (array_slice($rows, 1) as $row) {
                $username = isset($row[0]) ? trim($row[0]) : '';
                $name = isset($row[1]) ? trim($row[1]) : '';
                $password = isset($row[2]) ? trim($row[2]) : '';
                $role = isset($row[3]) ? trim($row[3]) : '';
                // 跳过用户名或姓名为空的行
                if ($username === '' || $name === '') continue;
                $parsedUsers[] = [
                    'username' => $username,
                    'name' => $name,
                    'password' => $password,
                    'role' => $role,
                ];
            }
            if (empty($parsedUsers)) {
                $message = "未解析到有效用户数据。";
                $messageType = 'warning';
            }
        } else {
            $message = "请上传一个 .xlsx 文件。";
            $messageType = 'info';
        }
    } elseif (isset($_POST['upload'])) {
        // 上传到数据库
        if (isset($_POST['users']) && is_array($_POST['users'])) {
            $stmt = $pdo->prepare('INSERT INTO users (username, name, password, role) VALUES (?, ?, ?, ?)');
            foreach ($_POST['users'] as $user) {
                $username = trim($user['username']);
                // 跳过用户名为空的项
                if ($username === '') {
                    continue;
                }
                $name = trim($user['name']);
                // 跳过姓名为空的项
                if ($name === '') {
                    $uploadResults[] = "插入用户失败: 用户名 '{$username}' 错误信息: 姓名不能为空";
                    continue;
                }
                $password = password_hash($user['password'], PASSWORD_DEFAULT);
                $role = in_array($user['role'], ['admin', 'user']) ? $user['role'] : 'user';

                // 检查数据库中是否已存在该用户名
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                $checkStmt->execute([$username]);
                if ($checkStmt->fetchColumn() > 0) {
                    $uploadResults[] = "插入用户失败: 用户名 '{$username}' 已存在（数据库查询确认）。";
                    continue;
                }

                try {
                    $stmt->execute([$username, $name, $password, $role]);
                    $uploadResults[] = "用户 '{$username}' 上传成功。";
                } catch (PDOException $e) {
                    $uploadResults[] = "插入用户失败: 用户名 '{$username}' 错误信息: " . $e->getMessage();
                }
            }
            $message = "用户批量添加完成！";
            $messageType = 'success';
        } else {
            $message = "没有可上传的用户数据。";
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="/static/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/static/favicon.svg" />
    <link rel="shortcut icon" href="/static/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/static/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="牛图图传输" />
    <link rel="manifest" href="/static/site.webmanifest" />
    <title>批量添加用户</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
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

    <?php if (empty($parsedUsers) && empty($uploadResults)): ?>
        <!-- 第一步：上传并解析 -->
        <form action="upload_users.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="file" class="form-label fw-bold px-3 py-2 mb-2">
                    选择 <code class="rounded-2 px-2 py-1" style="background:#e3f0ff;">.xlsx</code> 文件
                </label>
                <div id="drop-area" class="border border-2 border-primary rounded-4 p-5 text-center bg-light position-relative shadow-sm"
                     style="cursor:pointer;transition:box-shadow 0.3s,background 0.3s;">
                    <input type="file" class="form-control d-none" id="file" name="file" accept=".xlsx" required>
                    <div id="drop-icon" style="font-size:3em;transition:transform 0.3s;">
                        <svg t="1747341408750" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1513" width="100" height="100">
                            <path d="M1024 693.248q0 25.6-8.704 48.128t-24.576 40.448-36.864 30.208-45.568 16.384l1.024 1.024-17.408 0-4.096 0-4.096 0-675.84 0q-5.12 1.024-16.384 1.024-39.936 0-74.752-15.36t-60.928-41.472-40.96-60.928-14.848-74.752 14.848-74.752 40.96-60.928 60.928-41.472 74.752-15.36l1.024 0q-1.024-8.192-1.024-15.36l0-16.384q0-72.704 27.648-137.216t75.776-112.128 112.128-75.264 136.704-27.648 137.216 27.648 112.64 75.264 75.776 112.128 27.648 137.216q0 37.888-8.192 74.24t-22.528 69.12q5.12-1.024 10.752-1.536t10.752-0.512q27.648 0 52.736 10.752t43.52 29.696 29.184 44.032 10.752 53.76zM665.6 571.392q20.48 0 26.624-4.608t-8.192-22.016q-14.336-18.432-31.744-48.128t-36.352-60.416-38.4-57.344-37.888-38.912q-18.432-13.312-27.136-14.336t-25.088 12.288q-18.432 15.36-35.84 38.912t-35.328 50.176-35.84 52.224-36.352 45.056q-18.432 18.432-13.312 32.768t25.6 14.336l16.384 0q9.216 0 19.968 0.512t20.992 0.512l17.408 0q14.336 1.024 18.432 9.728t4.096 24.064q0 17.408-0.512 30.72t-0.512 25.6-0.512 25.6-0.512 30.72q0 7.168 1.536 15.36t5.632 15.36 12.288 11.776 21.504 4.608l23.552 0q9.216 0 27.648 1.024 24.576 0 28.16-12.288t3.584-38.912q0-23.552 0.512-42.496t0.512-51.712q0-23.552 4.608-36.352t19.968-12.8q11.264 0 32.256-0.512t32.256-0.512z" p-id="1514"></path>
                        </svg>
                    </div>
                    <div id="drop-text" style="font-size:1.15em;">
                        <span class="fw-semibold">拖拽文件到此处或点击选择文件</span>
                    </div>
                    <div id="file-name" class="mt-3 text-success fw-semibold"></div>
                    <div id="drop-tip" class="text-secondary mt-2 small" style="opacity:0.7;transition:opacity 0.3s;">
                        支持拖拽或点击上传，文件仅限 .xlsx 格式
                    </div>
                    <div id="drop-anim" class="position-absolute top-0 start-0 w-100 h-100 rounded-4" style="pointer-events:none;z-index:1;opacity:0;transition:opacity 0.3s;background:linear-gradient(135deg,#e3f0ff 0%,#f8fbff 100%);"></div>
                </div>
            </div>
            <button type="submit" name="parse" class="btn btn-primary px-4 fw-bold me-2 shadow-sm">解析</button>
            <button type="button" class="btn btn-secondary px-4 me-2" onclick="history.back();">返回上一页</button>
            <a href="批量创建用户模板.xlsx" class="btn btn-outline-primary px-4">下载模板文件</a>
            <a href="up_user" class="btn btn-secondary btn-info mt-mobile">换方式</a>
        </form>
        <style>
            #drop-area.dragover {
                box-shadow: 0 0 0 0.25rem #0d6efd40, 0 8px 32px #0d6efd22;
                background: linear-gradient(135deg,#e3f0ff 0%,#f8fbff 100%);
            }
            #drop-area.dragover #drop-anim {
                opacity: 1;
            }
            #drop-area.dragover #drop-icon {
                transform: scale(1.15) rotate(-8deg);
                color: #0d6efd;
            }
            #drop-area #drop-icon {
                transition: transform 0.3s, color 0.3s;
            }
        </style>
        <script>
            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('file');
            const fileName = document.getElementById('file-name');
            const dropAnim = document.getElementById('drop-anim');
            const dropTip = document.getElementById('drop-tip');
            const dropIcon = document.getElementById('drop-icon');

            // 拖拽高亮
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropArea.classList.add('dragover');
                    dropTip.textContent = "松开鼠标即可上传文件";
                    dropTip.style.opacity = 1;
                }, false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropArea.classList.remove('dragover');
                    dropTip.textContent = "支持拖拽或点击上传，文件仅限 .xlsx 格式";
                    dropTip.style.opacity = 0.7;
                }, false);
            });

            // 拖拽文件
            dropArea.addEventListener('drop', e => {
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    fileName.textContent = e.dataTransfer.files[0].name;
                    dropTip.textContent = "已选择文件：" + e.dataTransfer.files[0].name;
                    dropTip.style.opacity = 1;
                }
            });

            // 点击选择文件
            dropArea.addEventListener('click', () => fileInput.click());

            // 选择文件后显示文件名
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) {
                    fileName.textContent = fileInput.files[0].name;
                    dropTip.textContent = "已选择文件：" + fileInput.files[0].name;
                    dropTip.style.opacity = 1;
                }
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($parsedUsers)): ?>
        <!-- 第二步：展示解析结果并上传 -->
        <form action="upload_users.php" method="post">
            <h3>解析结果：</h3>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>用户名</th>
                    <th>姓名</th>
                    <th>密码</th>
                    <th>角色</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($parsedUsers as $i => $user): ?>
                    <tr>
                        <td><input type="hidden" name="users[<?php echo $i; ?>][username]" value="<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><input type="hidden" name="users[<?php echo $i; ?>][name]" value="<?php echo htmlspecialchars($user['name']); ?>"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><input type="hidden" name="users[<?php echo $i; ?>][password]" value="<?php echo htmlspecialchars($user['password']); ?>">******</td>
                        <td><input type="hidden" name="users[<?php echo $i; ?>][role]" value="<?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="upload" class="btn btn-success">确认上传</button>
            <a href="upload_users" class="btn btn-secondary">重新选择文件</a>
        </form>
    <?php endif; ?>

    <?php if (!empty($uploadResults)): ?>
        <div class="mt-4">
            <h3>上传结果：</h3>
            <?php foreach ($uploadResults as $result): ?>
                <?php if (strpos($result, '上传成功') !== false): ?>
                    <div class="alert alert-success d-flex align-items-center py-2 mb-2" role="alert">
                        <span class="me-2" style="font-size:1.2em;">✅</span>
                        <span><?php echo $result; ?></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger d-flex align-items-center py-2 mb-2" role="alert">
                        <span class="me-2" style="font-size:1.2em;">❌</span>
                        <span><?php echo $result; ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>