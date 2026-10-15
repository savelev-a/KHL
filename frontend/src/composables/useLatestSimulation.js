// src/composables/useLatestSimulation.js
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { fetchLatestSimulation } from '../api/standings'

// Форматирование возраста в человекочитаемое «X назад».
export function formatAge(seconds) {
  if (seconds < 60) return 'только что'
  if (seconds < 3600) return `${Math.floor(seconds / 60)} мин назад`
  return `${Math.floor(seconds / 3600)} ч назад`
}

export function useLatestSimulation() {
  const data = ref(null)
  const error = ref(null)
  const ageSeconds = ref(0)

  let tickTimer = null

  // Считаем возраст ОТ calculated_at, а не из age_seconds бэкенда.
  // Причина: бэкенд отдаёт signed float (отрицательный для прошлого)
  // и точность до миллисекунд — здесь это только мешает.
  function recomputeAge() {
    if (!data.value?.calculated_at) return
    const calcMs = new Date(data.value.calculated_at).getTime()
    if (Number.isNaN(calcMs)) return
    ageSeconds.value = Math.max(0, Math.floor((Date.now() - calcMs) / 1000))
  }

  async function load() {
    try {
      data.value = await fetchLatestSimulation()
      recomputeAge()
      error.value = null
    } catch (e) {
      error.value = e.message || 'Не удалось получить время последнего расчёта'
    }
  }

  const ageLabel = computed(() => formatAge(ageSeconds.value))

  onMounted(() => {
    load()
    // Раз в 30 секунд пересчитываем возраст от реального calculated_at —
    // так подпись не «уплывает» даже если часы бэкенда и фронта разошлись.
    tickTimer = setInterval(recomputeAge, 30_000)
  })

  onUnmounted(() => {
    if (tickTimer) clearInterval(tickTimer)
  })

  return { data, error, ageSeconds, ageLabel, reload: load }
}