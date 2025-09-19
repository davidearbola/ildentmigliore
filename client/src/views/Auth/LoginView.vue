<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import * as yup from 'yup'
import { Form, Field, ErrorMessage } from 'vee-validate'

const authStore = useAuthStore()
const { isLoading } = storeToRefs(authStore)
const toast = useToast()
const router = useRouter()
const isNotVerified = ref(false)
const schema = yup.object({
  email: yup.string().required("L'email è obbligatoria").email("Inserisci un'email valida"),
  password: yup.string().required('La password è obbligatoria'),
  remember: yup.boolean(),
})

const handleLogin = async (values, { resetForm }) => {
  isNotVerified.value = false
  // values.remember = true;
  const response = await authStore.login(values)
  if (response.success) {
    toast.success(response.message)
    router.push('/dashboard')
  } else {
    if (response.status === 403) {
      isNotVerified.value = true
    }
    toast.error(response.message)
    resetForm({
      values: {
        ...values,
        password: '',
      },
    })
  }
}
const googleAuthUrl = `${import.meta.env.VITE_API_URL}/api/auth/google/redirect`;
</script>

<template>
  <div class="card border-0">
    <div class="card-body p-4">
      <h1 class="card-title text-center mb-4">Login</h1>
      <Form @submit="handleLogin" :validation-schema="schema" v-slot="{ errors }">
        <div class="mb-3">
          <label for="email" class="form-label">Indirizzo Email</label>
          <Field
            name="email"
            type="email"
            class="form-control"
            :class="{ 'is-invalid': errors.email }"
            id="email"
            required
            :disabled="isLoading"
          />
          <ErrorMessage name="email" class="text-danger small" />
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <Field
            name="password"
            type="password"
            class="form-control"
            :class="{ 'is-invalid': errors.password }"
            id="password"
            required
            :disabled="isLoading"
          />
          <ErrorMessage name="password" class="text-danger small" />
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check ">
            <Field
              name="remember"
              class="form-check-input"
              type="checkbox"
              :value="true"
              id="remember"
            />
            <label class="form-check-label" for="remember"> Ricordami </label>
          </div>
          <RouterLink to="/forgot-password">Password dimenticata?</RouterLink>
        </div>
        <div v-if="isNotVerified" class="alert alert-warning" role="alert">
          La tua email non è stata verificata. Controlla la tua casella di posta.
          <RouterLink to="/resend-verification" class="alert-link ms-1">Invia di nuovo</RouterLink>
        </div>
        <button type="submit" class="btn btn-primary w-100" :disabled="isLoading">
          <span v-if="isLoading" class="spinner-border spinner-border-sm"></span>
          <span v-else>Accedi</span>
        </button>
        <div class="text-center mt-3">
          <p class="text-muted">
            Non hai un account? <RouterLink to="/register">Registrati</RouterLink>
          </p>
        </div>
      </Form>
      <div class="my-4">
        <p class="text-center fw-bold mx-3 mb-0">Oppure</p>
      </div>
      <div>
        <a :href="googleAuthUrl" class="btn btn-danger w-100">
          <i class="fa-brands fa-google me-2"></i> Accedi con Google
        </a>
      </div>

    </div>
  </div>
</template>
