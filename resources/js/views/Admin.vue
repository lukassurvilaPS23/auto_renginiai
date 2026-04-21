<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="page-title">Administravimas</h1>
        <p class="muted mt-2 text-sm">Vartotojų valdymas, statistika ir moderavimas.</p>
      </div>
      <button class="btn" type="button" @click="reloadAll" :disabled="loadingAny">
        Atnaujinti
      </button>
    </div>

    <div v-if="error" class="alert alert-danger mt-4">{{ error }}</div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
      <div class="card">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold">Sistemos statistika</h3>
          <span class="badge">Live</span>
        </div>

        <p v-if="stats.loading" class="muted mt-2 text-sm">Kraunama...</p>
        <div v-else class="mt-3 grid grid-cols-2 gap-3 text-sm">
          <div>
            <div class="muted">Vartotojai</div>
            <div class="text-lg font-semibold">{{ stats.data.vartotojai }}</div>
          </div>
          <div>
            <div class="muted">Renginiai</div>
            <div class="text-lg font-semibold">{{ stats.data.renginiai }}</div>
          </div>
          <div>
            <div class="muted">Registracijos</div>
            <div class="text-lg font-semibold">{{ stats.data.registracijos }}</div>
          </div>
          <div>
            <div class="muted">Registracijos laukia</div>
            <div class="text-lg font-semibold">{{ stats.data.registracijos_laukia }}</div>
          </div>
          <div>
            <div class="muted">Nuotraukos laukia</div>
            <div class="text-lg font-semibold">{{ stats.data.nuotraukos_laukia }}</div>
          </div>
        </div>
      </div>

      <div class="card">
        <h3 class="text-base font-semibold">Moderavimas</h3>
        <p class="muted mt-2 text-sm">
          Admin gali redaguoti / trinti renginius ir tvirtinti registracijas / nuotraukas per jau esamus puslapius.
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
          <a class="btn btn-primary" href="/mano-renginiai">Moderuoti renginius</a>
          <a class="btn" href="/renginiai">Peržiūrėti renginius</a>
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-base font-semibold">Valdyti vartotojus</h3>
        <div class="flex items-center gap-2">
          <input v-model="filter" class="input w-64" placeholder="Filtras: vardas arba el. paštas" />
        </div>
      </div>

      <p v-if="users.loading" class="muted mt-2 text-sm">Kraunama...</p>

      <div v-else class="mt-3 overflow-auto rounded-2xl border" :style="{ borderColor: 'var(--border)' }">
        <table class="min-w-[920px] w-full text-sm" style="border-collapse: collapse;">
          <thead>
            <tr>
              <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">
                ID
              </th>
              <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">
                Vartotojas
              </th>
              <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">
                Rolė
              </th>
              <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">
                Sukurta
              </th>
              <th class="p-3 text-left font-semibold" :style="{ borderBottom: 'var(--border-width) solid var(--border)', background: 'var(--surface-2)' }">
                Veiksmai
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in filteredUsers" :key="u.id">
              <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">{{ u.id }}</td>
              <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
                <div class="font-medium">{{ u.name }}</div>
                <div class="muted text-xs">{{ u.email }}</div>
              </td>
              <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
                <span class="badge">{{ (u.roles && u.roles[0]) || '—' }}</span>
              </td>
              <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">{{ u.sukurta || '—' }}</td>
              <td class="p-3 align-top" :style="{ borderBottom: 'var(--border-width) solid var(--border)' }">
                <div class="flex flex-wrap items-center gap-2">
                  <select class="select" v-model="roleDraft[u.id]">
                    <option value="vartotojas">vartotojas</option>
                    <option value="organizatorius">organizatorius</option>
                    <option value="administratorius">administratorius</option>
                  </select>
                  <button class="btn btn-primary" type="button" @click="saveRole(u.id)" :disabled="saving[u.id]">
                    Saugoti
                  </button>
                  <router-link class="btn" :to="`/profilis/${u.id}`">Profilis</router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const error = ref('');
const filter = ref('');

const stats = ref({
  loading: true,
  data: { vartotojai: 0, renginiai: 0, registracijos: 0, registracijos_laukia: 0, nuotraukos_laukia: 0 },
});

const users = ref({ loading: true, list: [] });
const roleDraft = ref({});
const saving = ref({});

const loadingAny = computed(() => stats.value.loading || users.value.loading);

function tokenOrRedirect() {
  const token = localStorage.getItem('token');
  if (!token) {
    router.push('/prisijungti');
    return null;
  }
  return token;
}

async function assertAdmin(token) {
  const meRes = await fetch('/api/as', {
    headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
  });
  if (!meRes.ok) return false;
  const me = await meRes.json();
  const roles = me.roles || me.vartotojas?.roles || [];
  return Array.isArray(roles) ? roles.includes('administratorius') : false;
}

async function loadStats() {
  const token = tokenOrRedirect();
  if (!token) return;
  stats.value.loading = true;
  const res = await fetch('/api/admin/statistika', {
    headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    error.value = data.zinute || data.message || `Klaida ${res.status}`;
    stats.value.loading = false;
    return;
  }
  stats.value.data = data.statistika || stats.value.data;
  stats.value.loading = false;
}

async function loadUsers() {
  const token = tokenOrRedirect();
  if (!token) return;
  users.value.loading = true;
  const res = await fetch('/api/admin/vartotojai', {
    headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    error.value = data.zinute || data.message || `Klaida ${res.status}`;
    users.value.loading = false;
    return;
  }
  users.value.list = data.vartotojai || [];
  roleDraft.value = Object.fromEntries(users.value.list.map((u) => [u.id, (u.roles && u.roles[0]) || 'vartotojas']));
  users.value.loading = false;
}

const filteredUsers = computed(() => {
  const q = filter.value.trim().toLowerCase();
  if (!q) return users.value.list;
  return users.value.list.filter((u) => String(u.name || '').toLowerCase().includes(q) || String(u.email || '').toLowerCase().includes(q));
});

async function saveRole(userId) {
  const token = tokenOrRedirect();
  if (!token) return;
  const role = roleDraft.value[userId];
  if (!role) return;
  saving.value[userId] = true;
  error.value = '';

  const res = await fetch(`/api/admin/vartotojai/${userId}/role`, {
    method: 'PATCH',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ role }),
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    error.value = data.zinute || data.message || `Klaida ${res.status}`;
    saving.value[userId] = false;
    return;
  }

  users.value.list = users.value.list.map((u) => (u.id === userId ? { ...u, roles: data.vartotojas?.roles || [role] } : u));
  saving.value[userId] = false;
}

async function reloadAll() {
  error.value = '';
  await Promise.all([loadStats(), loadUsers()]);
}

onMounted(async () => {
  const token = tokenOrRedirect();
  if (!token) return;

  const isAdmin = await assertAdmin(token);
  if (!isAdmin) {
    router.push('/');
    return;
  }

  await reloadAll();
});
</script>

<style scoped></style>

