<?php $appName = env('APP_NAME', 'Support Portal'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 – Forbidden | <?= $appName ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #94a3b8;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; text-align: center; padding: 2rem;
        }
        .container { max-width: 500px; }
        .code { font-size: 8rem; font-weight: 800; color: #f59e0b; line-height: 1; text-shadow: 0 0 40px rgba(245,158,11,0.4); }
        h1 { font-size: 1.5rem; color: #e2e8f0; margin: 1rem 0 0.5rem; }
        p  { font-size: 0.95rem; margin-bottom: 2rem; }
        a  { display: inline-block; padding: 0.6rem 1.5rem; background: #f59e0b; color: #000; border-radius: 6px; text-decoration: none; font-size: 0.9rem; }
        a:hover { background: #d97706; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">403</div>
        <h1>Access Forbidden</h1>
        <p>You don't have permission to access this resource.</p>
        <a href="/">← Go Home</a>
    </div>
</body>
</html>
