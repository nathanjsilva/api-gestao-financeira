<script setup>
defineProps({
  currentPage: { type: Number, required: true },
  totalPages:  { type: Number, required: true },
  total:       { type: Number, default: 0 },
  perPage:     { type: Number, default: 7 },
  pageNumbers: { type: Array, default: () => [] },
})

defineEmits(['prev', 'next', 'go'])
</script>

<template>
  <div v-if="totalPages > 1" class="mt-5 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
    <p class="text-sm text-slate-400">
      Mostrando <strong class="text-slate-200">{{ Math.min((currentPage - 1) * perPage + 1, total) }}–{{ Math.min(currentPage * perPage, total) }}</strong>
      de <strong class="text-slate-200">{{ total }}</strong> itens
    </p>

    <div class="flex items-center gap-1">
      <button
        class="grid h-9 min-w-9 place-items-center rounded-xl border border-white/10 px-3 text-sm text-slate-300 transition hover:bg-white/[0.06] disabled:cursor-not-allowed disabled:opacity-40"
        :disabled="currentPage === 1"
        @click="$emit('prev')"
      >
        ←
      </button>

      <template v-for="(page, i) in pageNumbers" :key="i">
        <span v-if="page === '...'" class="grid h-9 min-w-9 place-items-center text-sm text-slate-500">…</span>
        <button
          v-else
          class="grid h-9 min-w-9 place-items-center rounded-xl border text-sm font-semibold transition"
          :class="page === currentPage
            ? 'border-sky-400/40 bg-sky-400/10 text-sky-200'
            : 'border-white/10 text-slate-300 hover:bg-white/[0.06]'"
          @click="$emit('go', page)"
        >
          {{ page }}
        </button>
      </template>

      <button
        class="grid h-9 min-w-9 place-items-center rounded-xl border border-white/10 px-3 text-sm text-slate-300 transition hover:bg-white/[0.06] disabled:cursor-not-allowed disabled:opacity-40"
        :disabled="currentPage === totalPages"
        @click="$emit('next')"
      >
        →
      </button>
    </div>
  </div>
</template>
