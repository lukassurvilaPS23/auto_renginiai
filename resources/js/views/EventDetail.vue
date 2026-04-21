<template>
  <div>
    <h1 class="page-title">Auto renginys</h1>
    <div class="card mt-4" v-if="loading">Kraunama...</div>
    <div class="card mt-4" v-else-if="!event">Renginys nerastas.</div>
    <div v-else>
      <div class="card mt-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">{{ event.pavadinimas }}</h2>
            <p class="muted mt-1 text-sm">{{ event.miestas }} · {{ formatDate(event.pradzios_data) }}</p>
          </div>
          <span class="badge">{{ event.statusas }}</span>
        </div>

        <p class="mt-4 text-sm leading-relaxed">{{ event.aprasymas }}</p>

        <div class="mt-5" v-if="eventNuotraukos.length">
          <h3 class="text-base font-semibold">Renginio nuotraukos</h3>
          <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
            <a
              v-for="(p, idx) in eventNuotraukos"
              :key="idx + '-' + p"
              :href="storageUrl(p)"
              target="_blank"
              rel="noreferrer"
              class="overflow-hidden rounded-2xl border"
              :style="{ borderColor: 'var(--border)' }"
            >
              <img :src="storageUrl(p)" class="h-32 w-full object-cover" alt="" loading="lazy" />
            </a>
          </div>
        </div>

        <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
          <div class="card card-flat">
            <div class="muted text-xs font-medium">Adresas</div>
            <div class="mt-1 font-medium">{{ event.adresas }}</div>
          </div>
          <div class="card card-flat">
            <div class="muted text-xs font-medium">Laikas</div>
            <div class="mt-1 font-medium">Pradžia: {{ formatDate(event.pradzios_data) }}</div>
            <div class="mt-1 font-medium">Pabaiga: {{ formatDate(event.pabaigos_data) }}</div>
          </div>
        </div>
      </div>

      <div class="card mt-4" v-if="hasMapData">
        <h3 class="text-base font-semibold">Vieta / schema</h3>
        <EventMap
          v-model="event.zemelapio_objektai"
          :center="{ lat: Number(event.latitude ?? 54.6872), lng: Number(event.longitude ?? 25.2797) }"
          :editable="false"
        />
      </div>
      <div class="card mt-4">
        <div class="flex flex-wrap gap-2">
          <button class="btn btn-primary" type="button" @click="openRegistrationForm">Registruotis</button>
          <button class="btn" type="button" @click="unregister">Atšaukti registraciją</button>
        </div>
        <p class="muted mt-3 text-sm">Registracija veikia tik prisijungus (token).</p>
      </div>

      <div class="card mt-4" v-if="eventEnded">
        <h3 class="text-base font-semibold">Atsiliepimai</h3>
        <p class="muted mt-2 text-sm">Komentarai ir nuotraukos galimi tik pasibaigus renginiui. Nuotraukas patvirtina organizatorius.</p>

        <div v-if="atsiliepimaiLoading" class="muted mt-3 text-sm">Kraunama...</div>

        <div v-else>
          <div>
            <h4 class="mt-2 font-semibold">Nuotraukos</h4>
            <div class="photos mt-3" v-if="atsiliepimai.nuotraukos?.length">
              <button
                v-for="(n, idx) in atsiliepimai.nuotraukos"
                :key="n.id"
                type="button"
                class="photoBtn"
                @click="openGallery(idx)"
              >
                <img :src="storageUrl(n.url ?? n.kelias)" alt="Nuotrauka" />
              </button>
            </div>
            <p v-else class="muted mt-2 text-sm">Nuotraukų dar nėra.</p>
          </div>

          <div class="mt-6">
            <h4 class="font-semibold">Komentarai</h4>
            <div v-if="atsiliepimai.komentarai?.length" class="mt-3 grid gap-3">
              <div v-for="k in atsiliepimai.komentarai" :key="k.id" class="card card-flat">
                <div class="flex items-start gap-3">
                  <div
                    v-if="k.vartotojas?.nuotrauka"
                    class="h-10 w-10 overflow-hidden rounded-full border"
                    :style="{ borderColor: 'var(--border)' }"
                  >
                    <img class="h-full w-full object-cover" :src="storageUrl(k.vartotojas.nuotrauka)" alt="Vartotojo nuotrauka" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                      <strong class="min-w-0">
                        <router-link v-if="k.vartotojas?.id" class="link" :to="`/profilis/${k.vartotojas.id}`">
                          {{ k.vartotojas?.vardas ?? 'Vartotojas' }}
                        </router-link>
                        <span v-else>{{ k.vartotojas?.vardas ?? 'Vartotojas' }}</span>
                      </strong>
                      <span class="muted text-xs">{{ k.sukurta }}</span>
                    </div>
                    <div class="mt-2 whitespace-pre-wrap text-sm leading-relaxed">{{ k.komentaras }}</div>
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="muted mt-2 text-sm">Komentarų dar nėra.</p>
          </div>

          <div class="mt-6" v-if="isAuthenticated">
            <h4 class="font-semibold">Palikti komentarą</h4>
            <p v-if="commentMessage" class="muted mt-2 text-sm">{{ commentMessage }}</p>
            <textarea v-model="newComment" rows="3" class="textarea mt-3" placeholder="Parašyk komentarą..."></textarea>
            <button class="btn btn-primary mt-3" type="button" @click="submitComment" :disabled="commentSaving || !newComment.trim()">
              {{ commentSaving ? 'Siunčiama...' : 'Siųsti' }}
            </button>
          </div>
          <div class="mt-6" v-else>
            <p class="muted text-sm">Prisijunk, kad galėtum rašyti komentarus ir kelti nuotraukas.</p>
          </div>

          <div class="mt-6" v-if="isAuthenticated">
            <h4 class="font-semibold">Įkelti nuotraukas (iki 5)</h4>
            <p v-if="uploadMessage" class="muted mt-2 text-sm">{{ uploadMessage }}</p>
            <input class="mt-3 block w-full text-sm" type="file" accept="image/*" multiple @change="onFilesSelected" />
            <button class="btn btn-primary mt-3" type="button" @click="uploadPhotos" :disabled="uploading || selectedFiles.length === 0">
              {{ uploading ? 'Įkeliama...' : 'Įkelti' }}
            </button>
            <p class="muted mt-2 text-sm" v-if="selectedFiles.length">Pasirinkta failų: {{ selectedFiles.length }}</p>
          </div>

          <div class="mt-6" v-if="isAuthenticated">
            <h4 class="font-semibold">Mano laukiančios nuotraukos</h4>
            <p v-if="myPendingMessage" class="muted mt-2 text-sm">{{ myPendingMessage }}</p>
            <button class="btn" type="button" @click="loadMyPendingPhotos" :disabled="myPendingLoading">
              {{ myPendingLoading ? 'Kraunama...' : 'Atnaujinti' }}
            </button>
            <div v-if="myPendingPhotos.length" class="pending mt-3">
              <div v-for="p in myPendingPhotos" :key="p.id" class="pendingItem" style="grid-template-columns: 1fr auto;">
                <a class="link" :href="p.url" target="_blank" rel="noreferrer">Atidaryti</a>
                <button class="btn" type="button" @click="cancelMyPendingPhoto(p.id)">Atšaukti</button>
              </div>
            </div>
            <p v-else class="muted mt-2 text-sm">Nėra laukiančių nuotraukų.</p>
          </div>

          <div v-if="canModerate" class="mt-7">
            <h4 class="font-semibold">Laukiančios nuotraukos (patvirtinimui)</h4>
            <p v-if="pendingMessage" class="muted mt-2 text-sm">{{ pendingMessage }}</p>
            <button class="btn" type="button" @click="loadPendingPhotos" :disabled="pendingLoading">
              {{ pendingLoading ? 'Kraunama...' : 'Atnaujinti' }}
            </button>

            <div v-if="pendingPhotos.length" class="pending">
              <div v-for="p in pendingPhotos" :key="p.id" class="pendingItem">
                <a class="link" :href="p.url" target="_blank" rel="noreferrer">Atidaryti</a>
                <span class="muted">{{ p.vartotojas?.email ?? '' }}</span>
                <button class="btn btn-primary" type="button" @click="approvePhoto(p.id)">Patvirtinti</button>
                <button class="btn" type="button" @click="rejectPhoto(p.id)">Atmesti</button>
              </div>
            </div>
            <p v-else class="muted mt-2 text-sm">Nėra laukiančių nuotraukų.</p>
          </div>
        </div>
      </div>

      <div v-if="galleryOpen" class="overlay" @click.self="closeGallery">
        <button type="button" class="overlayClose" @click="closeGallery">×</button>
        <button type="button" class="overlayNav left" @click.stop="prevPhoto" :disabled="galleryUrls.length <= 1">‹</button>
        <img class="overlayImg" :src="galleryUrls[galleryIndex]" alt="Nuotrauka" />
        <button type="button" class="overlayNav right" @click.stop="nextPhoto" :disabled="galleryUrls.length <= 1">›</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import EventMap from '../EventMap.vue';
