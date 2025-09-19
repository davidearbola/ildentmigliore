<script setup>
import { RouterLink } from 'vue-router'
import logoSrc from '@/assets/images/logo-IDM.png'
</script>
<template>
  <footer class="bg-dark text-white">
    <div class="container py-4">
      <div class="row justify-content-between align-items-center">
        <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
            <strong>Il Dentista Migliore &copy; 2025</strong>
            <p class="text-white-50 mb-0">Tutti i diritti riservati.</p>
            <img :src="logoSrc" alt="Il Dentista Migliore Logo" style="height: 2.5rem" />
        </div>
        <div class="col-md-4 text-center mb-3 mb-md-0">
          <h5 class="text-uppercase text-accent">Link Utili</h5>
          <ul class="list-unstyled">
            <li><RouterLink to="/come-funziona" class="text-white-50 text-decoration-none">Come funziona</RouterLink></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Termini di Servizio</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="col-md-4 text-end">
          <h5 class="text-uppercase text-accent">Seguici</h5>
          <a href="#" class="text-white me-3 fs-4"
            ><i class="fa-brands fa-facebook"></i></a>
          <a href="#" class="text-white me-3 fs-4"
            ><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="text-white fs-4"><i class="fa-brands fa-linkedin"></i></a>
        </div>
      </div>
    </div>
  </footer>
</template>
