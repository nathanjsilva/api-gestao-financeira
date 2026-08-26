<script setup>
import { computed } from 'vue'
import EmptyState from '../data-display/EmptyState.vue'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

const LABELS = { cash: 'À vista', installment: 'Parcelado' }

const series = computed(() => props.items.map((item) => Number(item.total || 0)))

const chartOptions = computed(() => ({
  chart: {
    type: 'donut',
    background: 'transparent',
    fontFamily: 'inherit',
    animations: { enabled: true, easing: 'easeinout', speed: 400 },
  },
  theme: { mode: 'dark' },
  colors: ['#38bdf8', '#a78bfa'],
  labels: props.items.map((item) => LABELS[item.payment_type] || item.payment_type),
  dataLabels: { enabled: false },
  plotOptions: {
    pie: { donut: { size: '65%' } },
  },
  legend: {
    position: 'bottom',
    labels: { colors: '#94a3b8' },
    markers: { size: 8, shape: 'circle' },
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
      <p class="text-sm font-bold uppercase text-sky-300">Forma de pagamento</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">À vista x parcelado</h2>
    </div>

    <EmptyState v-if="!items.length" title="Sem gastos no período" description="Cadastre compras para visualizar a comparação." />
    <apexchart v-else type="donut" height="260" :options="chartOptions" :series="series" />
  </section>
</template>