import { storageUrl, normalizeStoragePaths } from '../utils/storageUrl.js';

const route = useRoute();
const router = useRouter();
const event = ref(null);
const loading = ref(true);

const me = ref(null);
const atsiliepimai = ref({ komentarai: [], nuotraukos: [] });

const hasMapData = computed(() => {
  if (!event.value) return false;
  const hasCoords = event.value.latitude != null && event.value.longitude != null;
  const hasGeo = event.value.zemelapio_objektai != null;
  return !!(hasCoords || hasGeo);
});
const atsiliepimaiLoading = ref(false);

const newComment = ref('');
const commentSaving = ref(false);
const commentMessage = ref('');

const selectedFiles = ref([]);
const uploading = ref(false);
const uploadMessage = ref('');

const pendingPhotos = ref([]);
const pendingLoading = ref(false);
const pendingMessage = ref('');

const myPendingPhotos = ref([]);
const myPendingLoading = ref(false);
const myPendingMessage = ref('');

const galleryOpen = ref(false);
const galleryIndex = ref(0);
const galleryUrls = computed(() =>
  (atsiliepimai.value.nuotraukos ?? []).map((n) => storageUrl(n.url ?? n.kelias ?? '')),
);

const eventNuotraukos = computed(() => normalizeStoragePaths(event.value?.nuotraukos));

