<template>
  <div>
    <h1 class="page-title">Automobilio detalės</h1>
    <div class="card mt-4" style="min-height: 120px;">
      <p v-if="loading" class="muted">Kraunama...</p>
      <p v-else-if="!car" class="muted">Automobilis nerastas.</p>
      <div v-else>
        <div v-if="car.nuotrauka" class="mt-4">
          <img class="w-full max-h-[420px] rounded-2xl object-cover" :src="car.nuotrauka" alt="Automobilio nuotrauka" />
        </div>

        <div class="mt-5 grid gap-2 text-sm">
          <p><span class="muted">Markė:</span> <span class="font-medium">{{ car.marke }}</span></p>
          <p><span class="muted">Modelis:</span> <span class="font-medium">{{ car.modelis }}</span></p>
          <p><span class="muted">Metai:</span> <span class="font-medium">{{ car.metai }}</span></p>
          <p v-if="car.spalva"><span class="muted">Spalva:</span> <span class="font-medium">{{ car.spalva }}</span></p>
          <p v-if="car.vin"><span class="muted">VIN kodas:</span> <span class="font-medium">{{ car.vin }}</span></p>
          <p v-if="car.variklis"><span class="muted">Variklis:</span> <span class="font-medium">{{ car.variklis }}</span></p>
          <p v-if="car.kuras"><span class="muted">Kuras:</span> <span class="font-medium">{{ car.kuras }}</span></p>
          <p v-if="car.aprasymas" class="leading-relaxed"><span class="muted">Aprašymas:</span> {{ car.aprasymas }}</p>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
          <button @click="router.back()" class="btn" type="button">Grįžti atgal</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
  id: { type: String, required: true },
});

const router = useRouter();
const car = ref(null);
const loading = ref(true);
const token = localStorage.getItem('token');

onMounted(async () => {
  const res = await fetch(`/api/automobiliai/${props.id}`, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
  });
  if (res.ok) {
    const data = await res.json();
    car.value = data.automobilis || null;
  } else if (res.status === 403) {
    alert('Neturite teisių peržiūrėti šį automobilį.');
    router.push('/garazas');
  } else if (res.status === 404) {
    alert('Automobilis nerastas.');
    router.push('/garazas');
  }
  loading.value = false;
});
</script>

<style scoped></style>
