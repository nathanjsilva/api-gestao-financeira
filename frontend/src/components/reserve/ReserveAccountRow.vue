<script setup lang="ts">
import { computed } from 'vue'
import BaseButton from '../base/BaseButton.vue'
import BaseInput from '../base/BaseInput.vue'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  account: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:modelValue', 'archive'])

const delta = computed(() => props.account.delta)
const hasDelta = computed(() => delta.value !== null && delta.value !== undefined && Number(delta.value) !== 0)

function updateBalance(value) {
  emit('update:modelValue', { ...props.modelValue, balance: value })
}

function updateName(value) {
  emit('update:modelValue', { ...props.modelValue, name: value })
}

function updateNote(value) {
  emit('update:modelValue', { ...props.modelValue, note: value })
}
</script>

<template>
  <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="truncate text-sm font-bold text-slate-100">{{ account.name }}</p>
        <p v-if="account.is_inherited" class="text-xs text-slate-500">Herdado do mês anterior</p>
      </div>
      <span
        v-if="hasDelta"
        class="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold"
        :class="Number(delta) > 0 ? 'bg-emerald-400/10 text-emerald-300' : 'bg-rose-400/10 text-rose-300'"
      >
        {{ Number(delta) > 0 ? '+' : '' }}{{ formatCurrency(delta) }}
      </span>
    </div>

    <div class="mt-3 grid gap-2 @min-[40rem]:grid-cols-2">
      <BaseInput
        :id="`reserve-account-${account.id}-name`"
        label="Nome da conta"
        :model-value="modelValue.name"
        @update:model-value="updateName"
      />
      <BaseInput
        :id="`reserve-account-${account.id}-balance`"
        label="Saldo do mês"
        type="number"
        :model-value="modelValue.balance"
        @update:model-value="updateBalance"
      />
      <BaseInput
        :id="`reserve-account-${account.id}-note`"
        label="Nota (opcional)"
        :model-value="modelValue.note"
        placeholder="Ex: retirada para conserto do carro"
        @update:model-value="updateNote"
      />
    </div>

    <div class="mt-3 flex justify-end">
      <BaseButton type="button" variant="secondary" @click="$emit('archive')">Arquivar conta</BaseButton>
    </div>
  </div>
</template>
