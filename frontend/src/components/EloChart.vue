<!-- src/components/EloChart.vue -->
<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import {
  Chart,
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  CategoryScale,
  Tooltip,
  Filler,
} from 'chart.js'

// Регистрируем ТОЛЬКО нужные модули Chart.js.
// Tree-shaking: бандл будет существенно меньше,
// чем при import Chart from 'chart.js/auto'.
Chart.register(
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  CategoryScale,
  Tooltip,
  Filler,
)

const props = defineProps({
  // elo_history: [{ match_id, rating_before, rating_after, created_at }]
  history: { type: Array, required: true },
})

const canvasRef = ref(null)
let chart = null

// Собираем точки графика из истории.
// Ось X — порядковый номер матча (проще, чем даты: они у КХЛ неровные).
// Ось Y — рейтинг ПОСЛЕ матча (rating_after).
// Дополнительно подсунем rating_before как вторую серию —
// она показывает «откуда пришли», полезно визуально.
function buildData(history) {
  return {
    labels: history.map((_, i) => i + 1),
    datasets: [
      {
        label: 'Эло после матча',
        data: history.map((h) => Number(h.rating_after)),
        borderColor: '#4a9eff',
        backgroundColor: 'rgba(74, 158, 255, 0.12)',
        fill: true,
        tension: 0.25,
        pointRadius: 0,        // точки убираем — на 100+ матчах они мешают
        pointHoverRadius: 4,
        borderWidth: 2,
      },
    ],
  }
}

function render() {
  if (!canvasRef.value) return
  if (chart) chart.destroy()
  if (!props.history.length) return

  chart = new Chart(canvasRef.value, {
    type: 'line',
    data: buildData(props.history),
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          // В тултипе показываем и номер матча, и дату, и точное значение.
          callbacks: {
            title: (items) => {
              const i = items[0].dataIndex
              const h = props.history[i]
              const d = new Date(h.created_at)
              return `Матч #${i + 1} · ${d.toLocaleDateString('ru-RU')}`
            },
            label: (item) => ` Эло: ${item.parsed.y.toFixed(2)}`,
          },
        },
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: { color: '#9aa0aa', maxTicksLimit: 10 },
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: { color: '#9aa0aa' },
        },
      },
    },
  })
}

// Перерисовываем при изменении history.
// (watch вместо onMounted — на случай если данные придут позже,
//  что у нас и происходит: сначала loading, потом данные.)
watch(() => props.history, render, { immediate: false })

onMounted(render)

onUnmounted(() => {
  if (chart) {
    chart.destroy()
    chart = null
  }
})
</script>

<template>
  <div class="elo-chart">
    <canvas ref="canvasRef"></canvas>
    <p v-if="!history.length" class="has-text-grey-light chart-empty">
      История Эло пока пуста — команда ещё не провела ни одного завершённого матча.
    </p>
  </div>
</template>

<style scoped>
.elo-chart {
  position: relative;
  width: 100%;
  height: 320px;
  background: var(--khl-surface);
  border: 1px solid var(--khl-border);
  border-radius: 6px;
  padding: 0.75rem;
}
.chart-empty {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
}
</style>