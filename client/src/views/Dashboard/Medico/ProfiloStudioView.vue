<script setup>
import { onMounted, ref, reactive } from 'vue';
import { useMedicoStore } from '@/stores/medicoStore';
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';
import { useToast } from 'vue-toastification';
import * as yup from 'yup';
import { Form, Field, ErrorMessage } from 'vee-validate';
import { Modal } from 'bootstrap';
import { useRouter } from 'vue-router';

// --- STORES E UTILITY ---
const medicoStore = useMedicoStore();
const authStore = useAuthStore();
const { profilo, isLoading } = storeToRefs(medicoStore);
const { user } = storeToRefs(authStore);
const toast = useToast();
const router = useRouter();

// --- STATO LOCALE ---
const descrizione = ref('');
const staffModalRef = ref(null);
let staffModalInstance = null;
const isEditingStaff = ref(false);
const currentStaffMember = reactive({ nome: '', ruolo: '', specializzazione: '', esperienza: '', foto: null });
const isCompleted = ref(false)

// --- SCHEMI DI VALIDAZIONE ---
const descSchema = yup.object({
  descrizione: yup.string().required('La descrizione è obbligatoria.').min(50, 'La descrizione deve contenere almeno 50 parole.'),
});
const staffSchema = yup.object({
  nome: yup.string().required('Il nome è obbligatorio.'),
  ruolo: yup.string().required('Il ruolo è obbligatorio.'),
  foto: yup.mixed().when('isEditing', {
      is: false,
      then: (schema) => schema.required('La foto è obbligatoria.'),
      otherwise: (schema) => schema.nullable(),
  }),

});

// --- LOGICA DEL COMPONENTE ---
onMounted(async () => {
  await medicoStore.fetchProfilo();
  descrizione.value = medicoStore.profilo?.anagrafica?.descrizione || '';
  if (staffModalRef.value) {
    staffModalInstance = new Modal(staffModalRef.value);
  }
  if(medicoStore.profilo.anagrafica.step_listino_completed_at && step_profilo_completed_at && step_staff_completed_at){
    isCompleted.value = true
  }
});

const goToProfiloPubblico = () => {
    if(user.value?.id) {
        router.push({ name: 'medico-profilo-pubblico', params: { id: user.value.id } });
    }
}

const handleDescrizioneUpdate = async () => {
  const { success, message } = await medicoStore.updateDescrizione(descrizione.value);
  if (success) toast.success(message); else toast.error(message);
};

const handleFotoStudioUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('foto', file);

  const { success, message } = await medicoStore.uploadFotoStudio(formData);
  if (success) toast.success(message); else toast.error(message);
  event.target.value = ''; // Resetta l'input file
};

const handleDeleteFoto = async (fotoId) => {
    if(window.confirm('Sei sicuro di voler eliminare questa foto?')){
        const { success, message } = await medicoStore.deleteFotoStudio(fotoId);
        if (success) toast.success(message); else toast.error(message);
    }
}

const handleStaffFotoChange = (event) => {
    currentStaffMember.foto = event.target.files[0];
}

const openStaffModal = (staff = null) => {
    if (staff) { // Modal in modalità modifica
        isEditingStaff.value = true;
        Object.assign(currentStaffMember, { ...staff, foto: null });
    } else { // Modal in modalità creazione
        isEditingStaff.value = false;
        Object.assign(currentStaffMember, { id: null, nome: '', ruolo: '', specializzazione: '', esperienza: '', foto: null });
    }
    staffModalInstance?.show();
}

const handleStaffSubmit = async (values) => {
    const formData = new FormData();
    Object.keys(values).forEach(key => {
        if(values[key]) formData.append(key, values[key]);
    });
    if (currentStaffMember.foto) {
        formData.append('foto', currentStaffMember.foto);
    }

    let response;
    if (isEditingStaff.value) {
        response = await medicoStore.updateStaff(currentStaffMember.id, formData);
    } else {
        response = await medicoStore.createStaff(formData);
    }

    if (response.success) {
        toast.success(response.message);
        staffModalInstance?.hide();
    } else {
        toast.error(response.message);
    }
}

const handleDeleteStaff = async (staffId) => {
    if (window.confirm('Sei sicuro di voler eliminare questo membro dello staff?')) {
        const { success, message } = await medicoStore.deleteStaff(staffId);
        if (success) toast.success(message); else toast.error(message);
    }
}

</script>

