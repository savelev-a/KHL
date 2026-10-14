<!-- src/views/HomeView.vue -->
<script setup>
import { onMounted } from 'vue'
import { useStandingsStore } from '../stores/standings'

const store = useStandingsStore()

// Пока просто грузим данные, чтобы убедиться,
// что связка axios → store → компонент работает.
onMounted(() => store.load())
</script>

<template>
  <section>
    <h1 class="title is-4 has-text-light">Плей-офф: шансы команд</h1>

    <div v-if="store.loading" class="has-text-grey-light">Загрузка…</div>
    <div v-else-if="store.error" class="notification is-danger is-light">
      {{ store.error }}
    </div>
    <div v-else>
      <p class="has-text-grey-light">
        Загружено команд: {{ store.items.length }}
        (Восток: {{ store.east.length }}, Запад: {{ store.west.length }}).
      </p>
      <!-- Таблицы появятся на шаге 4. -->
    </div>
  </section>
</template>