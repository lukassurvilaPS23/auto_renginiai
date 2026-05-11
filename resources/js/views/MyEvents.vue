<template>
  <div>
    <h1 class="page-title">Mano renginiai</h1>
    <p class="muted mt-2 text-sm">
      Šis puslapis veiks, jei esi prisijungęs ir turi teises kurti/valdyti renginius.
      <span v-if="isAdmin">Kaip administratorius gali moderuoti visus renginius.</span>
    </p>

    <div class="card mt-4">
      <h3 class="text-base font-semibold">Kur aš užsiregistravau</h3>
      <p v-if="manoRegistracijos.loading" class="muted mt-2 text-sm">Kraunama...</p>
      <p v-else-if="!manoRegistracijos.list.length" class="muted mt-2 text-sm">Neturi registracijų.</p>

      <div v-else class="mt-3 overflow-auto rounded-2xl border" :style="{ borderColor: 'var(--border)' }">
        <table class="min-w-[720px] w-full text-sm" style="border-collapse: collapse;">
        <thead>
          <tr>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Renginys</th>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Data</th>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Statusas</th>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Veiksmai</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="x in manoRegistracijos.list" :key="x.id">
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
              <a class="link" :href="`/renginiai/${x.auto_renginys?.id}`">{{ x.auto_renginys?.pavadinimas ?? '-' }}</a>
              <div class="muted mt-1 text-xs">{{ x.auto_renginys?.miestas ?? '' }}</div>
            </td>
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">{{ formatDate(x.auto_renginys?.pradzios_data) }}</td>
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
              <span class="badge">{{ x.statusas }}</span>
            </td>
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
              <button class="btn" type="button" v-if="x.statusas === 'laukia' || x.statusas === 'patvirtinta'" @click="atsauktiManoRegistracija(x.auto_renginys?.id)">
                Atšaukti
              </button>
              <span v-else class="muted">—</span>
            </td>
          </tr>
        </tbody>
        </table>
      </div>
    </div>

    <div class="card mt-4">
      <h3 class="text-base font-semibold">Sukurti / atnaujinti renginį</h3>
      <input type="hidden" v-model="form.editId" />
      <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
          <label class="label">Pavadinimas</label>
          <input v-model="form.pavadinimas" class="input mt-2" />
        </div>
        <div>
          <label class="label">Miestas</label>
          <input v-model="form.miestas" class="input mt-2" />
        </div>
      </div>

      <div class="mt-4">
        <label class="label">Aprašymas</label>
        <textarea v-model="form.aprasymas" class="textarea mt-2" rows="3"></textarea>
      </div>

      <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
          <label class="label">Pradžios data (YYYY-MM-DD HH:MM:SS)</label>
          <input v-model="form.pradzios_data" class="input mt-2" placeholder="2025-12-30 18:00:00" />
        </div>
        <div>
          <label class="label">Pabaigos data (nebūtina)</label>
          <input v-model="form.pabaigos_data" class="input mt-2" placeholder="2025-12-30 21:00:00" />
        </div>
      </div>

      <div class="mt-4">
        <label class="label">Adresas</label>
        <input v-model="form.adresas" class="input mt-2" />
      </div>

      <div class="mt-5">
        <label class="label">Žemėlapis (lokacija + piešimas)</label>
        <EventMap
          v-model="form.zemelapio_objektai"
          :center="{ lat: form.latitude, lng: form.longitude }"
          :editable="true"
          help="Paspausk ant žemėlapio, kad pakeistum lokaciją. Naudok piešimo įrankius zonoms/linijoms." 
          @update:center="(c) => { form.latitude = c.lat; form.longitude = c.lng; }"
        />
      </div>

      <div class="mt-5">
        <label class="label">Renginio nuotraukos (iki 5)</label>
        <input class="input mt-2" type="file" accept="image/*" multiple @change="onPhotosSelected" />
        <p class="muted mt-2 text-xs">Jei redaguojant pasirinksi nuotraukas iš naujo — jos pakeis senas.</p>

        <div v-if="existingPhotoPaths.length" class="mt-3">
          <div class="muted text-xs font-medium">Esamos nuotraukos</div>
          <div class="mt-2 flex flex-wrap gap-3">
            <a
              v-for="(p, i) in existingPhotoPaths"
              :key="`${p}-${i}`"
              class="block overflow-hidden rounded-xl border"
              :style="{ borderColor: 'var(--border)' }"
              :href="storageUrl(p)"
              target="_blank"
              rel="noreferrer"
            >
              <img :src="storageUrl(p)" alt="" class="h-20 w-28 object-cover" loading="lazy" />
            </a>
          </div>
        </div>
      </div>

      <div class="mt-5 flex flex-wrap gap-2">
        <button class="btn btn-primary" type="button" @click="save">Saugoti</button>
        <button class="btn" type="button" @click="clearForm">Išvalyti formą</button>
      </div>
    </div>

    <div class="card mt-4" v-if="registracijos.show" style="display: block;">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-base font-semibold">Registracijos: {{ registracijos.pavad }}</h3>
        <button class="btn" type="button" @click="registracijos.show = false">Uždaryti</button>
      </div>
      <p v-if="registracijos.loading" class="muted mt-2 text-sm">Kraunama registracijos...</p>
      <p v-else-if="!registracijos.list.length" class="muted mt-2 text-sm">Nėra registracijų.</p>

      <div v-else class="mt-3 overflow-auto rounded-2xl border" :style="{ borderColor: 'var(--border)' }">
        <table class="min-w-[920px] w-full text-sm" style="border-collapse: collapse;">
        <thead>
          <tr>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Vardas</th>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">El. paštas</th>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Data</th>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Statusas</th>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Forma</th>
            <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">Veiksmai</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="x in registracijos.list" :key="x.id">
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
              <router-link v-if="x.vartotojas?.id" :to="`/profilis/${x.vartotojas.id}`">
                <span class="link">{{ x.vartotojas?.vardas ?? '-' }}</span>
              </router-link>
              <span v-else>{{ x.vartotojas?.vardas ?? '-' }}</span>
            </td>
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">{{ x.vartotojas?.el_pastas ?? '-' }}</td>
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">{{ formatDate(x.sukurta) }}</td>
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
              <span class="badge">{{ x.statusas ?? '-' }}</span>
            </td>
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
              <details>
                <summary class="cursor-pointer font-medium">Peržiūrėti</summary>
                <div class="muted mt-2 text-xs">
                  <div><b>Vardas pavardė:</b> {{ x.vardas_pavarde ?? '-' }}</div>
                  <div><b>Telefonas:</b> {{ x.telefonas ?? '-' }}</div>
                  <div><b>Automobilis:</b> {{ x.automobilis ?? '-' }}</div>
                  <div><b>Valst. nr:</b> {{ x.valstybinis_nr ?? '-' }}</div>
                  <div><b>Komentaras:</b> {{ x.komentaras ?? '-' }}</div>
                  <div v-if="Array.isArray(x.nuotraukos) && x.nuotraukos.length" class="mt-2">
                    <b>Nuotraukos:</b>
                    <div class="mt-2 flex flex-wrap gap-2">
                      <a v-for="(p, i) in normalizeStoragePaths(x.nuotraukos)" class="link" :key="`${p}-${i}`" :href="storageUrl(p)" target="_blank" rel="noreferrer">Atidaryti</a>
                    </div>
                  </div>
                </div>
              </details>
            </td>
            <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
              <template v-if="x.statusas === 'laukia'">
                <div class="flex flex-wrap gap-2">
                  <button class="btn btn-primary" type="button" @click="patvirtintiRegistracija(x.id)">Patvirtinti</button>
                  <button class="btn" type="button" @click="atsauktiRegistracija(x.id)">Atšaukti</button>
                </div>
              </template>
              <template v-else-if="x.statusas === 'patvirtinta'">
                <button class="btn" type="button" @click="atsauktiRegistracija(x.id)">Atšaukti</button>
              </template>
              <span v-else class="muted">—</span>
            </td>
          </tr>
        </tbody>
        </table>
      </div>
    </div>

    <div class="card mt-4">
      <p v-if="loading" class="muted text-sm">Kraunama...</p>
      <p v-else-if="!mine.length" class="muted text-sm">Neturi sukurtų renginių.</p>
      <div v-else>
        <h3 class="text-base font-semibold">{{ isAdmin ? 'Visi renginiai (moderavimas)' : 'Mano sukurti renginiai' }}</h3>
        <div class="mt-3 divide-y-2" :style="{ borderColor: 'var(--border)' }">
          <div v-for="r in mine" :key="r.id" class="py-6 first:pt-0 last:pb-0">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-semibold">{{ r.pavadinimas }}</div>
                <div class="muted mt-1 text-sm">
                  {{ r.miestas }} · {{ formatDate(r.pradzios_data) }}
                </div>
                <div class="muted mt-1 text-xs">
                  Pabaiga: {{ formatDate(r.pabaigos_data) || 'Nenurodyta' }} | ID: {{ r.id }}
                </div>
              </div>
              <div class="flex flex-wrap gap-2">
                <button class="btn" type="button" @click="edit(r)">Redaguoti</button>
                <button class="btn" type="button" @click="del(r.id)">Trinti</button>
                <button class="btn btn-primary" type="button" @click="loadRegistracijos(r.id, r.pavadinimas)">Registracijos</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import EventMap from '../EventMap.vue';