<template>
  <div>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-5 fw-bold">Profilo Studio Medico</h1>
            <p class="lead text-muted">Completa il tuo profilo per presentarti al meglio ai pazienti.</p>
        </div>
        <button v-if="isCompleted" class="btn btn-outline-primary" @click="goToProfiloPubblico">
            <i class="fa-solid fa-eye me-2"></i>Anteprima Profilo Pubblico
        </button>
    </div>
    <hr class="my-4">

    <div class="row">
      <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header">
            <h5 class="mb-0">Descrizione dello Studio</h5>
          </div>
          <div class="card-body">
            <Form @submit="handleDescrizioneUpdate" :initial-values="{ descrizione }" :validation-schema="descSchema" v-slot="{ errors }">
              <Field as="textarea" name="descrizione" v-model="descrizione" class="form-control" :class="{'is-invalid': errors.descrizione}" rows="6" />
              <ErrorMessage name="descrizione" class="text-danger small" />
              <div class="text-end mt-3">
                <button class="btn btn-primary" :disabled="isLoading">Salva Descrizione</button>
              </div>
            </Form>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header">
            <h5 class="mb-0">Galleria Foto Studio (min. 3)</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
                <div v-for="foto in profilo?.fotoStudi" :key="foto.id" class="col-md-4">
                    <div class="position-relative">
                        <img :src="foto.url" class="img-fluid rounded" alt="Foto studio">
                        <button @click="handleDeleteFoto(foto.id)" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" title="Elimina foto">&times;</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="foto-studio-upload" class="d-flex align-items-center justify-content-center border-2 border-dashed rounded h-100 text-center" style="cursor: pointer; min-height: 150px;">
                        <div>
                            <i class="fa-solid fa-plus fa-2x text-muted"></i>
                            <p class="text-muted mt-2 mb-0">Aggiungi Foto</p>
                        </div>
                    </label>
                    <input type="file" id="foto-studio-upload" class="d-none" @change="handleFotoStudioUpload" accept="image/*">
                </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Membri dello Staff</h5>
            <button class="btn btn-primary btn-sm" @click="openStaffModal()">Aggiungi Membro</button>
          </div>
          <ul class="list-group list-group-flush">
            <li v-for="membro in profilo?.staff" :key="membro.id" class="list-group-item d-flex align-items-center">
                <img :src="membro.url" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                <div class="flex-grow-1">
                    <h6 class="mb-0">{{ membro.nome }}</h6>
                    <small class="text-muted">{{ membro.ruolo }}</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-light me-2" @click="openStaffModal(membro)">Modifica</button>
                    <button class="btn btn-sm btn-light text-danger" @click="handleDeleteStaff(membro.id)">Elimina</button>
                </div>
            </li>
            <li v-if="!profilo?.staff?.length" class="list-group-item text-center text-muted py-3">Nessun membro dello staff aggiunto.</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="modal fade" id="staffModal" ref="staffModalRef" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <Form @submit="handleStaffSubmit" :initial-values="currentStaffMember" :validation-schema="staffSchema" v-slot="{ errors }">
            <div class="modal-header">
              <h5 class="modal-title">{{ isEditingStaff ? 'Modifica Membro' : 'Aggiungi Membro' }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6"><label>Nome</label><Field name="nome" v-model="currentStaffMember.nome" class="form-control" /><ErrorMessage name="nome" class="text-danger small"/></div>
                <div class="col-md-6"><label>Ruolo</label><Field name="ruolo" v-model="currentStaffMember.ruolo" class="form-control" /><ErrorMessage name="ruolo" class="text-danger small"/></div>
                <div class="col-12"><label>Specializzazioni</label><Field as="textarea" name="specializzazione" v-model="currentStaffMember.specializzazione" class="form-control" /></div>
                <div class="col-12"><label>Esperienze</label><Field as="textarea" name="esperienza" v-model="currentStaffMember.esperienza" class="form-control" /></div>
                <div class="col-12">
                    <label>Foto</label>
                    <input type="file" name="foto" @change="handleStaffFotoChange" class="form-control" accept="image/*" />
                    <ErrorMessage name="foto" class="text-danger small"/>
                    <small v-if="isEditingStaff" class="form-text text-muted">Lascia vuoto per non modificare la foto attuale.</small>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
              <button type="submit" class="btn btn-primary" :disabled="isLoading">Salva</button>
            </div>
          </Form>
        </div>
      </div>
    </div>
  </div>
</template>