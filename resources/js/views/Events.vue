<template>
  <div>
    <div class="flex flex-wrap items-center gap-3">
      <h1 class="page-title">Renginiai</h1>
      <span class="badge" v-if="!loading">{{ list.length }} vnt.</span>
    </div>

    <div class="card mt-4">
      <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
        <div class="lg:col-span-2">
          <label class="label">Pavadinimas</label>
          <input v-model="filters.pavadinimas" class="input mt-2" placeholder="Pvz. Drift" />
        </div>
        <div class="lg:col-span-2">
          <label class="label">Miestas</label>
          <input v-model="filters.miestas" class="input mt-2" placeholder="Pvz. Vilnius" />
        </div>
        <div>
          <label class="label">Statusas</label>
          <select v-model="filters.statusas" class="select mt-2">
            <option value="visi">Visi (numatyta)</option>
            <option value="aktyvus">Aktyvūs</option>
            <option value="pasibaiges">Pasibaigę</option>
            <option value="atsauktas">Atšaukti</option>
          </select>
        </div>
        <div>
          <label class="label">Rikiuoti</label>
          <select v-model="filters.sort" class="select mt-2">
            <option value="pradzios_data:desc">Nuo naujausių</option>
            <option value="pradzios_data:asc">Nuo seniausių</option>
            <option value="pavadinimas:asc">Pavadinimas A-Z</option>
            <option value="pavadinimas:desc">Pavadinimas Z-A</option>
            <option value="miestas:asc">Miestas A-Z</option>
            <option value="miestas:desc">Miestas Z-A</option>
          </select>
        </div>
        <div>
          <label class="label">Data nuo</label>
          <input v-model="filters.pradzios_nuo" class="input mt-2" type="date" />
        </div>
        <div>
          <label class="label">Data iki</label>
          <input v-model="filters.pradzios_iki" class="input mt-2" type="date" />
        </div>
        <div class="flex items-end">
          <button class="btn btn-primary w-full" @click="load" type="button">Filtruoti</button>
        </div>
      </div>
    </div>

    <div class="card mt-4" style="min-height: 120px;">
      <p v-if="loading" class="muted">Kraunama...</p>
      <p v-else-if="!list.length" class="muted">Renginių nėra.</p>
      <div v-else>
        <div class="divide-y-2" :style="{ borderColor: 'var(--border)' }">
          <div v-for="r in list" :key="r.id" class="py-6 first:pt-0 last:pb-0">
            <div class="flex items-start justify-between gap-4">
              <div class="flex min-w-0 flex-1 items-start gap-3">
                <div
                  v-if="eventListThumb(r)"
                  class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border-2"
                  :style="{ borderColor: 'var(--border)' }"
                >
                  <img :src="eventListThumb(r)" alt="" class="h-full w-full object-cover" loading="lazy" />
                </div>
                <div class="min-w-0">
                  <a :href="`/renginiai/${r.id}`" class="link text-base sm:text-lg">
                    {{ r.pavadinimas }}
                  </a>
                  <p class="mt-1 text-sm muted">{{ r.miestas }} · {{ formatDate(r.pradzios_data) }}</p>
                </div>
              </div>
              <span class="badge" :class="statusBadgeClass(r)">{{ statusLabel(r) }}</span>
            </div>
            <p class="mt-3 text-sm leading-relaxed" :style="{ color: 'var(--text)' }">
              {{ r.aprasymas }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { storageUrl, normalizeStoragePaths } from '../utils/storageUrl.js';

function eventListThumb(r) {
  const paths = normalizeStoragePaths(r?.nuotraukos);
  const p = paths[0];
  return p ? storageUrl(p) : '';
}

const filters = ref({
  pavadinimas: '',
  miestas: '',
  statusas: 'visi',
  sort: 'pradzios_data:desc',
  pradzios_nuo: '',
  pradzios_iki: '',
});
const list = ref([]);
const loading = ref(true);

onMounted(load);

async function load() {
  loading.value = true;
  const qs = new URLSearchParams();
  if (filters.value.pavadinimas) qs.set('pavadinimas', filters.value.pavadinimas);
  if (filters.value.miestas) qs.set('miestas', filters.value.miestas);
  if (filters.value.statusas && filters.value.statusas !== 'visi') qs.set('statusas', filters.value.statusas);
  if (filters.value.pradzios_nuo) qs.set('pradzios_nuo', filters.value.pradzios_nuo);
  if (filters.value.pradzios_iki) qs.set('pradzios_iki', filters.value.pradzios_iki);

  const [rikiuoti, kryptis] = String(filters.value.sort || '').split(':');
  if (rikiuoti) qs.set('rikiuoti', rikiuoti);
  if (kryptis) qs.set('kryptis', kryptis);

  const url = '/api/auto-renginiai' + (qs.toString() ? `?${qs}` : '');
  const res = await fetch(url, { headers: { Accept: 'application/json' } });
  if (res.ok) {
    const data = await res.json();
    list.value = data.auto_renginiai || [];
  }
  loading.value = false;
}

function formatDate(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function isEnded(r) {
  const end = r.pabaigos_data || r.pradzios_data;
  if (!end) return false;
  const d = new Date(end);
  if (Number.isNaN(d.getTime())) return false;
  return d.getTime() < Date.now();
}

function statusLabel(r) {
  if (r.statusas === 'atsauktas') return 'atšauktas';
  if (isEnded(r)) return 'pasibaigęs';
  if (r.statusas) return String(r.statusas);
  return 'aktyvus';
}

function statusBadgeClass(r) {
  if (r.statusas === 'atsauktas') return 'badge-warning';
  if (isEnded(r)) return 'badge-danger';
  return 'badge-success';
}
</script>

<style scoped></style>
