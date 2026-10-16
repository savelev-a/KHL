<!-- src/views/TeamView.vue -->
<script setup>
import { computed, toRef } from 'vue'
import { useRoute } from 'vue-router'
import { useTeam } from '../composables/useTeam'
import EloChart from '../components/EloChart.vue'

const route = useRoute()
// route.params.id — строка из URL.
// toRef нужен, чтобы useTeam перезагружал данные при смене id.
const idRef = toRef(route.params, 'id')

const { team, loading, error } = useTeam(idRef)

const eloHistory = computed(() => team.value?.elo_history ?? [])

// Небольшая утилита: «матчей» / «матч» / «матча» — но для наших чисел
// всегда «матчей» (не бывает 1 матча в истории, минимум 1-2).
// Пока хардкодим «матчей» — этого достаточно.
</script>

<template>
  <section>
    <!-- Навигация назад -->
    <RouterLink to="/" class="back-link">← К таблице</RouterLink>

    <div v-if="loading && !team" class="has-text-grey-light mt-4">Загрузка…</div>

    <div v-else-if="error" class="notification is-danger is-light mt-4">
      {{ error }}
    </div>

    <template v-else-if="team">
      <!-- Шапка команды: лого + название + город + конференция/дивизион -->
      <header class="team-header">
        <img
          v-if="team.logo_url"
          :src="team.logo_url"
          :alt="team.name"
          class="team-header-logo"
        />
        <div class="team-header-info">
          <h1 class="title is-4 has-text-light mb-1">{{ team.name }}</h1>
          <p class="team-header-meta">
            <span v-if="team.city">{{ team.city }}</span>
            <span v-if="team.city && team.conference_name">·</span>
            <span v-if="team.conference_name">{{ team.conference_name }}</span>
            <span v-if="team.conference_name && team.division_name">·</span>
            <span v-if="team.division_name">{{ team.division_name }}</span>
          </p>
        </div>

        <div class="team-header-elo">
          <div class="elo-value">{{ Number(team.elo_rating).toFixed(2) }}</div>
          <div class="elo-label">текущий Эло</div>
        </div>
      </header>

      <!-- Статистика сезона из standing -->
      <div v-if="team.standing" class="stats-grid">
        <div class="stat">
          <div class="stat-value">{{ team.standing.position }}</div>
          <div class="stat-label">место</div>
        </div>
        <div class="stat">
          <div class="stat-value">{{ team.standing.points }}</div>
          <div class="stat-label">очки</div>
        </div>
        <div class="stat">
          <div class="stat-value">{{ team.standing.games_played }}</div>
          <div class="stat-label">игр</div>
        </div>
        <div class="stat">
          <div class="stat-value">{{ team.standing.wins }}</div>
          <div class="stat-label">побед в осн.</div>
        </div>
        <div class="stat">
          <div class="stat-value">{{ team.standing.ot_wins }}</div>
          <div class="stat-label">побед в ОТ/б</div>
        </div>
        <div class="stat">
          <div class="stat-value">{{ team.standing.losses }}</div>
          <div class="stat-label">поражений</div>
        </div>
        <div class="stat">
          <div class="stat-value">
            {{ team.standing.goals_for }}/{{ team.standing.goals_against }}
          </div>
          <div class="stat-label">шайбы</div>
        </div>
        <div class="stat">
          <div class="stat-value">
            {{ (team.standing.playoff_probability * 100).toFixed(1) }}%
          </div>
          <div class="stat-label">шанс плей-офф</div>
        </div>
      </div>

      <!-- График Эло -->
      <h2 class="title is-5 has-text-light mt-5">Динамика Эло</h2>
      <p class="has-text-grey-light mb-3 elo-hint">
        По горизонтали — порядковый номер матча, по вертикали — рейтинг после матча.
      </p>
      <EloChart :history="eloHistory" />
    </template>
  </section>
</template>

<style scoped>
.back-link {
  display: inline-block;
  font-size: 0.9rem;
  color: var(--khl-text-muted);
}
.back-link:hover {
  color: var(--khl-accent);
}

.team-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  margin-top: 0.5rem;
  background: var(--khl-surface);
  border: 1px solid var(--khl-border);
  border-radius: 6px;
}
.team-header-logo {
  width: 64px;
  height: 64px;
  object-fit: contain;
  flex: 0 0 auto;
}
.team-header-info {
  flex: 1;
  min-width: 0;
}
.team-header-meta {
  color: var(--khl-text-muted);
  font-size: 0.9rem;
  margin: 0;
}
.team-header-elo {
  text-align: right;
  flex: 0 0 auto;
}
.elo-value {
  font-size: 1.6rem;
  font-weight: 600;
  color: var(--khl-accent);
  font-variant-numeric: tabular-nums;
}
.elo-label {
  font-size: 0.75rem;
  color: var(--khl-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
  gap: 0.75rem;
  margin-top: 1rem;
}
.stat {
  background: var(--khl-surface);
  border: 1px solid var(--khl-border);
  border-radius: 6px;
  padding: 0.75rem 0.9rem;
  text-align: center;
}
.stat-value {
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--khl-text);
  font-variant-numeric: tabular-nums;
}
.stat-label {
  font-size: 0.72rem;
  color: var(--khl-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 0.2rem;
}

.elo-hint {
  font-size: 0.85rem;
}
</style>