<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { Form, Field, ErrorMessage } from 'vee-validate';
import * as yup from 'yup';
import { useToast } from 'vue-toastification';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { usePazienteStore } from '@/stores/pazienteStore';
import { useMedicoStore } from '@/stores/medicoStore';
import { storeToRefs } from 'pinia';

// Stores
const authStore = useAuthStore();
const pazienteStore = usePazienteStore();
const medicoStore = useMedicoStore();
const { user } = storeToRefs(authStore);

// Utility
const toast = useToast();
const router = useRouter();

// State per i form
const anagraficaPazienteData = reactive({});
const anagraficaMedicoData = reactive({});
const emailData = reactive({ email: '' });
const passwordData = reactive({ current_password: '', password: '', password_confirmation: '' });

const isSocialUser = computed(() => !!user.value?.auth_provider);

// Popola i dati iniziali
onMounted(() => {
  if (user.value?.role === 'paziente' && user.value.anagrafica_paziente) {
    Object.assign(anagraficaPazienteData, user.value.anagrafica_paziente);
  }
  if (user.value?.role === 'medico' && user.value.anagrafica_medico) {
    Object.assign(anagraficaMedicoData, user.value.anagrafica_medico);
  }
  emailData.email = user.value?.email || '';
});

// Schemi di validazione
const anagraficaPazienteSchema = yup.object({
    cellulare: yup.string().required('Obbligatorio'),
    indirizzo: yup.string().required('Obbligatorio'),
    citta: yup.string().required('Obbligatorio'),
    cap: yup.string().required('Obbligatorio').length(5, 'Deve essere di 5 cifre'),
    provincia: yup.string().required('Obbligatorio').length(2, 'Deve essere di 2 lettere'),
});

// Schema per il medico
const anagraficaMedicoSchema = yup.object({
    cellulare: yup.string().required('Obbligatorio'),
    ragione_sociale: yup.string().required('Obbligatorio'),
    p_iva: yup.string().required('Obbligatorio').length(11, 'Deve essere di 11 cifre'),
    indirizzo: yup.string().required('Obbligatorio'),
    citta: yup.string().required('Obbligatorio'),
    cap: yup.string().required('Obbligatorio').length(5, 'Deve essere di 5 cifre'),
    provincia: yup.string().required('Obbligatorio').length(2, 'Deve essere di 2 lettere'),
});

const emailSchema = yup.object({ email: yup.string().required('Obbligatorio').email() });
const passwordSchema = yup.object({
    current_password: yup.string().required('Obbligatorio'),
    password: yup.string().required('Obbligatorio').min(8),
    password_confirmation: yup.string().oneOf([yup.ref('password'), null], 'Le password non coincidono'),
});

// Funzioni di Submit
const handleUpdateAnagrafica = async () => {
    const store = user.value.role === 'paziente' ? pazienteStore : medicoStore;
    const data = user.value.role === 'paziente' ? anagraficaPazienteData : anagraficaMedicoData;
    const { success, message } = await store.updateAnagrafica(data);
    if (success) toast.success(message); else toast.error(message);
};

const handleUpdateEmail = async () => {
    const { success, message } = await authStore.updateEmail(emailData.email);
    if (success) {
        toast.success(message);
        router.push({ name: 'login' });
    } else {
        toast.error(message);
    }
};

const handleUpdatePassword = async () => {
    const { success, message } = await authStore.updatePassword(passwordData);
    if (success) {
        toast.success(message);
        router.push({ name: 'login' });
    } else {
        toast.error(message);
    }
};
</script>

