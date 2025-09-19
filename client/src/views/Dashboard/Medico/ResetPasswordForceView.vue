<script setup>
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'

const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()

// Usiamo lo stato 'isLoading' direttamente dallo store
// in modo che il componente sia sempre sincronizzato.
const { isLoading } = storeToRefs(authStore)

// Schema di validazione per i campi del form (invariato)
const schema = yup.object({
  current_password: yup.string().required('La password attuale è obbligatoria.'),
  password: yup
    .string()
    .required('La nuova password è obbligatoria.')
    .min(8, 'La password deve contenere almeno 8 caratteri.'),
  password_confirmation: yup
    .string()
    .oneOf([yup.ref('password'), null], 'Le password non corrispondono.')
    .required('La conferma della password è obbligatoria.'),
})

// Funzione per gestire l'invio del form, ora usa lo store
const handlePasswordUpdate = async (values) => {
  // 1. Chiamiamo l'azione centralizzata del tuo store
  const response = await authStore.updatePassword(values)

  // 2. Controlliamo l'esito che ci restituisce lo store
  if (response.success) {
    // La funzione dello store ha già effettuato il logout.
    // Dobbiamo solo avvisare l'utente e reindirizzarlo.
    toast.success('Password aggiornata con successo! Effettua di nuovo il login.')
    router.push({ name: 'login' })
  } else {
    // In caso di errore, mostriamo il messaggio restituito dallo store
    toast.error(response.message)
  }
}
</script>

<template>
  <div class="container-fluid p-4">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 p-lg-5">
            <h1 class="card-title h3 text-center mb-2">Cambio Password Obbligatorio</h1>
            <p class="text-center text-muted mb-4">
              Per motivi di sicurezza, imposta una nuova password personale per poter accedere alla piattaforma.
            </p>

            <Form @submit="handlePasswordUpdate" :validation-schema="schema" v-slot="{ errors }">
              <div class="mb-3">
                <label for="current_password" class="form-label">Password Attuale (ricevuta via email)</label>
                <Field
                  name="current_password"
                  type="password"
                  class="form-control"
                  :class="{ 'is-invalid': errors.current_password }"
                  id="current_password"
                  :disabled="isLoading"
                />
                <ErrorMessage name="current_password" class="text-danger small" />
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">Nuova Password</label>
                <Field
                  name="password"
                  type="password"
                  class="form-control"
                  :class="{ 'is-invalid': errors.password }"
                  id="password"
                  :disabled="isLoading"
                />
                <ErrorMessage name="password" class="text-danger small" />
              </div>

              <div class="mb-4">
                <label for="password_confirmation" class="form-label">Conferma Nuova Password</label>
                <Field
                  name="password_confirmation"
                  type="password"
                  class="form-control"
                  :class="{ 'is-invalid': errors.password_confirmation }"
                  id="password_confirmation"
                  :disabled="isLoading"
                />
                <ErrorMessage name="password_confirmation" class="text-danger small" />
              </div>

              <button type="submit" class="btn btn-primary w-100 btn-lg" :disabled="isLoading">
                <span v-if="isLoading" class="spinner-border spinner-border-sm"></span>
                <span v-else>Imposta Nuova Password</span>
              </button>
            </Form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>