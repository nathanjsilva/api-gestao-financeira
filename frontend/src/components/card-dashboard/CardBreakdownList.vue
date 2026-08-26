<script setup>
import { computed } from 'vue'
import EmptyState from '../data-display/EmptyState.vue'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  items: { type: Array, default: () => [] },
})

const biggestTotal = computed(() => Math.max(...props.items.map((item) => Number(item.total || 0)), 1))

function barWidth(total) {
  return `${Math.max((Number(total || 0) / biggestTotal.value) * 100, 5)}%`
}
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">{{ title }}</p>
      <h2 v-if="subtitle" class="mt-2 text-2xl font-black text-slate-50">{{ subtitle }}</h2>
    </div>

    <EmptyState v-if="!items.length" title="Sem gastos no período" description="Cadastre compras para visualizar este ranking." />

    <div v-else class="space-y-3">
      <div v-for="item in items" :key="item.label" class="rounded-2xl bg-white/[0.04] p-4">
        <div class="flex items-center justify-between gap-3">
          <strong class="break-words text-slate-100">{{ item.label }}</strong>
          <span class="shrink-0 text-sm text-slate-400">{{ formatCurrency(item.total) }}</span>
        </div>
        <div class="mt-3 h-2 rounded-full bg-slate-800">
          <div class="h-full rounded-full bg-gradient-to-r from-sky-300 to-violet-300" :style="{ width: barWidth(item.total) }" />
        </div>
      </div>
    </div>
  </section>
</template>
