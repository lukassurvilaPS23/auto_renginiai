@extends('layouts.app')

@section('content')
<h1>Mano renginiai</h1>
<p class="muted">Šis puslapis veiks, jei esi prisijungęs ir turi teises kurti/valdyti renginius.</p>

<div class="card">
  <h3>Sukurti / atnaujinti renginį</h3>

  <input type="hidden" id="editId">

  <div class="row">
    <div style="flex:1; min-width:240px;">
      <label>Pavadinimas</label>
      <input id="pavadinimas">
    </div>
    <div style="flex:1; min-width:240px;">
      <label>Miestas</label>
      <input id="miestas">
    </div>
  </div>

  <div style="margin-top:10px;">
    <label>Aprašymas</label>
    <textarea id="aprasymas" rows="3"></textarea>
  </div>

  <div class="row" style="margin-top:10px;">
    <div style="flex:1; min-width:240px;">
      <label>Pradžios data (YYYY-MM-DD HH:MM:SS)</label>
      <input id="pradzios_data" placeholder="2025-12-30 18:00:00">
    </div>
    <div style="flex:1; min-width:240px;">
      <label>Pabaigos data (nebūtina)</label>
      <input id="pabaigos_data" placeholder="2025-12-30 21:00:00">
    </div>
  </div>

  <div style="margin-top:10px;">
    <label>Adresas</label>
    <input id="adresas">
  </div>

  <div class="row" style="margin-top:12px;">
    <button class="btn" id="saugotiBtn" type="button">Saugoti</button>
    <button class="btn" id="isvalytiBtn" type="button">Išvalyti formą</button>
  </div>
</div>

{{-- ČIA BUS RODOMOS REGISTRACIJOS --}}
<div id="registracijosBox" class="card" style="display:none;"></div>

<div id="list" class="card">Kraunama...</div>