<template>
  <div>
    <h1 class="display-5 fw-bold">Impostazioni Account</h1>
    <p class="lead text-muted">Gestisci i tuoi dati personali, le informazioni del tuo studio e la sicurezza del tuo account.</p>
    <hr class="my-4">

    <div v-if="user?.role === 'paziente' && anagraficaPazienteData" class="card shadow-sm mb-4">
      <div class="card-header"><h5 class="mb-0">Dati Personali</h5></div>
      <div class="card-body">
        <Form @submit="handleUpdateAnagrafica" :initial-values="anagraficaPazienteData" :validation-schema="anagraficaPazienteSchema" v-slot="{ isSubmitting }">
            <div class="row g-3">
              <div class="col-md-6"><label>Cellulare</label><Field name="cellulare" v-model="anagraficaPazienteData.cellulare" class="form-control" /> <ErrorMessage name="cellulare" class="text-danger small" /></div>
              <div class="col-md-6"><label>Indirizzo</label><Field name="indirizzo" v-model="anagraficaPazienteData.indirizzo" class="form-control" /> <ErrorMessage name="indirizzo" class="text-danger small" /></div>
              <div class="col-md-4"><label>Provincia</label><Field name="provincia" v-model="anagraficaPazienteData.provincia" class="form-control" /> <ErrorMessage name="provincia" class="text-danger small" /></div>
              <div class="col-md-5"><label>Città</label><Field name="citta" v-model="anagraficaPazienteData.citta" class="form-control" /> <ErrorMessage name="citta" class="text-danger small" /></div>
              <div class="col-md-3"><label>CAP</label><Field name="cap" v-model="anagraficaPazienteData.cap" class="form-control" /> <ErrorMessage name="cap" class="text-danger small" /></div>
            </div>
          <div class="text-end mt-3">
            <button class="btn btn-primary" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                     Aggiorna Dati
            </button>
          </div>
        </Form>
      </div>
    </div>

    <div v-if="user?.role === 'medico' && anagraficaMedicoData" class="card shadow-sm mb-4">
       <div class="card-header"><h5 class="mb-0">Dati Studio Medico</h5></div>
       <div class="card-body">
           <Form @submit="handleUpdateAnagrafica" :initial-values="anagraficaMedicoData" :validation-schema="anagraficaMedicoSchema" v-slot="{ isSubmitting }">
               <div class="row g-3">
                 <div class="col-md-6"><label>Ragione Sociale</label><Field name="ragione_sociale" v-model="anagraficaMedicoData.ragione_sociale" class="form-control" /> <ErrorMessage name="ragione_sociale" class="text-danger small" /></div>
                 <div class="col-md-6"><label>Partita IVA</label><Field name="p_iva" v-model="anagraficaMedicoData.p_iva" class="form-control" /> <ErrorMessage name="p_iva" class="text-danger small" /></div>
                 <div class="col-6"><label>Indirizzo Sede</label><Field name="indirizzo" v-model="anagraficaMedicoData.indirizzo" class="form-control" /> <ErrorMessage name="indirizzo" class="text-danger small" /></div>
                <div class="col-md-6"><label>Cellulare</label><Field name="cellulare" v-model="anagraficaMedicoData.cellulare" class="form-control" /> <ErrorMessage name="cellulare" class="text-danger small" /></div>
                <div class="col-md-4"><label>Provincia</label><Field name="provincia" v-model="anagraficaMedicoData.provincia" class="form-control" /> <ErrorMessage name="provincia" class="text-danger small" /></div>
                 <div class="col-md-5"><label>Città</label><Field name="citta" v-model="anagraficaMedicoData.citta" class="form-control" /> <ErrorMessage name="citta" class="text-danger small" /></div>
                 <div class="col-md-3"><label>CAP</label><Field name="cap" v-model="anagraficaMedicoData.cap" class="form-control" /> <ErrorMessage name="cap" class="text-danger small" /></div>
               </div>
                <div class="text-end mt-3">
                   <button class="btn btn-primary" :disabled="isSubmitting">
                     <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                     Aggiorna Dati Studio
                    </button>
                </div>
           </Form>
       </div>
    </div>

    <div class="row" v-if="!isSocialUser">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="card shadow-sm h-100">
          <div class="card-header"><h5 class="mb-0">Aggiorna Email</h5></div>
          <div class="card-body d-flex flex-column">
            <Form @submit="handleUpdateEmail" :initial-values="emailData" :validation-schema="emailSchema" v-slot="{ isSubmitting }" class="d-flex flex-column flex-grow-1">
              <div class="flex-grow-1">
                <label>Indirizzo Email</label>
                <Field name="email" type="email" v-model="emailData.email" class="form-control" />
                <ErrorMessage name="email" class="text-danger small" />
              </div>
              <div class="text-end mt-3">
                <button class="btn btn-primary" :disabled="isSubmitting">
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                  Aggiorna Email
                </button>
              </div>
            </Form>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header"><h5 class="mb-0">Cambia Password</h5></div>
          <div class="card-body">
            <Form @submit="handleUpdatePassword" :validation-schema="passwordSchema" v-slot="{ isSubmitting }">
                <div class="mb-3">
                    <label>Password Attuale</label>
                    <Field name="current_password" type="password" v-model="passwordData.current_password" class="form-control" />
                    <ErrorMessage name="current_password" class="text-danger small" />
                </div>
                <div class="mb-3">
                    <label>Nuova Password</label>
                    <Field name="password" type="password" v-model="passwordData.password" class="form-control" />
                    <ErrorMessage name="password" class="text-danger small" />
                </div>
                <div class="mb-3">
                    <label>Conferma Nuova Password</label>
                    <Field name="password_confirmation" type="password" v-model="passwordData.password_confirmation" class="form-control" />
                    <ErrorMessage name="password_confirmation" class="text-danger small" />
                </div>
              <div class="text-end mt-3">
                <button class="btn btn-primary" :disabled="isSubmitting">
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                  Cambia Password
                </button>
              </div>
            </Form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>