const isAuthenticated = computed(() => !!localStorage.getItem('token'));

const eventEnded = computed(() => {
  if (!event.value) return false;
  const end = event.value.pabaigos_data || event.value.pradzios_data;
  if (!end) return false;
  const d = new Date(end);
  if (Number.isNaN(d.getTime())) return false;
  return d.getTime() < Date.now();
});

const canModerate = computed(() => {
  if (!me.value || !event.value) return false;
  const roleNames = Array.isArray(me.value.roles)
    ? me.value.roles.map(r => (r?.name ?? r))
    : [];
  const isAdmin = roleNames.includes('administratorius');
  const isOwner = String(event.value.organizatorius_id) === String(me.value.id);
  return isAdmin || isOwner;
});

onMounted(async () => {
  const id = route.params.id;
  const res = await fetch(`/api/auto-renginiai/${id}`, {
    headers: { Accept: 'application/json' },
  });
  if (res.ok) {
    const data = await res.json();
    event.value = data.auto_renginys;
  }
  loading.value = false;

  const token = localStorage.getItem('token');
  if (token) {
    const meRes = await fetch('/api/as', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    });
    if (meRes.ok) {
      const meData = await meRes.json();
      me.value = meData.vartotojas ?? meData ?? null;
      if (meData.roles && me.value && !me.value.roles) {
        me.value.roles = meData.roles;
      }
    }
  }

  if (eventEnded.value) {
    await loadAtsiliepimai();
    if (canModerate.value) {
      await loadPendingPhotos();
    }
    if (isAuthenticated.value) {
      await loadMyPendingPhotos();
    }
  }
});

