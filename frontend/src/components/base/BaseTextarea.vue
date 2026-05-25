<script setup>
defineProps({
  id: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div>
    <label :for="id" class="mb-2 block text-sm font-semibold text-slate-200">
      {{ label }}
    </label>
    <textarea
      :id="id"
      :value="modelValue"
      :placeholder="placeholder"
      rows="4"
      class="w-full rounded-2xl border bg-slate-950/70 px-4 py-3 text-sm text-slate-50 outline-none transition placeholder:text-slate-600 focus:border-sky-400 focus:ring-4 focus:ring-sky-400/10"
      :class="error ? 'border-rose-400/70' : 'border-white/10'"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="mt-2 text-sm text-rose-300">{{ error }}</p>
  </div>
</template>
