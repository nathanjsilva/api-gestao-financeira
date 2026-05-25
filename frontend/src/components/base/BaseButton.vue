<script setup>
defineProps({
  type: {
    type: String,
    default: 'button',
  },
  variant: {
    type: String,
    default: 'primary',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex h-12 items-center justify-center rounded-2xl px-4 text-sm font-bold transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
    :class="{
      'bg-sky-400 text-slate-950 hover:bg-sky-300': variant === 'primary',
      'border border-white/10 bg-white/5 text-slate-100 hover:bg-white/10': variant === 'secondary',
      'bg-rose-500/15 text-rose-200 hover:bg-rose-500/25': variant === 'danger',
    }"
  >
    <span v-if="loading">Carregando...</span>
    <slot v-else />
  </button>
</template>
