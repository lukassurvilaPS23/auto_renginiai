<template>
  <div class="flex min-h-full flex-col items-center justify-center">
    <div class="w-full max-w-lg">
      <h1 class="page-title">Naujas slaptažodis</h1>
      <div class="card mt-4 w-full">
        <p v-if="!hasParams" class="muted text-sm">
          Trūksta nuorodos parametrų. Naudok nuorodą iš el. laiško arba
          <router-link class="link" to="/pamirsau-slaptazodi">užsakyk naują</router-link>.
        </p>
        <form v-else @submit.prevent="submit">
          <div>
            <label class="label">El. paštas</label>
            <input v-model="form.el_pastas" class="input mt-2" type="email" required readonly />
          </div>
          <div class="mt-4">
            <label class="label">Naujas slaptažodis</label>
            <input v-model="form.slaptazodis" class="input mt-2" type="password" required minlength="6" autocomplete="new-password" />
          </div>
          <div class="mt-4">
            <label class="label">Pakartok slaptažodį</label>
            <input v-model="form.slaptazodis_confirmation" class="input mt-2" type="password" required minlength="6" autocomplete="new-password" />
          </div>
          <div class="mt-5 flex flex-wrap items-center gap-3">
            <button class="btn btn-primary" type="submit" :disabled="loading">
              {{ loading ? 'Saugoma...' : 'Išsaugoti' }}
            </button>
            <router-link class="link text-sm" to="/prisijungti">Į prisijungimą</router-link>
          </div>
        </form>
        <div v-if="message" class="alert mt-4" :class="error ? 'alert-danger' : ''" :style="!error ? { borderColor: 'var(--border)', background: 'var(--surface-2)' } : {}">
          {{ message }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const form = ref({
  el_pastas: '',
  token: '',
  slaptazodis: '',
  slaptazodis_confirmation: '',
});
const loading = ref(false);
const message = ref('');
const error = ref(false);

const hasParams = computed(() => !!(form.value.token && form.value.el_pastas));

onMounted(() => {
  const token = typeof route.query.token === 'string' ? route.query.token : '';
  let email = typeof route.query.email === 'string' ? route.query.email : '';
  if (email && email.includes(' ')) {
    email = email.replace(/\s/g, '+');
  }
  form.value.token = token;
  form.value.el_pastas = email;
});

async function submit() {
  if (!hasParams.value) return;
  loading.value = true;
  message.value = '';
  error.value = false;
  try {
    const res = await fetch('/api/slaptazodzio-keitimas', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        el_pastas: form.value.el_pastas,
        token: form.value.token,
        slaptazodis: form.value.slaptazodis,
        slaptazodis_confirmation: form.value.slaptazodis_confirmation,
      }),
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok) {
      message.value = data.zinute || 'Slaptažodis pakeistas.';
      error.value = false;
      setTimeout(() => router.push('/prisijungti'), 2000);
      return;
    }
    message.value = data.zinute || data.message || 'Nepavyko pakeisti slaptažodžio.';
    error.value = true;
    if (data.errors && typeof data.errors === 'object') {
      const first = Object.values(data.errors)[0];
      if (Array.isArray(first) && first[0]) message.value = String(first[0]);
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped></style>
