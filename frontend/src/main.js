// src/main.js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

import 'bulma/css/bulma.min.css'
import './styles/theme.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')