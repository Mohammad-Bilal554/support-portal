<?php $appName = env('APP_NAME', 'Support Portal'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>500 — <?= htmlspecialchars($appName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* {box-sizing:border-box;}
body {font-family:'Inter',sans-serif;background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:2rem;margin:0;overflow:hidden;}
body::before {content:'';position:fixed;width:500px;height:500px;border-radius:50%;background:#ef4444;filter:blur(120px);opacity:.08;top:-100px;right:-100px;pointer-events:none;}
.box {max-width:480px;position:relative;z-index:1;}
.code {font-size:7rem;font-weight:800;line-height:1;color:#ef4444;text-shadow:0 0 60px #ef444455;margin-bottom:.25rem;}
h1 {color:#e2e8f0;font-size:1.5rem;font-weight:700;margin-bottom:.5rem;}
p  {color:#64748b;font-size:.9rem;margin-bottom:2rem;line-height:1.7;}
.actions {display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;}
.btn-back {background:#ef4444;color:#fff;border:none;padding:.65rem 1.5rem;border-radius:10px;font-weight:600;font-size:.875rem;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;transition:all .2s;}
.btn-back:hover {opacity:.9;transform:translateY(-1px);color:#fff;}
.btn-home {background:transparent;color:#64748b;border:1px solid #334155;padding:.65rem 1.5rem;border-radius:10px;font-weight:500;font-size:.875rem;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;transition:all .2s;}
.btn-home:hover {border-color:#475569;color:#e2e8f0;}
</style>
</head>
<body>
<div class="box">
<div class="code">500</div>
<h1>Server Error</h1>
<p>Something went wrong on our end. Please try again shortly.</p>
<div class="actions">
<a href="javascript:history.back()" class="btn-back"><i class="bi bi-arrow-left"></i> Go Back</a>
<a href="/" class="btn-home"><i class="bi bi-house-door"></i> Home</a>
</div>
</div>
</body>
</html>