<script setup>
import { computed, ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'
import HeroSrc from '../assets/images/sfondo-hero-idm.jpg'
import CtaSection from '@/components/CtaSection.vue'

const authStore = useAuthStore()
const { user } = storeToRefs(authStore)
// const dashboardLink = computed(() => {
//   if (!user.value) return '/login';
//   return user.value.role === 'medico' ? '/medico/dashboard' : '/paziente/dashboard';
// });
const heroStyle = computed(() => ({
  backgroundImage: `url(${HeroSrc})`,
}))
// const dentistCounter = ref(0)
// const quoteCounter = ref(0)
// const heroSectionRef = ref(null)

// const animateCounter = (targetRef, endValue, duration) => {
//   let start = 0
//   const increment = endValue / (duration / 16)

//   const updateCounter = () => {
//     start += increment
//     if (start < endValue) {
//       targetRef.value = Math.ceil(start)
//       requestAnimationFrame(updateCounter)
//     } else {
//       targetRef.value = endValue
//     }
//   }
//   updateCounter()
// }

// onMounted(() => {
//   const observer = new IntersectionObserver(
//     (entries) => {
//       if (entries[0].isIntersecting) {
//         animateCounter(dentistCounter, 540, 2000)
//         animateCounter(quoteCounter, 2100, 2000)
//         observer.disconnect()
//       }
//     },
//     { threshold: 0.5 }
//   )

//   if (heroSectionRef.value) {
//     observer.observe(heroSectionRef.value)
//   }
// })

const howItWorksSteps = [
  {
    icon: 'https://img.icons8.com/fluency/96/upload-to-cloud.png',
    title: '1. Inviaci il tuo preventivo',
    text: 'Puoi caricarlo sul sito, inviarlo via WhatsApp o via email.',
  },
  {
    icon: 'https://img.icons8.com/fluency/96/get-revenue.png',
    title: '2. Analizziamo e confrontiamo per te',
    text: 'Il nostro team seleziona 3 dentisti affidabili della tua zona, proponendoti piani di cura alternativi di pari qualità ma a un prezzo più vantaggioso.',
  },
  {
    icon: 'https://img.icons8.com/color/96/choose.png',
    title: '3. Scegli tu il dentista migliore',
    text: 'Confronta prezzo, esperienza e recensioni. Decidi in libertà e senza vincoli.',
  },
]
</script>

<template>
  <div>
    <section
      class="hero-section d-flex align-items-center justify-content-center text-center text-white"
      ref="heroSectionRef"
      :style="heroStyle"
    >
      <div class="hero-overlay"></div>
      <div class="container hero-content">
        <h1 class="display-3 fw-bolder text-white">
           Il tuo preventivo dentistico, al miglior prezzo garantito.
        </h1>
        <h3 class="text-white">Ricevi 3 proposte da dentisti selezionati della tua zona e scegli la migliore.</h3>
        <p class="lead col-lg-8 mx-auto my-4">
          Hai già un preventivo? Inviacelo. Noi confrontiamo, tu risparmi. <br>
        </p>
        <h4 class="text-accent">Servizio gratuito e senza impegno.</h4>

        <!-- <div class="row mt-5 mb-4 justify-content-center">
          <div class="col-6 col-md-4">
            <h2 class="display-4 fw-bold text-accent">{{ Math.round(dentistCounter) }}+</h2>
            <p class="lead">Dentisti Verificati</p>
          </div>
          <div class="col-6 col-md-4">
            <h2 class="display-4 fw-bold text-accent">{{ Math.round(quoteCounter) }}+</h2>
            <p class="lead">Preventivi Confrontati</p>
          </div>
        </div> -->

        <div>
          <RouterLink :to="!user ? 'login' : '/dashboard'" class="btn btn-accent btn-lg px-5 py-3 mt-3 fw-bold text-white">{{ !user ? 'Carica Preventivo Ora' : 'Vai alla tua Dashboard' }}</RouterLink>
        </div>
      </div>
    </section>

    <section class="py-5">
      <div class="container px-4 py-5">
        <div class="text-center mx-auto" style="max-width: 700px">
          <h2 class="display-6 fw-bold mb-3">Semplice, Veloce, Conveniente</h2>
        </div>
        <div class="row g-4 mt-4 row-cols-1 row-cols-lg-3">
          <div v-for="step in howItWorksSteps" :key="step.title" class="col">
            <div class="card h-100 text-center border-0 shadow-sm p-3">
              <div class="card-body">
                <img :src="step.icon" :alt="step.title" height="80" class="mb-3" />
                <h3 class="h5 fw-bold">{{ step.title }}</h3>
                <p class="text-muted small">{{ step.text }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <CtaSection/>

    <section class="py-5 bg-light">
      <div class="container px-4">
        <div class="row align-items-center g-5">
          <div class="col-lg-6">
            <img
              src="https://images.pexels.com/photos/40568/medical-appointment-doctor-healthcare-40568.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2"
              class="img-fluid rounded-3 shadow"
              alt="Studio medico moderno"
            />
          </div>
          <div class="col-lg-6">
            <h2 class="fw-bold display-6">Sei un Dentista? Unisciti a Noi!</h2>
            <p class="text-muted my-4">
              Entra a far parte del nostro network esclusivo. Riempi le ore vuote della tua agenda e
              aumenta il numero di pazienti senza sforzo. Concentrati sul tuo lavoro, alla
              burocrazia pensiamo noi.
            </p>
            <RouterLink to="/register-medico" class="btn btn-primary btn-lg">Registra il Tuo Studio</RouterLink>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.hero-section {
  margin-top: 10vh;
  position: relative;
  min-height: 90vh;
  background-size: cover;
  background-position: center;
}

.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  /* Leggera variazione per un contrasto migliore */
  background-color: rgba(0, 0, 0, 0.4); 
  z-index: 1;
}

.hero-content {
  position: relative;
  z-index: 2;
}

/* Questa classe .btn-accent funzionerà grazie alla nostra configurazione SCSS.
  Aggiungere uno stile :hover è un'ottima pratica.
*/
.btn-accent:hover {
  filter: brightness(90%);
}
</style>