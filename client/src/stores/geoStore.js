import { defineStore } from 'pinia'
import axios from '@/axios'

export const useGeoStore = defineStore('geo', {
  state: () => ({
    provinces: [],
    cities: [],
    isLoadingProvinces: false,
    isLoadingCities: false,
  }),
  actions: {
    async fetchProvinces() {
      if (this.provinces.length > 0) return

      this.isLoadingProvinces = true
      try {
        const response = await axios.get('/api/province')
        this.provinces = response.data
      } catch (error) {
        console.error('Errore nel caricamento delle province:', error)
        this.provinces = []
      } finally {
        this.isLoadingProvinces = false
      }
    },

    async fetchCities(provinceInitials) {
      if (!provinceInitials) {
        this.cities = []
        return
      }

      this.isLoadingCities = true
      this.cities = []
      try {
        const response = await axios.get(`/api/comuni/${provinceInitials}`)
        this.cities = response.data
      } catch (error) {
        console.error(`Errore nel caricamento dei comuni per ${provinceInitials}:`, error)
        this.cities = []
      } finally {
        this.isLoadingCities = false
      }
    },

    setInitialCities(initialCity, initialCap) {
      if (initialCity && initialCap) {
        this.cities = [{ name: initialCity, cap: initialCap }]
      } else {
        this.cities = []
      }
    },
  },
})
