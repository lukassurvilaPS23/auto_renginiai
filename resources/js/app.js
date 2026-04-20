import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import 'leaflet/dist/leaflet.css';
import 'leaflet-draw/dist/leaflet.draw.css';
import App from './App.vue';
import Home from './views/Home.vue';
import Events from './views/Events.vue';
import EventDetail from './views/EventDetail.vue';
import EventRegistration from './views/EventRegistration.vue';
import MyEvents from './views/MyEvents.vue';
import Profile from './views/Profile.vue';
import Garage from './views/Garage.vue';
import Login from './views/Login.vue';
import Register from './views/Register.vue';
import CarDetail from './views/CarDetail.vue';
import Admin from './views/Admin.vue';

const routes = [
    { path: '/', component: Home },
    { path: '/renginiai', component: Events },
    { path: '/renginiai/:id', component: EventDetail, props: true },
    { path: '/renginiai/:id/registracija', component: EventRegistration, props: true, meta: { requiresAuth: true } },
    { path: '/mano-renginiai', component: MyEvents, meta: { requiresAuth: true } },
    { path: '/profilis', component: Profile, meta: { requiresAuth: true } },
    { path: '/profilis/:userId', component: Profile, props: true, meta: { requiresAuth: true } },
    { path: '/garazas', component: Garage, meta: { requiresAuth: true } },
    { path: '/automobiliai/:id', component: CarDetail, props: true, meta: { requiresAuth: true } },
    { path: '/admin', component: Admin, meta: { requiresAuth: true } },
    { path: '/prisijungti', component: Login },
    { path: '/registruotis', component: Register },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    if (to.meta.requiresAuth && !token) {
        next('/prisijungti');
    } else {
        next();
    }
});

const app = createApp(App);
app.use(router);
app.mount('#app');
