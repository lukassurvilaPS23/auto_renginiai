<template>
  <div>
    <h1 class="page-title">Profilis</h1>

    <div class="card mt-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-base font-semibold">Vartotojo informacija</h3>
        <p id="profileMessage" class="muted text-sm">{{ message }}</p>
      </div>
      <div v-if="user">
        <div class="mt-4 grid gap-5 md:grid-cols-3">
          <div class="md:col-span-1">
            <div
              v-if="user.nuotrauka"
              class="overflow-hidden rounded-2xl border cursor-pointer"
              :style="{ borderColor: 'var(--border)' }"
              @click="viewFullPhoto(user.nuotrauka)"
            >
              <img class="h-[180px] w-full object-cover" :src="user.nuotrauka" alt="Profilio nuotrauka" />
            </div>
            <div
              v-else
              class="flex h-[180px] items-center justify-center rounded-2xl border"
              :style="{ borderColor: 'var(--border)', background: 'var(--surface-2)', color: 'var(--muted)' }"
            >
              <span class="text-sm">Nuotrauka nėra</span>
            </div>
          </div>

          <div class="md:col-span-2 grid gap-4 sm:grid-cols-2">
            <div class="card card-flat">
              <p class="muted text-xs font-medium">Vardas</p>
              <p class="mt-1 font-semibold">{{ user.name }}</p>
            </div>
            <div class="card card-flat">
              <p class="muted text-xs font-medium">El. paštas</p>
              <p class="mt-1 font-semibold break-words">{{ user.email }}</p>
            </div>
            <div class="card card-flat">
              <p class="muted text-xs font-medium">Rolės</p>
              <p class="mt-1 font-semibold">{{ rolesDisplay }}</p>
            </div>
            <div class="card card-flat">
              <p class="muted text-xs font-medium">Prisijungė</p>
              <p class="mt-1 font-semibold">{{ formatDate(user.created_at) }}</p>
              <p class="muted mt-2 text-xs">ID: {{ user.id }}</p>
            </div>
          </div>
        </div>

        <div class="my-6 h-px w-full" :style="{ background: 'var(--border)' }"></div>

        <div v-if="isOwnProfile">
          <h3 class="text-base font-semibold">Redaguoti profilį</h3>
          <p v-if="saveMessage" class="muted mt-2 text-sm">{{ saveMessage }}</p>
          <div v-if="Object.keys(errors).length" class="alert alert-danger mt-4">
            <div v-for="(msgs, key) in errors" :key="key">
              <strong>{{ key }}</strong>: {{ Array.isArray(msgs) ? msgs.join(', ') : msgs }}
            </div>
          </div>

          <form @submit.prevent="save" class="mt-4 grid gap-4 max-w-2xl">
            <div class="grid gap-4 md:grid-cols-2">
              <div>
                <label class="label">Vardas</label>
                <input v-model="form.vardas" class="input mt-2" type="text" required />
              </div>

              <div>
                <label class="label">El. paštas</label>
                <input v-model="form.el_pastas" class="input mt-2" type="email" required />
              </div>
            </div>

            <div>
              <label class="label">Profilio nuotrauka</label>
              <input class="mt-2 block w-full text-sm" type="file" accept="image/*" @change="handlePhotoUpload" />
              <div v-if="form.nuotrauka_preview" class="mt-3">
                <img class="h-[160px] w-[160px] rounded-2xl object-cover" :src="form.nuotrauka_preview" alt="Profilio nuotraukos peržiūra" />
              </div>
              <div v-if="user?.nuotrauka && !form.nuotrauka_preview" class="mt-3">
                <p class="muted text-sm">Dabartinė nuotrauka</p>
                <img class="mt-2 h-[160px] w-[160px] rounded-2xl object-cover" :src="user.nuotrauka" alt="Dabartinė profilio nuotrauka" />
              </div>
            </div>

            <details class="card card-flat">
              <summary class="font-medium cursor-pointer">Pakeisti slaptažodį (nebūtina)</summary>
              <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div class="md:col-span-1">
                  <label class="label">Dabartinis slaptažodis</label>
                  <input v-model="form.dabartinis_slaptazodis" class="input mt-2" type="password" autocomplete="current-password" />
                </div>
                <div class="md:col-span-1">
                  <label class="label">Naujas slaptažodis</label>
                  <input v-model="form.naujas_slaptazodis" class="input mt-2" type="password" autocomplete="new-password" />
                </div>
                <div class="md:col-span-1">
                  <label class="label">Pakartok naują</label>
                  <input v-model="form.naujas_slaptazodis_confirmation" class="input mt-2" type="password" autocomplete="new-password" />
                </div>
              </div>
            </details>

            <div class="flex items-center gap-2">
              <button class="btn btn-primary" type="submit" :disabled="saving">
                {{ saving ? 'Saugoma...' : 'Išsaugoti' }}
              </button>
            </div>
          </form>
        </div>
      </div>
      <details class="mt-6">
        <summary class="font-medium cursor-pointer">Rodyti JSON</summary>
        <pre class="mt-3 rounded-2xl border p-4 text-xs overflow-auto" :style="{ borderColor: 'var(--border)', background: 'var(--surface-2)' }">{{
          JSON.stringify(user, null, 2)
        }}</pre>
      </details>
    </div>

    <div class="card mt-4">
      <h3 class="text-base font-semibold">{{ isOwnProfile ? 'Mano' : 'Vartotojo' }} garažas</h3>
      <p v-if="activityLoading" class="muted mt-2 text-sm">Kraunama...</p>
      <p v-else-if="!activity.cars?.length" class="muted mt-2 text-sm">Automobilių nėra.</p>
      <div v-else>
        <div class="mt-3 divide-y" :style="{ borderColor: 'var(--border)' }">
          <div
            v-for="car in activity.cars"
            :key="car.id"
            class="py-3 cursor-pointer"
            @click="viewCarDetail(car.id)"
          >
            <div class="font-medium">{{ car.marke }} {{ car.modelis }}</div>
            <div class="muted text-sm">{{ car.metai }} · {{ car.spalva || 'Nenurodyta spalva' }}</div>
          </div>
        </div>
        <router-link v-if="isOwnProfile" to="/garazas" class="btn mt-4 inline-flex">Valdyti garažą</router-link>
      </div>
    </div>

    <div class="card mt-4">
      <h3 class="text-base font-semibold">Aktyvumas renginiuose</h3>
      <p v-if="activityLoading" class="muted mt-2 text-sm">Kraunama...</p>
      <div v-else>
        <h4 class="mt-2 font-semibold">Komentarai</h4>
        <p v-if="!activity.komentarai?.length" class="muted mt-2 text-sm">Komentarų nėra.</p>
        <div v-else>
          <div class="mt-3 grid gap-3">
            <div v-for="k in activity.komentarai" :key="k.id" class="card card-flat">
              <div class="flex items-start gap-3">
                <div v-if="k.vartotojas?.nuotrauka" class="h-10 w-10 overflow-hidden rounded-full border" :style="{ borderColor: 'var(--border)' }">
                  <img class="h-full w-full object-cover" :src="k.vartotojas.nuotrauka" alt="Vartotojo nuotrauka" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <strong class="min-w-0">
                      <a class="link" :href="`/renginiai/${k.renginys?.id}`">{{ k.renginys?.pavadinimas || 'Renginys' }}</a>
                    </strong>
                    <span class="muted text-xs">{{ formatDate(k.created_at) }}</span>
                  </div>
                  <p class="mt-2 text-sm leading-relaxed">{{ k.komentaras }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <h4 class="mt-6 font-semibold">Nuotraukos</h4>
        <p v-if="!activity.nuotraukos?.length" class="muted mt-2 text-sm">Nuotraukų nėra.</p>
        <div v-else>
          <div class="mt-3 grid gap-4 md:grid-cols-2">
            <div v-for="n in activity.nuotraukos" :key="n.id" class="card card-flat">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <strong class="min-w-0">
                  <a class="link" :href="`/renginiai/${n.renginys?.id}`">{{ n.renginys?.pavadinimas || 'Renginys' }}</a>
                </strong>
                <span class="muted text-xs">{{ formatDate(n.patvirtinta_at) }}</span>
              </div>

              <a
                class="mt-3 block overflow-hidden rounded-2xl border"
                :style="{ borderColor: 'var(--border)' }"
                :href="photoHref(n)"
                target="_blank"
                rel="noreferrer"
                :aria-label="`Atidaryti nuotrauką: ${n.renginys?.pavadinimas || 'Renginys'}`"
              >
                <img
                  class="h-[180px] w-full object-cover transition"
                  :src="photoHref(n)"
                  alt="Renginio nuotrauka"
                  loading="lazy"
                />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activity.renginiai?.length" class="card mt-4">
      <h3 class="text-base font-semibold">Sukurti renginiai</h3>
      <div class="mt-3 divide-y" :style="{ borderColor: 'var(--border)' }">
        <div v-for="r in activity.renginiai" :key="r.id" class="py-3">
          <strong><a class="link" :href="`/renginiai/${r.id}`">{{ r.pavadinimas }}</a></strong>
          <div class="muted text-sm">{{ r.miestas }} · {{ formatDate(r.pradzios_data) }}</div>
        </div>
      </div>
    </div>

    <div v-if="fullPhotoUrl" class="photo-overlay" @click="closeFullPhoto">
      <img :src="fullPhotoUrl" alt="Pilno dydžio nuotrauka" @click.stop />
      <button class="overlay-close" type="button" @click="closeFullPhoto">×</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
  userId: { type: String, default: null },
});

