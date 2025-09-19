<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'
import { useToast } from 'vue-toastification';
import { useRoute } from 'vue-router';

const authStore = useAuthStore()
const { isLoading } = storeToRefs(authStore);
const route = useRoute();
const toast = useToast();

const form = ref({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
});

onMounted(() => {
    form.value.token = route.query.token || '';
    form.value.email = route.query.email || '';
});

const handleResetPassword = async () => {
    const response = await authStore.resetPassword(form.value);
    if (response.success) {
        toast.success(response.message);
    } else {
        toast.error(response.message);
    }
};
</script>
<template>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
        <div class="card-body">
        <h1 class="card-title text-center mb-4">Reimposta Password</h1>
        <form @submit.prevent="handleResetPassword">
            <input type="hidden" v-model="form.token">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" v-model="form.email" required disabled>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Nuova Password</label>
                <input type="password" class="form-control" id="password" v-model="form.password" required :disabled="isLoading">
            </div>
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Conferma Nuova Password</label>
                <input type="password" class="form-control" id="password_confirmation" v-model="form.password_confirmation" required :disabled="isLoading">
            </div>

            <button type="submit" class="btn btn-primary w-100" :disabled="isLoading">
                <span v-if="isLoading" class="spinner-border spinner-border-sm"></span>
                <span v-else>Reimposta Password</span>
            </button>
        </form>
        </div>
    </div>
    </div>
</div>
</template>