<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Kasir')</title>
    <style>
        :root {
            --bg: #f4f5f3;
            --card: #fff;
            --tx: #1d2420;
            --mut: #6b7570;
            --bd: #dfe3df;
            --ac: #0f766e;
            --on: #fff;
            --danger: #991b1b;
            --danger-bg: #fee2e2;
            --danger-bd: #fecaca;
            --ok: #166534;
            --ok-bg: #dcfce7;
            --ok-bd: #bbf7d0;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #141a17;
                --card: #1d2521;
                --tx: #e8ede9;
                --mut: #93a09a;
                --bd: #2b3631;
                --ac: #2dd4bf;
                --on: #06201c;
                --danger: #fca5a5;
                --danger-bg: #3f1d1d;
                --danger-bd: #7f1d1d;
                --ok: #86efac;
                --ok-bg: #103f38;
                --ok-bd: #115e59;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--tx);
            font: 15px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        button, input, select { font: inherit; color: inherit; }
        button {
            cursor: pointer;
            border: 1px solid var(--bd);
            background: var(--card);
            border-radius: 8px;
            padding: 8px 12px;
        }
        input {
            width: 100%;
            border: 1px solid var(--bd);
            background: var(--card);
            border-radius: 8px;
            padding: 9px 10px;
        }
        a { color: var(--ac); }
        header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--bd);
            background: var(--card);
        }
        header b { white-space: nowrap; }
        header nav {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        header nav a {
            color: var(--mut);
            text-decoration: none;
            font-size: 14px;
            padding: 6px 10px;
            border-radius: 7px;
        }
        header nav a:hover { background: var(--bg); color: var(--tx); }
        header nav a.on {
            background: var(--ac);
            color: var(--on);
            font-weight: 600;
        }
        header form { margin-left: auto; }
        main { padding: 18px 14px; }
        .card {
            width: min(100%, 420px);
            margin: 0 auto;
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 12px;
            padding: 18px;
        }
        .card h1 { margin: 0 0 14px; font-size: 20px; }
        .field { display: grid; gap: 6px; margin-bottom: 12px; }
        .field label { font-weight: 600; font-size: 14px; }
        .pri {
            width: 100%;
            background: var(--ac);
            color: var(--on);
            border-color: var(--ac);
            font-weight: 600;
            padding: 11px;
        }
        .mut { color: var(--mut); }
        .error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-bd);
            border-radius: 8px;
            padding: 9px 10px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .success {
            background: var(--ok-bg);
            color: var(--ok);
            border: 1px solid var(--ok-bd);
            border-radius: 8px;
            padding: 9px 10px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .flash { width: min(100%, 980px); margin: 14px auto 0; }
        .page { width: min(100%, 980px); margin: 0 auto; }
        .page-head {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px 16px;
            margin-bottom: 14px;
        }
        .page-head h1 { margin: 0; font-size: 22px; }
        .page-head p { margin: 0; color: var(--mut); font-size: 13px; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }
        .summary {
            width: auto;
            margin: 0;
            padding: 12px 14px;
        }
        .summary span { display: block; color: var(--mut); font-size: 13px; }
        .summary strong {
            display: block;
            margin-top: 2px;
            font-size: 20px;
            font-variant-numeric: tabular-nums;
        }
        .summary small { color: var(--mut); }
        .summary.grand {
            border-color: var(--ac);
            background: var(--ac);
            color: var(--on);
        }
        .summary.grand span,
        .summary.grand small { color: var(--on); opacity: 0.85; }
        .panel {
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
        }
        .panel h2 { margin: 0 0 10px; font-size: 16px; }
        .tx-head {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 4px 12px;
            margin-bottom: 8px;
        }
        .tx-head strong { font-size: 15px; }
        .tx-head span { color: var(--mut); font-size: 13px; }
        .tx-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 14px;
            margin-bottom: 10px;
            color: var(--mut);
            font-size: 13px;
        }
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            padding: 8px 6px;
            border-bottom: 1px solid var(--bd);
            text-align: left;
            vertical-align: top;
        }
        th { color: var(--mut); font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        tr:last-child td { border-bottom: 0; }
        .tenant-name { font-weight: 600; }
        .badge {
            display: inline-block;
            border: 1px solid var(--bd);
            border-radius: 999px;
            padding: 2px 9px;
            font-size: 12px;
            color: var(--mut);
            white-space: nowrap;
        }
        .badge.on { border-color: var(--ok-bd); background: var(--ok-bg); color: var(--ok); }
        .badge.off { border-color: var(--danger-bd); background: var(--danger-bg); color: var(--danger); }
        .inline-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }
        .chip {
            border: 1px solid var(--bd);
            border-radius: 999px;
            background: var(--card);
            color: var(--tx);
            padding: 6px 14px;
            font-size: 14px;
            line-height: 1.3;
            text-decoration: none;
        }
        .chip.on {
            border-color: var(--ac);
            background: var(--ac);
            color: var(--on);
            font-weight: 600;
        }
        .price-form {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .price-form input { width: 140px; }
        .inline-form input { flex: 1 1 180px; width: auto; }
        .row-actions { display: flex; flex-wrap: wrap; gap: 6px; }
        .row-actions form { margin: 0; }
        .empty {
            padding: 22px;
            border: 1px dashed var(--bd);
            border-radius: 10px;
            color: var(--mut);
            text-align: center;
        }
        @media (max-width: 640px) {
            header { flex-wrap: wrap; }
            header form { margin-left: 0; }
            .page { padding: 0; }
        }
    </style>
</head>
<body>
    @auth
        <header>
            <b>Kasir Kantin</b>
            <nav aria-label="Navigasi utama">
                <a href="{{ route('kasir') }}" class="{{ request()->routeIs('kasir') ? 'on' : '' }}">Kasir</a>
                <a href="{{ route('riwayat') }}" class="{{ request()->routeIs('riwayat') ? 'on' : '' }}">Riwayat</a>
                <a href="{{ route('menus.index') }}" class="{{ request()->routeIs('menus.*') ? 'on' : '' }}">Harga Menu</a>
                <a href="{{ route('tenants.index') }}" class="{{ request()->routeIs('tenants.*') ? 'on' : '' }}">Kelola</a>
            </nav>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        </header>
    @endauth

    @if (session('success'))
        <div class="flash">
            <div class="success">{{ session('success') }}</div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
