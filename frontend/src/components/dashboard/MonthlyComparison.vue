<script setup>
import { formatCurrency } from '../../helpers/currency'
import { formatPercentage } from '../../helpers/percentage'

defineProps({
  comparison: {
    type: Object,
    default: () => ({}),
  },
})
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5 flex items-center justify-between gap-4">
      <div>
        <p class="text-sm font-bold uppercase text-sky-300">Comparacao mensal</p>
        <h2 class="mt-2 text-2xl font-black text-slate-50">Atual vs anterior</h2>
      </div>
      <span
        class="rounded-full px-3 py-1 text-xs font-bold"
        :class="comparison.trend === 'better' ? 'bg-emerald-400/10 text-emerald-200' : 'bg-rose-400/10 text-rose-200'"
      >
        {{ comparison.trend === 'better' ? 'Melhorou' : 'Atencao' }}
      </span>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
      <div class="rounded-2xl bg-white/[0.04] p-4">
        <p class="text-sm text-slate-400">Variacao de gastos</p>
        <strong class="mt-2 block text-xl text-slate-50">
          {{ formatCurrency(comparison.expense_difference) }}
        </strong>
        <span class="text-sm text-slate-400">{{ formatPercentage(comparison.expense_percentage) }}</span>
      </div>
      <div class="rounded-2xl bg-white/[0.04] p-4">
        <p class="text-sm text-slate-400">Variacao da reserva</p>
        <strong class="mt-2 block text-xl text-slate-50">
          {{ formatCurrency(comparison.reserve_difference) }}
        </strong>
        <span class="text-sm text-slate-400">comparado ao mes anterior</span>
      </div>
    </div>
  </section>
</template>

