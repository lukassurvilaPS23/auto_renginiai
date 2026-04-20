<template>
  <div class="card">
    <h1 class="page-title">Swagger</h1>
    <p class="muted mt-2 text-sm">Peradresuojama į Swagger dokumentaciją...</p>
    <p v-if="error" class="alert alert-danger mt-4">{{ error }}</p>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const error = ref('');

onMounted(async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    router.push('/prisijungti');
    return;
  }

  const meRes = await fetch('/api/as', {
    headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
  });
  if (!meRes.ok) {
    router.push('/prisijungti');
    return;
  }
  const me = await meRes.json();
  const roles = Array.isArray(me.roles) ? me.roles : [];
  if (!roles.includes('administratorius')) {
    error.value = 'Neturite teisių.';
    return;
  }

  window.location.href = `/api/documentation?token=${encodeURIComponent(token)}`;
});
</script>

<style scoped></style>

