<script setup>
import { computed } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';
import { useToast } from 'vue-toastification';
import logoSrc from '@/assets/images/logo-IDM.png';
import logoFaviconSrc from '@/assets/images/logo-IDM-favicon.png';
import { useUiStore } from '@/stores/uiStore';
import { usePazienteStore } from '@/stores/pazienteStore';
import { useMedicoStore } from '@/stores/medicoStore';

const router = useRouter();
const toast = useToast();
const props = defineProps({
  isCollapsed: Boolean
});

const emit = defineEmits(['toggle-sidebar']);
const uiStore = useUiStore();
const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const pazienteStore = usePazienteStore();
const medicoStore = useMedicoStore();

// MODIFICA QUI: Accediamo allo stato direttamente
const unreadNotificationsCount = computed(() => {
    if (user.value?.role === 'paziente') {
        return pazienteStore.unreadNotificationsCount;
    }
    if (user.value?.role === 'medico') {
        return medicoStore.unreadNotificationsCount;
    }
    return 0;
});

const linksPaziente = [
  { name: 'Proposte Ricevute', path: '/dashboard/proposte', icon: 'fa-solid fa-inbox' },
  { name: 'Carica Preventivo', path: '/dashboard/carica-preventivo', icon: 'fa-solid fa-cloud-arrow-up' },
];

const linksMedico = [
  { name: 'Preventivi', path: '/dashboard/preventivi-accettati', icon: 'fa-solid fa-file-invoice-dollar' },
  { name: 'Profilo Studio', path: '/dashboard/profilo', icon: 'fa-solid fa-store' },
  { name: 'Listino', path: '/dashboard/listino', icon: 'fa-solid fa-tags' },
];

const menuLinks = computed(() => user.value?.role === 'medico' ? linksMedico : linksPaziente);

const handleLogout = async () => {
  if (uiStore.hasUnsavedChanges) {
    if (!window.confirm('Hai delle modifiche non salvate. Sei sicuro di voler effettuare il logout?')) {
      return; 
    }
  }
  const response = await authStore.logout();
  if (!response.success) {
    toast.error(response.message);
  }
  toast.success(response.message);
  router.push({ name: 'login' });
}
</script>

<template>
  <div>
    <aside 
      class="sidebar d-none d-lg-flex"
      :class="{ 'collapsed': props.isCollapsed }"
    >
      <div class="logo-container">
        <RouterLink to="/dashboard">
          <img :src="props.isCollapsed ? logoFaviconSrc : logoSrc" alt="Logo" class="logo-img">
        </RouterLink>
      </div>

      <ul class="nav flex-column flex-grow-1">
        <li v-for="link in menuLinks" :key="link.path" class="nav-item position-relative">
          <RouterLink :to="link.path" class="nav-link" :title="link.name">
            <i :class="link.icon"></i>
            <span class="link-text">{{ link.name }}</span>
          </RouterLink>
          
          <span 
            v-if="(link.name === 'Proposte Ricevute' || link.name === 'Preventivi') && unreadNotificationsCount > 0" 
            class="notification-badge badge rounded-pill bg-danger"
          >
            {{ unreadNotificationsCount }}
          </span>
        </li>
      </ul>

      <div class="user-area">
          <RouterLink to="/dashboard/impostazioni" class="nav-link" title="Impostazioni">
              <i class="fa-solid fa-gear"></i>
              <span class="link-text">Impostazioni</span>
          </RouterLink>
          <a href="#" @click.prevent="handleLogout" class="nav-link" title="Logout">
              <i class="fa-solid fa-arrow-right-from-bracket"></i>
              <span class="link-text">Logout</span>
          </a>
      </div>
      
      <div class="sidebar-toggler" @click="emit('toggle-sidebar')">
        <i class="fa-solid" :class="props.isCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
      </div>
    </aside>

    <nav class="mobile-nav d-lg-none">
      <template v-if="user?.role === 'paziente'">
        <RouterLink v-for="link in menuLinks" :key="link.path" :to="link.path" class="mobile-nav-item">
          <i :class="link.icon"></i>
          <span class="label">{{ link.name }}</span>
          <span v-if="link.name === 'Proposte Ricevute' && unreadNotificationsCount > 0" class="notification-badge badge rounded-pill bg-danger">
            {{ unreadNotificationsCount }}
          </span>
        </RouterLink>
        <div></div>
      </template>

      <template v-if="user?.role === 'medico'">
         <RouterLink v-for="link in menuLinks" :key="link.path" :to="link.path" class="mobile-nav-item">
            <i :class="link.icon"></i>
            <span class="label">{{ link.name }}</span>
            <span v-if="link.name === 'Preventivi' && unreadNotificationsCount > 0" class="notification-badge badge rounded-pill bg-danger">
              {{ unreadNotificationsCount }}
            </span>
          </RouterLink>
      </template>
      
      <RouterLink to="/dashboard/impostazioni" class="mobile-nav-item">
        <i class="fa-solid fa-gear"></i>
        <span class="label">Impostazioni</span>
      </RouterLink>

      <a href="#" @click.prevent="handleLogout" class="mobile-nav-item">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
        <span class="label">Logout</span>
      </a>
    </nav>
  </div>
