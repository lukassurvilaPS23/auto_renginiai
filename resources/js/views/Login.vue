<template>
  <div class="flex min-h-full flex-col items-center justify-center">
    <div class="w-full max-w-lg">
      <h1 class="page-title">Prisijungti</h1>
      <div class="card mt-4 w-full">
        <form @submit.prevent="submit">
          <div>
            <label class="label">El. paštas</label>
            <input v-model="form.el_pastas" class="input mt-2" type="email" required />
          </div>
          <div class="mt-4">
            <label class="label">Slaptažodis</label>
            <input v-model="form.slaptazodis" class="input mt-2" type="password" required />
          </div>
          <div class="mt-5 flex items-center gap-3">
            <button class="btn btn-primary" type="submit">Prisijungti</button>
            <a class="link text-sm" href="/registruotis">Neturi paskyros? Registruokis</a>
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
const form = ref({ el_pastas: '', slaptazodis: '' });
const error = ref('');

async function submit() {
  error.value = '';
  const res = await fetch('/api/prisijungti', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(form.value),
  });
  const data = await res.json();
  if (!res.ok) {
    error.value = data.message || 'Klaida';
    return;
  }
  localStorage.setItem('token', data.token);
  router.push('/');
}
</script>

<style scoped></style>
