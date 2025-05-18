<?php
require __DIR__ . '/vendor/autoload.php';
$mdFile = __DIR__ . '/CHANGELOG.md';
$changelog = file_exists($mdFile) ? file_get_contents($mdFile) : '# 暂无更新日志';
$Parsedown = new Parsedown();
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
    <title>版本更新日志 - 文件管理系统</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/github-markdown.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            background: #f6f8fa;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        header {
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 100;
            background: #ffffffdd;
            backdrop-filter: blur(12px);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            padding: 12px 24px;
            text-align: right;
        }

        h1 {
            margin-top: 32px;
            margin-bottom: 24px;
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        h1 .icon {
            font-size: 2.5rem;
            color: #3f51b5;
            vertical-align: middle;
        }

        main {
            padding: 40px 32px;
            margin: 0 16px 48px;
            max-width: 860px;
            width: 100%;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #0d1117;
            }

            header {
                background: #1e1e1ecc;
                color: #eee;
            }

            h1 {
                color: #dfe6f3;
            }

            main {
            }
        }
    </style>
</head>
<body>
    <header>
        <button type="button" class="btn btn-outline-primary btn-sm" style="display: flex; align-items: center;" onclick="if(document.referrer){window.location=document.referrer;}else{window.history.back();}">
            <img src="static/返回.svg" alt="" style="width:1em;height:1em;vertical-align:middle;margin-right:4px;display:inline-block;"> 
            <span style="vertical-align:middle;">返回</span>
        </button>
    </header>
    <h1><span class="icon"><img src="static/ai-双星.svg" alt=""></span>版本更新日志</h1>
    <main class="markdown-body">
        <?php echo $Parsedown->text($changelog); ?>
    </main>
</body>
</html>