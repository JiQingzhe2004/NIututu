<?php
require_once 'config.php';
session_start();

// 只响应AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyword = trim($_POST['keyword'] ?? '');
    $results = [];

    if ($keyword !== '') {
        // 判断用户身份
        $userId = $_SESSION['user']['id'] ?? 0;
        $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

        // 管理员可见全部，普通用户只能看到公开或自己上传的文件
        if ($isAdmin) {
            $stmt = $pdo->prepare("
                SELECT f.id, f.original_name, f.type, f.size, u.username
                FROM files f
                LEFT JOIN users u ON f.user_id = u.id
                WHERE SUBSTRING_INDEX(f.original_name, '.', 1) LIKE :kw
                ORDER BY f.upload_time DESC
                LIMIT 30
            ");
            $stmt->execute([':kw' => '%' . $keyword . '%']);
        } else {
            $stmt = $pdo->prepare("
                SELECT f.id, f.original_name, f.type, f.size, u.username
                FROM files f
                LEFT JOIN users u ON f.user_id = u.id
                WHERE 
                    (f.is_private = 0 OR f.user_id = :uid)
                    AND SUBSTRING_INDEX(f.original_name, '.', 1) LIKE :kw
                ORDER BY f.upload_time DESC
                LIMIT 30
            ");
            $stmt->execute([
                ':kw' => '%' . $keyword . '%',
                ':uid' => $userId
            ]);
        }
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // 去掉search-result-box外层
    if ($keyword === '') {
        echo '<div class="search-hint search-hint-init text-center py-4">请输入关键词进行搜索</div>';
    } elseif (empty($results)) {
        echo '<div class="search-hint search-hint-init text-center py-4">没有找到相关文件</div>';
    } else {
        ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 search-table">
            <thead>
                <tr class="d-none d-sm-table-row">
                    <th>文件名</th>
                    <th>类型</th>
                    <th>大小</th>
                    <th>上传人</th>
                    <th>操作</th>
                </tr>
                <tr class="d-table-row d-sm-none">
                    <th>查询结果</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $file): ?>
                <tr class="d-none d-sm-table-row">
                    <td title="<?= htmlspecialchars($file['original_name']) ?>">
                        <?= htmlspecialchars($file['original_name']) ?>
                    </td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($file['type']) ?></span></td>
                    <td><?= number_format($file['size'] / 1024, 2) ?> KB</td>
                    <td><?= htmlspecialchars($file['username'] ?? '未知') ?></td>
                    <td>
                        <button class="btn btn-info btn-sm rounded-pill px-3" onclick="viewFile(<?= $file['id'] ?>)">查看</button>
                        <button class="btn btn-success btn-sm rounded-pill px-3" onclick="downloadFile(<?= $file['id'] ?>, '<?= htmlspecialchars($file['original_name'], ENT_QUOTES) ?>')">下载</button>
                    </td>
                </tr>
                <tr class="d-table-row d-sm-none">
                    <td colspan="3">
                        <div class="search-mobile-card d-flex flex-column small text-muted">
                            <div><strong>文件名：</strong> <?= htmlspecialchars($file['original_name']) ?></div>
                            <div><strong>类型：</strong> <?= htmlspecialchars($file['type']) ?> ｜ <strong>大小：</strong> <?= number_format($file['size'] / 1024, 2) ?> KB</div>
                            <div><strong>上传人：</strong> <?= htmlspecialchars($file['username'] ?? '未知') ?></div>
                            <div class="mt-2">
                                <button class="btn btn-info btn-sm rounded-pill px-3 me-2" onclick="viewFile(<?= $file['id'] ?>)">查看</button>
                                <button class="btn btn-success btn-sm rounded-pill px-3" onclick="downloadFile(<?= $file['id'] ?>, '<?= htmlspecialchars($file['original_name'], ENT_QUOTES) ?>')">下载</button>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php
    }
    ?>
    <style>
        .search-result-box {
            background: var(--search-bg, #fff);
            border-radius: 18px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
            padding: 18px 12px;
            margin-top: 10px;
            max-height: 420px;
            overflow-y: auto;
            min-height: 120px;
            transition: background 0.2s;
            color: inherit;
        }
        .search-table {
            border-radius: 14px;
            overflow: hidden;
            background: transparent;
        }
        .search-table th {
            background: var(--search-th-bg, #f6f8fa);
            border-top: none;
            border-bottom: 1px solid #e5e7eb;
            color: inherit;
        }
        .search-table td {
            background: transparent;
            border-bottom: 1px solid #f0f0f0;
            color: inherit;
        }
        .search-table tr:last-child td {
            border-bottom: none;
        }
        .file-icon {
            vertical-align: middle;
            display: inline-block;
        }
        .btn-sm.rounded-pill {
            border-radius: 999px !important;
        }
        /* 主题适配 */
        body.dark-theme .search-result-box,
        .dark-theme .search-result-box {
            background: #23272f !important;
            color: #eee;
        }
        body.dark-theme .search-table th,
        .dark-theme .search-table th {
            background: #23272f;
            color: #bfc9d4;
            border-bottom: 1px solid #333a45;
        }
        body.dark-theme .search-table td,
        .dark-theme .search-table td {
            background: transparent;
            color: #eee;
            border-bottom: 1px solid #333a45;
        }
        body.dark-theme .badge.bg-secondary,
        .dark-theme .badge.bg-secondary {
            background: #444c5c !important;
            color: #bfc9d4 !important;
        }
        /* 搜索提示高对比度，仅文字颜色 */
        .search-hint,
        .search-hint-init {
            font-weight: 500;
        }
        /* 响应式表格，仅保留文件名、上传人、操作（手机端） */
        @media (max-width: 991.98px) {
            .search-table thead tr.d-sm-table-row,
            .search-table tbody tr.d-sm-table-row,
            .search-table th:nth-child(2),
            .search-table th:nth-child(3),
            .search-table td:nth-child(2),
            .search-table td:nth-child(3) {
                display: none !important;
            }
            .search-table thead tr.d-table-row,
            .search-table tbody tr.d-table-row {
                display: table-row !important;
            }
            .search-table th, .search-table td {
                font-size: 15px;
                padding: 7px 4px;
            }
        }
        .search-table thead tr.d-table-row,
        .search-table tbody tr.d-table-row {
            display: none;
        }
        @media (max-width: 991.98px) {
            .search-table thead tr.d-table-row,
            .search-table tbody tr.d-table-row {
                display: table-row;
            }
        }
        /* 搜索弹窗宽度适配手机端，保持一致 */
        #search-box.search-box {
            width: 100%;
            max-width: 800px;
            box-sizing: border-box;
            padding: 32px 24px !important;
            border-radius: 14px;
            box-shadow: 0 2px 24px rgba(0,0,0,0.13);
            position: relative;
            background: var(--search-bg,#fff);
        }
        @media (max-width: 991.98px) {
            #search-box.search-box {
                min-width: 0 !important;
                max-width: 96% !important;
                padding: 32px 24px !important;
                border-radius: 14px !important;
            }
        }
        body.dark-theme .search-table th,
        body.dark-theme .search-table td,
        .dark-theme .search-table th,
        .dark-theme .search-table td {
        	color: #f1f1f1 !important;
        }
        body.dark-theme .search-mobile-card,
        .dark-theme .search-mobile-card {
        	color: #f1f1f1 !important;
        }
        body.dark-theme .search-result-box,
        .dark-theme .search-result-box {
        	color: #f1f1f1 !important;
        }
        #search-modal-bg {
            padding: 16px;
        }
    </style>
    <style>
    .search-mobile-card {
        background: #f9fafb;
        padding: 10px 12px;
        border-radius: 12px;
        color: #111;
    }
    body.dark-theme .search-mobile-card {
        background: #2c2f36;
        color: #f1f1f1 !important;
    }
    </style>
    <?php
    exit;
}
?>
<!-- 搜索框UI（只在首次引入时输出） -->
<div id="search-modal-bg" class="search-modal-bg" style="position:fixed;left:0;top:0;width:100vw;height:100vh;z-index:9999;background:rgba(0,0,0,0.18);display:flex;align-items:center;justify-content:center;">
    <div id="search-box" class="search-box" style="width: 96%; max-width: 800px; padding:32px 24px; border-radius:14px; box-shadow:0 2px 24px rgba(0,0,0,0.13); position:relative; background:var(--search-bg,#fff); min-width:auto;">
        <button type="button" id="close-search" style="position:absolute;right:12px;top:12px;border:none;background:transparent;font-size:1.5rem;line-height:1;">&times;</button>
        <h5 class="mb-3">🔍 文件名快速搜索</h5>
        <form id="search-form" autocomplete="off" class="d-flex mb-2">
            <input type="text" class="form-control me-2" id="search-keyword" name="keyword" placeholder="输入文件名关键词..." autofocus>
        </form>
        <div id="search-result">
            <div class="search-hint text-center py-4">请输入关键词进行搜索</div>
        </div>
    </div>
</div>
<style>
/* 主题适配：跟随 body 的 light-theme/dark-theme */
body.light-theme .search-modal-bg .search-box,
.light-theme .search-modal-bg .search-box {
    --search-bg: #fff;
    color: #222;
}
body.dark-theme .search-modal-bg .search-box,
.dark-theme .search-modal-bg .search-box {
    --search-bg: #23272f;
    color: #eee;
}
body.dark-theme .search-modal-bg,
.dark-theme .search-modal-bg {
    background: rgba(0,0,0,0.45) !important;
}
body.dark-theme .form-control {
    background: #23272f;
    color: #eee;
    border-color: #444;
}
body.dark-theme .btn-primary {
    background: #3b82f6;
    border-color: #3b82f6;
}
</style>
<script>
(function(){
    const modalBg = document.getElementById('search-modal-bg');
    const closeBtn = document.getElementById('close-search');
    const form = document.getElementById('search-form');
    const input = document.getElementById('search-keyword');
    const resultBox = document.getElementById('search-result');

    // 关闭
    function closeSearchModal() {
        if (modalBg && modalBg.parentNode) {
            modalBg.parentNode.removeChild(modalBg);
        }
    }
    closeBtn.onclick = closeSearchModal;
    modalBg.onclick = function(e) {
        if(e.target === modalBg) closeSearchModal();
    };
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape') closeSearchModal();
    });

    // 搜索
    let lastKeyword = '';
    function doSearch() {
        const kw = input.value.trim();
        if(kw === lastKeyword) return;
        lastKeyword = kw;
        resultBox.innerHTML = '<div class="search-hint text-center py-4">搜索中...</div>';
        fetch('sousuo.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'keyword=' + encodeURIComponent(kw)
        })
        .then(r=>r.text())
        .then(html=>{
            // 直接插入返回内容，和初始提示保持一致
            if (html.trim()) {
                resultBox.innerHTML = html;
            } else {
                resultBox.innerHTML = '<div class="search-hint text-center py-4">没有找到相关文件</div>';
            }
        });
    }
    form.onsubmit = function(e) {
        e.preventDefault();
        doSearch();
    };
    // 支持输入时按回车搜索
    input.addEventListener('keydown', function(e){
        if(e.key === 'Enter'){
            e.preventDefault();
            doSearch();
        }
    });
    // 自动聚焦
    input.focus();
})();
</script>