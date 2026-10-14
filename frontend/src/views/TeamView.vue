<!-- src/views/TeamView.vue -->
<script setup>
defineProps({
  id: { type: String, required: true },
})
</script>

<template>
  <section>
    <h1 class="title is-4 has-text-light">Команда #{{ id }}</h1>
    <p class="has-text-grey-light">
      Здесь будет карточка команды и график Эло (шаг 7).
    </p>
    <RouterLink to="/" class="button is-small is-dark mt-4">← К таблице</RouterLink>
  </section>
</template>