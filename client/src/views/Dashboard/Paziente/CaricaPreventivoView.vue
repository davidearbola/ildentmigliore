<script setup>
import { ref, computed, onUnmounted, watch } from 'vue';
import { Form, Field, ErrorMessage } from 'vee-validate';
import * as yup from 'yup';
import { useToast } from 'vue-toastification';
import { useRouter } from 'vue-router'; 
import { useAuthStore } from '@/stores/authStore';
import { usePazienteStore } from '@/stores/pazienteStore';
import { storeToRefs } from 'pinia';
import AddressSelector from '@/components/AddressSelector.vue';

// --- STORES E ROUTER ---
const authStore = useAuthStore();
const pazienteStore = usePazienteStore();
const { user } = storeToRefs(authStore);
const { isLoading, processoPreventivo } = storeToRefs(pazienteStore);
const toast = useToast();
const router = useRouter();

// --- STATO DEL COMPONENTE ---
const preventivoFile = ref(null);
const isDragging = ref(false);
const formRef = ref(null);
const pollingInterval = ref(null);

// Resetta lo stato dello store quando il componente viene montato, per assicurare una view pulita
pazienteStore.resetProcessoPreventivo();

const showAnagraficaForm = computed(() => !user.value?.anagrafica_paziente);

const schema = computed(() => {
  let baseSchema = {
    preventivoFile: yup.mixed().required('È necessario caricare un file.')
      .test('fileSize', 'Il file è troppo grande (max 10MB)', value => !value || (value && value.size <= 10 * 1024 * 1024))
      .test('fileType', 'Formato non supportato (accettati: PDF, JPG, PNG)', value => !value || (value && ['application/pdf', 'image/jpeg', 'image/png'].includes(value.type))),
  };

  if (showAnagraficaForm.value) {
    baseSchema = {
      ...baseSchema,
      cellulare: yup.string().required('Il cellulare è obbligatorio').min(9, 'Numero non valido'),
      indirizzo: yup.string().required("L'indirizzo è obbligatorio"),
      citta: yup.string().required('La città è obbligatoria'),
      cap: yup.string().required('Il CAP è obbligatorio').length(5, 'Il CAP deve essere di 5 cifre'),
      provincia: yup.string().required('La provincia è obbligatoria').length(2, 'La sigla deve essere di 2 lettere'),
    };
  }
  return yup.object(baseSchema);
});

// --- GESTIONE FILE ---
const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    preventivoFile.value = file;
    formRef.value.setFieldValue('preventivoFile', file);
  }
};
const handleDrop = (event) => {
  isDragging.value = false;
  const file = event.dataTransfer.files[0];
  if (file) {
    preventivoFile.value = file;
    formRef.value.setFieldValue('preventivoFile', file);
  }
};
const removeFile = () => {
  preventivoFile.value = null;
  formRef.value.setFieldValue('preventivoFile', null);
};

// --- LOGICA DI PROCESSO ---

// 1. UPLOAD
const handleUpload = async (values) => {
  const dataToUpload = { ...values, preventivoFile: preventivoFile.value }; // Assicura che il file sia preso da ref
  const response = await pazienteStore.uploadQuote(dataToUpload);

  if (response.success) {
    toast.info('Il tuo preventivo è in fase di analisi. Attendi...');
    startPolling(pazienteStore.controllaStatoPreventivo);
  } else {
    toast.error(response.message);
  }
};


// 2. POLLING
const startPolling = (action) => {
  stopPolling();
  action(); // Esegui subito
  pollingInterval.value = setInterval(action, 5000); // E poi ogni 5 secondi
};

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value);
    pollingInterval.value = null;
  }
};

watch(
  () => processoPreventivo.value.status,
  (newStatus) => {
    if (newStatus === 'ready_for_confirmation') {
      toast.success('Abbiamo analizzato il tuo preventivo! Controlla le voci qui sotto.');
      stopPolling();
    }
    // Aggiunto questo blocco per gestire il redirect
    if (newStatus === 'proposte_pronte') {
        toast.success('Proposte trovate! Ti stiamo reindirizzando...');
        stopPolling();
        router.push({ name: 'dashboard-proposte' });
    }
    if (newStatus === 'error') {
      toast.error(processoPreventivo.value.errorMessage || 'Errore sconosciuto');
      stopPolling();
    }
  }
);

