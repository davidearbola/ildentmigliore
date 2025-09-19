<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/authStore';

const authStore = useAuthStore();

onMounted(() => {
  // Estraiamo il token dal frammento # dell'URL
  const hash = window.location.hash.substring(1); // Rimuove il # iniziale
  const params = new URLSearchParams(hash);
  const token = params.get('token');

  // Chiamiamo l'azione dello store per gestire il login
  authStore.handleSocialLoginCallback(token);
});
</script>

<template>
  <div class="d-flex justify-content-center align-items-center vh-100">
    <div>
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3">Finalizzando l'autenticazione...</p>
    </div>
  </div>
</template>