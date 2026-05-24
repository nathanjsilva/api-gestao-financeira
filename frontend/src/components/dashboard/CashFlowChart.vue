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
    </div>

    <div class="flex h-72 items-end gap-4 overflow-x-auto rounded-3xl bg-slate-950/60 p-5">
      <div v-for="item in items" :key="item.competency" class="flex min-w-24 flex-1 flex-col items-center justify-end gap-3">
        <div class="flex h-48 w-full items-end justify-center gap-2">
          <span class="w-5 rounded-t-full bg-emerald-300" :style="{ height: height(item.income) }" :title="formatCurrency(item.income)" />
          <span class="w-5 rounded-t-full bg-rose-300" :style="{ height: height(item.expense) }" :title="formatCurrency(item.expense)" />
        </div>
        <span class="text-xs font-bold text-slate-400">{{ item.competency }}</span>
      </div>
    </div>
  </section>
</template>
