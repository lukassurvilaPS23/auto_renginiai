<template>
  <div class="flex min-h-full flex-col items-center justify-center">
    <div class="w-full max-w-lg">
      <h1 class="page-title">Registruotis</h1>
      <div class="card mt-4 w-full">
        <form @submit.prevent="submit">
          <div>
            <label class="label">Vardas</label>
            <input v-model="form.vardas" class="input mt-2" type="text" required />
          </div>
          <div class="mt-4">
            <label class="label">El. paštas</label>
            <input v-model="form.el_pastas" class="input mt-2" type="email" required />
          </div>
          <div class="mt-4">
            <label class="label">Slaptažodis</label>
            <input v-model="form.slaptazodis" class="input mt-2" type="password" required />
          </div>
          <div class="mt-5 flex items-center gap-3">
            <button class="btn btn-primary" type="submit">Registruotis</button>
            <a class="link text-sm" href="/prisijungti">Jau turi paskyrą? Prisijunk</a>
          </div>
        </form>
        <div v-if="error" class="alert alert-danger mt-4">
          {{ error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({ vardas: '', el_pastas: '', slaptazodis: '' });
const error = ref('');

function pickFirstValidationError(errors) {
  if (!errors || typeof errors !== 'object') return '';
  const firstKey = Object.keys(errors)[0];
  const firstVal = firstKey ? errors[firstKey] : null;
  if (Array.isArray(firstVal) && firstVal.length > 0) return String(firstVal[0]);
  if (typeof firstVal === 'string') return firstVal;
  return '';
}

async function submit() {
  error.value = '';
  const res = await fetch('/api/registruotis', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(form.value),
  });
  const data = await res.json();
  if (!res.ok) {
    error.value = pickFirstValidationError(data.errors) || data.zinute || data.message || 'Klaida';
    return;
  }
  localStorage.setItem('token', data.token);
  router.push('/');
}
</script>

<style scoped></style>