</template>

<style scoped>
.sidebar { flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width); background-color: white; box-shadow: 0 0 15px rgba(0,0,0,0.07); transition: width 0.3s ease; z-index: 1031; padding: 1rem 0; }
.logo-container { padding: 0 1.5rem; margin-bottom: 2rem; height: 50px; display: flex; align-items: center; transition: padding 0.3s ease; }
.sidebar.collapsed .logo-container { padding: 0; justify-content: center; }
.logo-img { max-height: 40px; transition: all 0.3s ease; }
.sidebar.collapsed .logo-img { max-height: 35px; }
.sidebar .nav-link { display: flex; align-items: center; color: #555; padding: 0.75rem; overflow: hidden; }
.sidebar.collapsed .nav-link { padding-left: 0; padding-right: 0; justify-content: center; }
.sidebar .nav-link i { font-size: 1.2rem; width: 50px; text-align: center; flex-shrink: 0; transition: width 0.3s ease; }
.sidebar.collapsed .nav-link i { width: 80px; }
.sidebar .nav-link .link-text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: opacity 0.2s; }
.sidebar.collapsed .nav-link .link-text { opacity: 0; width: 0; }
.sidebar .nav-link:hover { background-color: #f8f9fa; }
.sidebar .nav-link.router-link-exact-active { color: var(--bs-accent); background-color: rgba(var(--bs-accent-rgb), 0.1); font-weight: 600; }
.user-area { margin-top: auto; padding-top: 1rem; border-top: 1px solid #eee; }
.sidebar-toggler { position: absolute; top: 1rem; right: -15px; background-color: white; border: 1px solid #eee; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.3s ease; opacity: 0.3; }
.sidebar-toggler:hover { transform: scale(1.1); opacity: 1; }
.sidebar.collapsed .sidebar-toggler { right: -15px; }

/* Stili Mobile */
.mobile-nav { position: fixed; bottom: 0; left: 0; right: 0; height: 65px; background-color: white; box-shadow: 0 -5px 15px rgba(0,0,0,0.05); display: grid; grid-template-columns: repeat(5, 1fr); align-items: center; z-index: 1030; border-top: 1px solid #eee; }
.mobile-nav-item { position: relative; display: flex; flex-direction: column; align-items: center; text-decoration: none; color: #6c757d; font-size: 0.7rem; }
.mobile-nav-item i { font-size: 1.4rem; margin-bottom: 2px; }
.mobile-nav-item.router-link-exact-active { color: var(--bs-accent); font-weight: 600; }

/* --- NUOVI STILI PER IL BADGE --- */
.notification-badge {
    position: absolute;
    top: 10px;
    left: 45px; /* Posizione iniziale relativa all'icona */
    font-size: 0.65em;
    padding: 0.3em 0.6em;
    transition: all 0.3s ease;
    pointer-events: none; /* Impedisce al badge di interferire con i click */
}
/* Quando la sidebar è chiusa, sposta il badge */
.sidebar.collapsed .notification-badge {
    top: 12px;
    left: 50px;
}
/* Stile per il badge su mobile */
.mobile-nav .notification-badge {
    top: -2px;
    left: calc(50% + 10px);
}
</style>