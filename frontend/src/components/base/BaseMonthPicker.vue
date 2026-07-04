<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  label:      { type: String, default: 'Competência' },
  error:      { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const MONTHS = [
  { label: 'Janeiro',   value: 1 },
  { label: 'Fevereiro', value: 2 },
  { label: 'Março',     value: 3 },
  { label: 'Abril',     value: 4 },
  { label: 'Maio',      value: 5 },
  { label: 'Junho',     value: 6 },
  { label: 'Julho',     value: 7 },
  { label: 'Agosto',    value: 8 },
  { label: 'Setembro',  value: 9 },
  { label: 'Outubro',   value: 10 },
  { label: 'Novembro',  value: 11 },
  { label: 'Dezembro',  value: 12 },
]

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 6 }, (_, i) => currentYear - 3 + i)

const selectedMonth = computed(() =>
  props.modelValue ? Number(props.modelValue.split('-')[1]) : new Date().getMonth() + 1,
)

const selectedYear = computed(() =>
  props.modelValue ? Number(props.modelValue.split('-')[0]) : currentYear,
)

function emit_value(month, year) {
  emit('update:modelValue', `${year}-${String(month).padStart(2, '0')}`)
}
</script>

<template>
  <div>
    <label class="mb-1.5 block text-sm font-medium text-slate-300">{{ label }}</label>

    <div class="flex items-center gap-2">
      <select
        class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-slate-950/70 px-3 py-3 text-sm text-slate-100 outline-none transition focus:border-sky-400/50 focus:ring-1 focus:ring-sky-400/30"
        :class="error ? 'border-rose-400/50' : ''"
        :value="selectedMonth"
        @change="emit_value(Number($event.target.value), selectedYear)"
      >
        <option v-for="m in MONTHS" :key="m.value" :value="m.value">{{ m.label }}</option>
      </select>

      <span class="shrink-0 text-sm text-slate-500">—</span>

      <select
        class="w-24 rounded-2xl border border-white/10 bg-slate-950/70 px-3 py-3 text-sm text-slate-100 outline-none transition focus:border-sky-400/50 focus:ring-1 focus:ring-sky-400/30"
        :class="error ? 'border-rose-400/50' : ''"
        :value="selectedYear"
        @change="emit_value(selectedMonth, Number($event.target.value))"
      >
        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
      </select>
    </div>

    <p v-if="error" class="mt-1.5 text-xs text-rose-400">{{ error }}</p>
  </div>
</template>
