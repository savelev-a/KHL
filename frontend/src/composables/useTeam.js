// src/composables/useTeam.js
import { ref, watch } from 'vue'
import { fetchTeam } from '../api/standings'

// Загружает одну команду по id.
// watch — потому что при переходе /team/3 → /team/5 компонент переиспользуется,
// onMounted снова не вызовется, а id уже другой. Без watch будет «залипшая» команда.
export function useTeam(idRef) {
  const team = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function load(id) {
    if (!id) return
    loading.value = true
    error.value = null
    try {
      team.value = await fetchTeam(id)
    } catch (e) {
      error.value = e.message || 'Не удалось загрузить команду'
      team.value = null
    } finally {
      loading.value = false
    }
  }

  // immediate: true — сразу грузим при первом монтировании.
  watch(idRef, (v) => load(v), { immediate: true })

  return { team, loading, error, reload: () => load(idRef.value) }
}