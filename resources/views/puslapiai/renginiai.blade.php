@extends('layouts.app')

@section('content')
<h1>Auto renginiai</h1>

<div class="card">
    <div class="row">
        <div style="flex:1; min-width:240px;">
            <label>Miestas</label>
            <input id="miestas" placeholder="Pvz. Vilnius">
        </div>
        <div style="flex:1; min-width:240px;">
            <label>Statusas</label>
            <input id="statusas" placeholder="Pvz. aktyvus">
        </div>
        <div style="align-self:end;">
            <button class="btn" id="filtruotiBtn" type="button">Filtruoti</button>
        </div>
    </div>
</div>

<div id="out" class="card" style="min-height:120px;">Kraunama...</div>

<script>
(async () => {
  const out = document.getElementById('out');

  async function load() {
    out.textContent = 'Kraunama...';

    const miestas = document.getElementById('miestas').value.trim();
    const statusas = document.getElementById('statusas').value.trim();

    const qs = new URLSearchParams();
    if (miestas) qs.set('miestas', miestas);
    if (statusas) qs.set('statusas', statusas);

    const url = '/api/auto-renginiai' + (qs.toString() ? `?${qs}` : '');
    const { ok, status, payload } = await Api.request('GET', url);

    if (!ok) { out.textContent = `Klaida (status ${status})`; return; }

    const list = payload.auto_renginiai || [];
    if (list.length === 0) { out.textContent = 'Renginių nėra.'; return; }

    out.innerHTML = list.map(r => `
      <div style="padding:14px 0; border-bottom:1px solid #eee;">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
          <div>
            <a href="/renginiai/${r.id}" style="font-size:18px; font-weight:600; color:#111827;">${r.pavadinimas}</a>
            <p class="muted" style="margin:4px 0 0;">${r.miestas ?? '—'} · ${Api.formatDate(r.pradzios_data)}</p>
          </div>
          <span style="align-self:flex-start; background:#eef2ff; color:#312e81; padding:4px 12px; border-radius:999px; font-size:13px;">${r.statusas ?? '—'}</span>
        </div>
        <p style="margin:8px 0 0; color:#374151;">${r.aprasymas ?? ''}</p>
      </div>
    `).join('');
  }

  document.getElementById('filtruotiBtn').addEventListener('click', load);
  await load();
})();
</script>
@endsection