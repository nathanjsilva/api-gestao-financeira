<script setup>
defineProps({
  insights: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Inteligencia</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Insights automaticos</h2>
    </div>

    <div class="space-y-3">
      <article
        v-for="insight in insights"
        :key="`${insight.title}-${insight.description}`"
        class="rounded-2xl border p-4"
        :class="{
          'border-emerald-400/20 bg-emerald-400/10': insight.type === 'success',
          'border-rose-400/20 bg-rose-400/10': insight.type === 'danger',
          'border-amber-400/20 bg-amber-400/10': insight.type === 'warning',
          'border-sky-400/20 bg-sky-400/10': insight.type === 'info',
        }"
      >
        <strong class="text-slate-50">{{ insight.title }}</strong>
        <p class="mt-2 text-sm leading-6 text-slate-300">{{ insight.description }}</p>
      </article>
    </div>
  </section>
</template>