const router = useRouter();

function viewCarDetail(carId) {
  router.push(`/automobiliai/${carId}`);
}

const fullPhotoUrl = ref(null);

function viewFullPhoto(url) {
  fullPhotoUrl.value = url;
}

function closeFullPhoto() {
  fullPhotoUrl.value = null;
}

function photoHref(n) {
  const url = n?.url || n?.href || n?.link;
  if (typeof url === 'string' && url.trim()) return url;

  const path = n?.kelias || n?.path;
  if (typeof path !== 'string' || !path.trim()) return '#';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  if (path.startsWith('/storage/')) return path;
  return '/storage/' + path.replace(/^\/+/, '');
}

const user = ref(null);
const message = ref('Kraunama...');
const saveMessage = ref('');
const saving = ref(false);
const errors = ref({});
const activityLoading = ref(true);
const activity = ref({
  cars: [],
  komentarai: [],
  nuotraukos: [],
  renginiai: [],
});
const isOwnProfile = computed(() => !props.userId);

const form = ref({
  vardas: '',
  el_pastas: '',
  dabartinis_slaptazodis: '',
  naujas_slaptazodis: '',
  naujas_slaptazodis_confirmation: '',
  nuotrauka: null,
  nuotrauka_preview: null,
});

function handlePhotoUpload(e) {
  const file = e.target.files[0];
  if (file) {
    form.value.nuotrauka = file;
    form.value.nuotrauka_preview = URL.createObjectURL(file);
  }
}