// 3. CONFERMA
// *** MODIFICA: handleConfirm avvia il secondo polling ***
const handleConfirm = async () => {
    const response = await pazienteStore.confermaVociPreventivo(processoPreventivo.value.voci);
    if(response.success) {
        toast.info('Perfetto! Stiamo cercando le migliori proposte per te.');
        // Rimosso setTimeout e avviato il secondo polling
        startPolling(pazienteStore.controllaStatoProposte); 
    } else {
        toast.error(response.message);
    }
};

// --- FUNZIONI UTILITY PER LA TABELLA ---
const addVoce = () => {
    processoPreventivo.value.voci.push({ prestazione: '', quantità: 1, prezzo: 0 });
};

const removeVoce = (index) => {
    processoPreventivo.value.voci.splice(index, 1);
};

const formatCurrency = (value) => {
    if (isNaN(parseFloat(value))) return '0,00';
    return parseFloat(value).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const totalPreventivo = computed(() => {
    return processoPreventivo.value.voci.reduce((acc, voce) => acc + parseFloat(voce.prezzo || 0), 0);
});

// --- CICLO DI VITA ---
onUnmounted(() => {
  stopPolling();
});

</script>

<template>
  <div>
    <h1 class="display-5 fw-bold">Carica il Tuo Preventivo</h1>
    <p class="lead text-muted">Trascina o seleziona un file (PDF, JPG, PNG) per ricevere le migliori proposte dai nostri studi medici.</p>
    <hr class="my-4" />

    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">

        <div v-if="processoPreventivo.status === 'idle' || processoPreventivo.status === 'uploading' || processoPreventivo.status === 'error'">
          <div v-if="processoPreventivo.status === 'error'" class="alert alert-danger d-flex justify-content-between align-items-center">
            <div>
              <p class="fw-bold mb-0">Si è verificato un errore</p>
              <small>{{ processoPreventivo.errorMessage }}</small>
            </div>
             <button @click="pazienteStore.resetProcessoPreventivo()" class="btn btn-sm btn-danger">Riprova</button>
          </div>
          
          <Form @submit="handleUpload" :validation-schema="schema" ref="formRef" v-slot="{ errors, setFieldValue, values }">
            
            <div class="upload-area text-center p-5 rounded-3 border-2" :class="{ 'dragging': isDragging, 'has-file': preventivoFile }" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop">
              <div v-if="!preventivoFile">
                <i class="fa-solid fa-cloud-arrow-up fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold">Trascina il tuo file qui</h5>
                <p class="text-muted">o</p>
                <label for="file-input" class="btn btn-primary">Seleziona File</label>
                <input type="file" id="file-input" @change="handleFileChange" accept=".pdf,.jpg,.jpeg,.png" class="d-none" />
              </div>
              <div v-else class="file-preview">
                <i class="fa-solid fa-file-invoice fa-3x text-accent mb-3"></i>
                <p class="fw-bold mb-1">{{ preventivoFile.name }}</p>
                <p class="text-muted small">{{ (preventivoFile.size / 1024).toFixed(2) }} KB</p>
                <button type="button" @click="removeFile" class="btn btn-sm btn-danger mt-2">Rimuovi</button>
              </div>
            </div>
            <ErrorMessage name="preventivoFile" class="text-danger small mt-2 d-block" />

            <div v-if="showAnagraficaForm" class="mt-4">
              <h4 class="mb-3">Completa i tuoi dati</h4>
              <p class="text-muted small">Queste informazioni sono necessarie solo per il primo caricamento e ci aiuteranno a trovare gli studi più vicini a te.</p>
              <div class="row g-3 mt-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Cellulare</label>
                  <Field name="cellulare" type="tel" class="form-control" :class="{'is-invalid': errors.cellulare}" />
                  <ErrorMessage name="cellulare" class="text-danger small" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Indirizzo (es. Via Roma, 1)</label>
                  <Field name="indirizzo" type="text" class="form-control" :class="{'is-invalid': errors.indirizzo}" />
                  <ErrorMessage name="indirizzo" class="text-danger small" />
                </div>
              </div>
              
              <AddressSelector 
                  @update:province="setFieldValue('provincia', $event)"
                  @update:city="setFieldValue('citta', $event)"
                  @update:cap="setFieldValue('cap', $event)"
              />
              
              <Field name="provincia" :model-value="values.provincia" class="d-none" />
              <Field name="citta" :model-value="values.citta" class="d-none" />
              <ErrorMessage name="provincia" class="text-danger small d-block" />
              <ErrorMessage name="citta" class="text-danger small d-block" />

              <div class="row mt-3">
                  <div class="col-md-6">
                      <label class="form-label">CAP</label>
                      <Field name="cap" :model-value="values.cap" type="text" class="form-control" :class="{'is-invalid': errors.cap}" @input="setFieldValue('cap', $event.target.value)" />
                      <ErrorMessage name="cap" class="text-danger small" />
                  </div>
              </div>
            </div>
            
            <div class="text-end mt-4">
              <button type="submit" class="btn btn-accent btn-lg" :disabled="isLoading || !preventivoFile">
                <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                {{ isLoading ? 'Caricamento...' : 'Carica Preventivo' }}
              </button>
            </div>
          </Form>
        </div>

        <div v-else-if="processoPreventivo.status === 'processing'" class="text-center p-5">
            <div class="spinner-border text-accent" role="status" style="width: 3rem; height: 3rem;"></div>
            <h4 class="mt-4">Stiamo elaborando il tuo preventivo...</h4>
            <p class="text-muted">Questa operazione potrebbe richiedere fino a un minuto.</p>
        </div>

        <div v-else-if="processoPreventivo.status === 'ready_for_confirmation'">
            <h3 class="mb-3">Verifica le voci del preventivo</h3>
            <p class="text-muted">Abbiamo estratto queste informazioni dal file che hai caricato. Controllale e, se necessario, modificane i valori prima di continuare.</p>
            
            <div class="table-responsive">
              <table class="table table-striped table-hover align-middle">
                <thead>
                  <tr>
                    <th scope="col">Prestazione</th>
                    <th scope="col" style="width: 120px;">Quantità</th>
                    <th scope="col" style="width: 150px;">Prezzo (€)</th>
                    <th scope="col" style="width: 80px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(voce, index) in processoPreventivo.voci" :key="index">
                    <td>
                      <input type="text" class="form-control" v-model="voce.prestazione" placeholder="Nome prestazione">
                    </td>
                    <td>
                      <input type="number" class="form-control" v-model.number="voce.quantità" min="1">
                    </td>
                    <td>
                      <input type="number" class="form-control" v-model.number="voce.prezzo" step="0.01" placeholder="0.00">
                    </td>
                    <td class="text-center">
                      <button @click="removeVoce(index)" class="btn btn-sm btn-outline-danger" title="Rimuovi riga">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <button @click="addVoce" class="btn btn-sm btn-outline-secondary">
              <i class="fa-solid fa-plus me-1"></i> Aggiungi riga
            </button>

            <div class="text-end mt-4 fs-5 fw-bold">
                Totale Stimato: € {{ formatCurrency(totalPreventivo) }}
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center">
                <button @click="pazienteStore.resetProcessoPreventivo()" class="btn btn-secondary">Annulla e ricarica</button>
                <button @click="handleConfirm" class="btn btn-accent btn-lg" :disabled="isLoading">
                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                    Conferma e Cerca Proposte
                </button>
            </div>
        </div>

        <div v-else-if="processoPreventivo.status === 'confirming' || processoPreventivo.status === 'generating'" class="text-center p-5">
            <div class="spinner-border text-accent" role="status" style="width: 3rem; height: 3rem;"></div>
            <h4 class="mt-4">Fantastico!</h4>
            <p class="text-muted">Stiamo creando le proposte migliori per te in base al preventivo che hai confermato.</p>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.upload-area {
  border: 2px dashed #dee2e6;
  transition: all 0.3s ease;
  background-color: #f8f9fa;
}
.upload-area.dragging {
  border-color: var(--bs-primary);
  background-color: rgba(var(--bs-primary-rgb), 0.1);
}
.upload-area.has-file {
    border-color: var(--bs-accent);
    background-color: rgba(var(--bs-accent-rgb), 0.1);
}
</style>