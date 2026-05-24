<script setup>
defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: String,
    required: true,
  },
  hint: {
    type: String,
    default: '',
  },
  trend: {
    type: String,
    default: 'neutral',
  },
  accent: {
    type: String,
    default: 'sky',
  },
})
</script>

<template>
  <article class="financial-card group" :class="`financial-card--${accent}`">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-sm font-semibold text-slate-400">{{ label }}</p>
        <strong class="mt-3 block text-2xl font-black text-slate-50 md:text-3xl">
          {{ value }}
        </strong>
      </div>
      <span class="financial-card__orb" />
    </div>

    <p v-if="hint" class="mt-4 text-sm leading-6 text-slate-400">
      {{ hint }}
    </p>

    <p
      v-if="trend !== 'neutral'"
      class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-bold"
      :class="{
        'bg-emerald-400/10 text-emerald-200': trend === 'up',
        'bg-rose-400/10 text-rose-200': trend === 'down',
      }"
    >
      {{ trend === 'up' ? 'Tendência positiva' : 'Ponto de atenção' }}
    </p>
  </article>
</template>
