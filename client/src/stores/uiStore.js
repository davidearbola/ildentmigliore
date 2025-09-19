import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
  state: () => ({
    hasUnsavedChanges: false,
  }),
  actions: {
    setUnsavedChanges(value) {
      this.hasUnsavedChanges = value
    },
    clearUnsavedChanges() {
      this.hasUnsavedChanges = false
    },
  },
})
