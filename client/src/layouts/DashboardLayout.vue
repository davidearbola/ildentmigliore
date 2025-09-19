<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import DashboardSidebar from '@/components/DashboardSidebar.vue';
import { usePazienteStore } from '@/stores/pazienteStore';
import { useMedicoStore } from '@/stores/medicoStore';
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';

const isCollapsed = ref(true);
const pazienteStore = usePazienteStore(); 
const medicoStore = useMedicoStore();
const authStore = useAuthStore();
const { user } = storeToRefs(authStore)
let pollingInterval = null;

const sidebarWidth = computed(() => {
  return isCollapsed.value ? '80px' : '250px';
});

const handleToggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value;
};

watch(user, (newUser) => {
  clearInterval(pollingInterval);

  if (newUser) { 
    const userRole = newUser.role;
    let storeToPoll = null;

    if (userRole === 'paziente') {
      storeToPoll = pazienteStore;
    } else if (userRole === 'medico') {
      storeToPoll = medicoStore;
    }

    if (storeToPoll) {
      storeToPoll.checkForNotifications(); 
      pollingInterval = setInterval(() => {
        storeToPoll.checkForNotifications();
      }, 30000);
    }
  }
}, { immediate: true });

onUnmounted(() => {
  clearInterval(pollingInterval);
});
</script>

<template>
  <div class="dashboard-layout" :style="{ '--sidebar-width': sidebarWidth }">
    <DashboardSidebar 
      :is-collapsed="isCollapsed"
      @toggle-sidebar="handleToggleSidebar"
    />
    <div class="main-content">
      <div class="container-fluid p-3 p-md-4">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-layout { display: flex; background-color: #f4f7f6; }
.main-content { flex-grow: 1; padding-left: var(--sidebar-width); padding-bottom: 80px; min-height: 100vh; transition: padding-left 0.3s ease; }
@media (max-width: 991.98px) { .main-content { padding-left: 0; } }
</style>