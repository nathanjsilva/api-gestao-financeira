<script setup>
import { computed } from 'vue'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})

const maxValue = computed(() => Math.max(...props.items.map((item) => Number(item.total_saved || 0)), 1))

function point(index, item) {
  const x = props.items.length <= 1 ? 0 : (index / (props.items.length - 1)) * 100
  const y = 100 - ((Number(item.total_saved || 0) / maxValue.value) * 82 + 8)

  return `${x},${y}`
}

const linePoints = computed(() => props.items.map((item, index) => point(index, item)).join(' '))
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Patrimonio</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Evolucao da reserva</h2>
    </div>

    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-56 w-full overflow-visible">
      <polyline :points="linePoints" fill="none" stroke="#38bdf8" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
      <circle
        v-for="(item, index) in items"
        :key="item.competency"
        :cx="point(index, item).split(',')[0]"
        :cy="point(index, item).split(',')[1]"
        r="2.5"
        fill="#f8fafc"
      />
    </svg>

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
      <div v-for="item in items.slice(-3)" :key="item.competency" class="rounded-2xl bg-white/[0.04] p-4">
        <p class="text-sm text-slate-400">{{ item.competency }}</p>
        <strong class="mt-1 block text-slate-50">{{ formatCurrency(item.total_saved) }}</strong>
      </div>
    </div>
  </section>
</template>

