<script setup>
import { computed } from 'vue'
import EmptyState from '../data-display/EmptyState.vue'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  categories: { type: Array, default: () => [] },
})

const COLORS = ['#38bdf8', '#f97316', '#a78bfa', '#22c55e', '#f43f5e', '#eab308']

const slice = computed(() => props.categories.slice(0, 6))

const series = computed(() => slice.value.map((c) => Number(c.total || 0)))

const chartOptions = computed(() => ({
  chart: {
    type: 'donut',
    background: 'transparent',
    fontFamily: 'inherit',
    animations: { enabled: true, easing: 'easeinout', speed: 400 },
  },
  theme: { mode: 'dark' },
  colors: COLORS,
  labels: slice.value.map((c) => c.category_name),
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total',
            color: '#94a3b8',
            fontSize: '13px',
            fontWeight: 400,
            formatter: (w) => {
              const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0)
              return formatCurrency(total)
            },
          },
          value: {
            color: '#f8fafc',
            fontSize: '18px',
            fontWeight: 700,
            formatter: (val) => formatCurrency(Number(val)),
          },
        },
      },
    },
  },
  legend: {
    position: 'bottom',
    labels: { colors: '#94a3b8' },
    markers: { size: 8, shape: 'circle' },
    itemMargin: { horizontal: 8, vertical: 4 },
  },
  tooltip: {
    theme: 'dark',
    y: {
      formatter: (val, { seriesIndex, w }) => {
        const pct = w.globals.seriesPercent[seriesIndex]?.[0]?.toFixed(1) ?? '0'
        return `${formatCurrency(val)} (${pct}%)`
      },
    },
  },
}))
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

    <EmptyState v-if="!categories.length" title="Sem gastos registrados" description="Registre transações de saída para visualizar a distribuição." />
    <apexchart v-else type="donut" height="320" :options="chartOptions" :series="series" />

    <p class="mt-5 rounded-2xl bg-white/4 p-4 text-sm leading-6 text-slate-400">
      Cálculo: somamos todos os gastos do mês e depois calculamos a porcentagem de cada categoria sobre esse total.
    </p>
  </section>
</template>
