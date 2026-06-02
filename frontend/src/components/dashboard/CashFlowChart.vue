<script setup>
import { computed } from 'vue'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})

const maxValue = computed(() => Math.max(
  ...props.items.flatMap((item) => [Number(item.income || 0), Number(item.expense || 0)]),
  1,
))

function height(value) {
  return `${Math.max((Number(value || 0) / maxValue.value) * 100, 4)}%`
}
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Fluxo do dinheiro</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Entradas vs gastos</h2>
      <p class="mt-3 text-sm leading-6 text-slate-400">
        Compara, mês a mês, quanto entrou e quanto saiu. Verde representa entradas e rosa representa gastos.
      </p>
    </div>

    <div class="responsive-chart-frame flex h-64 items-end gap-2 rounded-3xl bg-slate-950/60 p-3 sm:h-72 sm:gap-4 sm:p-5">
      <div v-for="item in items" :key="item.competency" class="flex min-w-0 flex-1 basis-0 flex-col items-center justify-end gap-3">
        <div class="flex h-40 w-full min-w-0 items-end justify-center gap-1.5 sm:h-48 sm:gap-2">
          <span class="w-3 rounded-t-full bg-emerald-300 sm:w-5" :style="{ height: height(item.income) }" :title="formatCurrency(item.income)" />
          <span class="w-3 rounded-t-full bg-rose-300 sm:w-5" :style="{ height: height(item.expense) }" :title="formatCurrency(item.expense)" />
        </div>
        <span class="max-w-full truncate text-[10px] font-bold text-slate-400 sm:text-xs">{{ item.competency }}</span>
      </div>
    </div>

    <p class="mt-5 rounded-2xl bg-white/[0.04] p-4 text-sm leading-6 text-slate-400">
      Cálculo: para cada competência, somamos todas as transações de entrada e todas as transações de gasto.
      A diferença entre elas forma o saldo restante do mês.
    </p>
  </section>
</template>
