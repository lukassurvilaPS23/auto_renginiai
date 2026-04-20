@extends('layouts.app')

@section('content')
<h1>Auto renginiai</h1>

<div class="card" style="background:linear-gradient(135deg,#eff6ff,#eef2ff); border:none; box-shadow:0 12px 25px rgba(15,23,42,0.12);">
    <div class="row" style="align-items:center;">
        <div style="flex:1; min-width:240px;">
            <p class="muted" style="text-transform:uppercase; letter-spacing:.15em; font-size:12px;">Platforma entuziastams</p>
            <h2 style="margin:6px 0 12px; font-size:30px;">Atrask, organizuok ir valdyk auto renginius.</h2>
            <div style="margin-top:16px; display:flex; gap:12px; flex-wrap:wrap;">
                <a class="btn" href="{{ route('renginiai') }}" style="background:#111827; color:#fff; border-color:#111827;">Peržiūrėti renginius</a>
                <a class="btn" href="{{ route('mano_renginiai') }}">Mano renginiai</a>
            </div>
        </div>
    </div>
</div>

<div class="row" style="gap:18px;">
    <div class="card" style="flex:1; min-width:260px;">
        <h3>Greitos nuorodos</h3>
        <ul style="line-height:1.9;">
            <li><a href="{{ route('renginiai') }}">Renginių sąrašas</a></li>
            <li><a href="{{ route('prisijungti') }}">Prisijungti</a></li>
            <li><a href="{{ route('registruotis') }}">Registruotis</a></li>
            <li><a href="{{ route('xml') }}">XML eksportas</a></li>
            <li><a href="{{ route('swagger') }}">Swagger dokumentacija</a></li>
        </ul>
    </div>

    <div class="card" style="flex:2; min-width:300px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <h3 style="margin:0;">Naujausi renginiai</h3>
            <a class="btn" href="{{ route('renginiai') }}" style="padding:6px 12px; font-size:13px;">Visi renginiai</a>
        </div>
        <p class="muted" id="latestStatus">Kraunama...</p>
        <ul id="latestList" class="hide" style="list-style:none; padding:0; margin:0;"></ul>
    </div>
</div>

<script>
(async () => {
    const statusEl = document.getElementById('latestStatus');
    const listEl = document.getElementById('latestList');
    const eventsRes = await Api.request('GET', '/api/auto-renginiai');

    if (!eventsRes.ok) {
        statusEl.textContent = `Nepavyko užkrauti (status ${eventsRes.status})`;
        return;
    }

    const list = (eventsRes.payload.auto_renginiai || []).slice(0, 4);
    if (list.length === 0) {
        statusEl.textContent = 'Šiuo metu nėra aktyvių renginių.';
        return;
    }

    statusEl.classList.add('hide');
    listEl.classList.remove('hide');
    listEl.innerHTML = list.map(item => `
        <li style="padding:10px 0; border-bottom:1px solid #eee;">
            <a href="/renginiai/${item.id}" style="font-weight:600; color:#111827;">${item.pavadinimas}</a>
            <div class="muted">${item.miestas ?? '—'} · ${item.pradzios_data ?? ''}</div>
        </li>
    `).join('');
})();
</script>
@endsection