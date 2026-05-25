<script setup>
defineProps({
  status: {
    type: String,
    required: true,
  },
  recurring: {
    type: Boolean,
    default: false,
  },
})

function statusLabel(status) {
  if (status === 'paid') {
    return 'Pago'
  }

  if (status === 'pending') {
    return 'Pendente'
  }

  return status
}
</script>

<template>
  <div class="flex flex-wrap gap-2">
    <span
      class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold"
      :class="status === 'paid' ? 'bg-emerald-400/10 text-emerald-200' : 'bg-amber-400/10 text-amber-200'"
    >
      <span class="size-2 rounded-full" :class="status === 'paid' ? 'bg-emerald-300' : 'bg-amber-300'" />
      {{ statusLabel(status) }}
    </span>

    <span
      v-if="recurring"
      class="inline-flex items-center gap-2 rounded-full bg-sky-400/10 px-3 py-1 text-xs font-bold text-sky-200"
    >
      Recorrente
    </span>
  </div>
</template>

