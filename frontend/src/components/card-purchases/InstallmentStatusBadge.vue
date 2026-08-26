<script setup>
import { computed } from 'vue'
import { CARD_PAYMENT_TYPES } from '../../constants/cardPurchases'

const props = defineProps({
  purchase: {
    type: Object,
    required: true,
  },
})

const paymentLabel = computed(() => CARD_PAYMENT_TYPES.find((type) => type.value === props.purchase.payment_type)?.label || props.purchase.payment_type)
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <span class="rounded-full bg-white/6 px-3 py-1 text-xs font-bold text-slate-200">
      {{ paymentLabel }}<template v-if="purchase.payment_type === 'installment'"> · {{ purchase.installments_total }}x</template>
    </span>
    <span
      class="rounded-full px-3 py-1 text-xs font-bold"
      :class="purchase.is_settled ? 'bg-emerald-400/10 text-emerald-300' : 'bg-amber-400/10 text-amber-300'"
    >
      {{ purchase.is_settled ? 'Quitada' : 'Em andamento' }}
    </span>
  </div>
</template>
