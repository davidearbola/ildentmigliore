import './assets/scss/_variables.scss'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import './assets/main.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from './axios'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import 'vue-select/dist/vue-select.css'

const app = createApp(App)

app.use(createPinia())
app.use(Toast)
app.use(router)

app.mount('#app')