function onKeyDown(e) {
  if (!galleryOpen.value) return;
  if (e.key === 'Escape') closeGallery();
  if (e.key === 'ArrowLeft') prevPhoto();
  if (e.key === 'ArrowRight') nextPhoto();
}

window.addEventListener('keydown', onKeyDown);

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeyDown);
});

async function loadAtsiliepimai() {
  atsiliepimaiLoading.value = true;
  try {
    const res = await fetch(`/api/auto-renginiai/${route.params.id}/atsiliepimai`, {
      headers: { Accept: 'application/json' },
    });
    if (res.ok) {
      const data = await res.json();
      atsiliepimai.value = data;
    }
  } finally {
    atsiliepimaiLoading.value = false;
  }
}

async function submitComment() {
  commentMessage.value = '';
  const token = localStorage.getItem('token');
  if (!token) {
    router.push('/prisijungti');
    return;
  }

  commentSaving.value = true;
  try {
    const res = await fetch(`/api/auto-renginiai/${route.params.id}/komentarai`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ komentaras: newComment.value }),
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      commentMessage.value = data.zinute ?? data.message ?? `Klaida (status ${res.status})`;
      return;
    }

    newComment.value = '';
    commentMessage.value = data.zinute ?? 'Komentaras pridėtas';
    await loadAtsiliepimai();
  } finally {
    commentSaving.value = false;
  }
}

function onFilesSelected(e) {
  const files = Array.from(e.target.files || []);
  selectedFiles.value = files.slice(0, 5);
  uploadMessage.value = '';
}

async function uploadPhotos() {
  uploadMessage.value = '';
  const token = localStorage.getItem('token');
  if (!token) {
    router.push('/prisijungti');
    return;
  }

  if (selectedFiles.value.length === 0) {
    return;
  }

  uploading.value = true;
  try {
    const fd = new FormData();
    selectedFiles.value.slice(0, 5).forEach((f) => fd.append('nuotraukos[]', f));

    const res = await fetch(`/api/auto-renginiai/${route.params.id}/nuotraukos`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      uploadMessage.value = data.zinute ?? data.message ?? `Klaida (status ${res.status})`;
      return;
    }

    uploadMessage.value = data.zinute ?? 'Nuotraukos įkeltos';
    selectedFiles.value = [];
    await loadAtsiliepimai();
    await loadMyPendingPhotos();
    if (canModerate.value) {
      await loadPendingPhotos();
    }
  } finally {
    uploading.value = false;
  }
}

async function loadMyPendingPhotos() {
  myPendingMessage.value = '';
  const token = localStorage.getItem('token');
  if (!token) return;

  myPendingLoading.value = true;
  try {
    const res = await fetch(`/api/auto-renginiai/${route.params.id}/mano-nuotraukos-laukia`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      myPendingMessage.value = data.zinute ?? data.message ?? `Klaida (status ${res.status})`;
      return;
    }
    myPendingPhotos.value = data.nuotraukos ?? [];
  } finally {
    myPendingLoading.value = false;
  }
}

