<!-- src/components/ConferenceTable.vue -->
<script setup>
import ProbabilityBar from './ProbabilityBar.vue'

defineProps({
  title: { type: String, required: true },
  rows:  { type: Array,  required: true },
})
</script>

<template>
  <section class="conference">
    <h2 class="title is-5 has-text-light">{{ title }}</h2>

    <div class="table-wrap">
      <table class="table is-fullwidth is-hoverable">
        <thead>
          <tr>
            <th class="col-pos">#</th>
            <th class="col-team">Команда</th>
            <th class="col-num">И</th>
            <th class="col-num">О</th>
            <th class="col-goals">Шайбы</th>
            <th class="col-num">Шанс</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, index) in rows"
            :key="row.team.id"
            class="row-clickable"
            @click="$router.push(`/team/${row.team.id}`)"
          >
            <td class="col-pos has-text-grey-light">{{ index + 1 }}</td>

            <td class="col-team">
              <div class="team-cell">
                <img
                  v-if="row.team.logo_url"
                  :src="row.team.logo_url"
                  :alt="row.team.name"
                  class="team-logo"
                  loading="lazy"
                />
                <span class="team-name">{{ row.team.name }}</span>
              </div>
            </td>

            <td class="col-num">{{ row.games_played }}</td>
            <td class="col-num has-text-weight-semibold">{{ row.points }}</td>
            <td class="col-goals">{{ row.goals_for }}/{{ row.goals_against }}</td>
            <td class="col-num">
              <ProbabilityBar :value="row.playoff_probability" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<style scoped>
.conference + .conference {
  margin-top: 2rem;
}

.table-wrap {
  overflow-x: auto;
  border: 1px solid var(--khl-border);
  border-radius: 6px;
}

/* Тёмная тема для таблицы.
   Bulma 1.x берёт цвет текста и выравнивание из CSS-переменных,
   поэтому переопределяем ИМЕННО переменные, а не свойства —
   иначе наши правила перебиваются переменными Bulma. */
.table {
  background: var(--khl-surface);
  margin: 0;

  /* Обычный текст в ячейках — мягкий светлый, не почти-белый. */
  --bulma-table-color: #cdd2da;
  /* Цвет текста шапки. */
  --bulma-table-head-cell-color: var(--khl-text-muted);
  /* Шапка — на тон светлее поверхности. */
  --bulma-table-head-background-color: #232732;
  /* Hover-фон строки. */
  --bulma-table-row-hover-background-color: #232732;
  /* Линии. */
  --bulma-table-cell-border-color: var(--khl-border);
  --bulma-table-head-cell-border-color: var(--khl-border);
}

.table thead th {
  font-weight: 500;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

/* Hover не должен «вспыхивать» — держим тот же цвет текста. */
.table tbody tr:hover td {
  color: #cdd2da;
}

/* Точечное выравнивание заголовков.
   У th Bulma использует `text-align: var(--bulma-table-cell-text-align)`,
   а селектор `th:not([align])` имеет специфичность выше класса.
   Поэтому: (а) переопределяем переменную прямо на th, (б) дублируем
   text-align — на случай, если Bulma когда-нибудь сменит подход. */

th.col-num,
td.col-num {
  --bulma-table-cell-text-align: right;
  width: 5rem;
  text-align: right;
}

th.col-goals,
td.col-goals {
  --bulma-table-cell-text-align: right;
  width: 7rem;
  text-align: right;
  font-variant-numeric: tabular-nums;
}

/* # — по центру в обеих строках (шапка/тело). */
th.col-pos,
td.col-pos {
  --bulma-table-cell-text-align: center;
  width: 3rem;
  text-align: center;
}

/* «Команда» — по левому краю, как и было. */
th.col-team,
td.col-team {
  --bulma-table-cell-text-align: left;
  min-width: 220px;
  text-align: left;
}

.row-clickable { cursor: pointer; }

.team-cell {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.team-logo {
  width: 28px;
  height: 28px;
  object-fit: contain;
  flex: 0 0 auto;
}
.team-name {
  font-weight: 500;
}

</style>