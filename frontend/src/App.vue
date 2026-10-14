<!-- src/App.vue -->
<script setup>
// Простая шапка с логотипом-ссылкой на главную.
// RouterView ниже рендерит текущую страницу.
</script>

<template>
  <div class="app-shell">
    <header class="app-header">
      <RouterLink to="/" class="app-logo">
        <span class="app-logo-mark">KHL</span>
        <span class="app-logo-text">Analyzer</span>
      </RouterLink>
    </header>

    <main class="app-main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.app-header {
  border-bottom: 1px solid var(--khl-border);
  background: var(--khl-surface);
  padding: 0.9rem 1.25rem;
}

.app-logo {
  display: inline-flex;
  align-items: baseline;
  gap: 0.4rem;
  text-decoration: none;
}
.app-logo-mark {
  font-weight: 800;
  letter-spacing: 0.05em;
  color: var(--khl-accent);
}
.app-logo-text {
  font-weight: 500;
  color: var(--khl-text-muted);
}

.app-main {
  flex: 1;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 1.5rem 1rem 3rem;
}
</style>