import { storageUrl, normalizeStoragePaths } from '../utils/storageUrl.js';
import { cropImages } from '../utils/cropImage.js';

const existingPhotoPaths = computed(() => normalizeStoragePaths(form.value?.nuotraukos));

const router = useRouter();
const loading = ref(true);
const mine = ref([]);
const isAdmin = ref(false);
const manoRegistracijos = ref({ loading: true, list: [] });
const form = ref({
  editId: '',
  pavadinimas: '',
  miestas: '',
  aprasymas: '',
  pradzios_data: '',
  pabaigos_data: '',
  adresas: '',
  latitude: 54.6872,
  longitude: 25.2797,
  zemelapio_objektai: null,
  nuotraukos: [],
});
const registracijos = ref({ show: false, pavad: '', list: [], loading: false });
const selectedPhotos = ref([]);

onMounted(async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    router.push('/prisijungti');
    return;
  }
  const meRes = await fetch('/api/as', {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  if (!meRes.ok) {
    router.push('/prisijungti');
    return;
  }
  const meData = await meRes.json();
  const myId = meData.vartotojas?.id ?? meData.id ?? null;
  const roles = Array.isArray(meData.roles) ? meData.roles : [];
  isAdmin.value = roles.includes('administratorius');

  const myRegRes = await fetch('/api/mano-registracijos', {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  if (myRegRes.ok) {
    const d = await myRegRes.json();
    manoRegistracijos.value.list = d.registracijos ?? [];
  } else {
    manoRegistracijos.value.list = [];
  }
  manoRegistracijos.value.loading = false;

  const res = await fetch('/api/auto-renginiai', {
    headers: { Accept: 'application/json' },
  });
  if (res.ok) {
    const data = await res.json();
    const all = data.auto_renginiai || [];
    mine.value = isAdmin.value ? all : (myId ? all.filter(r => Number(r.organizatorius_id) === Number(myId)) : []);
  }
  loading.value = false;
});

function clearForm() {
  form.value = {
    editId: '',
    pavadinimas: '',
    miestas: '',
    aprasymas: '',
    pradzios_data: '',
    pabaigos_data: '',
    adresas: '',
    latitude: 54.6872,
    longitude: 25.2797,
    zemelapio_objektai: null,
    nuotraukos: [],
  };
  selectedPhotos.value = [];
}

function edit(r) {
  form.value.editId = r.id;
  form.value.pavadinimas = r.pavadinimas ?? '';
  form.value.miestas = r.miestas ?? '';
  form.value.aprasymas = r.aprasymas ?? '';
  form.value.pradzios_data = (r.pradzios_data ?? '').replace('T', ' ').substring(0, 19);
  form.value.pabaigos_data = r.pabaigos_data ? String(r.pabaigos_data).replace('T', ' ').substring(0, 19) : '';
  form.value.adresas = r.adresas ?? '';
  form.value.latitude = Number(r.latitude ?? 54.6872);
  form.value.longitude = Number(r.longitude ?? 25.2797);
  form.value.zemelapio_objektai = r.zemelapio_objektai ?? null;
  form.value.nuotraukos = Array.isArray(r.nuotraukos) ? r.nuotraukos : [];
  selectedPhotos.value = [];
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function onPhotosSelected(e) {
  const input = e?.target;
  const files = Array.from(input?.files || []).slice(0, 5);
  if (input) input.value = '';
  const cropped = await cropImages(files, { aspectRatio: 16 / 9, outputWidth: 1600 });
  selectedPhotos.value = await Promise.all(cropped.map((f) => downscaleImage(f)));
}

async function downscaleImage(file) {
  try {
    const img = await fileToImage(file);
    const maxSide = 1400;
    const ratio = Math.min(1, maxSide / Math.max(img.width, img.height));
    const w = Math.max(1, Math.round(img.width * ratio));
    const h = Math.max(1, Math.round(img.height * ratio));

    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    if (!ctx) return file;
    ctx.drawImage(img, 0, 0, w, h);

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
    if (!blob) return file;
    const nameBase = String(file.name || 'nuotrauka').replace(/\.[^/.]+$/, '');
    return new File([blob], `${nameBase}.jpg`, { type: 'image/jpeg' });
  } catch {
    return file;
  }
}

function fileToImage(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve(img);
    };
    img.onerror = (e) => {
      URL.revokeObjectURL(url);
      reject(e);
    };
    img.src = url;
  });
}

