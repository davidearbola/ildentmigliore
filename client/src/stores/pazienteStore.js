import { defineStore } from 'pinia'
import axios from 'axios'

export const usePazienteStore = defineStore('paziente', {
  state: () => ({
    isLoading: false,
    // Le notifiche e le proposte verranno gestite in modo diverso
    proposte: [],
    preventivoSalvato: null, // Per la pagina pubblica delle proposte
    processoPreventivo: {
      preventivoId: null,
      preventivoToken: null, // <-- Nuovo
      status: 'idle',
      voci: [],
      errorMessage: '',
    },
  }),
  getters: {
    proposalPublicUrl: (state) => {
      if (!state.processoPreventivo.preventivoToken) return '';
      // Costruisce l'URL completo per la pagina delle proposte pubbliche
      return `${window.location.origin}/proposte/${state.processoPreventivo.preventivoToken}`;
    }
  },
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
      this.processoPreventivo.status = 'uploading';
      this.isLoading = true;

      const formData = new FormData();
      formData.append('preventivo', data.preventivoFile);
      formData.append('email', data.email);
      formData.append('cellulare', data.cellulare);
      formData.append('indirizzo', data.indirizzo);
      formData.append('citta', data.citta);
      formData.append('cap', data.cap);
      formData.append('provincia', data.provincia);

      try {
        const response = await axios.post('/api/pubblico/preventivi', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        this.processoPreventivo.preventivoId = response.data.preventivo_id;
        this.processoPreventivo.preventivoToken = response.data.token;
        this.processoPreventivo.status = 'processing';

        return { success: true };
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
      if (!this.processoPreventivo.preventivoId) return;

      try {
        const response = await axios.get(
          `/api/pubblico/preventivi/${this.processoPreventivo.preventivoId}/stato`,
        );
        const { stato_elaborazione, voci_preventivo } = response.data;

        if (stato_elaborazione === 'completato') {
          this.processoPreventivo.status = 'ready_for_confirmation';
          this.processoPreventivo.voci = voci_preventivo || [];
        } else if (stato_elaborazione === 'errore') {
          this.processoPreventivo.status = 'error';
          this.processoPreventivo.errorMessage = "Si è verificato un errore durante l'analisi del preventivo.";
        }
      } catch (error) {
        this.processoPreventivo.status = 'error';
        this.processoPreventivo.errorMessage = 'Impossibile verificare lo stato del preventivo.';
        console.error('Errore nel polling dello stato:', error);
      }
    },

    async confermaVociPreventivo(voci) {
      if (!this.processoPreventivo.preventivoId) return;

      this.processoPreventivo.status = 'confirming';
      this.isLoading = true;

      try {
        const response = await axios.post(
          `/api/pubblico/preventivi/${this.processoPreventivo.preventivoId}/conferma`,
          { voci },
        );
        this.processoPreventivo.status = 'generating';
        return { success: true, message: response.data.message };
      } catch (error) {
        this.processoPreventivo.status = 'error';
        const message = error.response?.data?.message || 'Si è verificato un errore durante la conferma.';
        this.processoPreventivo.errorMessage = message;
        return { success: false, message };
      } finally {
        this.isLoading = false;
      }
    },

    async controllaStatoProposte() {
      if (!this.processoPreventivo.preventivoId || this.processoPreventivo.status !== 'generating') return;

      try {
        const response = await axios.get(
          `/api/pubblico/preventivi/${this.processoPreventivo.preventivoId}/proposte-stato`,
        );
        if (response.data.proposte_pronte) {
          this.processoPreventivo.status = 'proposte_pronte_public'; // <-- Nuovo stato
        }
      } catch (error) {
        console.error('Errore nel polling dello stato proposte:', error);
      }
    },

    async fetchPublicProposte(token) {
      this.isLoading = true;
      try {
        const response = await axios.get(`/api/pubblico/proposte/${token}`);
        this.preventivoSalvato = response.data.preventivo;
        this.proposte = response.data.proposte;
        return { success: true };
      } catch (error) {
        console.error('Errore nel caricamento delle proposte pubbliche:', error);
        return { success: false, message: 'Impossibile caricare le proposte.' };
      } finally {
        this.isLoading = false;
      }
    }
  },
})
