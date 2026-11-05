<script setup lang="ts">
import EmptyState from '../data-display/EmptyState.vue'
import { formatCurrency } from '../../helpers/currency'
import { formatPercentage } from '../../helpers/percentage'

defineProps({
  total: { type: Number, default: 0 },
  categories: { type: Array, default: () => [] },
})
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-sm font-bold uppercase text-sky-300">Cartão</p>
        <h2 class="mt-2 text-2xl font-black text-slate-50">Gastos no cartão por categoria</h2>
        <p class="mt-3 text-sm leading-6 text-slate-400">
          Parcelas lançadas na competência selecionada, agrupadas pela categoria do cartão.
        </p>
      </div>
      <div class="rounded-2xl bg-white/[0.04] px-4 py-3">
        <span class="block text-xs font-semibold uppercase text-slate-400">Total no cartão</span>
        <strong class="mt-1 block text-xl text-slate-50">{{ formatCurrency(total) }}</strong>
      </div>
    </div>

    <EmptyState
      v-if="!categories.length"
      title="Sem gastos no cartão nesta competência"
      description="As compras registradas aparecerão aqui agrupadas por categoria."
    />

    <div v-else class="space-y-3">
      <article
        v-for="category in categories"
        :key="category.card_category_id"
        class="rounded-2xl bg-white/[0.04] p-4"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <strong class="text-slate-100">{{ category.category_name }}</strong>
          <strong class="text-slate-50">{{ formatCurrency(category.total) }}</strong>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
          <div
            class="h-full rounded-full bg-gradient-to-r from-sky-300 to-violet-300"
            :style="{ width: `${Math.min(Number(category.percentage_of_expenses || 0), 100)}%` }"
          />
        </div>
        <p class="mt-2 text-sm text-slate-400">
          {{ formatPercentage(category.percentage_of_expenses) }} do total no cartão
          <span :class="category.difference > 0 ? 'text-rose-300' : 'text-emerald-300'">
            · {{ formatCurrency(category.difference) }} vs. mês anterior
          </span>
        </p>
      </article>
    </div>
  </section>
</template>
