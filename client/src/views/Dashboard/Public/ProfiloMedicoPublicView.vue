<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMedicoStore } from '@/stores/medicoStore'
import { storeToRefs } from 'pinia'
import { useToast } from 'vue-toastification'

const route = useRoute()
const router = useRouter()
const medicoStore = useMedicoStore()
const toast = useToast()

const { profiloPubblico, isLoading } = storeToRefs(medicoStore)
const error = ref(null)

onMounted(async () => {
  const medicoId = route.params.id
  const { success, message } = await medicoStore.fetchProfiloPubblico(medicoId)
  if (!success) {
    error.value = message
    toast.error(message)
    router.push('/dashboard') // Reindirizza se non autorizzato
  }
})

// Funzione per ottenere l'URL completo dell'asset
const getAssetUrl = (path) => {
    return `${import.meta.env.VITE_API_URL}/storage/${path}`;
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="d-flex justify-content-center align-items-center vh-100">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Caricamento...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else-if="profiloPubblico" class="profilo-container">
      <section class="hero-section text-center p-5 rounded-3 mb-4">
        <h1 class="display-4 fw-bold">{{ profiloPubblico.anagrafica.ragione_sociale }}</h1>
        <p class="lead text-white-50">
          <i class="fa-solid fa-location-dot me-2"></i>
          {{ profiloPubblico.anagrafica.indirizzo }}, {{ profiloPubblico.anagrafica.citta }} ({{ profiloPubblico.anagrafica.provincia }})
        </p>
      </section>

      <section class="mb-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="card-title h4 mb-3"><i class="fa-solid fa-circle-info me-2"></i>Chi Siamo</h2>
            <p class="card-text text-muted" style="white-space: pre-wrap;">{{ profiloPubblico.anagrafica.descrizione }}</p>
          </div>
        </div>
      </section>

      <section class="mb-5" v-if="profiloPubblico.fotoStudi && profiloPubblico.fotoStudi.length > 0">
        <h2 class="mb-4 text-center"><i class="fa-solid fa-images me-2"></i>Galleria dello Studio</h2>
        <div class="row g-4">
          <div v-for="foto in profiloPubblico.fotoStudi" :key="foto.id" class="col-md-4">
            <div class="card gallery-card">
              <img :src="getAssetUrl(foto.file_path)" class="card-img-top" :alt="'Foto dello studio ' + profiloPubblico.anagrafica.ragione_sociale">
            </div>
          </div>
        </div>
      </section>

      <section v-if="profiloPubblico.staff && profiloPubblico.staff.length > 0">
        <h2 class="mb-4 text-center"><i class="fa-solid fa-users me-2"></i>Il Nostro Staff</h2>
        <div class="row g-4">
          <div v-for="membro in profiloPubblico.staff" :key="membro.id" class="col-md-4">
            <div class="card text-center staff-card shadow-sm">
              <img :src="getAssetUrl(membro.foto_path)" class="staff-img rounded-circle mx-auto mt-3" :alt="'Foto di ' + membro.nome">
              <div class="card-body">
                <h5 class="card-title">{{ membro.nome }}</h5>
                <h6 class="card-subtitle mb-2 text-primary">{{ membro.ruolo }}</h6>
                <p v-if="membro.specializzazione" class="card-text text-muted small">{{ membro.specializzazione }}</p>
                <p v-if="membro.esperienza" class="card-text text-muted small"><em>{{ membro.esperienza }}</em></p>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<style scoped>
.profilo-container {
  max-width: 1200px;
  margin: auto;
}

.hero-section {
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('@/assets/images/sfondo-hero-idm.jpg') no-repeat center center;
  background-size: cover;
  color: white;
}

.gallery-card img {
  height: 250px;
  object-fit: cover;
}

.staff-card {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}
.staff-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.staff-img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border: 4px solid #fff;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.text-accent {
  color: var(--bs-primary);
}
</style>