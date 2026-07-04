<script setup>
import { computed } from 'vue'
import BaseButton from '../base/BaseButton.vue'
import BaseInput from '../base/BaseInput.vue'
import BaseMonthPicker from '../base/BaseMonthPicker.vue'
import BaseSelect from '../base/BaseSelect.vue'
import { TRANSACTION_STATUS, TRANSACTION_TYPES } from '../../constants/transactions'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  categoryOptions: {
    type: Array,
    required: true,
  },
  fieldError: {
    type: Function,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  submitLabel: {
    type: String,
    required: true,
  },
  showCancel: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['submit', 'cancel'])

const impactPreview = computed(() => {
  const amount = Number(props.form.amount || 0)

  return props.form.type === 'income' ? amount : -amount
})
</script>

<template>
  <form class="space-y-4" @submit.prevent="$emit('submit')">
    <div class="grid grid-cols-2 gap-2">
      <button
        v-for="type in TRANSACTION_TYPES"
        :key="type.value"
        type="button"
        class="rounded-2xl border p-3 text-left transition"
        :class="form.type === type.value ? 'border-sky-300 bg-sky-400/10 text-sky-100' : 'border-white/10 bg-white/[0.03] text-slate-300 hover:bg-white/[0.06]'"
        @click="form.type = type.value; form.category_id = ''"
      >
        <span class="block text-sm font-bold">{{ type.label }}</span>
        <span class="mt-1 block text-xs text-slate-400">{{ type.value === 'income' ? 'Aumenta o saldo' : 'Reduz o saldo' }}</span>
      </button>
    </div>

    <BaseSelect :id="`transaction-category-${form.type}`" v-model="form.category_id" label="Categoria" :options="categoryOptions" :error="fieldError('category_id')" />
    <BaseInput id="transaction-description" v-model="form.description" label="Descrição" placeholder="Ex: Supermercado" :error="fieldError('description')" />
    <BaseInput id="transaction-amount" v-model="form.amount" label="Valor" type="number" placeholder="Ex: 250.90" :error="fieldError('amount')" />

    <div class="grid gap-3">
      <BaseSelect id="transaction-status" v-model="form.status" label="Status" :options="TRANSACTION_STATUS" :error="fieldError('status')" />
      <BaseMonthPicker id="transaction-form-competency" v-model="form.competency" label="Competência" :error="fieldError('competency')" />
    </div>

    <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/70 p-3 text-sm text-slate-200">
      <input v-model="form.is_recurring" type="checkbox" class="size-4 accent-sky-400">
      Lançamento recorrente
    </label>

    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-3">
      <p class="text-xs font-bold uppercase text-sky-300">Impacto financeiro</p>
      <strong class="mt-1 block text-xl" :class="impactPreview >= 0 ? 'text-emerald-300' : 'text-rose-300'">
        {{ impactPreview >= 0 ? '+' : '-' }} {{ formatCurrency(Math.abs(impactPreview)) }}
      </strong>
    </div>

    <div class="grid gap-3">
      <BaseButton type="submit" class="w-full" :loading="isLoading">{{ submitLabel }}</BaseButton>
      <BaseButton v-if="showCancel" type="button" class="w-full" variant="secondary" @click="$emit('cancel')">Cancelar</BaseButton>
    </div>
  </form>
</template>
