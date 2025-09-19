<script setup>
import { onMounted, watch, ref } from 'vue'
import { useGeoStore } from '@/stores/geoStore'
import { storeToRefs } from 'pinia'
import vSelect from 'vue-select'

const props = defineProps({
  initialProvince: { type: String, default: '' }, 
  initialCity: { type: String, default: '' },
  initialCap: { type: String, default: '' },
})
const emit = defineEmits(['update:province', 'update:city', 'update:cap'])

const geoStore = useGeoStore()
const { provinces, cities, isLoadingProvinces, isLoadingCities } = storeToRefs(geoStore)

const selectedProvince = ref(null) 
const selectedCity = ref(null)

// --- BLOCCO MODIFICATO ---
onMounted(async () => {
  // 1. Carica tutte le province
  await geoStore.fetchProvinces()
  
  // 2. Se è stata fornita una provincia iniziale, procedi a inizializzare i campi
  if (props.initialProvince && provinces.value.length > 0) {
    const provinceObject = provinces.value.find(p => p.initials === props.initialProvince);

    if (provinceObject) {
      // 3. Imposta il valore del v-select della provincia
      selectedProvince.value = provinceObject;
      
      // 4. [LA CORREZIONE CHIAVE] Carica TUTTI i comuni per quella provincia
      await geoStore.fetchCities(provinceObject.initials);
      
      // 5. Dopo che i comuni sono stati caricati, trova e imposta la città iniziale
      if (props.initialCity && cities.value.length > 0) {
        // Cerchiamo il comune per nome. Assicurati che i nomi siano univoci per provincia.
        const cityObject = cities.value.find(c => c.name === props.initialCity);
        if (cityObject) {
          selectedCity.value = cityObject;
        }
      }
    }
  }
})
// --- FINE BLOCCO MODIFICATO ---


watch(selectedProvince, (newProvince, oldProvince) => {
  // Questa condizione previene che il watcher si attivi al montaggio del componente,
  // il che è corretto perché la logica di inizializzazione è già in onMounted.
  // Si attiverà solo per le modifiche fatte dall'utente.
  if (!oldProvince && !newProvince) return;
  if (newProvince?.initials === oldProvince?.initials) return;
  
  emit('update:province', newProvince ? newProvince.initials : '')
  
  // Resetta la città quando la provincia cambia
  selectedCity.value = null;
  emit('update:city', '')
  emit('update:cap', '')

  // Carica i nuovi comuni
  geoStore.fetchCities(newProvince ? newProvince.initials : null)
})

watch(selectedCity, (newCity) => {
  // Quando una nuova città viene selezionata (o resettata a null),
  // emetti i nuovi valori al componente padre.
  if (newCity) {
    emit('update:city', newCity.name)
    emit('update:cap', newCity.cap)
  }
})
</script>

<template>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Provincia</label>
      <v-select
        v-model="selectedProvince"
        :options="provinces"
        label="name" 
        placeholder="Seleziona o digita una provincia"
        :loading="isLoadingProvinces"
      >
        <template #no-options>Nessuna provincia trovata.</template>
      </v-select>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Città</label>
      <v-select
        v-model="selectedCity"
        :options="cities"
        label="name"
        placeholder="Seleziona una città"
        :loading="isLoadingCities"
        :disabled="!selectedProvince || isLoadingCities"
      >
        <template #no-options>Nessun comune trovato.</template>
      </v-select>
    </div>
  </div>
</template>


<style>
.vs__dropdown-toggle {
  border: var(--bs-border-width) solid var(--bs-border-color);
  border-radius: var(--bs-border-radius);
  padding: 0.375rem 0.75rem;
}
.vs--open .vs__dropdown-toggle {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25);
}
</style>