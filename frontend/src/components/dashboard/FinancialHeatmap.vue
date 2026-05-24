<script setup>
defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Concentração</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Heatmap financeiro</h2>
      <p class="mt-3 text-sm leading-6 text-slate-400">
        Mostra quanto cada categoria representa dentro do total de gastos do mês.
        Quanto maior a porcentagem, maior a concentração do seu dinheiro ali.
      </p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-3">
      <div
        v-for="item in items"
        :key="item.label"
        class="min-h-28 rounded-2xl p-4 transition hover:-translate-y-1"
        :class="{
          'bg-rose-500/25 text-rose-50': item.tone === 'critical',
          'bg-orange-400/20 text-orange-50': item.tone === 'warning',
          'bg-amber-300/15 text-amber-50': item.tone === 'attention',
          'bg-emerald-400/10 text-emerald-50': item.tone === 'safe',
        }"
      >
        <strong class="block break-words text-sm leading-5 sm:text-base">
          {{ item.label }}
        </strong>
        <span class="mt-3 block text-sm font-semibold opacity-80">{{ item.value }}% dos gastos</span>
      </div>
    </div>

    <p class="mt-5 rounded-2xl bg-white/[0.04] p-4 text-sm leading-6 text-slate-400">
      Cálculo: valor gasto na categoria dividido pelo total de gastos do mês, multiplicado por 100.
    </p>
  </section>
</template>
