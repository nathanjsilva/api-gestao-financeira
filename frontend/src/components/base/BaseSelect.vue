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
    type: [String, Number, Boolean],
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Selecione',
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
    <select
      :id="id"
      :value="modelValue"
      class="h-11 w-full rounded-md border bg-slate-950/70 px-3 text-sm text-slate-50 outline-none transition focus:border-sky-400"
      :class="error ? 'border-rose-400/70' : 'border-white/10'"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option value="">{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    <p v-if="error" class="mt-2 text-sm text-rose-300">{{ error }}</p>
  </div>
</template>

