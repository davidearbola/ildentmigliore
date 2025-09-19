import { defineStore } from 'pinia'
import axios from 'axios'
import { useAuthStore } from './authStore'

export const useMedicoStore = defineStore('medico', {
  state: () => ({
    isLoading: false,
    profilo: null,
    profiloPubblico: null,
    listino: [],
    tipologie: [],
    proposteAccettate: [],
    unreadNotificationsCount: 0,
  }),
  actions: {
    // ---- Azione per il Profilo Pubblico ----
    async fetchProfiloPubblico(medicoId) {
      this.isLoading = true
      this.profiloPubblico = null // Resetta lo stato precedente
      try {
        const response = await axios.get(`/api/profilo-pubblico-medico/${medicoId}`)
        this.profiloPubblico = {
          anagrafica: response.data.anagrafica_medico,
          fotoStudi: response.data.foto_studi,
          staff: response.data.staff,
        }
        return { success: true }
      } catch (error) {
        const message =
          error.response?.status === 403
            ? 'Non hai il permesso di visualizzare questo profilo.'
            : 'Errore nel caricamento del profilo.'
        return { success: false, message }
      } finally {
        this.isLoading = false
      }
    },
    // ---- Anagrafica -----
    async updateAnagrafica(data) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/impostazioni/anagrafica', data)
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: error.response?.data?.message || 'Errore' }
      } finally {
        this.isLoading = false
      }
    },
    // ---- Listino -----
    // Carica il listino completo del medico
    async fetchListino() {
      this.isLoading = true
      try {
        const response = await axios.get('/api/listino')
        this.listino = response.data.listino
        this.tipologie = response.data.tipologie
        return { success: true }
      } catch (error) {
        this.listino = []
        this.tipologie = []
        return { success: false, message: 'Errore nel caricamento del listino.' }
      } finally {
        this.isLoading = false
      }
    },
    // Salva tutte le modifiche in blocco
    async saveListino(payload) {
      this.isLoading = true
      const { masterItems, customItems } = payload
      try {
        const requests = []
        if (masterItems.length > 0) {
          requests.push(axios.post('/api/listino/master', { items: masterItems }))
        }
        if (customItems.length > 0) {
          requests.push(axios.post('/api/listino/custom', { items: customItems }))
        }
        if (requests.length === 0) {
          return { success: true, message: 'Nessuna modifica da salvare.' }
        }
        await Promise.all(requests)
        await this.fetchListino()
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: 'Listino salvato con successo!' }
      } catch (error) {
        return {
          success: false,
          message: error.response?.data?.message || 'Errore durante il salvataggio.',
        }
      } finally {
        this.isLoading = false
      }
    },

    async updateCustomItem(item) {
      this.isLoading = true
      try {
        const response = await axios.put(`/api/listino/custom/${item.id}`, item)
        await this.fetchListino() // Ricarica per consistenza
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return {
          success: false,
          message: error.response?.data?.message || "Errore durante l'aggiornamento.",
        }
      } finally {
        this.isLoading = false
      }
    },
    async deleteCustomItem(itemId) {
      this.isLoading = true
      try {
        const response = await axios.delete(`/api/listino/custom/${itemId}`)
        const index = this.listino.findIndex((item) => item.id === itemId)
        if (index !== -1) this.listino.splice(index, 1)
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return {
          success: false,
          message: error.response?.data?.message || "Errore durante l'eliminazione.",
        }
      } finally {
        this.isLoading = false
      }
    },
    // --- Profilo ---
    async fetchProfilo() {
      this.isLoading = true
      try {
        const response = await axios.get('/api/profilo-medico')
        this.profilo = {
          anagrafica: response.data.anagrafica_medico,
          fotoStudi: response.data.foto_studi,
          staff: response.data.staff,
        }
        return { success: true }
      } catch (error) {
        return { success: false, message: 'Errore nel caricamento del profilo.' }
      } finally {
        this.isLoading = false
      }
    },

    async updateDescrizione(descrizione) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/profilo-medico/descrizione', { descrizione })
        await this.fetchProfilo()
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: error.response?.data?.message || 'Errore' }
      } finally {
        this.isLoading = false
      }
    },

    async uploadFotoStudio(formData) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/profilo-medico/foto-studio', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        await this.fetchProfilo()
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: error.response?.data?.message || 'Errore' }
      } finally {
        this.isLoading = false
      }
    },

    async deleteFotoStudio(fotoId) {
      this.isLoading = true
      try {
        const response = await axios.delete(`/api/profilo-medico/foto-studio/${fotoId}`)
        await this.fetchProfilo()
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: "Errore durante l'eliminazione della foto." }
      } finally {
        this.isLoading = false
      }
    },

    async createStaff(formData) {
      this.isLoading = true
      try {
        const response = await axios.post('/api/profilo-medico/staff', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        await this.fetchProfilo()
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: error.response?.data?.message || 'Errore' }
      } finally {
        this.isLoading = false
      }
    },
    async updateStaff(staffId, formData) {
      this.isLoading = true
      try {
        // FormData gestisce correttamente l'invio di file e dati
        const response = await axios.post(`/api/profilo-medico/staff/${staffId}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          params: { _method: 'PUT' }, // Workaround per file e metodo PUT
        })
        await this.fetchProfilo()
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: error.response?.data?.message || 'Errore' }
      } finally {
        this.isLoading = false
      }
    },

    async deleteStaff(staffId) {
      this.isLoading = true
      try {
        const response = await axios.delete(`/api/profilo-medico/staff/${staffId}`)
        await this.fetchProfilo()
        const authStore = useAuthStore()
        await authStore.getUser()
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: "Errore durante l'eliminazione." }
      } finally {
        this.isLoading = false
      }
    },

    // Azione per le notifiche
    async checkForNotifications() {
      try {
        const response = await axios.get(`/api/notifiche?_=${Date.now()}`)
        this.unreadNotificationsCount = response.data.length
      } catch (error) {
        console.error('Errore nel controllo delle notifiche medico:', error)
        this.unreadNotificationsCount = 0
      }
    },

    // Azione per segnare come lette tutte le notifiche
    async markAsReadNotifications() {
      try {
        await axios.post('/api/notifiche-mark-as-read')
        this.unreadNotificationsCount = 0
        await this.fetchProposteAccettate()
        return { success: true }
      } catch (error) {
        console.error('Errore nel segnare le proposte come lette:', error)
        return { success: false, message: "Errore nell'aggiornamento dello stato delle proposte." }
      }
    },

    // Azione per recuperare le proposte accettate
    async fetchProposteAccettate() {
      this.isLoading = true
      try {
        const response = await axios.get('/api/proposte-accettate')
        this.proposteAccettate = response.data
        return { success: true }
      } catch (error) {
        console.error('Errore nel caricamento delle proposte accettate:', error)
        return { success: false }
      } finally {
        this.isLoading = false
      }
    },
  },
})