async function cancelMyPendingPhoto(photoId) {
  myPendingMessage.value = '';
  const token = localStorage.getItem('token');
  if (!token) return;

  const res = await fetch(`/api/renginiu-nuotraukos/${photoId}`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await res.json().catch(() => ({}));
  myPendingMessage.value = data.zinute ?? data.message ?? '';
  if (res.ok) {
    await loadMyPendingPhotos();
  }
}

function openGallery(idx) {
  galleryIndex.value = idx;
  galleryOpen.value = true;
}

function closeGallery() {
  galleryOpen.value = false;
}

function prevPhoto() {
  if (!galleryUrls.value.length) return;
  galleryIndex.value = (galleryIndex.value - 1 + galleryUrls.value.length) % galleryUrls.value.length;
}

function nextPhoto() {
  if (!galleryUrls.value.length) return;
  galleryIndex.value = (galleryIndex.value + 1) % galleryUrls.value.length;
}

async function loadPendingPhotos() {
  pendingMessage.value = '';
  const token = localStorage.getItem('token');
  if (!token) return;

  pendingLoading.value = true;
  try {
    const res = await fetch(`/api/auto-renginiai/${route.params.id}/nuotraukos-laukia`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      pendingMessage.value = data.zinute ?? data.message ?? `Klaida (status ${res.status})`;
      return;
    }
    pendingPhotos.value = data.nuotraukos ?? [];
  } finally {
    pendingLoading.value = false;
  }
}

async function approvePhoto(photoId) {
  const token = localStorage.getItem('token');
  if (!token) return;
  const res = await fetch(`/api/renginiu-nuotraukos/${photoId}/patvirtinti`, {
    method: 'PATCH',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await res.json().catch(() => ({}));
  pendingMessage.value = data.zinute ?? data.message ?? '';
  if (res.ok) {
    await loadPendingPhotos();
    await loadAtsiliepimai();
  }
}

async function rejectPhoto(photoId) {
  const token = localStorage.getItem('token');
  if (!token) return;
  const res = await fetch(`/api/renginiu-nuotraukos/${photoId}/atmesti`, {
    method: 'PATCH',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await res.json().catch(() => ({}));
  pendingMessage.value = data.zinute ?? data.message ?? '';
  if (res.ok) {
    await loadPendingPhotos();
  }
}

async function register() {
  const token = localStorage.getItem('token');
  if (!token) {
    alert('Prisijunk, kad galėtum registruotis.');
    router.push('/prisijungti');
    return;
  }
  window.open(`/renginiai/${route.params.id}/registracija`, '_blank');
}

async function openRegistrationForm() {
  return register();
}

async function unregister() {
  const token = localStorage.getItem('token');
  if (!token) {
    alert('Prisijunk, kad galėtum atšaukti registraciją.');
    router.push('/prisijungti');
    return;
  }
  const res = await fetch(`/api/auto-renginiai/${route.params.id}/registracija`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await res.json();
  if (!res.ok) {
    alert(`Klaida ${res.status}: ${data.zinute ?? data.message ?? 'Nepavyko'}`);
  } else {
    alert(data.zinute ?? 'Registracija atšaukta');
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
.photos {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
}
.photoBtn {
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
}
.photos img {
  width: 100%;
  height: 110px;
  object-fit: cover;
  border-radius: 14px;
  border: 2px solid var(--border);
}
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: grid;
  grid-template-columns: 60px 1fr 60px;
  align-items: center;
  z-index: 50;
}
.overlayImg {
  width: 100%;
  max-height: 85vh;
  object-fit: contain;
  border-radius: 16px;
}
.overlayNav {
  height: 100%;
  border: 0;
  background: transparent;
  color: #fff;
  font-size: 48px;
  cursor: pointer;
}
.overlayNav:disabled {
  opacity: 0.4;
  cursor: default;
}
.overlayClose {
  position: fixed;
  top: 12px;
  right: 16px;
  border: 0;
  background: rgba(0, 0, 0, 0.4);
  color: #fff;
  font-size: 28px;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  cursor: pointer;
  z-index: 60;
}
.pending {
  display: grid;
  gap: 8px;
  margin-top: 10px;
}
.pendingItem {
  display: grid;
  grid-template-columns: 1fr 2fr auto auto;
  gap: 8px;
  align-items: center;
  border: 2px solid var(--border);
  border-radius: 10px;
  padding: 10px 12px;
}
</style>
