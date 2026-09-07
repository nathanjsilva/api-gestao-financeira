<script setup>
defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
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
  <header class="mb-8">
    <p v-if="eyebrow" class="mb-3 text-sm font-bold uppercase text-sky-300">
      {{ eyebrow }}
    </p>
    <div class="flex flex-col gap-4 @min-[48rem]:flex-row @min-[48rem]:items-end @min-[48rem]:justify-between">
      <div>
        <h1 class="text-4xl font-black leading-none text-slate-50 @min-[48rem]:text-5xl">
          {{ title }}
        </h1>
        <p v-if="description" class="mt-4 max-w-2xl text-base leading-7 text-slate-300">
          {{ description }}
        </p>
      </div>
      <slot name="actions" />
    </div>
  </header>
</template>
