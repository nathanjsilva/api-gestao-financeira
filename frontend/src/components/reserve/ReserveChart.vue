<script setup>
import { computed } from 'vue'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})

const orderedItems = computed(() => [...props.items].reverse())
const maxValue = computed(() => Math.max(...orderedItems.value.map((item) => Number(item.total_saved || 0)), 1))

function barHeight(value) {
  return `${Math.max((Number(value || 0) / maxValue.value) * 100, 8)}%`
}
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Evolução patrimonial</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Reserva e investimentos</h2>
      <p class="mt-3 text-sm leading-6 text-slate-400">
        Compara o total guardado e os investimentos registrados em cada competência.
      </p>
    </div>

    <div class="responsive-chart-frame flex h-64 items-end gap-2 rounded-3xl bg-slate-950/60 p-3 sm:h-72 sm:gap-4 sm:p-5">
      <div
        v-for="item in orderedItems"
        :key="item.id"
        class="flex min-w-0 flex-1 basis-0 flex-col items-center justify-end gap-3"
      >
        <div class="flex h-40 w-full min-w-0 items-end justify-center gap-1.5 sm:h-48 sm:gap-2">
          <span
            class="w-3 rounded-t-full bg-sky-300 sm:w-5"
            :style="{ height: barHeight(item.total_saved) }"
            :title="formatCurrency(item.total_saved)"
          />
          <span
            class="w-3 rounded-t-full bg-emerald-300 sm:w-5"
            :style="{ height: barHeight(item.investimento) }"
            :title="formatCurrency(item.investimento)"
          />
        </div>
        <span class="max-w-full truncate text-[10px] font-bold text-slate-400 sm:text-xs">{{ item.competency }}</span>
      </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-3 text-sm text-slate-400">
      <span class="inline-flex items-center gap-2"><i class="size-3 rounded-full bg-sky-300" /> Total guardado</span>
      <span class="inline-flex items-center gap-2"><i class="size-3 rounded-full bg-emerald-300" /> Investimentos</span>
    </div>
  </section>
</template>
