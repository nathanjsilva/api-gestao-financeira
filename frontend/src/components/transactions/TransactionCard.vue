<script setup>
import BaseButton from '../base/BaseButton.vue'
import { formatCurrency } from '../../helpers/currency'
import TransactionStatusBadge from './TransactionStatusBadge.vue'

defineProps({
  transaction: {
    type: Object,
    required: true,
  },
})

defineEmits(['edit', 'remove'])
</script>

<template>
  <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-4 shadow-xl shadow-black/10">
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0">
        <p class="text-xs font-bold uppercase text-slate-500">{{ transaction.category?.name || 'Sem categoria' }}</p>
        <h3 class="mt-1 break-words text-lg font-black text-slate-50">{{ transaction.description }}</h3>
      </div>

      <span
        class="grid size-11 shrink-0 place-items-center rounded-2xl text-lg"
        :class="transaction.type === 'income' ? 'bg-emerald-400/10 text-emerald-200' : 'bg-rose-400/10 text-rose-200'"
      >
        {{ transaction.type === 'income' ? '↑' : '↓' }}
      </span>
    </div>

    <div class="mt-4 flex items-center justify-between gap-4">
      <TransactionStatusBadge :status="transaction.status" :recurring="Boolean(transaction.is_recurring)" />
      <strong
        class="text-xl"
        :class="transaction.type === 'income' ? 'text-emerald-300' : 'text-rose-300'"
      >
        {{ transaction.type === 'income' ? '+' : '-' }} {{ formatCurrency(transaction.amount) }}
      </strong>
    </div>

    <div class="mt-4 flex gap-2">
      <BaseButton class="flex-1" variant="secondary" @click="$emit('edit', transaction)">Editar</BaseButton>
      <BaseButton class="flex-1" variant="danger" @click="$emit('remove', transaction)">Excluir</BaseButton>
    </div>
  </article>
</template>

