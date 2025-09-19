<script setup>
import { onMounted, ref } from 'vue';
import { usePazienteStore } from '@/stores/pazienteStore';
import { storeToRefs } from 'pinia';
import { useToast } from 'vue-toastification';
import { Modal } from 'bootstrap';
import { useRouter } from 'vue-router';

// --- STORES E UTILITY ---
const pazienteStore = usePazienteStore();
const { proposteNuove, proposteArchiviate, isLoading } = storeToRefs(pazienteStore);
const toast = useToast();
const router = useRouter();

// --- STATO LOCALE PER IL MODALE ---
const selectedProposta = ref(null);
const dettaglioModalRef = ref(null);
let dettaglioModalInstance = null;

// --- LOGICA DEL COMPONENTE ---
onMounted(async () => {
  // Inizializza il modale
  if (dettaglioModalRef.value) {
    dettaglioModalInstance = new Modal(dettaglioModalRef.value);
  }

  // Carica le proposte
  await pazienteStore.fetchProposte();
  
  // Se ci sono proposte nuove, le segna come lette
  if (proposteNuove.value.length > 0) {
    await pazienteStore.markProposteComeLette();
  }
});

const openDettaglioModal = (proposta) => {
  selectedProposta.value = proposta;
  dettaglioModalInstance?.show();
};

const handleAccetta = async (propostaId) => {
    if (window.confirm('Sei sicuro di voler accettare questa proposta? L\'azione è irreversibile e lo studio dentistico verrà notificato.')) {
        const { success, message } = await pazienteStore.accettaProposta(propostaId);
        if (success) toast.success(message); else toast.error(message);
    }
}

const handleRifiuta = async (propostaId) => {
    if (window.confirm('Sei sicuro di voler rifiutare questa proposta? L\'azione è irreversibile.')) {
        const { success, message } = await pazienteStore.rifiutaProposta(propostaId);
        if (success) toast.success(message); else toast.error(message);
    }
}

const goToProfiloMedico = (medicoId) => {
    router.push({ name: 'medico-profilo-pubblico', params: { id: medicoId } });
}


// Funzione per formattare la data
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('it-IT', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}
</script>

<template>
  <div>
    <h1 class="display-5 fw-bold">Le Tue Proposte</h1>
    <p class="lead text-muted">Qui trovi tutte le controproposte che hai ricevuto dai nostri studi medici.</p>
    <hr class="my-4">

    <div v-if="proposteNuove.length > 0" class="mb-5">
      <h3 class="mb-3">Nuove Proposte Ricevute</h3>
      <div class="row g-4">
        <div v-for="proposta in proposteNuove" :key="proposta.id" class="col-lg-6">
          <div class="card shadow-sm proposta-card new">
            <div class="card-body">
              <h5 class="card-title">{{ proposta.medico.anagrafica_medico.ragione_sociale }}</h5>
              <p class="card-subtitle mb-2 text-muted">
                Ricevuta il: {{ formatDate(proposta.created_at) }}
              </p>
              <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">Tuo preventivo: €{{ proposta.preventivo_paziente.json_preventivo.totale_preventivo }}</small>
                <h4 class="fw-bold text-accent mb-0">Totale proposta: €{{ proposta.json_proposta.totale_proposta }}</h4>
              </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-end gap-2">
              <button class="btn btn-sm btn-outline-secondary me-2" @click="goToProfiloMedico(proposta.medico.id)">Profilo Studio</button>
              <button class="btn btn-sm btn-primary me-2" @click="openDettaglioModal(proposta)">Vedi Dettaglio</button>
              <button v-if="proposta.stato !== 'accettata' && proposta.stato !== 'rifiutata'" class="btn btn-sm btn-success me-2" @click="handleAccetta(proposta.id)">Accetta</button>
              <button v-if="proposta.stato !== 'accettata' && proposta.stato !== 'rifiutata'" class="btn btn-sm btn-danger" @click="handleRifiuta(proposta.id)">Rifiuta</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div>
      <h3 class="mb-3">Archivio Proposte</h3>
      <div v-if="proposteArchiviate.length > 0" class="row g-4">
        <div v-for="proposta in proposteArchiviate" :key="proposta.id" class="col-lg-6">
          <div class="card shadow-sm proposta-card" :class="proposta.stato">
             <div class="card-body">
              <h5 class="card-title">{{ proposta.medico.anagrafica_medico.ragione_sociale }}</h5>
              <p class="card-subtitle mb-2 text-muted">
                Data: {{ formatDate(proposta.created_at) }}
              </p>
              <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">Tuo preventivo: €{{ proposta.preventivo_paziente.json_preventivo.totale_preventivo }}</small>
                <h4 class="fw-bold mb-0">Totale proposta: €{{ proposta.json_proposta.totale_proposta }}</h4>
              </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <span class="badge" :class="{ 'bg-success': proposta.stato === 'accettata', 'bg-danger': proposta.stato === 'rifiutata', 'bg-secondary': proposta.stato === 'visualizzata' }">
                    {{ proposta.stato }}
                </span>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2" @click="goToProfiloMedico(proposta.medico.id)">Profilo Studio</button>
                    <button class="btn btn-sm btn-primary me-2" @click="openDettaglioModal(proposta)">Vedi Dettaglio</button>
                    <button v-if="proposta.stato !== 'accettata' && proposta.stato !== 'rifiutata'" class="btn btn-sm btn-success me-2" @click="handleAccetta(proposta.id)">Accetta</button>
                    <button v-if="proposta.stato !== 'accettata' && proposta.stato !== 'rifiutata'" class="btn btn-sm btn-danger" @click="handleRifiuta(proposta.id)">Rifiuta</button>
                </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-center text-muted p-5">
        <p>Non hai ancora nessuna proposta nel tuo archivio.</p>
      </div>
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
                        <h6 class="text-center">Il Tuo Preventivo Originale</h6>
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
                        <h6 class="text-center">Proposta di {{ selectedProposta.medico.anagrafica_medico.ragione_sociale }}</h6>
                         <ul class="list-group list-group-flush">
                            <li v-for="(voce, index) in selectedProposta.json_proposta.voci_proposta" :key="index" class="list-group-item d-flex justify-content-between">
                                <span>{{ voce.prestazione_corrispondente }} <small class="d-block text-muted"> (da: {{ voce.prestazione_originale }})</small></span>
                                <span class="fw-bold text-accent">€ {{ voce.prezzo }}</span>
                            </li>
                        </ul>
                         <hr>
                        <div class="text-end fw-bold fs-5 text-accent">
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

<style scoped>
.proposta-card.new {
    border-left: 4px solid var(--bs-primary);
}
.proposta-card.accettata {
    border-left: 4px solid var(--bs-success);
}
.proposta-card.rifiutata {
    border-left: 4px solid var(--bs-danger);
}
.proposta-card.visualizzata {
    border-left: 4px solid var(--bs-secondary);
}
</style>