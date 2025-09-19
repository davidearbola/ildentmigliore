import { defineStore } from 'pinia'
import axios from 'axios'
import { useAuthStore } from './authStore'

export const usePazienteStore = defineStore('paziente', {
  state: () => ({
    isLoading: false,
    unreadNotificationsCount: 0,
    proposteNuove: [],
    proposteArchiviate: [],
    processoPreventivo: {
      preventivoId: null,
      status: 'idle',
      voci: [],
      errorMessage: '',
    },
  }),
  actions: {
    /**
     * Resetta lo stato del processo di caricamento del preventivo.
     */
    resetProcessoPreventivo() {
      this.processoPreventivo = {
        preventivoId: null,
        status: 'idle',
        voci: [],
        errorMessage: '',
      }
    },

    /**
     * Inizia il processo di caricamento di un nuovo preventivo.
     * @param {Object} data - L'oggetto contenente il file e i campi del form.
     */
    async uploadQuote(data) {
      this.processoPreventivo.status = 'uploading'
      this.isLoading = true

      const formData = new FormData()
      formData.append('preventivo', data.preventivoFile)

      if (data.cellulare) formData.append('cellulare', data.cellulare)
      if (data.indirizzo) formData.append('indirizzo', data.indirizzo)
      if (data.citta) formData.append('citta', data.citta)
      if (data.cap) formData.append('cap', data.cap)
      if (data.provincia) formData.append('provincia', data.provincia)

      try {
        const response = await axios.post('/api/preventivi', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })

        // Il backend ora restituisce l'ID del preventivo
        this.processoPreventivo.preventivoId = response.data.preventivo_id
        this.processoPreventivo.status = 'processing' // Pronto per il polling

        const authStore = useAuthStore()
        if (!authStore.user.anagrafica_paziente) {
          authStore.isAuthCheckCompleted = false
          await authStore.getUser()
        }

        return { success: true, preventivoId: response.data.preventivo_id }
      } catch (error) {
        this.processoPreventivo.status = 'error'
        this.processoPreventivo.errorMessage =
          error.response?.data?.message || 'Si è verificato un errore durante il caricamento.'
        return { success: false, message: this.processoPreventivo.errorMessage }
      } finally {
        this.isLoading = false
      }
    },

    /**
     * *** NUOVA AZIONE ***
     * Controlla lo stato di elaborazione del preventivo sul backend.
     */
    async controllaStatoPreventivo() {
      if (!this.processoPreventivo.preventivoId) return

      try {
        const response = await axios.get(
          `/api/preventivi/${this.processoPreventivo.preventivoId}/stato`,
        )
        const { stato_elaborazione, voci_preventivo } = response.data

        if (stato_elaborazione === 'completato') {
          this.processoPreventivo.status = 'ready_for_confirmation'
          this.processoPreventivo.voci = voci_preventivo || [] // Assicura che sia un array
        } else if (stato_elaborazione === 'errore') {
          this.processoPreventivo.status = 'error'
          this.processoPreventivo.errorMessage =
            "Si è verificato un errore durante l'analisi del preventivo."
        }
        // Se lo stato è 'caricato' o 'in_elaborazione', non facciamo nulla e il polling continuerà.
      } catch (error) {
        this.processoPreventivo.status = 'error'
        this.processoPreventivo.errorMessage = 'Impossibile verificare lo stato del preventivo.'
        console.error('Errore nel polling dello stato:', error)
      }
    },

    /**
     * *** NUOVA AZIONE ***
     * Invia le voci confermate/modificate al backend.
     * @param {Array} voci - L'array delle voci del preventivo.
     */
    async confermaVociPreventivo(voci) {
      if (!this.processoPreventivo.preventivoId) return

      this.processoPreventivo.status = 'confirming'
      this.isLoading = true

      try {
        const response = await axios.post(
          `/api/preventivi/${this.processoPreventivo.preventivoId}/conferma`,
          { voci },
        )

        this.processoPreventivo.status = 'generating'
        return { success: true, message: response.data.message }
      } catch (error) {
        this.processoPreventivo.status = 'error'
        const message =
          error.response?.data?.message || 'Si è verificato un errore durante la conferma.'
        this.processoPreventivo.errorMessage = message
        return { success: false, message }
      } finally {
        this.isLoading = false
      }
    },
    /**
     * *** NUOVA AZIONE ***
     * Controlla se le proposte sono state generate per il preventivo corrente.
     */
    async controllaStatoProposte() {
      if (!this.processoPreventivo.preventivoId || this.processoPreventivo.status !== 'generating')
        return

      try {
        const response = await axios.get(
          `/api/preventivi/${this.processoPreventivo.preventivoId}/proposte-stato`,
        )
        if (response.data.proposte_pronte) {
          this.processoPreventivo.status = 'proposte_pronte'
        }
      } catch (error) {
        // Non impostiamo lo stato su 'error' qui per non interrompere la UI,
        // il polling continuerà a provare.
        console.error('Errore nel polling dello stato proposte:', error)
      }
    },
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

    async checkForNotifications() {
      try {
        const response = await axios.get(`/api/notifiche?_=${Date.now()}`)
        this.unreadNotificationsCount = response.data.length
      } catch (error) {
        console.error('Errore nel controllo delle notifiche:', error)
        this.unreadNotificationsCount = 0
      }
    },

    /**
     * Carica le proposte dal backend e le divide in 'nuove' e 'archiviate'.
     */
    async fetchProposte() {
      this.isLoading = true
      try {
        const response = await axios.get('/api/proposte')
        this.proposteNuove = response.data.nuove || []
        this.proposteArchiviate = response.data.archiviate || []
        return { success: true }
      } catch (error) {
        console.error('Errore nel caricamento delle proposte:', error)
        return { success: false, message: 'Errore nel caricamento delle proposte.' }
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Segna le proposte nuove come lette e azzera le notifiche.
     */
    async markProposteComeLette() {
      if (this.proposteNuove.length === 0) {
        return { success: true }
      }

      const proposteIds = this.proposteNuove.map((p) => p.id)

      try {
        await axios.post('/api/proposte/mark-as-read-paziente', { proposteIds })
        this.unreadNotificationsCount = 0
        await this.fetchProposte()
        return { success: true }
      } catch (error) {
        console.error('Errore nel segnare le proposte come lette:', error)
        return { success: false, message: "Errore nell'aggiornamento dello stato delle proposte." }
      }
    },

    /**
     * Accetta una proposta.
     * @param {number} propostaId L'ID della proposta da accettare.
     */
    async accettaProposta(propostaId) {
      this.isLoading = true
      try {
        const response = await axios.post(`/api/proposte/${propostaId}/accetta`)
        await this.fetchProposte() // Ricarica per aggiornare lo stato
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: "Errore durante l'accettazione della proposta." }
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Rifiuta una proposta.
     * @param {number} propostaId L'ID della proposta da rifiutare.
     */
    async rifiutaProposta(propostaId) {
      this.isLoading = true
      try {
        const response = await axios.post(`/api/proposte/${propostaId}/rifiuta`)
        await this.fetchProposte() // Ricarica per aggiornare lo stato
        return { success: true, message: response.data.message }
      } catch (error) {
        return { success: false, message: 'Errore durante il rifiuto della proposta.' }
      } finally {
        this.isLoading = false
      }
    },
  },
})
