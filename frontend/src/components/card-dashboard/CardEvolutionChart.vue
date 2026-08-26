<script setup>
import { computed } from 'vue'
import EmptyState from '../data-display/EmptyState.vue'
import { formatCompetencyLabel } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

function formatShort(val) {
  if (Math.abs(val) >= 1000) return `R$ ${(val / 1000).toFixed(1)}k`
  return `R$ ${Number(val).toFixed(0)}`
}

const series = computed(() => [
  { name: 'Gasto no cartão', data: props.items.map((item) => Number(item.total || 0)) },
])

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    background: 'transparent',
    fontFamily: 'inherit',
    toolbar: { show: false },
    animations: { enabled: true, easing: 'easeinout', speed: 400 },
  },
  theme: { mode: 'dark' },
  colors: ['#fda4af'],
  stroke: { curve: 'smooth', width: 3 },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0, stops: [0, 90, 100] },
  },
  dataLabels: { enabled: false },
  grid: {
    borderColor: 'rgba(148,163,184,0.08)',
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
  },
  xaxis: {
    categories: props.items.map((item) => formatCompetencyLabel(item.competency)),
    labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: { style: { colors: '#94a3b8', fontSize: '11px' }, formatter: formatShort },
  },
  tooltip: {
    theme: 'dark',
    y: { formatter: (val) => formatCurrency(val) },
  },
}))
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Evolução</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Gasto de cartão mês a mês</h2>
      <p class="mt-3 text-sm leading-6 text-slate-400">
        Soma de todas as parcelas com vencimento em cada competência do período selecionado.
      </p>
    </div>

    <EmptyState v-if="!items.length" title="Sem dados no período" description="Cadastre compras para visualizar a evolução." />
    <apexchart v-else type="area" height="260" :options="chartOptions" :series="series" />
  </section>
</template>
