<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { RouterLink, useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'
import logoSrc from '@/assets/images/logo-IDM.png';
import { useToast } from 'vue-toastification';
import { Collapse } from 'bootstrap';

const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const router = useRouter();
const route = useRoute();
const toast = useToast();
const collapseMenuRef = ref(null);
let collapseInstance = null;

const isMenuOpen = ref(false);

// const dashboardLink = computed(() => {
//   if (!user.value) return '/login';
//   return user.value.role === 'medico' ? '/medico/dashboard' : '/paziente/dashboard';
// });

const closeMenu = () => {
  if (collapseInstance && collapseMenuRef.value) {
    collapseInstance.hide();
  }
};

const toggleMenu = () => {
  if (collapseInstance) {
    collapseInstance.toggle();
  }
};

const logout = async () => {
  closeMenu();
  const response = await authStore.logout();
  if (!response.success) {
    toast.error(response.message);
  }
  toast.success(response.message);
  router.push({ name: 'home' });
};

onMounted(() => {
  const menuEl = collapseMenuRef.value;
  if (menuEl) {
    collapseInstance = new Collapse(menuEl, { toggle: false });

    menuEl.addEventListener('show.bs.collapse', () => {
      isMenuOpen.value = true;
    });
    menuEl.addEventListener('hide.bs.collapse', () => {
      isMenuOpen.value = false;
    });
  }
});

onUnmounted(() => {
  if (collapseInstance) {
    collapseInstance.dispose();
  }
});

watch(() => route.path, () => {
  closeMenu();
});
</script>

<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container">
      <RouterLink class="navbar-brand fw-bold" to="/">
        <img :src="logoSrc" alt="Il Dentista Migliore Logo" style="height: 2.5rem;">
      </RouterLink>

      <button 
        class="navbar-toggler" 
        type="button" 
        aria-controls="publicNavbar" 
        aria-expanded="false" 
        aria-label="Toggle navigation"
        @click="toggleMenu"
        :class="{ 'open': isMenuOpen }"
      >
        <div class="hamburger-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
      </button>

      <div class="collapse navbar-collapse" id="publicNavbar" ref="collapseMenuRef">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center" @click="closeMenu">
          <li class="nav-item">
            <RouterLink class="nav-link" to="/come-funziona">Come Funziona</RouterLink>
          </li>
          <template v-if="!user">
            <li class="nav-item">
              <RouterLink class="nav-link" to="/register-medico">Sei un dentista?</RouterLink>
            </li>
            <li class="nav-item ms-lg-2">
              <RouterLink to="/login" class="btn btn-primary btn-sm">Accedi</RouterLink>
            </li>
            <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
              <RouterLink to="/register" class="btn btn-accent btn-sm">Registrati</RouterLink>
            </li>
          </template>
          <template v-else>
            <li class="nav-item ms-lg-2">
              <RouterLink class="btn btn-accent btn-sm" to="/dashboard">Area Personale</RouterLink>
            </li>
            <li class="nav-item ms-lg-2">
              <button type="button" class="btn btn-primary btn-sm" @click.stop="logout">Logout</button>
            </li>
          </template>
        </ul>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.btn { padding: 0.5rem 1.25rem; font-weight: 500; color: white !important; }
.nav-link.router-link-active, .nav-link.router-link-exact-active { font-weight: 600; color: var(--bs-primary) !important; }
nav { height: 10vh; }
@media (max-width: 991.98px) {
  .navbar-collapse { background-color: white; padding: 1rem; margin-top: 0.5rem; border-radius: var(--bs-border-radius); border: 1px solid rgba(0, 0, 0, 0.1); }
}

.navbar-toggler {
  border: none;
  padding: 0;
}
.navbar-toggler:focus {
  box-shadow: none;
}

.hamburger-icon {
  width: 24px;
  height: 20px;
  position: relative;
  transform: rotate(0deg);
  transition: .5s ease-in-out;
  cursor: pointer;
}

.hamburger-icon span {
  display: block;
  position: absolute;
  height: 3px;
  width: 100%;
  background: #343a40; 
  border-radius: 9px;
  opacity: 1;
  left: 0;
  transform: rotate(0deg);
  transition: .25s ease-in-out;
}

.hamburger-icon span:nth-child(1) { top: 0px; }
.hamburger-icon span:nth-child(2) { top: 8px; }
.hamburger-icon span:nth-child(3) { top: 16px; }

.navbar-toggler.open .hamburger-icon span:nth-child(1) {
  top: 8px;
  transform: rotate(135deg);
}
.navbar-toggler.open .hamburger-icon span:nth-child(2) {
  opacity: 0;
  left: -60px;
}
.navbar-toggler.open .hamburger-icon span:nth-child(3) {
  top: 8px;
  transform: rotate(-135deg);
}
</style>