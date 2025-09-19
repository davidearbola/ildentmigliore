<script setup>
import { Form, Field, ErrorMessage } from 'vee-validate';
import * as yup from 'yup';
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'

const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();
const { isLoading } = storeToRefs(authStore);

const schema = yup.object({
  email: yup.string().required("L'email è obbligatoria").email("Inserisci un'email valida"),
});

const handleResend = async (values, { resetForm}) => {
  const response = await authStore.publicResendVerificationEmail(values.email);
  if (!response.success) {
    toast.error(response.message);
    resetForm();
    return;
  }
  toast.success(response.message);
  router.push({ name: 'login' });
};
</script>

<template>
  <div class="card border-0">
    <div class="card-body p-4">
      <h1 class="card-title text-center mb-4">Invia di Nuovo l'Email</h1>
      <p class="text-muted text-center mb-4">Non hai ricevuto l'email? Inserisci il tuo indirizzo qui sotto per riceverne una nuova.</p>
      <Form @submit="handleResend" :validation-schema="schema" v-slot="{ errors }">
        <div class="mb-3">
          <label for="email" class="form-label">Il Tuo Indirizzo Email</label>
          <Field name="email" type="email" class="form-control" :class="{'is-invalid': errors.email}" id="email" required :disabled="isLoading" />
          <ErrorMessage name="email" class="text-danger small" />
        </div>
        <button type="submit" class="btn btn-primary w-100" :disabled="isLoading">
          <span v-if="isLoading" class="spinner-border spinner-border-sm"></span>
          <span v-else>Invia di Nuovo il Link</span>
        </button>
         <div class="text-center mt-3">
          <RouterLink to="/login">Torna al Login</RouterLink>
        </div>
      </Form>
    </div>
  </div>
</template>