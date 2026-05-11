<template>
  <div>
    <header
      class="sticky top-0 z-50 border-b-2 transition-colors"
      :style="{ borderColor: 'var(--header-border)', background: 'var(--header-bg)' }"
    >
      <div class="container-app">
        <div class="flex h-16 items-center gap-3">
          <a href="/" class="brand-link flex items-center" aria-label="Motoruok — į pradžią">
            <img
              :src="wordmarkSrc"
              alt="Motoruok"
              class="brand-wordmark hidden sm:block"
              :class="{ 'brand-invert': theme === 'dark' }"
            />
            <img
              :src="markSrc"
              alt="Motoruok"
              class="brand-mark sm:hidden"
              :class="{ 'brand-invert': theme === 'dark' }"
            />
          </a>

          <nav class="hidden items-center gap-1 md:flex">
            <a href="/renginiai" class="btn btn-ghost">Renginiai</a>
            <a href="/mano-renginiai" class="btn btn-ghost">Mano</a>
            <a href="/garazas" class="btn btn-ghost">Garažas</a>
            <a href="/profilis" class="btn btn-ghost">Profilis</a>
            <a v-if="isAdmin" href="/admin" class="btn btn-ghost">Admin</a>
            <a v-if="isAdmin" href="/xml" class="btn btn-ghost">XML</a>
            <a v-if="isAdmin" href="/swagger" class="btn btn-ghost">Swagger</a>
          </nav>

          <div class="ml-auto flex items-center gap-2">
            <button class="btn" @click="toggleTheme" type="button">
              <span class="hidden sm:inline">Tema</span>
              <span class="kbd">{{ theme === 'dark' ? 'Dark' : 'Light' }}</span>
            </button>

            <template v-if="isLoggedIn">
              <span class="hidden text-sm sm:inline muted">{{ user?.name }}</span>
              <button class="btn btn-primary" @click="logout" type="button">Atsijungti</button>
            </template>
            <template v-else>
              <a href="/prisijungti" class="btn">Prisijungti</a>
              <a href="/registruotis" class="btn btn-primary">Registruotis</a>
            </template>
          </div>
        </div>
      </div>
    </header>

    <main class="container-app page min-h-[calc(100vh-4rem)]">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const isLoggedIn = ref(false);
const user = ref(null);
const roles = ref([]);
const theme = ref('light');
const wordmarkSrc = '/img/brand/motoruok-wordmark-dark.png';
const markSrc = '/img/brand/motoruok-mark-dark.png';

onMounted(() => {
  const saved = localStorage.getItem('theme');
  if (saved === 'dark' || saved === 'light') {
    theme.value = saved;
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    theme.value = 'dark';
  }
  applyTheme();

  const token = localStorage.getItem('token');
  if (token) {
    isLoggedIn.value = true;
    fetchUser();
  }
});

function applyTheme() {
  document.documentElement.classList.toggle('dark', theme.value === 'dark');
  localStorage.setItem('theme', theme.value);
}

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark';
  applyTheme();
}

async function fetchUser() {
  const token = localStorage.getItem('token');
  const res = await fetch('/api/as', {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  if (res.ok) {
    const data = await res.json();
    user.value = data.vartotojas;
    roles.value = Array.isArray(data.roles) ? data.roles : [];
  } else {
    localStorage.removeItem('token');
    isLoggedIn.value = false;
    roles.value = [];
  }
}

const isAdmin = computed(() => roles.value.includes('administratorius'));

async function logout() {
  const token = localStorage.getItem('token');
  await fetch('/api/atsijungti', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  localStorage.removeItem('token');
  isLoggedIn.value = false;
  user.value = null;
  roles.value = [];
  router.push('/');
}
</script>

<style scoped>
.brand-link {
  flex-shrink: 0;
  line-height: 0;
}
.brand-wordmark,
.brand-mark {
  height: 36px;
  width: auto;
  display: block;
  transition: filter 0.2s ease;
}
.brand-invert {
  /* Juodas logo paverčiamas baltu tamsioje temoje */
  filter: invert(1) brightness(1.05);
}
@media (min-width: 640px) {
  .brand-wordmark {
    height: 40px;
  }
}
</style>
