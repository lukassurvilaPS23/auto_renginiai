@extends('layouts.app')

@section('content')
<h1>Profilis</h1>

<div class="card">
  <h3>Vartotojo informacija</h3>
  <p id="profileMessage" class="muted">Kraunama...</p>

  <div id="profileDetails" class="hide">
    <div class="row" style="gap:24px; align-items:flex-start;">
      <div style="flex:1; min-width:220px;">
        <p><strong>Vardas</strong><br><span id="profileName"></span></p>
        <p><strong>El. paštas</strong><br><span id="profileEmail"></span></p>
        <p><strong>Rolės</strong><br><span id="profileRoles"></span></p>
      </div>
      <div style="flex:1; min-width:220px;">
        <p><strong>Prisijungė</strong><br><span id="profileCreated"></span></p>
        <p><strong>ID</strong><br><span id="profileId"></span></p>
      </div>
    </div>
  </div>

  <details style="margin-top:16px;">
    <summary>Rodyti JSON</summary>
    <pre id="profileJson" style="margin-top:12px;">Kraunama...</pre>
  </details>
</div>

<script>
(async () => {
  const msg = document.getElementById('profileMessage');
  const box = document.getElementById('profileDetails');
  const out = document.getElementById('profileJson');

  const { ok, status, payload } = await Api.request('GET', '/api/as');
  if (!ok) {
    msg.textContent = `Neprisijungęs (status ${status}).`;
    out.textContent = '—';
    UI.setAuthNav(false);
    return;
  }

  const user = payload.vartotojas ?? payload ?? {};

  let roles = 'Nenurodyta';
  if (Array.isArray(user.roles) && user.roles.length) {
    roles = user.roles.map(r => r.name ?? r).join(', ');
  } else if (typeof user.roles === 'string' && user.roles.trim()) {
    roles = user.roles;
  } else if (Array.isArray(user.all_roles) && user.all_roles.length) {
    roles = user.all_roles.map(r => r.name ?? r).join(', ');
  }

  document.getElementById('profileName').textContent = user.name ?? '—';
  document.getElementById('profileEmail').textContent = user.email ?? '—';
  document.getElementById('profileRoles').textContent = roles || '—';
  document.getElementById('profileCreated').textContent = user.created_at ?? '—';
  document.getElementById('profileId').textContent = user.id ?? '—';

  msg.textContent = 'Prisijungęs.';
  box.classList.remove('hide');
  out.textContent = JSON.stringify(user, null, 2);
})();
</script>
@endsection
