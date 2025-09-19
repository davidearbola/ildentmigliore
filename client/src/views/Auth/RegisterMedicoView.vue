<script setup>
import { reactive, ref } from 'vue';
import { Form, Field, ErrorMessage } from 'vee-validate';
import * as yup from 'yup';
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';
import { useToast } from 'vue-toastification';
import { useRouter } from 'vue-router';
import AddressSelector from '@/components/AddressSelector.vue';

const authStore = useAuthStore();
const { isLoading } = storeToRefs(authStore);
const toast = useToast();
const router = useRouter();
const currentStep = ref(1);

const formData = reactive({
  name: '',
  email: '',
  cellulare: '',
  password: '',
  password_confirmation: '',
  ragione_sociale: '',
  p_iva: '',
  indirizzo: '',
  citta: '',
  cap: '',
  provincia: '',
});

const schemaStep1 = yup.object({
  name: yup.string().required('Il nome è obbligatorio'),
  email: yup.string().required('L\'email è obbligatoria').email('Email non valida'),
  cellulare: yup.string().required('Il cellulare è obbligatorio').matches(/^[0-9]+$/, "Deve contenere solo numeri").min(9, 'Numero non valido'),
  password: yup.string().required('La password è obbligatoria').min(8, 'La password deve contenere almeno 8 caratteri'),
  password_confirmation: yup.string().oneOf([yup.ref('password'), null], 'Le password non coincidono'),
});

const schemaStep2 = yup.object({
  ragione_sociale: yup.string().required('La ragione sociale è obbligatoria'),
  p_iva: yup.string().required('La Partita IVA è obbligatoria').length(11, 'La Partita IVA deve essere di 11 cifre'),
  indirizzo: yup.string().required('L\'indirizzo è obbligatorio'),
  citta: yup.string().required('La città è obbligatoria'),
  cap: yup.string().required('Il CAP è obbligatorio').length(5, 'Il CAP deve essere di 5 cifre'),
  provincia: yup.string().required('La provincia è obbligatoria').length(2, 'La sigla della provincia deve essere di 2 lettere'),
});

