<template>
  <div>
    <div class="flex flex-wrap items-center gap-3">
      <h1 class="page-title">Mano garažas</h1>
      <span class="badge" v-if="!loading">{{ cars.length }} vnt.</span>
      <div class="ml-auto">
        <button class="btn btn-primary" type="button" @click="showForm = true">+ Pridėti automobilį</button>
      </div>
    </div>

    <div v-if="showForm" class="card mt-4">
      <h3 class="text-base font-semibold">{{ editing ? 'Redaguoti automobilį' : 'Naujas automobilis' }}</h3>
      <form @submit.prevent="save">
        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <div>
            <label class="label">Markė *</label>
            <input v-model="form.marke" class="input mt-2" required placeholder="Pvz. BMW" />
          </div>
          <div>
            <label class="label">Modelis *</label>
            <input v-model="form.modelis" class="input mt-2" required placeholder="Pvz. M3" />
          </div>
          <div>
            <label class="label">Metai *</label>
            <input v-model="form.metai" class="input mt-2" type="number" required min="1900" :max="currentYear + 1" />
          </div>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <div>
            <label class="label">Spalva</label>
            <input v-model="form.spalva" class="input mt-2" placeholder="Pvz. Juoda" />
          </div>
          <div>
            <label class="label">VIN kodas</label>
            <input v-model="form.vin" class="input mt-2" maxlength="17" placeholder="17 simbolių" />
          </div>
          <div>
            <label class="label">Variklis</label>
            <input v-model="form.variklis" class="input mt-2" placeholder="Pvz. 3.0L" />
          </div>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <div>
            <label class="label">Kuras</label>
            <input v-model="form.kuras" class="input mt-2" placeholder="Pvz. Benzinas" />
          </div>
          <div class="md:col-span-2">
            <label class="label">Aprašymas</label>
            <textarea v-model="form.aprasymas" class="textarea mt-2" rows="2"></textarea>
          </div>
        </div>
        <div class="mt-4">
          <label class="label">Nuotrauka</label>
          <input class="mt-2 block w-full text-sm" type="file" @change="handlePhotoUpload" accept="image/*" />
          <p v-if="form.nuotrauka_preview" class="muted mt-2 text-sm">Pasirinkta: {{ form.nuotrauka_preview.name }}</p>
        </div>
        <div class="mt-5 flex flex-wrap gap-2">
          <button type="submit" class="btn btn-primary">Išsaugoti</button>
          <button type="button" class="btn" @click="cancelForm">Atšaukti</button>
        </div>
      </form>
    </div>

    <div class="card mt-4" style="min-height: 120px;">
      <p v-if="loading" class="muted">Kraunama...</p>
      <p v-else-if="!cars.length" class="muted">Automobilių nėra.</p>
      <div v-else>
        <div class="divide-y-2" :style="{ borderColor: 'var(--border)' }">
          <div v-for="car in cars" :key="car.id" class="py-6 first:pt-0 last:pb-0">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0">
                <h3 class="text-base font-semibold">{{ car.marke }} {{ car.modelis }}</h3>
                <p class="mt-1 text-sm muted">{{ car.metai }} · {{ car.spalva || 'Nenurodyta spalva' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button class="btn" type="button" @click.stop="edit(car)">Redaguoti</button>
                <button class="btn" type="button" @click.stop="remove(car.id)">Ištrinti</button>
              </div>
            </div>

            <div v-if="car.nuotrauka" class="mt-4">
              <img class="w-full max-h-[320px] rounded-2xl object-cover" :src="car.nuotrauka" alt="Automobilio nuotrauka" />
            </div>

            <div class="mt-4 grid gap-2 text-sm">
              <p v-if="car.vin"><span class="muted">VIN:</span> {{ car.vin }}</p>
              <p v-if="car.variklis"><span class="muted">Variklis:</span> {{ car.variklis }}</p>
              <p v-if="car.kuras"><span class="muted">Kuras:</span> {{ car.kuras }}</p>
              <p v-if="car.aprasymas" class="leading-relaxed">{{ car.aprasymas }}</p>
              <button class="btn btn-ghost w-fit" type="button" @click="viewDetail(car)">Atidaryti</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { cropImage } from '../utils/cropImage.js';

const cars = ref([]);
const loading = ref(true);
const showForm = ref(false);
const editing = ref(null);
const form = ref({
  marke: '',
  modelis: '',
  metai: new Date().getFullYear(),
  spalva: '',
  vin: '',
  variklis: '',
  kuras: '',
  aprasymas: '',
  nuotrauka: null,
  nuotrauka_preview: null,
});

const currentYear = computed(() => new Date().getFullYear());

const token = localStorage.getItem('token');
const router = useRouter();

function viewDetail(car) {
  console.log('Viewing car detail:', car);
  router.push(`/automobiliai/${car.id}`);
}

async function load() {
  loading.value = true;
  const res = await fetch('/api/automobiliai', {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (res.ok) {
    const data = await res.json();
    console.log('API response:', data);
    cars.value = data.automobiliai || [];
  } else {
    console.error('API error:', res.status);
  }
  loading.value = false;
}

async function handlePhotoUpload(e) {
  const file = e.target.files[0];
  e.target.value = '';
  if (!file) return;
  const cropped = await cropImage(file, { aspectRatio: 16 / 9, outputWidth: 1600 });
  if (!cropped) return;
  form.value.nuotrauka = cropped;
  form.value.nuotrauka_preview = cropped;
}

async function save() {
  const method = editing.value ? 'PUT' : 'POST';
  const url = editing.value ? `/api/automobiliai/${editing.value.id}` : '/api/automobiliai';

  const formData = new FormData();
  formData.append('marke', form.value.marke);
  formData.append('modelis', form.value.modelis);
  formData.append('metai', form.value.metai);
  if (form.value.spalva) formData.append('spalva', form.value.spalva);
  if (form.value.vin) formData.append('vin', form.value.vin);
  if (form.value.variklis) formData.append('variklis', form.value.variklis);
  if (form.value.kuras) formData.append('kuras', form.value.kuras);
  if (form.value.aprasymas) formData.append('aprasymas', form.value.aprasymas);
  if (form.value.nuotrauka) formData.append('nuotrauka', form.value.nuotrauka);

  const res = await fetch(url, {
    method,
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
    body: formData,
  });

  if (res.ok) {
    cancelForm();
    await load();
  } else {
    const err = await res.json().catch(() => ({ message: 'Nežinoma klaida' }));
    const errorMsg = err.errors ? Object.values(err.errors).flat().join(', ') : (err.message || err.zinute || JSON.stringify(err));
    alert('Klaida: ' + errorMsg);
  }
}

function edit(car) {
  editing.value = car;
  form.value = { ...car };
  showForm.value = true;
}

async function remove(id) {
  if (!confirm('Ar tikrai norite ištrinti šį automobilį?')) return;
  const res = await fetch(`/api/automobiliai/${id}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (res.ok) {
    await load();
  }
}

function cancelForm() {
  showForm.value = false;
  editing.value = null;
  form.value = {
    marke: '',
    modelis: '',
    metai: new Date().getFullYear(),
    spalva: '',
    vin: '',
    variklis: '',
    kuras: '',
    aprasymas: '',
    nuotrauka: null,
    nuotrauka_preview: null,
  };
}

onMounted(load);
</script>

<style scoped></style>
