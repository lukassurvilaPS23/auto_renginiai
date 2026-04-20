<template>
  <div>
    <h1 class="page-title">Auto renginiai</h1>

    <div class="card hero mt-4">
      <p class="muted text-sm font-medium">Platforma entuziastams</p>
      <h2 class="mt-2 text-2xl sm:text-3xl font-semibold tracking-tight">
        Atrask, organizuok ir valdyk auto renginius.
      </h2>
      <p class="mt-3 max-w-2xl text-sm sm:text-base muted">
        Greitai susirask renginį, užsiregistruok ir sek naujienas vienoje vietoje.
      </p>
      <div class="mt-5 flex flex-wrap gap-3">
        <a href="/renginiai" class="btn btn-primary">Peržiūrėti renginius</a>
        <a href="/mano-renginiai" class="btn">Mano renginiai</a>
      </div>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
      <div class="card card-flat">
        <h3 class="text-base font-semibold">Greitos nuorodos</h3>
        <ul class="mt-3 space-y-2 text-sm">
          <li><a class="link" href="/renginiai">Renginių sąrašas</a></li>
          <li><a class="link" href="/prisijungti">Prisijungti</a></li>
          <li><a class="link" href="/registruotis">Registruotis</a></li>
          <li v-if="isAdmin"><a class="link" href="/xml">XML eksportas</a></li>
          <li v-if="isAdmin"><a class="link" href="/swagger">Swagger dokumentacija</a></li>
        </ul>
      </div>
      <div class="card card-flat">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold">Naujausi renginiai</h3>
          <a class="link text-sm" href="/renginiai">Visi</a>
        </div>
        <p v-if="loading" class="mt-3 muted text-sm">Kraunama...</p>
        <p v-else-if="!events.length" class="mt-3 muted text-sm">Šiuo metu nėra aktyvių renginių.</p>
        <ul v-else class="mt-3 space-y-2 text-sm">
          <li v-for="e in events" :key="e.id" class="flex items-start justify-between gap-3">
            <a class="link min-w-0" :href="`/renginiai/${e.id}`">{{ e.pavadinimas }}</a>
            <span class="muted whitespace-nowrap">{{ e.miestas }} · {{ formatDate(e.pradzios_data) }}</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="card calendar-card mt-6">
      <div class="calendar-header">
        <button class="btn" type="button" @click="changeMonth(-1)" aria-label="Ankstesnis mėnuo">‹</button>
        <div class="calendar-title">{{ monthLabel }}</div>
        <button class="btn" type="button" @click="changeMonth(1)" aria-label="Kitas mėnuo">›</button>
      </div>
      <div class="calendar-grid">
        <div class="calendar-weekday" v-for="dayName in weekdays" :key="dayName">{{ dayName }}</div>
        <div
          v-for="cell in calendarDays"
          :key="cell.key"
          class="calendar-cell"
          :class="{
            'calendar-cell--faded': !cell.isCurrentMonth,
            'calendar-cell--today': cell.isToday,
          }"
        >
          <span class="calendar-day">{{ cell.day }}</span>
          <div class="calendar-events">
            <template v-if="cell.events.length">
              <a
                v-for="event in cell.events"
                :key="event.id"
                :href="`/renginiai/${event.id}`"
              >
                {{ event.pavadinimas }}
              </a>
            </template>
            <span v-else class="calendar-empty">—</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

const events = ref([]);
const allEvents = ref([]);
const loading = ref(true);
const calendarDate = ref(new Date());
const weekdays = ['Pr', 'An', 'Tr', 'Kt', 'Pn', 'Št', 'Sk'];
const roles = ref([]);

const isAdmin = computed(() => roles.value.includes('administratorius'));

onMounted(async () => {
  const token = localStorage.getItem('token');
  if (token) {
    const meRes = await fetch('/api/as', {
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
    });
    if (meRes.ok) {
      const me = await meRes.json();
      roles.value = Array.isArray(me.roles) ? me.roles : [];
    } else {
      roles.value = [];
    }
  }

  const res = await fetch('/api/auto-renginiai', {
    headers: { Accept: 'application/json' },
  });
  if (res.ok) {
    const data = await res.json();
    allEvents.value = data.auto_renginiai || [];
    events.value = allEvents.value.slice(0, 4);
  }
  loading.value = false;
});

const eventsByDate = computed(() => {
  const map = {};
  for (const event of allEvents.value) {
    const key = toDateKey(event.pradzios_data);
    if (!key) continue;
    if (!map[key]) {
      map[key] = [];
    }
    map[key].push(event);
  }
  return map;
});

const monthLabel = computed(() => {
  const formatter = new Intl.DateTimeFormat('lt-LT', {
    month: 'long',
    year: 'numeric',
  });
  return formatter.format(calendarDate.value);
});

const calendarDays = computed(() => {
  const days = [];
  const base = new Date(calendarDate.value.getFullYear(), calendarDate.value.getMonth(), 1);
  const startWeekday = (base.getDay() + 6) % 7; // Monday = 0
  const gridStart = new Date(base);
  gridStart.setDate(base.getDate() - startWeekday);
  const todayKey = toDateKey(new Date());

  for (let i = 0; i < 42; i += 1) {
    const current = new Date(gridStart);
    current.setDate(gridStart.getDate() + i);
    const key = toDateKey(current);
    days.push({
      key,
      day: current.getDate(),
      isCurrentMonth: current.getMonth() === calendarDate.value.getMonth(),
      isToday: key === todayKey,
      events: eventsByDate.value[key] || [],
    });
  }

  return days;
});

function changeMonth(direction) {
  const next = new Date(calendarDate.value);
  next.setMonth(next.getMonth() + direction);
  calendarDate.value = next;
}

function formatDate(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function toDateKey(input) {
  if (!input) return null;
  const date = input instanceof Date ? input : new Date(input);
  if (Number.isNaN(date.getTime())) return null;
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}
</script>

<style scoped>
.hero {
  border: none;
  box-shadow: var(--shadow-lg);
  background: linear-gradient(
    135deg,
    color-mix(in srgb, var(--primary) 12%, var(--surface)),
    color-mix(in srgb, var(--primary) 6%, var(--surface))
  );
}

.calendar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}
.calendar-title {
  font-weight: 600;
  text-transform: capitalize;
}
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 8px;
}
.calendar-weekday {
  text-align: center;
  font-weight: 600;
  color: var(--muted);
  font-size: 13px;
}
.calendar-cell {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 6px;
  min-height: 110px;
  display: flex;
  flex-direction: column;
}
.calendar-cell--faded {
  opacity: 0.4;
}
.calendar-cell--today {
  border-color: color-mix(in srgb, var(--primary) 55%, var(--border));
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 18%, transparent);
}
.calendar-day {
  font-weight: 600;
  font-size: 14px;
}
.calendar-events {
  margin-top: 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  overflow: hidden;
}
.calendar-events a {
  font-size: 13px;
  color: var(--text);
  text-decoration: none;
  border-left: 3px solid var(--primary);
  padding-left: 6px;
}
.calendar-events a:hover {
  color: var(--primary);
}
.calendar-empty {
  color: var(--muted);
  font-size: 12px;
}
</style>
