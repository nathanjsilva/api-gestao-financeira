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
      <p class="text-sm font-bold uppercase text-sky-300">Concentracao</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Heatmap financeiro</h2>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <div
        v-for="item in items"
        :key="item.label"
        class="rounded-2xl p-4 transition hover:-translate-y-1"
        :class="{
          'bg-rose-500/25 text-rose-50': item.tone === 'critical',
          'bg-orange-400/20 text-orange-50': item.tone === 'warning',
          'bg-amber-300/15 text-amber-50': item.tone === 'attention',
          'bg-emerald-400/10 text-emerald-50': item.tone === 'safe',
        }"
      >
        <strong class="block truncate">{{ item.label }}</strong>
        <span class="mt-2 block text-sm opacity-80">{{ item.value }}%</span>
      </div>
    </div>
  </section>
</template>

