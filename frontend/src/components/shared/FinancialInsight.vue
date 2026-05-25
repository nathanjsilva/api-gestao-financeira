<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  tone: {
    type: String,
    default: 'info',
  },
})
</script>

<template>
  <article
    class="rounded-2xl border p-4"
    :class="{
      'border-sky-400/20 bg-sky-400/10 text-sky-50': tone === 'info',
      'border-emerald-400/20 bg-emerald-400/10 text-emerald-50': tone === 'success',
      'border-amber-400/20 bg-amber-400/10 text-amber-50': tone === 'warning',
      'border-rose-400/20 bg-rose-400/10 text-rose-50': tone === 'danger',
    }"
  >
    <strong class="block text-sm font-black">{{ title }}</strong>
    <p class="mt-2 text-sm leading-6 opacity-85">{{ description }}</p>
  </article>
</template>

