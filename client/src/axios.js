import axios from 'axios';
import router from './router'
import { useToast } from 'vue-toastification'

axios.defaults.baseURL = import.meta.env.VITE_API_URL
axios.defaults.withCredentials = true
axios.defaults.withXSRFToken = true

axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (
      error.response?.status === 403 &&
      error.response?.data?.error_code === 'FORCE_PASSWORD_CHANGE'
    ) {
      const toast = useToast()
      toast.warning('Per motivi di sicurezza, è necessario cambiare la password iniziale.')

      router.push({ name: 'reset-password-force' })
    }

    return Promise.reject(error)
  },
)

export default axios;