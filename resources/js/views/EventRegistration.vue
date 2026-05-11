<template>
  <div>
    <h1 class="page-title">Registracija į renginį</h1>

    <div class="card mt-4" v-if="loading">Kraunama...</div>
    <div class="card mt-4" v-else-if="!event">Renginys nerastas.</div>

    <div v-else>
      <div class="card mt-4 card-flat">
        <h2 class="text-xl font-semibold tracking-tight">{{ event.pavadinimas }}</h2>
        <p class="muted mt-1 text-sm">{{ event.miestas }} · {{ formatDate(event.pradzios_data) }}</p>
      </div>

      <div class="card mt-4">
        <form @submit.prevent="submit">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="label">Vardas Pavardė *</label>
              <input v-model="form.vardas_pavarde" class="input mt-2" required />
            </div>
            <div>
              <label class="label">Telefonas</label>
              <input v-model="form.telefonas" class="input mt-2" />
            </div>
          </div>

          <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
              <label class="label">Automobilis</label>
              <input v-model="form.automobilis" class="input mt-2" />
            </div>
            <div>
              <label class="label">Valstybinis nr.</label>
              <input v-model="form.valstybinis_nr" class="input mt-2" />
            </div>
          </div>

          <div class="mt-4">
            <label class="label">Komentaras</label>
            <textarea v-model="form.komentaras" class="textarea mt-2" rows="4"></textarea>
          </div>

          <div class="mt-4">
            <label class="label">Nuotraukos (iki 5)</label>
            <input class="mt-2 block w-full text-sm" type="file" accept="image/*" multiple @change="onFiles" />
            <div class="muted mt-2 text-sm" v-if="selectedFiles.length">Pasirinkta: {{ selectedFiles.length }}</div>
          </div>

          <div class="mt-5 flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit" :disabled="submitting">Pateikti registraciją</button>
            <button class="btn" type="button" @click="close">Uždaryti</button>
          </div>
        </form>

        <p v-if="message" class="muted mt-4 text-sm">{{ message }}</p>
        <div v-if="error" class="alert alert-danger mt-4">{{ error }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { cropImages } from '../utils/cropImage.js';

const route = useRoute();
const router = useRouter();

const event = ref(null);
const loading = ref(true);
const submitting = ref(false);
const error = ref('');
const message = ref('');

const form = ref({
  vardas_pavarde: '',
  telefonas: '',
  automobilis: '',
  valstybinis_nr: '',
  komentaras: '',
});

const selectedFiles = ref([]);

onMounted(async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    router.push('/prisijungti');
    return;
  }

  const res = await fetch(`/api/auto-renginiai/${route.params.id}`, {
    headers: { Accept: 'application/json' },
  });

  if (res.ok) {
    const data = await res.json();
    event.value = data.auto_renginys;
  }
  loading.value = false;
});

async function onFiles(e) {
  error.value = '';
  const input = e?.target;
  const raw = Array.from(input?.files || []);
  if (input) input.value = '';
  if (raw.length > 5) {
    error.value = 'Galima įkelti daugiausia 5 nuotraukas.';
  }
  const files = raw.slice(0, 5);
  selectedFiles.value = await cropImages(files, { aspectRatio: 16 / 9, outputWidth: 1600 });
}

async function submit() {
  submitting.value = true;
  error.value = '';
  message.value = '';

  const token = localStorage.getItem('token');
  if (!token) {
    router.push('/prisijungti');
    return;
  }

  const body = new FormData();
  body.append('vardas_pavarde', form.value.vardas_pavarde);
  if (form.value.telefonas) body.append('telefonas', form.value.telefonas);
  if (form.value.automobilis) body.append('automobilis', form.value.automobilis);
  if (form.value.valstybinis_nr) body.append('valstybinis_nr', form.value.valstybinis_nr);
  if (form.value.komentaras) body.append('komentaras', form.value.komentaras);
  for (const file of selectedFiles.value) {
    body.append('nuotraukos[]', file);
  }

  const res = await fetch(`/api/auto-renginiai/${route.params.id}/registracija`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    error.value = `Klaida ${res.status}: ${data.zinute ?? data.message ?? 'Nepavyko'}`;
    submitting.value = false;
    return;
  }

  message.value = data.zinute ?? 'Registracija pateikta.';
  submitting.value = false;
}

function close() {
  if (window.opener) {
    window.close();
  } else {
    router.push(`/renginiai/${route.params.id}`);
  }
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
