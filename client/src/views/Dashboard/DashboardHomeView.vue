<script setup>
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import OnboardingMedico from '@/components/OnBoardingMedico.vue'; 

const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const router = useRouter();

// 2. Controlla se tutti gli step sono completati
const isOnboardingCompleted = computed(() => {
    const anagrafica = user.value?.anagrafica_medico;
    if (!anagrafica) return false;
    return anagrafica.step_listino_completed_at && 
           anagrafica.step_profilo_completed_at && 
           anagrafica.step_staff_completed_at;
});

const goToProfiloPubblico = () => {
    if(user.value?.id) {
        router.push({ name: 'medico-profilo-pubblico', params: { id: user.value.id } });
    }
}
</script>

<template>
  <div>
    <OnboardingMedico 
      v-if="user?.role === 'medico' && user.anagrafica_medico && !isOnboardingCompleted"
      :anagrafica="user.anagrafica_medico"
    />

    <h1 class="display-5 fw-bold">Dashboard Principale</h1>
    <p class="lead text-muted">Benvenuto nella tua area personale, {{ user?.name }}.</p>
    <hr class="my-4">

    <div v-if="user?.role === 'medico' && isOnboardingCompleted">
        <div class="alert alert-success d-flex flex-column flex-md-row align-items-center justify-content-between">
            <div class="mb-3 mb-md-0">
                <h4 class="alert-heading">Profilo Completo!</h4>
                <p class="mb-0">Ottimo lavoro! Il tuo profilo è ora completo e visibile ai pazienti che riceveranno le tue proposte.</p>
            </div>
            <button class="btn btn-success" @click="goToProfiloPubblico">
                <i class="fa-solid fa-eye me-2"></i>Vedi Profilo Pubblico
            </button>
        </div>
    </div>
  </div>
</template>