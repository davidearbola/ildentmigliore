<script setup>
import logoSrc from '@/assets/images/logo-IDM.png';
import { RouterLink } from 'vue-router';
import HeroSrc from '../assets/images/sfondo-hero-idm.jpg'
</script>

<template>
  <div class="auth-container">
    <div class="container-fluid h-100 p-0">
      <div class="row h-100 g-0">
        <div class="col-lg-6 d-flex flex-column form-column">
          
          <div class="form-wrapper my-auto">
            
            <div class="text-center mb-4">
              <RouterLink to="/">
                <img style="height: 3rem;" :src="logoSrc" alt="Il Dentista Migliore Logo">
              </RouterLink>
            </div>
            
            <slot />
          </div>

        </div>

        <div class="col-lg-6 d-none d-lg-block image-column">
          <img 
            :src="HeroSrc" 
            alt="Immagine descrittiva" 
            class="img-fluid"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-container {
  height: 100vh;
  width: 100vw;
  background-color: var(--bs-light);
}

.form-column {
  height: 100vh;
  overflow-y: auto;
  padding: 2rem 1rem;
}

.form-wrapper {
  width: 100%;
  max-width: 550px;
  /* my-auto in un contenitore flex centra verticalmente il blocco. */
  margin-left: auto;
  margin-right: auto;
}

.image-column {
  height: 100vh;
  overflow: hidden;
}

.image-column .img-fluid {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>