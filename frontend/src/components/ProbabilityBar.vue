<!-- src/components/ProbabilityBar.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Значение от 0 до 1 (как приходит из API).
  value: { type: Number, required: true },
})

const pct = computed(() => Math.round(props.value * 1000) / 10) // 1 знак после запятой

// Пороги согласованы с ТЗ:
//   >= 0.80  → зелёный
//   0.30–0.80 → жёлтый
//   < 0.30   → красный
const level = computed(() => {
  if (props.value >= 0.8) return 'high'
  if (props.value >= 0.3) return 'mid'
  return 'low'
})

const formatted = computed(() => pct.value.toFixed(1) + '%')
</script>

<template>
  <div class="prob">
    <div class="prob-track">
      <div
        class="prob-fill"
        :class="`prob-fill--${level}`"
        :style="{ width: Math.max(pct, 1.5) + '%' }"
        :title="formatted"
      ></div>
    </div>
    <span class="prob-value">{{ formatted }}</span>
  </div>
</template>

<style scoped>
.prob {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  justify-content: flex-end;
}

.prob-track {
  position: relative;
  width: 70px;
  height: 6px;
  background: #2a2e37;
  border-radius: 3px;
  overflow: hidden;
  flex: 0 0 auto;
}

.prob-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.25s ease;
}

.prob-fill--high { background: var(--khl-prob-high, #3ec46d); }
.prob-fill--mid  { background: var(--khl-prob-mid,  #e0b400); }
.prob-fill--low  { background: var(--khl-prob-low,  #e0483e); }

.prob-value {
  font-variant-numeric: tabular-nums;
  min-width: 3.2rem;
  text-align: right;
  color: #cdd2da;
}
</style>