<script setup>
import { ref, onMounted } from 'vue'
import CtaSection from '@/components/CtaSection.vue'

const stepElements = ref([])
const howItWorksSteps = [
  {
    icon: 'https://img.icons8.com/fluency/96/upload-to-cloud.png',
    title: '1. Inviaci il tuo preventivo',
    text: 'Il primo passo è semplicissimo. Fai una foto chiara o una scansione del preventivo che hai ricevuto e caricalo sulla nostra piattaforma oppure inviacelo tramite WhatsApp o Email. Accettiamo i formati più comuni come PDF, JPG o PNG. Il processo è completamente anonimo per proteggere la tua privacy. I dentisti vedranno solo le cure richieste, non i tuoi dati personali.',
  },
  {
    icon: 'https://img.icons8.com/fluency/96/get-revenue.png',
    title: '2. Analizziamo e confrontiamo per te',
    text: 'Una volta ricevuto il tuo preventivo, il nostro team selezionerà i 3 Dentisti Migliori nella tua zona e genererà le proposte migliori per te. Potrai vedere le offerte comodamente dal tuo profilo.',
  },
  {
    icon: 'https://img.icons8.com/color/96/choose.png',
    title: '3. Scegli tu il Dentista Migliore',
    text: 'A questo punto tocca a te! Analizza tutte le proposte ricevute. Potrai confrontare non solo i prezzi, ma anche consultare i profili dei dentisti, vedere dove si trovano i loro studi e leggere le recensioni lasciate da altri pazienti come te. Questa trasparenza ti permette di fare una scelta informata, basata non solo sul risparmio ma anche sulla fiducia.',
  },
]

onMounted(() => {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible')
          observer.unobserve(entry.target)
        }
      })
    },
    { threshold: 0.2 },
  )

  stepElements.value.forEach((el) => {
    if (el) observer.observe(el)
  })
})
</script>

<template>
  <div style="margin-top: 10vh">
    <div class="container text-center py-5">
      <h1 class="display-4 fw-bold text-primary">Come Funziona il Nostro Servizio</h1>
      <p class="lead col-lg-8 mx-auto text-muted">
        Ottenere un preventivo migliore non è mai stato così facile. Segui questi tre semplici passi
        per iniziare a risparmiare.
      </p>
    </div>

    <div class="container py-5">
      <div class="timeline-container mx-auto">
        <div
          v-for="(step, index) in howItWorksSteps"
          :key="index"
          class="timeline-step"
          :ref="
            (el) => {
              if (el) stepElements[index] = el
            }
          "
        >
          <div class="timeline-icon bg-primary text-white">
            <img :src="step.icon" :alt="step.title" width="48" height="48" />
          </div>
          <div class="timeline-content">
            <h3 class="fw-semibold">{{ step.title }}</h3>
            <p class="text-muted">{{ step.text }}</p>
          </div>
        </div>
      </div>
    </div>

    <section class="py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Perché scegliere IlDentistaMigliore.it</h2>
        </div>
        <div class="row g-4">
          <div class="col-lg-3 col-md-6">
            <div class="card h-100 text-center border-0 shadow-sm">
              <div class="card-body p-4">
                <img
                  src="https://img.icons8.com/fluency/96/initiate-money-transfer.png"
                  alt="Risparmio Icona"
                  width="80"
                  height="80"
                  class="mb-3"
                />
                <h5 class="card-title fw-bold">Risparmio Garantito</h5>
                <p class="card-text text-muted">
                  Ottieni un risparmio fino al 50% rispetto al tuo preventivo iniziale.
                </p>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="card h-100 text-center border-0 shadow-sm">
              <div class="card-body p-4">
                <img
                  src="https://img.icons8.com/fluency/96/best-seller.png"
                  alt="Qualità Icona"
                  width="80"
                  height="80"
                  class="mb-3"
                />
                <h5 class="card-title fw-bold">Dentisti Certificati</h5>
                <p class="card-text text-muted">
                  Collaboriamo solo con studi dentistici selezionati e con ottime recensioni.
                </p>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="card h-100 text-center border-0 shadow-sm">
              <div class="card-body p-4">
                <img
                  src="https://img.icons8.com/fluency/96/rocket.png"
                  alt="Veloce Icona"
                  width="80"
                  height="80"
                  class="mb-3"
                />
                <h5 class="card-title fw-bold">Semplice e Veloce</h5>
                <p class="card-text text-muted">
                  Il servizio è gratuito, rapido e non ti impegna in alcun modo.
                </p>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="card h-100 text-center border-0 shadow-sm">
              <div class="card-body p-4">
                <img
                  src="https://img.icons8.com/fluency/96/online-support.png"
                  alt="Assistenza Icona"
                  width="80"
                  height="80"
                  class="mb-3"
                />
                <h5 class="card-title fw-bold">Supporto Dedicato</h5>
                <p class="card-text text-muted">
                  Ricevi assistenza personalizzata in ogni fase del processo.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <CtaSection />
  </div>
</template>

<style scoped>
.timeline-container {
  position: relative;
  max-width: 800px;
}

.timeline-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 39px;
  width: 4px;
  height: 100%;
  background-color: #e9ecef;
  border-radius: 2px;
}

.timeline-step {
  position: relative;
  display: flex;
  align-items: flex-start;
  margin-bottom: 4rem;
  opacity: 0;
  transform: translateY(30px);
  transition:
    opacity 0.6s ease-out,
    transform 0.6s ease-out;
}

.timeline-step:last-child {
  margin-bottom: 0;
}

.timeline-step.is-visible {
  opacity: 1;
  transform: translateY(0);
}

.timeline-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background-color: var(--bs-primary);
  border: 5px solid white;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
  z-index: 10;
}

.timeline-icon img {
  width: 48px;
  height: 48px;
}

.timeline-content {
  margin-left: 2rem;
  padding-top: 1.5rem;
}

.icon-circle {
  height: 64px;
  width: 64px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}
</style>