const rolesDisplay = computed(() => {
  if (!user.value) return '—';
  let roles = 'Nenurodyta';
  if (Array.isArray(user.value.roles) && user.value.roles.length) {
    roles = user.value.roles.map(r => r.name ?? r).join(', ');
  } else if (typeof user.value.roles === 'string' && user.value.roles.trim()) {
    roles = user.value.roles;
} else if (Array.isArray(user.value.all_roles) && user.value.all_roles.length) {
    roles = user.value.all_roles.map(r => r.name ?? r).join(', ');
  }
  return roles || '—';
});

async function loadActivity() {
  activityLoading.value = true;
  const token = localStorage.getItem('token');
  if (!token) {
    activityLoading.value = false;
    return;
  }
  const url = props.userId ? `/api/vartotojai/${props.userId}/aktyvumas` : '/api/mano-aktyvumas';
  console.log('Loading activity from:', url, 'userId:', props.userId);
  const res = await fetch(url, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  if (res.ok) {
    const data = await res.json();
    console.log('Activity API response:', data);
    if (props.userId) {
      user.value = data.vartotojas;
    }
    activity.value.cars = data.automobiliai || [];
    activity.value.komentarai = data.komentarai || [];
    activity.value.nuotraukos = data.nuotraukos || [];
    activity.value.renginiai = data.renginiai || [];
  } else {
    console.error('Activity API error:', res.status);
  }
  activityLoading.value = false;
}

onMounted(async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    message.value = 'Neprisijungęs.';
    router.push('/prisijungti');
    return;
  }
  const res = await fetch('/api/as', {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  if (!res.ok) {
    message.value = `Neprisijungęs (status ${res.status}).`;
    localStorage.removeItem('token');
    router.push('/prisijungti');
    return;
  }
  const data = await res.json();
  user.value = data.vartotojas ?? data ?? {};
  message.value = 'Prisijungęs.';

  console.log('User data loaded:', user.value);

  form.value.vardas = user.value.name ?? '';
  form.value.el_pastas = user.value.email ?? '';

  console.log('Form values after load:', form.value);

  await loadActivity();
});

async function save() {
  saveMessage.value = '';
  errors.value = {};

  const token = localStorage.getItem('token');
  if (!token) {
    router.push('/prisijungti');
    return;
  }

  console.log('Saving profile with form values:', form.value);

  saving.value = true;
  try {
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('vardas', form.value.vardas);
    formData.append('el_pastas', form.value.el_pastas);

    if (form.value.naujas_slaptazodis || form.value.dabartinis_slaptazodis) {
      formData.append('dabartinis_slaptazodis', form.value.dabartinis_slaptazodis);
      formData.append('naujas_slaptazodis', form.value.naujas_slaptazodis);
      formData.append('naujas_slaptazodis_confirmation', form.value.naujas_slaptazodis_confirmation);
    }

    if (form.value.nuotrauka) {
      formData.append('nuotrauka', form.value.nuotrauka);
    }

    console.log('FormData entries:');
    for (let [key, value] of formData.entries()) {
      console.log(key, value);
    }

    const res = await fetch('/api/profilis', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: formData,
    });

    const data = await res.json().catch(() => ({}));

    console.log('Server response:', res.status, data);

    if (!res.ok) {
      if (res.status === 422) {
        errors.value = data.errors ?? { klaida: data.message ?? 'Validacijos klaida' };
        console.log('Validation errors:', errors.value);
      } else if (res.status === 401) {
        localStorage.removeItem('token');
        router.push('/prisijungti');
      } else {
        errors.value = { klaida: data.message ?? `Klaida (status ${res.status})` };
      }
      return;
    }

    user.value = data.vartotojas ?? user.value;
    saveMessage.value = data.zinute ?? 'Išsaugota.';
    form.value.dabartinis_slaptazodis = '';
    form.value.naujas_slaptazodis = '';
    form.value.naujas_slaptazodis_confirmation = '';
    form.value.nuotrauka = null;
    form.value.nuotrauka_preview = null;
  } finally {
    saving.value = false;
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

<style scoped>
.photo-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  cursor: pointer;
  padding: 18px;
}
.photo-overlay img {
  max-width: 90%;
  max-height: 90%;
  border-radius: 16px;
  object-fit: contain;
}
.overlay-close {
  position: fixed;
  top: 20px;
  right: 30px;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: #fff;
  font-size: 32px;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  cursor: pointer;
  z-index: 1001;
}
.overlay-close:hover {
  background: rgba(0, 0, 0, 0.55);
}
</style>
