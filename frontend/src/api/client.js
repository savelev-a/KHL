// src/api/client.js
import axios from 'axios'

// baseURL берём из .env (VITE_API_URL).
// В dev это '/api/v1' → Vite проксирует на Laravel.
// В prod — полный URL бэкенда.
const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  timeout: 15000,
  headers: {
    Accept: 'application/json',
  },
})

// Небольшой перехватчик для единообразных ошибок в консоли.
// Позже можно добавить toast/уведомление в UI.
client.interceptors.response.use(
  (response) => response,
  (error) => {
    const url = error.config?.url ?? '?'
    console.error(`[api] ${url}:`, error.message)
    return Promise.reject(error)
  }
)

export default client