const nextStep = () => {
  if (currentStep.value < 3) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const handleRegister = async () => {
    const response = await authStore.registerMedico(formData);
    if (response.success) {
        toast.success(response.message || 'Registrazione completata!');
        router.push({ name: 'login' });
    } else {
        toast.error(response.message || 'Errore durante la registrazione.');
    }
};
</script>

<template>
  <div class="card border-0 shadow-sm w-100">
    <div class="card-body p-4 p-md-5">
      <div class="step-progress-bar mt-3 mb-5">
        <div class="step" :class="{ 'active': currentStep >= 1, 'completed': currentStep > 1 }">
          <div class="step-icon">1</div>
          <p class="d-none d-md-block">Dati Accesso</p>
        </div>
        <div class="step-connector" :class="{'completed': currentStep > 1}"></div>
        <div class="step" :class="{ 'active': currentStep >= 2, 'completed': currentStep > 2 }">
          <div class="step-icon">2</div>
          <p class="d-none d-md-block">Dati Studio</p>
        </div>
        <div class="step-connector" :class="{'completed': currentStep > 2}"></div>
        <div class="step" :class="{ 'active': currentStep === 3 }">
          <div class="step-icon">3</div>
          <p class="d-none d-md-block">Riepilogo</p>
        </div>
      </div>

      <Form v-if="currentStep === 1" @submit="nextStep" :validation-schema="schemaStep1" v-slot="{ errors }">
        <h3 class="text-center mb-4">Dati di Accesso</h3>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Nome e Cognome</label>
            <Field v-model="formData.name" name="name" type="text" class="form-control" :class="{'is-invalid': errors.name}" />
            <ErrorMessage name="name" class="text-danger small" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <Field v-model="formData.email" name="email" type="email" class="form-control" :class="{'is-invalid': errors.email}" />
            <ErrorMessage name="email" class="text-danger small" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Cellulare</label>
            <Field v-model="formData.cellulare" name="cellulare" type="tel" class="form-control" :class="{'is-invalid': errors.cellulare}" />
            <ErrorMessage name="cellulare" class="text-danger small" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <Field v-model="formData.password" name="password" type="password" class="form-control" :class="{'is-invalid': errors.password}" />
            <ErrorMessage name="password" class="text-danger small" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Conferma Password</label>
            <Field v-model="formData.password_confirmation" name="password_confirmation" type="password" class="form-control" :class="{'is-invalid': errors.password_confirmation}" />
            <ErrorMessage name="password_confirmation" class="text-danger small" />
          </div>
        </div>
        <div class="mt-4 text-end">
          <button type="submit" class="btn btn-primary">Avanti</button>
        </div>
      </Form>

      <Form v-if="currentStep === 2" @submit="nextStep" :initial-values="formData" :validation-schema="schemaStep2" v-slot="{ errors }">
        <h3 class="text-center mb-4">Dati dello Studio Medico</h3>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Ragione Sociale</label>
                <Field v-model="formData.ragione_sociale" name="ragione_sociale" type="text" class="form-control" :class="{'is-invalid': errors.ragione_sociale}" />
                <ErrorMessage name="ragione_sociale" class="text-danger small" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Partita IVA</label>
                <Field v-model="formData.p_iva" name="p_iva" type="text" class="form-control" :class="{'is-invalid': errors.p_iva}" />
                <ErrorMessage name="p_iva" class="text-danger small" />
            </div>
            <div class="col-12">
                <label class="form-label">Indirizzo</label>
                <Field v-model="formData.indirizzo" name="indirizzo" type="text" class="form-control" :class="{'is-invalid': errors.indirizzo}" />
                <ErrorMessage name="indirizzo" class="text-danger small" />
            </div>
        </div>
        
        <AddressSelector
            @update:province="formData.provincia = $event"
            @update:city="formData.citta = $event"
            @update:cap="formData.cap = $event"
        />
        
        <Field name="provincia" v-model="formData.provincia" class="d-none" />
        <Field name="citta" v-model="formData.citta" class="d-none" />
        <ErrorMessage name="provincia" class="text-danger small d-block" />
        <ErrorMessage name="citta" class="text-danger small d-block" />

        <div class="row mt-3">
            <div class="col-md-6">
                <label class="form-label">CAP</label>
                <Field name="cap" v-model="formData.cap" type="text" class="form-control" :class="{'is-invalid': errors.cap}" />
                <ErrorMessage name="cap" class="text-danger small" />
            </div>
        </div>
        
        <div class="mt-4 d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" @click="prevStep">Indietro</button>
          <button type="submit" class="btn btn-primary">Avanti</button>
        </div>
      </Form>

      <div v-if="currentStep === 3">
        <h3 class="text-center mb-4">Riepilogo Dati</h3>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Nome:</strong> {{ formData.name }}</li>
            <li class="list-group-item"><strong>Email:</strong> {{ formData.email }}</li>
            <li class="list-group-item"><strong>Cellulare:</strong> {{ formData.cellulare }}</li>
            <li class="list-group-item"><strong>Ragione Sociale:</strong> {{ formData.ragione_sociale }}</li>
            <li class="list-group-item"><strong>P.IVA:</strong> {{ formData.p_iva }}</li>
            <li class="list-group-item"><strong>Indirizzo:</strong> {{ formData.indirizzo }}, {{ formData.cap }} {{ formData.citta }} ({{ formData.provincia }})</li>
        </ul>
        <div class="mt-4 d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" @click="prevStep">Indietro</button>
          <button type="button" class="btn btn-accent" @click="handleRegister" :disabled="isLoading">
            <span v-if="isLoading" class="spinner-border spinner-border-sm"></span>
            <span v-else>Conferma e Registrati</span>
          </button>
        </div>
      </div>

       <div class="text-center mt-4">
            <p class="text-muted small">Sei un paziente? <RouterLink to="/register">Registrati qui</RouterLink></p>
            <p class="text-muted small">Hai già un account? <RouterLink to="/login">Accedi</RouterLink></p>
        </div>
    </div>
  </div>
</template>

<style scoped>
/* Stili invariati... */
.step-progress-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}
.step {
  text-align: center;
  flex-shrink: 0;
}
.step-icon {
  height: 30px;
  width: 30px;
  border-radius: 50%;
  background-color: #e9ecef;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  border: 2px solid #e9ecef;
  transition: all 0.3s ease;
  margin: 0 auto 0.5rem auto;
}
.step.active .step-icon {
  border-color: var(--bs-primary);
  color: var(--bs-primary);
}
.step.completed .step-icon {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
  color: white;
}
.step p {
    font-size: 0.8rem;
    color: #6c757d;
}
.step.active p, .step.completed p {
    color: var(--bs-primary);
}
.step-connector {
  flex-grow: 1;
  height: 2px;
  background-color: #e9ecef;
  margin-top: 15px;
  transition: all 0.3s ease;
}
.step-connector.completed {
    background-color: var(--bs-primary);
}
</style>