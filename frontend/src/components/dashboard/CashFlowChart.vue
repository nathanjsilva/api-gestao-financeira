<script setup lang="ts">
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
  { name: 'Entradas realizadas', data: props.items.map((i) => Number(i.income || 0)) },
  { name: 'Gastos realizados', data: props.items.map((i) => Number(i.expense || 0)) },
  { name: 'Entradas pendentes', data: props.items.map((i) => Number(i.pending_income || 0)) },
  { name: 'Gastos pendentes', data: props.items.map((i) => Number(i.pending_expense || 0)) },
])

const chartOptions = computed(() => ({
  chart: {
    type: 'bar',
    background: 'transparent',
    fontFamily: 'inherit',
    toolbar: {
      show: true,
      tools: { download: false, selection: false, zoom: true, zoomin: true, zoomout: true, pan: false, reset: true },
    },
    animations: { enabled: true, easing: 'easeinout', speed: 400 },
  },
  theme: { mode: 'dark' },
  colors: ['#6ee7b7', '#fda4af', '#34d399', '#fb7185'],
  plotOptions: {
    bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' },
  },
  dataLabels: { enabled: false },
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
      <p class="text-sm font-bold uppercase text-sky-300">Fluxo do dinheiro</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Entradas vs gastos</h2>
      <p class="mt-3 text-sm leading-6 text-slate-400">Separa receitas e despesas realizadas das que ainda estão pendentes.</p>
    </div>

    <EmptyState v-if="!items.length" title="Sem dados de fluxo de caixa" description="Registre transaÃ§Ãµes para visualizar o grÃ¡fico." />
    <apexchart v-else type="bar" height="280" :options="chartOptions" :series="series" />

    <p class="mt-5 rounded-2xl bg-white/4 p-4 text-sm leading-6 text-slate-400">O resultado realizado considera apenas transações pagas. O projetado inclui as pendentes sem incorporá-las ao patrimônio.</p>
  </section>
</template>
