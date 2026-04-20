<template>
  <div class="card">
    <h1 class="page-title">XML eksportas</h1>
    <p class="muted mt-2 text-sm">
      Šis puslapis prieinamas tik administratoriui. XML bus automatiškai atsiųstas.
    </p>
    <p v-if="error" class="alert alert-danger mt-4">{{ error }}</p>
    <p v-else class="muted mt-4 text-sm">Kraunama...</p>
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

  const res = await fetch('/api/auto-renginiai/export.xml', {
    headers: {
      Accept: 'application/xml',
      Authorization: `Bearer ${token}`,
    },
  });

  if (!res.ok) {
    const txt = await res.text().catch(() => '');
    error.value = txt || `Klaida ${res.status}`;
    return;
  }

  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'auto-renginiai.xml';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);

  router.push('/');
});
</script>

<style scoped></style>

