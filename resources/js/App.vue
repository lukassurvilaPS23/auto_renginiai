<template>
  <div>
    <header
      class="sticky top-0 z-50 border-b-2 transition-colors"
      :style="{ borderColor: 'var(--header-border)', background: 'var(--header-bg)' }"
    >
      <div class="container-app">
        <div class="flex h-16 items-center gap-3">
          <a
            href="/"
            class="brand-link flex items-center"
            aria-label="Motoruok — į pradžią"
            @click="closeMenu"
          >
            <img
              :src="wordmarkSrc"
              alt="Motoruok"
              class="brand-wordmark"
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

          <div class="ml-auto hidden items-center gap-2 md:flex">
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

          <button
            type="button"
            class="menu-toggle ml-auto md:hidden"
            :aria-expanded="menuOpen"
            aria-label="Atidaryti meniu"
            @click="toggleMenu"
          >
            <span class="menu-toggle-bars" :class="{ open: menuOpen }">
              <span></span>
              <span></span>
              <span></span>
            </span>
          </button>
        </div>
      </div>

      <Transition name="drawer">
        <div v-if="menuOpen" class="mobile-drawer md:hidden">
          <div class="container-app py-3">
            <nav class="flex flex-col gap-1">
              <a href="/renginiai" class="drawer-link" @click="closeMenu">Renginiai</a>
              <a href="/mano-renginiai" class="drawer-link" @click="closeMenu">Mano</a>
              <a href="/garazas" class="drawer-link" @click="closeMenu">Garažas</a>
              <a href="/profilis" class="drawer-link" @click="closeMenu">Profilis</a>
              <a v-if="isAdmin" href="/admin" class="drawer-link" @click="closeMenu">Admin</a>
              <a v-if="isAdmin" href="/xml" class="drawer-link" @click="closeMenu">XML</a>
              <a v-if="isAdmin" href="/swagger" class="drawer-link" @click="closeMenu">Swagger</a>
            </nav>

            <div class="drawer-divider"></div>

            <button class="btn w-full justify-center" @click="toggleTheme" type="button">
              <span>Tema</span>
              <span class="kbd ml-2">{{ theme === 'dark' ? 'Dark' : 'Light' }}</span>
            </button>

            <div class="mt-3 flex flex-col gap-2">
              <template v-if="isLoggedIn">
                <span v-if="user?.name" class="text-sm muted text-center">{{ user.name }}</span>
                <button
                  class="btn btn-primary w-full justify-center"
                  @click="logoutMobile"
                  type="button"
                >
                  Atsijungti
                </button>
              </template>
              <template v-else>
                <a href="/prisijungti" class="btn w-full justify-center" @click="closeMenu">
                  Prisijungti
                </a>
                <a
                  href="/registruotis"
                  class="btn btn-primary w-full justify-center"
                  @click="closeMenu"
                >
                  Registruotis
                </a>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </header>

    <Transition name="fade">
      <div
        v-if="menuOpen"
        class="mobile-backdrop md:hidden"
        @click="closeMenu"
        aria-hidden="true"
      ></div>
    </Transition>

    <main class="container-app page min-h-[calc(100vh-4rem)]">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();
const isLoggedIn = ref(false);
const user = ref(null);
const roles = ref([]);
const theme = ref('light');
const menuOpen = ref(false);
const wordmarkSrc = '/img/brand/motoruok-wordmark-dark.png';

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

watch(
  () => route.fullPath,
  () => {
    menuOpen.value = false;
  },
);

watch(menuOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : '';
});

function applyTheme() {
  document.documentElement.classList.toggle('dark', theme.value === 'dark');
  localStorage.setItem('theme', theme.value);
}

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark';
  applyTheme();
}

function toggleMenu() {
  menuOpen.value = !menuOpen.value;
}

function closeMenu() {
  menuOpen.value = false;
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

async function logoutMobile() {
  closeMenu();
  await logout();
}
</script>

<style scoped>
.brand-link {
  flex-shrink: 0;
  line-height: 0;
}
.brand-wordmark {
  height: 32px;
  width: auto;
  display: block;
  transition: filter 0.2s ease;
}
.brand-invert {
  /* Juodas wordmark paverčiamas baltu tamsioje temoje */
  filter: invert(1) brightness(1.05);
}
@media (min-width: 640px) {
  .brand-wordmark {
    height: 40px;
  }
}
@media (min-width: 768px) {
  .brand-wordmark {
    height: 44px;
  }
}

.menu-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text);
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
}
.menu-toggle:hover {
  background: var(--surface-2);
}
.menu-toggle-bars {
  position: relative;
  width: 20px;
  height: 14px;
  display: block;
}
.menu-toggle-bars span {
  position: absolute;
  left: 0;
  width: 100%;
  height: 2px;
  border-radius: 1px;
  background: currentColor;
  transition: transform 0.2s ease, opacity 0.2s ease, top 0.2s ease;
}
.menu-toggle-bars span:nth-child(1) {
  top: 0;
}
.menu-toggle-bars span:nth-child(2) {
  top: 6px;
}
.menu-toggle-bars span:nth-child(3) {
  top: 12px;
}
.menu-toggle-bars.open span:nth-child(1) {
  top: 6px;
  transform: rotate(45deg);
}
.menu-toggle-bars.open span:nth-child(2) {
  opacity: 0;
}
.menu-toggle-bars.open span:nth-child(3) {
  top: 6px;
  transform: rotate(-45deg);
}

.mobile-drawer {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  background: var(--header-bg);
  border-bottom: 2px solid var(--header-border);
  box-shadow: var(--shadow-lg);
  z-index: 49;
  max-height: calc(100vh - 4rem);
  overflow-y: auto;
}

.drawer-link {
  display: block;
  padding: 12px 14px;
  border-radius: 10px;
  font-weight: 500;
  color: var(--text);
  text-decoration: none;
  transition: background 0.15s ease, color 0.15s ease;
}
.drawer-link:hover,
.drawer-link:focus-visible {
  background: var(--surface-2);
  color: var(--primary);
}

.drawer-divider {
  height: 1px;
  background: var(--border);
  margin: 12px 0;
}

.mobile-backdrop {
  position: fixed;
  inset: 4rem 0 0 0;
  background: color-mix(in srgb, #000 35%, transparent);
  backdrop-filter: blur(2px);
  z-index: 40;
}

.drawer-enter-active,
.drawer-leave-active {
  transition: transform 0.22s ease, opacity 0.22s ease;
}
.drawer-enter-from,
.drawer-leave-to {
  transform: translateY(-8px);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
