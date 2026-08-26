<script setup>
import BaseButton from '../base/BaseButton.vue'
import { formatCurrency } from '../../helpers/currency'
import InstallmentStatusBadge from './InstallmentStatusBadge.vue'

defineProps({
  purchase: {
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
        <p class="text-xs font-bold uppercase text-slate-500">
          {{ purchase.category?.name || 'Sem categoria' }} · {{ purchase.card?.name }}
        </p>
        <h3 class="mt-1 break-words text-lg font-black text-slate-50">{{ purchase.description }}</h3>
      </div>
      <strong class="shrink-0 text-lg text-rose-300">{{ formatCurrency(purchase.total_amount) }}</strong>
    </div>

    <div class="mt-4">
      <InstallmentStatusBadge :purchase="purchase" />
    </div>

    <div class="mt-4 flex gap-2">
      <BaseButton class="flex-1" variant="secondary" @click="$emit('edit', purchase)">Editar</BaseButton>
      <BaseButton class="flex-1" variant="danger" @click="$emit('remove', purchase)">Excluir</BaseButton>
    </div>
  </article>
</template>
