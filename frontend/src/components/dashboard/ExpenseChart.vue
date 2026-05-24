<script setup>
import { computed } from 'vue'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})

const total = computed(() => props.categories.reduce((sum, item) => sum + Number(item.total || 0), 0))
const gradient = computed(() => {
  if (!total.value) {
    return 'conic-gradient(rgba(148, 163, 184, 0.25) 0 100%)'
  }

  const colors = ['#38bdf8', '#f97316', '#a78bfa', '#22c55e', '#f43f5e', '#eab308']
  let start = 0

  const parts = props.categories.slice(0, 6).map((item, index) => {
    const size = (Number(item.total || 0) / total.value) * 100
    const end = start + size
    const part = `${colors[index % colors.length]} ${start}% ${end}%`
    start = end

    return part
  })

  return `conic-gradient(${parts.join(', ')})`
})
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Distribuição</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Gastos por categoria</h2>
      <p class="mt-3 text-sm leading-6 text-slate-400">
        Mostra a participação de cada categoria no total gasto. Ajuda a enxergar para onde o dinheiro está indo.
      </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[220px_1fr] lg:items-center">
      <div class="relative mx-auto size-52 rounded-full shadow-2xl shadow-sky-950/40" :style="{ background: gradient }">
        <div class="absolute inset-8 grid place-items-center rounded-full bg-slate-950 text-center">
          <span class="text-sm text-slate-400">Total</span>
          <strong class="text-lg text-slate-50">{{ formatCurrency(total) }}</strong>
        </div>
      </div>

      <div class="space-y-3">
        <div v-for="item in categories.slice(0, 6)" :key="item.category_id" class="rounded-2xl bg-white/[0.04] p-4">
          <div class="flex items-center justify-between gap-4">
            <span class="break-words font-semibold leading-5 text-slate-100">{{ item.category_name }}</span>
            <span class="shrink-0 text-sm text-slate-400">{{ item.percentage_of_expenses }}%</span>
          </div>
          <div class="mt-3 h-2 rounded-full bg-slate-800">
            <div class="h-full rounded-full bg-sky-300" :style="{ width: `${item.percentage_of_expenses}%` }" />
          </div>
        </div>
      </div>
    </div>

    <p class="mt-5 rounded-2xl bg-white/[0.04] p-4 text-sm leading-6 text-slate-400">
      Cálculo: somamos todos os gastos do mês e depois calculamos a porcentagem de cada categoria sobre esse total.
    </p>
  </section>
</template>