async function del(id) {
  if (!confirm('Ar tikrai trinti?')) return;
  const token = localStorage.getItem('token');
  const res = await fetch(`/api/auto-renginiai/${id}`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await res.json();
  if (!res.ok) {
    alert(`Klaida ${res.status}: ${data.zinute ?? data.message ?? 'Nepavyko'}`);
  } else {
    alert(data.zinute ?? 'Ištrinta');
    location.reload(); // paprastas perkrovimas
  }
}

async function save() {
  const token = localStorage.getItem('token');
  const data = { ...form.value };
  delete data.editId;
  delete data.nuotraukos;
  if (!data.pavadinimas || !data.miestas || !data.pradzios_data) {
    alert('Reikia: pavadinimas, miestas, pradžios data');
    return;
  }
  const url = form.value.editId ? `/api/auto-renginiai/${form.value.editId}` : '/api/auto-renginiai';
  const method = form.value.editId ? 'PUT' : 'POST';
  const hasPhotos = Array.isArray(selectedPhotos.value) && selectedPhotos.value.length > 0;
  const uploadMethod = hasPhotos && method === 'PUT' ? 'POST' : method;
  const res = await fetch(url, hasPhotos ? {
    method: uploadMethod,
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: (() => {
      const fd = new FormData();
      if (method === 'PUT') {
        fd.set('_method', 'PUT');
      }
      for (const [k, v] of Object.entries(data)) {
        if (v === undefined || v === null) continue;
        if (k === 'zemelapio_objektai') {
          fd.set(k, JSON.stringify(v));
        } else {
          fd.set(k, String(v));
        }
      }
      for (const f of selectedPhotos.value.slice(0, 5)) {
        fd.append('nuotraukos[]', f);
      }
      return fd;
    })(),
  } : {
    method,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(data),
  });
  const payload = await res.json();
  if (!res.ok) {
    alert(`Klaida ${res.status}: ${payload.zinute ?? payload.message ?? 'Nepavyko'}`);
  } else {
    alert(payload.zinute ?? 'OK');
    clearForm();
    location.reload();
  }
}

async function loadRegistracijos(id, pavad) {
  const token = localStorage.getItem('token');
  registracijos.value = { show: true, pavad, list: [], loading: true };
  const res = await fetch(`/api/auto-renginiai/${id}/registracijos`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  if (res.ok) {
    const data = await res.json();
    registracijos.value.list = data.registracijos ?? [];
  } else {
    registracijos.value.list = [];
  }
  registracijos.value.loading = false;
}

async function patvirtintiRegistracija(registracijaId) {
  const token = localStorage.getItem('token');
  const res = await fetch(`/api/registracijos/${registracijaId}/patvirtinti`, {
    method: 'PATCH',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await res.json();
  if (!res.ok) {
    alert(`Klaida ${res.status}: ${data.zinute ?? data.message ?? 'Nepavyko'}`);
    return;
  }
  alert(data.zinute ?? 'Patvirtinta');
  registracijos.value.list = registracijos.value.list.map((x) => (x.id === registracijaId ? { ...x, statusas: 'patvirtinta' } : x));
}

async function atsauktiRegistracija(registracijaId) {
  const token = localStorage.getItem('token');
  const res = await fetch(`/api/registracijos/${registracijaId}/atsaukti`, {
    method: 'PATCH',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await res.json();
  if (!res.ok) {
    alert(`Klaida ${res.status}: ${data.zinute ?? data.message ?? 'Nepavyko'}`);
    return;
  }
  alert(data.zinute ?? 'Atšaukta');
  registracijos.value.list = registracijos.value.list.map((x) => (x.id === registracijaId ? { ...x, statusas: 'atsaukta' } : x));
}

async function atsauktiManoRegistracija(autoRenginysId) {
  if (!autoRenginysId) return;
  if (!confirm('Ar tikrai atšaukti registraciją?')) return;
  const token = localStorage.getItem('token');
  const res = await fetch(`/api/auto-renginiai/${autoRenginysId}/registracija`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await res.json();
  if (!res.ok) {
    alert(`Klaida ${res.status}: ${data.zinute ?? data.message ?? 'Nepavyko'}`);
    return;
  }
  alert(data.zinute ?? 'Atšaukta');
  manoRegistracijos.value.list = manoRegistracijos.value.list.map((x) => (x.auto_renginys?.id === autoRenginysId ? { ...x, statusas: 'atsaukta' } : x));
}

function formatDate(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
</script>

<style scoped></style>
