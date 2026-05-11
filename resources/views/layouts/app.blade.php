<!doctype html>
<html lang="lt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Motoruok</title>

    <style>
        body { font-family: Arial, sans-serif; margin:0; }
        nav { padding:12px; border-bottom:1px solid #ddd; display:flex; gap:12px; align-items:center; }
        nav a { text-decoration:none; color:#111; }
        nav a:hover { text-decoration:underline; }
        .right { margin-left:auto; display:flex; gap:12px; align-items:center; }
        .container { max-width: 1000px; margin: 18px auto; padding: 0 12px; }
        .card { border:1px solid #ddd; border-radius:10px; padding:12px; margin:10px 0; }
        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .btn { padding:8px 12px; border:1px solid #333; border-radius:8px; background:#fff; cursor:pointer; }
        .btn:hover { opacity:0.9; }
        input, textarea { width:100%; padding:8px; margin-top:6px; border:1px solid #ccc; border-radius:8px; }
        label { font-size: 14px; }
        .muted { color:#666; font-size: 14px; }
        .hide { display:none !important; }
        pre { background:#f6f6f6; padding:12px; border-radius:10px; overflow:auto; }
    </style>

    <script src="/js/app.js"></script>
</head>
<body>
<nav>
    <a href="{{ route('pagrindinis') }}">Pagrindinis</a>
    <a href="{{ route('renginiai') }}">Renginiai</a>
    <a href="{{ route('mano_renginiai') }}">Mano renginiai</a>
    <a href="{{ route('xml') }}">XML</a>

    <div class="right">
        <a id="navPrisijungti" href="{{ route('prisijungti') }}">Prisijungti</a>
        <a id="navRegistruotis" href="{{ route('registruotis') }}">Registruotis</a>
        <a id="navProfilis" href="{{ route('profilis') }}" class="hide">Profilis</a>
        <button id="atsijungtiBtn" class="btn hide" type="button">Atsijungti</button>
    </div>
</nav>

<div class="container">
    @yield('content')
</div>
</body>
</html>