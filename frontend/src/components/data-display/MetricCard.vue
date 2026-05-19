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
  tone: {
    type: String,
    default: 'neutral',
  },
})
</script>

<template>
  <article class="rounded-lg border border-white/10 bg-white/[0.04] p-5">
    <p class="text-sm font-semibold text-slate-400">{{ label }}</p>
    <strong
      class="mt-3 block text-3xl font-black"
      :class="{
        'text-slate-50': tone === 'neutral',
        'text-emerald-300': tone === 'positive',
        'text-rose-300': tone === 'negative',
        'text-sky-300': tone === 'info',
      }"
    >
      {{ value }}
    </strong>
    <p v-if="hint" class="mt-3 text-sm text-slate-500">{{ hint }}</p>
  </article>
</template>
