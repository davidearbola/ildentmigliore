<script setup>
import { onMounted, ref } from 'vue';
import { useMedicoStore } from '@/stores/medicoStore';
import { storeToRefs } from 'pinia';
import { Modal } from 'bootstrap';

const medicoStore = useMedicoStore();
const { proposteAccettate, isLoading } = storeToRefs(medicoStore);

const selectedProposta = ref(null);
const dettaglioModalRef = ref(null);
let dettaglioModalInstance = null;

onMounted(async () => {
  if (dettaglioModalRef.value) {
    dettaglioModalInstance = new Modal(dettaglioModalRef.value);
  }
  await medicoStore.markAsReadNotifications();
});

const openDettaglioModal = (proposta) => {
  selectedProposta.value = proposta;
  dettaglioModalInstance?.show();
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('it-IT', { day: '2-digit', month: 'long', year: 'numeric' });
}
</script>

<template>
  <div>
    <h1 class="display-5 fw-bold">Proposte Accettate</h1>
    <p class="lead text-muted">Qui trovi l'elenco dei pazienti che hanno accettato le tue proposte. Contattali per fissare una visita.</p>
    <hr class="my-4">

    <div v-if="isLoading" class="text-center p-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Caricamento...</span>
        </div>
    </div>

    <div v-else-if="proposteAccettate.length > 0" class="row g-4">
      <div v-for="proposta in proposteAccettate" :key="proposta.id" class="col-12">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-4">
                <h5 class="card-title mb-1">Paziente: {{ proposta.preventivo_paziente.anagrafica_paziente.user.name }}</h5>
                <small class="text-muted">Proposta accettata il: {{ formatDate(proposta.updated_at) }}</small>
                <div class="mt-2">
                    <p class="mb-0"><i class="fa-solid fa-envelope me-2"></i> {{ proposta.preventivo_paziente.anagrafica_paziente.user.email }}</p>
                    <p class="mb-0"><i class="fa-solid fa-phone me-2"></i> {{ proposta.preventivo_paziente.anagrafica_paziente.cellulare }}</p>
                </div>
              </div>
              <div class="col-md-4 text-center">
                <small class="d-block text-muted">Preventivo Originale</small>
                <span class="fs-4">€{{ proposta.preventivo_paziente.json_preventivo.totale_preventivo }}</span>
              </div>
              <div class="col-md-4 text-center">
                <small class="d-block text-muted">Tua Proposta Accettata</small>
                <span class="fs-4 fw-bold text-success">€{{ proposta.json_proposta.totale_proposta }}</span>
              </div>
            </div>
          </div>
          <div class="card-footer bg-light text-end">
            <button class="btn btn-primary btn-sm" @click="openDettaglioModal(proposta)">Vedi Confronto Dettagliato</button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center text-muted p-5 bg-light rounded">
      <h4>Nessuna proposta accettata per ora.</h4>
      <p>Quando un paziente accetterà una delle tue proposte, la troverai qui.</p>
    </div>

    <Teleport to="body">
      <div class="modal fade" id="dettaglioModal" ref="dettaglioModalRef" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" v-if="selectedProposta">
              <div class="modal-header">
                  <h5 class="modal-title">Confronto Proposte</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                  <div class="row">
                      <div class="col-md-6 border-end">
                          <h6 class="text-center">Preventivo Originale del Paziente</h6>
                          <ul class="list-group list-group-flush">
                              <li v-for="(voce, index) in selectedProposta.preventivo_paziente.json_preventivo.voci_preventivo" :key="index" class="list-group-item d-flex justify-content-between">
                                  <span>{{ voce.prestazione }}</span>
                                  <span class="fw-bold">€ {{ voce.prezzo }}</span>
                              </li>
                          </ul>
                          <hr>
                          <div class="text-end fw-bold fs-5">
                              Totale: € {{ selectedProposta.preventivo_paziente.json_preventivo.totale_preventivo }}
                          </div>
                      </div>
                      <div class="col-md-6">
                          <h6 class="text-center">La Tua Proposta Accettata</h6>
                          <ul class="list-group list-group-flush">
                              <li v-for="(voce, index) in selectedProposta.json_proposta.voci_proposta" :key="index" class="list-group-item d-flex justify-content-between">
                                  <span>{{ voce.prestazione_corrispondente }} <small class="d-block text-muted">(da: {{ voce.prestazione_originale }})</small></span>
                                  <span class="fw-bold text-success">€ {{ voce.prezzo }}</span>
                              </li>
                          </ul>
                          <hr>
                          <div class="text-end fw-bold fs-5 text-success">
                              Totale Proposta: € {{ selectedProposta.json_proposta.totale_proposta }}
                          </div>
                      </div>
                  </div>
              </div>
            </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>