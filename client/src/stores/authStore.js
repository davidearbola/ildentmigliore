import { defineStore } from 'pinia'
import axios from 'axios'
import router from '@/router'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthCheckCompleted: false,
    isLoading: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.user,
    authUser: (state) => state.user,
  },
  actions: {
    async login(credentials) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/login', credentials)
        if (response.data.success) {
          this.user = response.data.user
          await this.getUser()
          return {
            success: true,
            message: 'Login effettuato con successo!',
          }
        }
        return { success: false, message: 'Errore imprevisto.' }
      } catch (error) {
        if (error.response && error.response.data) {
          return {
            ...error.response.data,
            status: error.response.status,
          }
        }
        return {
          success: false,
          message: 'Errore di connessione. Riprova più tardi.',
        }
      } finally {
        this.isLoading = false
      }
    },
    async register(userInfo) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/register', userInfo)
        return response.data
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors
          const firstErrorMessage = errors[Object.keys(errors)[0]][0]
          return { success: false, message: firstErrorMessage }
        }
        return {
          success: false,
          message: 'Si è verificato un errore imprevisto durante la registrazione. Riprova!',
        }
      } finally {
        this.isLoading = false
      }
    },

    async registerMedico(userInfo) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/register-medico', userInfo)
        return response.data
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors
          const firstErrorMessage = errors[Object.keys(errors)[0]][0]
          return { success: false, message: firstErrorMessage }
        }
        return {
          success: false,
          message: 'Si è verificato un errore imprevisto durante la registrazione. Riprova!',
        }
      } finally {
        this.isLoading = false
      }
    },
    async logout() {
      this.isLoading = true
      try {
        await axios.post('/api/logout')
        this.user = null
        return {
          success: true,
          message: 'Logout effettuato con successo!',
        }
      } catch (error) {
        return {
          success: false,
          message: 'Impossibile effettuare il logout. Riprova.',
        }
      } finally {
        this.isLoading = false
      }
    },
    async getUser() {
      try {
        const response = await axios.get('/api/user')
        this.user = response.data
      } catch {
        this.user = null
      } finally {
        this.isAuthCheckCompleted = true
      }
    },
    async forgotPassword(email) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/forgot-password', { email })
        return response.data
      } catch (error) {
        if (error.response && error.response.status === 422) {
          return error.response.data
        }
        if (error.response && error.response.data) {
          return error.response.data
        }
        return {
          success: false,
          message: 'Si è verificato un errore di connessione. Riprova più tardi.',
        }
      } finally {
        this.isLoading = false
      }
    },
    async resetPassword(formData) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/reset-password', formData)
        return response.data
      } catch (error) {
        if (error.response && error.response.data) {
          return error.response.data
        }
        return {
          success: false,
          message: 'Si è verificato un errore di connessione. Riprova più tardi.',
        }
      } finally {
        this.isLoading = false
      }
    },
    async publicResendVerificationEmail(email) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/resend-verification-email', { email })
        return response.data
      } catch (error) {
        return {
          success: false,
          message: 'Si è verificato un errore improvviso. Riprova.',
        }
      } finally {
        this.isLoading = false
      }
    },
    async resendVerificationEmail() {
      this.isLoading = true
      try {
        const response = await axios.post('/api/email/verification-notification')
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.isLoading = false
      }
    },
    async updateEmail(newEmail) {
      this.isLoading = true
      try {
        const response = await axios.put('/api/impostazioni/email', { email: newEmail })
        this.user = null
        this.isAuthCheckCompleted = false
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: error.response?.data?.message || 'Errore' }
      } finally {
        this.isLoading = false
      }
    },
    async updatePassword(passwords) {
      this.isLoading = true
      try {
        const response = await axios.put('/api/impostazioni/password', passwords)
        this.user = null
        this.isAuthCheckCompleted = false
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: error.response?.data?.message || 'Errore' }
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Gestisce il login dopo essere tornati dal provider social.
     * Salva il token e recupera i dati dell'utente.
     */
    async handleSocialLoginCallback(token) {
      if (!token) {
        // Se non c'è il token, reindirizza al login con un errore
        router.push({ name: 'login', query: { error: 'auth_failed' } })
        return
      }

      // Salviamo il token in localStorage (o dove preferisci)
      localStorage.setItem('authToken', token)

      // Impostiamo l'header di default di Axios per le prossime chiamate
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

      // Recuperiamo i dati dell'utente e li salviamo nello store
      await this.getUser()

      // Se l'utente è stato recuperato con successo, lo mandiamo alla sua dashboard
      if (this.user) {
        router.push({ name: 'dashboard-home' })
      } else {
        // Altrimenti, puliamo tutto e lo rimandiamo al login
        this.logout()
      }
    },
  },
})