<script>
(async () => {
  const list = document.getElementById('list');
  const regBox = document.getElementById('registracijosBox');

  function formData() {
    return {
      pavadinimas: document.getElementById('pavadinimas').value.trim(),
      miestas: document.getElementById('miestas').value.trim(),
      aprasymas: document.getElementById('aprasymas').value.trim() || null,
      pradzios_data: document.getElementById('pradzios_data').value.trim(),
      pabaigos_data: document.getElementById('pabaigos_data').value.trim() || null,
      adresas: document.getElementById('adresas').value.trim() || null,
    };
  }

  function clearForm() {
    document.getElementById('editId').value = '';
    document.getElementById('pavadinimas').value = '';
    document.getElementById('miestas').value = '';
    document.getElementById('aprasymas').value = '';
    document.getElementById('pradzios_data').value = '';
    document.getElementById('pabaigos_data').value = '';
    document.getElementById('adresas').value = '';
  }

  function hideRegistracijos() {
    regBox.style.display = 'none';
    regBox.innerHTML = '';
  }

  async function loadRegistracijos(renginioId) {
    regBox.style.display = 'block';
    regBox.textContent = 'Kraunama registracijos...';

    const res = await Api.request('GET', `/api/auto-renginiai/${renginioId}/registracijos`);
    if (!res.ok) {
      regBox.innerHTML = `
        <h3>Registracijos</h3>
        <p>Klaida ${res.status}: ${(res.payload.zinute ?? res.payload.message ?? 'Nepavyko')}</p>
        <button class="btn" id="uzdarytiReg" type="button">Uždaryti</button>
      `;
      document.getElementById('uzdarytiReg').addEventListener('click', hideRegistracijos);
      return;
    }

    const pavad = res.payload.auto_renginys?.pavadinimas ?? '';
    const regs = res.payload.registracijos ?? [];

    if (regs.length === 0) {
      regBox.innerHTML = `
        <h3>Registracijos: ${pavad}</h3>
        <p>Nėra registracijų.</p>
        <button class="btn" id="uzdarytiReg" type="button">Uždaryti</button>
      `;
      document.getElementById('uzdarytiReg').addEventListener('click', hideRegistracijos);
      return;
    }

    regBox.innerHTML = `
      <h3>Registracijos: ${pavad}</h3>
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">Vardas</th>
            <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">El. paštas</th>
            <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">Data</th>
          </tr>
        </thead>
        <tbody>
          ${regs.map(x => `
            <tr>
              <td style="border-bottom:1px solid #eee; padding:8px;">${x.vartotojas?.vardas ?? '-'}</td>
              <td style="border-bottom:1px solid #eee; padding:8px;">${x.vartotojas?.el_pastas ?? '-'}</td>
              <td style="border-bottom:1px solid #eee; padding:8px;">${Api.formatDate(x.sukurta)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      <div style="margin-top:12px;">
        <button class="btn" id="uzdarytiReg" type="button">Uždaryti</button>
      </div>
    `;
    document.getElementById('uzdarytiReg').addEventListener('click', hideRegistracijos);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  async function load() {
    list.textContent = 'Kraunama...';
    hideRegistracijos();

    const me = await Api.request('GET', '/api/as');
    if (!me.ok) { list.textContent = 'Prisijunk, kad matytum šį puslapį.'; return; }

    const myId = me.payload.vartotojas?.id ?? me.payload.id ?? null;

    const res = await Api.request('GET', '/api/auto-renginiai');
    if (!res.ok) { list.textContent = `Klaida (status ${res.status})`; return; }

    const all = res.payload.auto_renginiai || [];

    const mine = myId ? all.filter(r => Number(r.organizatorius_id) === Number(myId)) : [];

    if (mine.length === 0) {
      list.innerHTML = '<p>Neturi sukurtų renginių.</p>';
      return;
    }

    list.innerHTML = '<h3>Mano sukurti renginiai</h3>' + mine.map(r => `
      <div class="card">
        <b>${r.pavadinimas}</b> — ${r.miestas} — ${Api.formatDate(r.pradzios_data)}<br>
        <span class="muted">Pabaiga: ${Api.formatDate(r.pabaigos_data) || 'Nenurodyta'} | ID: ${r.id}</span>
        <div class="row" style="margin-top:10px;">
          <button class="btn" data-edit="${r.id}" type="button">Redaguoti</button>
          <button class="btn" data-del="${r.id}" type="button">Trinti</button>
          <button class="btn" data-reg="${r.id}" type="button">Registracijos</button>
        </div>
      </div>
    `).join('');

    // Edit
    list.querySelectorAll('[data-edit]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-edit');
        const r = mine.find(x => String(x.id) === String(id));
        if (!r) return;
        document.getElementById('editId').value = r.id;
        document.getElementById('pavadinimas').value = r.pavadinimas ?? '';
        document.getElementById('miestas').value = r.miestas ?? '';
        document.getElementById('aprasymas').value = r.aprasymas ?? '';
        document.getElementById('pradzios_data').value = (r.pradzios_data ?? '').replace('T',' ').substring(0,19);
        document.getElementById('pabaigos_data').value = r.pabaigos_data ? String(r.pabaigos_data).replace('T',' ').substring(0,19) : '';
        document.getElementById('adresas').value = r.adresas ?? '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });

    // Delete
    list.querySelectorAll('[data-del]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-del');
        if (!confirm('Ar tikrai trinti?')) return;
        const del = await Api.request('DELETE', `/api/auto-renginiai/${id}`);
        if (!del.ok) { alert(`Klaida ${del.status}: ` + (del.payload.zinute ?? del.payload.message ?? 'Nepavyko')); return; }
        alert(del.payload.zinute ?? 'Ištrinta');
        await load();
      });
    });

    // Registracijos
    list.querySelectorAll('[data-reg]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-reg');
        await loadRegistracijos(id);
      });
    });
  }

  document.getElementById('isvalytiBtn').addEventListener('click', clearForm);

  document.getElementById('saugotiBtn').addEventListener('click', async () => {
    const id = document.getElementById('editId').value;
    const data = formData();

    if (!data.pavadinimas || !data.miestas || !data.pradzios_data) {
      alert('Reikia: pavadinimas, miestas, pradžios data');
      return;
    }

    let res;
    if (id) {
      res = await Api.request('PUT', `/api/auto-renginiai/${id}`, {
        pavadinimas: data.pavadinimas,
        miestas: data.miestas,
        aprasymas: data.aprasymas,
        pradzios_data: data.pradzios_data,
        pabaigos_data: data.pabaigos_data,
        adresas: data.adresas
      });
    } else {
      res = await Api.request('POST', '/api/auto-renginiai', data);
    }

    if (!res.ok) {
      alert(`Klaida ${res.status}: ` + Api.errorMessage(res.payload));
      return;
    }

    alert(res.payload.zinute ?? 'OK');
    clearForm();
    await load();
  });

  await load();
})();
</script>
@endsection
