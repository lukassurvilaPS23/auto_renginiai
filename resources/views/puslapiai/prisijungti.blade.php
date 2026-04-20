@extends('layouts.app')

@section('content')
<h1>Prisijungti</h1>

<div class="card">
  <form id="forma">
      <div>
          <label>El. paštas</label>
          <input name="el_pastas" required>
      </div>
      <div style="margin-top:10px;">
          <label>Slaptažodis</label>
          <input name="slaptazodis" type="password" required>
      </div>
      <button class="btn" style="margin-top:12px;" type="submit">Prisijungti</button>
  </form>
</div>

<script>
document.getElementById('forma').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = Object.fromEntries(fd.entries());

  const { ok, status, payload } = await Api.request('POST', '/api/prisijungti', data);
  if (!ok) { alert(`Klaida ${status}: ` + (payload.message ?? payload.zinute ?? 'Nepavyko')); return; }

  Api.setToken(payload.token);
  UI.setAuthNav(true);
  window.location.href = '/profilis';
});
</script>
@endsection