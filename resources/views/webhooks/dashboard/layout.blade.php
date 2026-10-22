<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Webhook Dashboard') — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #f4f6f8;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --primary: #0f766e;
            --primary-dark: #0d5f59;
            --danger: #b91c1c;
            --success: #15803d;
            --warning: #b45309;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            background: linear-gradient(180deg, #e8eef2 0%, var(--bg) 240px);
            color: var(--text);
        }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px 16px 48px; }
        header.appbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px;
        }
        header.appbar h1 { margin: 0; font-size: 1.6rem; letter-spacing: 0.02em; }
        header.appbar a, button.linkish {
            color: var(--primary); text-decoration: none; font-family: inherit; background: none; border: 0; cursor: pointer;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 16px;
        }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
        .stat { padding: 14px; border: 1px solid var(--border); border-radius: 8px; background: #fafafa; }
        .stat .label { color: var(--muted); font-size: 0.85rem; }
        .stat .value { font-size: 1.5rem; margin-top: 4px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { color: var(--muted); font-weight: 600; font-size: 0.85rem; }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.78rem;
            border: 1px solid var(--border); background: #f9fafb;
        }
        .badge.succeeded { color: var(--success); border-color: #bbf7d0; background: #f0fdf4; }
        .badge.failed, .badge.permanently_failed { color: var(--danger); border-color: #fecaca; background: #fef2f2; }
        .badge.pending, .badge.processing { color: var(--warning); border-color: #fde68a; background: #fffbeb; }
        .filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: end; }
        label { display: block; font-size: 0.85rem; color: var(--muted); margin-bottom: 4px; }
        input, select {
            padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font: inherit; min-width: 160px;
        }
        .btn {
            display: inline-block; padding: 8px 14px; border-radius: 6px; border: 0;
            background: var(--primary); color: #fff; text-decoration: none; cursor: pointer; font: inherit;
        }
        .btn:hover { background: var(--primary-dark); }
        .btn.secondary { background: #374151; }
        .btn.danger { background: var(--danger); }
        .flash { padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; }
        .flash.success { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
        .flash.error { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
        .muted { color: var(--muted); }
        pre {
            white-space: pre-wrap; word-break: break-word; background: #0b1220; color: #e5e7eb;
            padding: 12px; border-radius: 8px; font-size: 0.85rem; overflow: auto;
        }
        .login-box { max-width: 380px; margin: 80px auto; }
        .login-box h1 { margin-top: 0; }
        .stack > * + * { margin-top: 12px; }
        .pagination { margin-top: 16px; }
        .pagination nav { display: flex; gap: 6px; flex-wrap: wrap; }
    </style>
</head>
<body>
<div class="wrap">
    @yield('content')
</div>
</body>
</html>
