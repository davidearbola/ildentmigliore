<script setup>
import { Form, Field, ErrorMessage } from 'vee-validate';
import * as yup from 'yup';
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';
import { useToast } from 'vue-toastification';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const { isLoading } = storeToRefs(authStore);
const toast = useToast();
const router = useRouter();

const schema = yup.object({
  name: yup.string().required('Il nome è obbligatorio'),
  email: yup.string().required('L\'email è obbligatoria').email('Inserisci un\'email valida'),
  password: yup.string().required('La password è obbligatoria').min(8, 'La password deve contenere almeno 8 caratteri'),
  password_confirmation: yup.string()
    .required('La conferma della password è obbligatoria')
    .oneOf([yup.ref('password')], 'Le password non corrispondono'),
});

const handleRegister = async (values, { resetForm }) => {
    const response = await authStore.register(values);
    if (response.success) {
    toast.success(response.message);
    router.push({ name: 'login' });
  } else {
    toast.error(response.message);
    resetForm({
      values: {
        ...values,
        password: '',
        password_confirmation: '',
      },
    });
  }
};
</script>

<template>
  <div class="card border-0">
    <div class="card-body p-4">
      <h1 class="card-title text-center mb-4">Crea un Account</h1>
      <Form @submit="handleRegister" :validation-schema="schema" v-slot="{ errors }">
        <div class="mb-3">
          <label for="name" class="form-label">Nome</label>
          <Field name="name" type="text" class="form-control" :class="{'is-invalid': errors.name}" id="name" required :disabled="isLoading" />
          <ErrorMessage name="name" class="text-danger small" />
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Indirizzo Email</label>
          <Field name="email" type="email" class="form-control" :class="{'is-invalid': errors.email}" id="email" required :disabled="isLoading" />
          <ErrorMessage name="email" class="text-danger small" />
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <Field name="password" type="password" class="form-control" :class="{'is-invalid': errors.password}" id="password" required :disabled="isLoading" />
          <ErrorMessage name="password" class="text-danger small" />
        </div>
        <div class="mb-3">
          <label for="password_confirmation" class="form-label">Conferma Password</label>
          <Field name="password_confirmation" type="password" class="form-control" :class="{'is-invalid': errors.password_confirmation}" id="password_confirmation" required :disabled="isLoading" />
          <ErrorMessage name="password_confirmation" class="text-danger small" />
        </div>
        <button type="submit" class="btn btn-primary w-100" :disabled="isLoading">
          <span v-if="isLoading" class="spinner-border spinner-border-sm"></span>
          <span v-else>Registrati</span>
        </button>
        <div class="text-center mt-3">
            <p class="text-muted">Hai già un account? <RouterLink to="/login">Accedi</RouterLink></p>
        </div>
      </Form>
    </div>
  </div>
</template>