<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
  anagrafica: {
    type: Object,
    required: true,
  },
});

// Calcola lo stato di completamento per ogni step
const isListinoCompleted = computed(() => !!props.anagrafica?.step_listino_completed_at);
const isProfiloCompleted = computed(() => !!props.anagrafica?.step_profilo_completed_at);
const isStaffCompleted = computed(() => !!props.anagrafica?.step_staff_completed_at);

// Calcola il progresso totale per la barra
const steps = computed(() => [isListinoCompleted.value, isProfiloCompleted.value, isStaffCompleted.value]);
const progress = computed(() => {
  const completedCount = steps.value.filter(Boolean).length;
  return (completedCount / steps.value.length) * 100;
});
</script>

<template>
  <div class="card shadow-sm border-left-accent mb-4">
    <div class="card-body">
      <h4 class="card-title">Completa il tuo profilo per iniziare</h4>
      <p class="text-muted">
        Per poter ricevere le proposte dei pazienti, è necessario completare i seguenti passaggi.
      </p>

      <div class="progress mb-4" style="height: 10px;">
        <div 
          class="progress-bar bg-accent" 
          role="progressbar" 
          :style="{ width: progress + '%' }" 
          :aria-valuenow="progress" 
          aria-valuemin="0" 
          aria-valuemax="100"
        ></div>
      </div>

      <ul class="list-unstyled">
        <li class="d-flex align-items-center mb-2" :class="{ 'completed': isListinoCompleted }">
          <i class="fa-solid fa-fw me-2" :class="isListinoCompleted ? 'fa-circle-check text-success' : 'fa-circle'"></i>
          <RouterLink to="/dashboard/listino">Completa il tuo listino (min. 3 voci con prezzo)</RouterLink>
        </li>
        <li class="d-flex align-items-center mb-2" :class="{ 'completed': isProfiloCompleted }">
          <i class="fa-solid fa-fw me-2" :class="isProfiloCompleted ? 'fa-circle-check text-success' : 'fa-circle'"></i>
          <RouterLink to="/dashboard/profilo">Aggiungi descrizione e foto (min. 3)</RouterLink>
        </li>
        <li class="d-flex align-items-center" :class="{ 'completed': isStaffCompleted }">
          <i class="fa-solid fa-fw me-2" :class="isStaffCompleted ? 'fa-circle-check text-success' : 'fa-circle'"></i>
          <RouterLink to="/dashboard/profilo">Aggiungi almeno un membro dello staff</RouterLink>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.border-left-accent {
  border-left: 4px solid var(--bs-accent);
}
.list-unstyled li a {
  text-decoration: none;
  color: var(--bs-body-color);
  transition: color 0.2s ease;
}
.list-unstyled li a:hover {
  color: var(--bs-accent);
}
.list-unstyled li.completed a {
  text-decoration: line-through;
  color: var(--bs-gray-600);
}
.fa-circle {
    font-size: 0.8em;
    color: var(--bs-gray-400);
}
</style>