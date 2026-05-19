<script setup>
import MetricCard from '../../components/data-display/MetricCard.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
</script>

<template>
  <section class="mx-auto max-w-6xl px-6 py-10">
    <PageHeader
      eyebrow="Visao geral"
      title="Dashboard"
      description="Aqui vamos exibir cards, graficos e comparativos financeiros."
    />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <MetricCard label="Entradas" value="R$ 0,00" tone="positive" />
      <MetricCard label="Gastos" value="R$ 0,00" tone="negative" />
      <MetricCard label="Saldo restante" value="R$ 0,00" tone="info" />
      <MetricCard label="Total guardado" value="R$ 0,00" />
    </div>
  </section>
</template>
