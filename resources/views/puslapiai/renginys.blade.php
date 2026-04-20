@extends('layouts.app')

@section('content')
<h1>Auto renginys</h1>

<div id="box" class="card">Kraunama...</div>

<div class="card">
    <button class="btn" id="regBtn" type="button">Registruotis</button>
    <button class="btn" id="atsisBtn" type="button">Atšaukti registraciją</button>
    <p class="muted">Registracija veikia tik prisijungus (token).</p>
</div>

<script>
const id = {{ $id }};

(async () => {
  const box = document.getElementById('box');

  const { ok, status, payload } = await Api.request('GET', `/api/auto-renginiai/${id}`);
  if (!ok) { box.textContent = `Renginys nerastas (status ${status})`; return; }

  const r = payload.auto_renginys;
  box.innerHTML = `
    <h2>${r.pavadinimas}</h2>
    <p>${r.aprasymas ?? ''}</p>
    <p><b>Miestas:</b> ${r.miestas}</p>
    <p><b>Adresas:</b> ${r.adresas ?? ''}</p>
    <p><b>Pradžia:</b> ${Api.formatDate(r.pradzios_data)}</p>
    <p><b>Pabaiga:</b> ${Api.formatDate(r.pabaigos_data)}</p>
    <p><b>Statusas:</b> ${r.statusas}</p>
  `;
})();

document.getElementById('regBtn').addEventListener('click', async () => {
  if (!Api.getToken()) {
    alert('Prisijunk, kad galėtum registruotis.');
    window.location.href = '/prisijungti';
    return;
  }
  const { ok, status, payload } = await Api.request('POST', `/api/auto-renginiai/${id}/registracija`);
  if (!ok) { alert(`Klaida ${status}: ` + Api.errorMessage(payload)); return; }
  alert(payload.zinute ?? 'Registracija sėkminga');
});

document.getElementById('atsisBtn').addEventListener('click', async () => {
  if (!Api.getToken()) {
    alert('Prisijunk, kad galėtum atšaukti registraciją.');
    window.location.href = '/prisijungti';
    return;
  }
  const { ok, status, payload } = await Api.request('DELETE', `/api/auto-renginiai/${id}/registracija`);
  if (!ok) { alert(`Klaida ${status}: ` + Api.errorMessage(payload)); return; }
  alert(payload.zinute ?? 'Registracija atšaukta');
});
</script>
@endsection