<?php $appName = env('APP_NAME', 'Support Portal'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 – Server Error | <?= $appName ?></title>
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
        .code { font-size: 8rem; font-weight: 800; color: #ef4444; line-height: 1; text-shadow: 0 0 40px rgba(239,68,68,0.4); }
        h1 { font-size: 1.5rem; color: #e2e8f0; margin: 1rem 0 0.5rem; }
        p  { font-size: 0.95rem; margin-bottom: 2rem; }
        a  { display: inline-block; padding: 0.6rem 1.5rem; background: #ef4444; color: #fff; border-radius: 6px; text-decoration: none; font-size: 0.9rem; }
        a:hover { background: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">500</div>
        <h1>Internal Server Error</h1>
        <p>Something went wrong on our end. Please try again shortly.</p>
        <a href="/">← Go Home</a>
    </div>
</body>
</html>
