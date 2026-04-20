<template>
  <div class="flex min-h-full flex-col items-center justify-center">
    <div class="w-full max-w-lg">
      <h1 class="page-title">Pamiršai slaptažodį?</h1>
      <p class="muted mt-2 text-sm">
        Įvesk el. paštą — išsiųsime nuorodą slaptažodžiui atkurti (jei paskyra egzistuoja).
      </p>
      <div class="card mt-4 w-full">
        <form @submit.prevent="submit">
          <div>
            <label class="label">El. paštas</label>
            <input v-model="form.el_pastas" class="input mt-2" type="email" required autocomplete="email" />
          </div>
          <div class="mt-5 flex flex-wrap items-center gap-3">
            <button class="btn btn-primary" type="submit" :disabled="loading">
              {{ loading ? 'Siunčiama...' : 'Siųsti nuorodą' }}
            </button>
            <router-link class="link text-sm" to="/prisijungti">Atgal į prisijungimą</router-link>
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
import { ref } from 'vue';

const form = ref({ el_pastas: '' });
const loading = ref(false);
const message = ref('');
const error = ref(false);

async function submit() {
  loading.value = true;
  message.value = '';
  error.value = false;
  try {
    const res = await fetch('/api/slaptazodzio-nuoroda', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ el_pastas: form.value.el_pastas }),
    });
    const data = await res.json().catch(() => ({}));
    message.value = data.zinute || (res.ok ? 'Patikrink paštą.' : 'Nepavyko išsiųsti.');
    error.value = !res.ok;
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped></style>
