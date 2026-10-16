// src/composables/useStandings.js
import { ref, onMounted, onUnmounted } from 'vue'
import { useStandingsStore } from '../stores/standings'

const REFRESH_MS = 5 * 60 * 1000  // 5 минут

export function useStandings() {
  const store = useStandingsStore()

  // true, пока идёт ФОНОВОЕ обновление (после первой успешной загрузки).
  // Первичная загрузка остаётся видимой через store.loading + store.items.length === 0.
  const backgroundLoading = ref(false)

  let timer = null

  async function refresh() {
    // Фоновая загрузка — только если данные уже есть.
    // На самой первой итерации store.items пустой, и мы не хотим,
    // чтобы индикатор «обновляется» мигал рядом с «Загрузка…».
    if (store.items.length > 0) {
      backgroundLoading.value = true
    }
    try {
      await store.load()
    } finally {
      backgroundLoading.value = false
    }
  }

  onMounted(() => {
    refresh()
    timer = setInterval(refresh, REFRESH_MS)
  })

  onUnmounted(() => {
    if (timer) clearInterval(timer)
    timer = null
  })

  return { store, backgroundLoading, refresh }
}