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
  { name: 'Total guardado', data: props.items.map((i) => Number(i.total_saved || 0)) },
  { name: 'Investimento',   data: props.items.map((i) => Number(i.investment || 0)) },
])

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    background: 'transparent',
    fontFamily: 'inherit',
    toolbar: {
      show: true,
      tools: { download: false, selection: false, zoom: true, zoomin: true, zoomout: true, pan: false, reset: true },
    },
    animations: { enabled: true, easing: 'easeinout', speed: 400 },
  },
  theme: { mode: 'dark' },
  colors: ['#38bdf8', '#a78bfa'],
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.25,
      opacityTo: 0.01,
      stops: [0, 100],
    },
  },
  dataLabels: { enabled: false },
  markers: {
    size: 4,
    strokeWidth: 0,
    hover: { size: 6 },
  },
  grid: {
    borderColor: 'rgba(148,163,184,0.08)',
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
  },
  xaxis: {
    categories: props.items.map((i) => formatCompetencyLabel(i.competency)),
    labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#94a3b8', fontSize: '11px' },
      formatter: formatShort,
    },
  },
  tooltip: {
    theme: 'dark',
    y: { formatter: (val) => formatCurrency(val) },
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    labels: { colors: '#cbd5e1' },
    markers: { size: 6, shape: 'circle' },
  },
}))
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Patrimônio</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Evolução da reserva</h2>
      <p class="mt-3 text-sm leading-6 text-slate-400">
        Acompanha se sua reserva e seus investimentos estão crescendo ou diminuindo ao longo dos meses.
      </p>
    </div>

    <EmptyState v-if="!items.length" title="Sem dados de reserva" description="Cadastre reservas mensais para visualizar a evolução." />
    <apexchart v-else type="area" height="280" :options="chartOptions" :series="series" />

    <p class="mt-5 rounded-2xl bg-white/4 p-4 text-sm leading-6 text-slate-400">
      Cálculo: reserva atual = reserva anterior + saldo restante do mês. Total guardado = reserva atual + investimento.
    </p>
  </section>
</template>
