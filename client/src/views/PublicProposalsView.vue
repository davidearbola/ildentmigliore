<script setup>
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { usePazienteStore } from '@/stores/pazienteStore';
import { storeToRefs } from 'pinia';

const route = useRoute();
const pazienteStore = usePazienteStore();
const { isLoading, preventivoSalvato, proposte } = storeToRefs(pazienteStore);

const token = route.params.token;

onMounted(() => {
  pazienteStore.fetchPublicProposte(token);
});

const formatCurrency = (value) => {
    if (isNaN(parseFloat(value))) return '0,00';
    return parseFloat(value).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
  <div class="container py-5">
    <div v-if="isLoading" class="text-center">
      <div class="spinner-border text-accent" role="status" style="width: 3rem; height: 3rem;"></div>
      <h4 class="mt-3">Caricamento delle tue proposte...</h4>
    </div>

    <div v-else-if="!preventivoSalvato" class="text-center">
      <i class="fa-solid fa-circle-exclamation fa-3x text-danger mb-3"></i>
      <h2>Link non valido o scaduto</h2>
      <p class="text-muted">Non è stato possibile trovare un preventivo associato a questo link.</p>
    </div>

    <div v-else>
      <div class="row g-5">
        <!-- Colonna Riepilogo Preventivo -->
        <div class="col-lg-4">
          <div class="card shadow-sm sticky-top" style="top: 2rem;">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0">Il Tuo Preventivo</h4>
            </div>
            <div class="card-body">
              <p><strong>Email:</strong> {{ preventivoSalvato.mail_paziente }}</p>
              <hr>
              <h5 class="card-title">Voci Confermate</h5>
              <ul class="list-group list-group-flush">
                <li v-for="(voce, index) in preventivoSalvato.json_preventivo.voci_preventivo" :key="index" class="list-group-item d-flex justify-content-between align-items-center">
                  <span>{{ voce.prestazione }} (x{{ voce.quantità }})</span>
                  <span class="badge bg-secondary rounded-pill">€ {{ formatCurrency(voce.prezzo) }}</span>
                </li>
              </ul>
            </div>
            <div class="card-footer">
              <h5 class="d-flex justify-content-between">
                <span>Totale Originale:</span>
                <strong>€ {{ formatCurrency(preventivoSalvato.json_preventivo.totale_preventivo) }}</strong>
              </h5>
            </div>
          </div>
        </div>

        <!-- Colonna Proposte dei Medici -->
        <div class="col-lg-8">
          <h1 class="display-5 mb-4">Proposte Ricevute</h1>
          <div v-if="proposte.length === 0" class="alert alert-info">
            Nessuna proposta ancora disponibile. I nostri medici stanno elaborando la migliore offerta per te. Riprova più tardi!
          </div>
          <div v-else class="vstack gap-4">
            <div v-for="proposta in proposte" :key="proposta.id" class="card shadow-sm">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ proposta.medico.anagrafica_medico.ragione_sociale }}</h5>
                <span class="badge bg-success fs-6">Risparmio Potenziale: XX%</span>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm table-striped">
                    <thead>
                      <tr>
                        <th>Prestazione Originale</th>
                        <th>Prestazione Offerta</th>
                        <th class="text-end">Prezzo Offerto</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(voce, index) in proposta.json_proposta.voci_proposta" :key="index">
                        <td><small>{{ voce.prestazione_originale }}</small></td>
                        <td><strong>{{ voce.prestazione_corrispondente }}</strong></td>
                        <td class="text-end">€ {{ formatCurrency(voce.prezzo) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <button class="btn btn-sm btn-outline-primary">Vedi Profilo Studio</button>
                <h4 class="mb-0">Totale Proposto: <span class="text-accent fw-bold">€ {{ formatCurrency(proposta.json_proposta.totale_proposta) }}</span></h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bg-light-accent {
    background-color: rgba(var(--bs-accent-rgb), 0.1);
}
</style>
