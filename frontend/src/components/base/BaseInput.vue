<script setup>
import { computed, ref } from 'vue'

defineEmits(['update:modelValue'])

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  modelValue: {
    type: [String, Number],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
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

const isPasswordVisible = ref(false)
const isPasswordField = computed(() => props.type === 'password')
const inputType = computed(() => {
  if (!isPasswordField.value) {
    return props.type
  }

  return isPasswordVisible.value ? 'text' : 'password'
})

function togglePasswordVisibility() {
  isPasswordVisible.value = !isPasswordVisible.value
}
</script>

<template>
  <div>
    <label :for="id" class="mb-2 block text-sm font-semibold text-slate-200">
      {{ label }}
    </label>
    <div class="relative">
      <input
        :id="id"
        :type="inputType"
        :value="modelValue"
        :placeholder="placeholder"
        class="h-11 w-full rounded-md border bg-slate-950/70 px-3 text-sm text-slate-50 outline-none transition placeholder:text-slate-600 focus:border-sky-400"
        :class="[
          error ? 'border-rose-400/70' : 'border-white/10',
          isPasswordField ? 'pr-12' : '',
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
      >

      <button
        v-if="isPasswordField"
        type="button"
        class="absolute right-3 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-md p-1 text-slate-400 transition hover:bg-white/5 hover:text-slate-100"
        :aria-label="isPasswordVisible ? 'Esconder senha' : 'Mostrar senha'"
        @click="togglePasswordVisibility"
      >
        <svg
          v-if="!isPasswordVisible"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          class="size-5"
        >
          <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
          <circle cx="12" cy="12" r="3" />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          class="size-5"
        >
          <path d="M3 3l18 18" />
          <path d="M10.6 10.6A3 3 0 0 0 13.4 13.4" />
          <path d="M9.9 5.2A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a18.7 18.7 0 0 1-3.2 4.4" />
          <path d="M6.2 6.5C3.5 8.3 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 5-1.2" />
        </svg>
      </button>
    </div>
    <p v-if="error" class="mt-2 text-sm text-rose-300">{{ error }}</p>
  </div>
</template>
