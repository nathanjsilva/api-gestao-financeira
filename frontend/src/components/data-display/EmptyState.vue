<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div class="rounded-lg border border-dashed border-white/10 bg-white/[0.03] p-8 text-center">
    <p class="text-lg font-bold text-slate-100">{{ title }}</p>
    <p v-if="description" class="mt-2 text-sm leading-6 text-slate-400">
      {{ description }}
    </p>
  </div>
</template>
