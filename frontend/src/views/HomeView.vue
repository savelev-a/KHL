<!-- src/views/HomeView.vue -->
<script setup>
import { computed } from 'vue'
import ConferenceTable from '../components/ConferenceTable.vue'
import { useLatestSimulation } from '../composables/useLatestSimulation'
import { useStandings } from '../composables/useStandings'

// useStandings сам делает первичную загрузку + polling каждые 5 минут.
// onUnmounted внутри composable убивает таймер при уходе со страницы.
const { store, backgroundLoading } = useStandings()

const { data: latest, error: latestError, ageLabel, reload: reloadLatest } =
  useLatestSimulation()

const east = computed(() => store.east)
const west = computed(() => store.west)
</script>

<template>
  <section>
    <div class="page-head">
      <h1 class="title is-4 has-text-light">Плей-офф: шансы команд</h1>

      <div class="head-meta">
        <!-- Бейдж «обновляется…» появляется только на время фонового рефетча. -->
        <span v-if="backgroundLoading" class="last-run last-run--busy">
          обновляется…
        </span>

        <span
          v-if="latest"
          class="last-run"
          :title="new Date(latest.calculated_at).toLocaleString('ru-RU')"
        >
          Последний расчёт: {{ ageLabel }}
        </span>
        <span v-else-if="latestError" class="last-run last-run--error">
          Нет данных о расчёте
        </span>
      </div>
    </div>

    <div v-if="store.loading && !store.items.length" class="has-text-grey-light">
      Загрузка…
    </div>
    <div v-else-if="store.error && !store.items.length" class="notification is-danger is-light">
      {{ store.error }}
    </div>
    <template v-else>
      <div v-if="store.error" class="notification is-warning is-light">
        Не удалось обновить данные: {{ store.error }}
      </div>
      <ConferenceTable title="Конференция «Восток»" :rows="east" />
      <ConferenceTable title="Конференция «Запад»" :rows="west" />
    </template>
  </section>
</template>

<style scoped>
.page-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 0.5rem;
}
.head-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}
.last-run {
  font-size: 0.85rem;
  color: var(--khl-text-muted);
}
.last-run--error {
  color: var(--khl-red);
}
.last-run--busy {
  color: var(--khl-accent);
}
</style>