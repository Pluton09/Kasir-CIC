<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir - Kantin</title>
    @vite(['resources/css/app.css', 'resources/js/cashier.js'])
</head>
<body>
    <div id="kasir-app" data-user="{{ auth()->user()->name }}">
        <p class="boot">Memuat kasir...</p>
    </div>

    <noscript>
        <p class="boot">Aplikasi kasir membutuhkan JavaScript.</p>
    </noscript>
</body>
</html>
