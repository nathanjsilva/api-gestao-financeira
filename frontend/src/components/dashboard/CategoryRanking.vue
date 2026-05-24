<script setup>
import { formatCurrency } from '../../helpers/currency'
import { formatPercentage } from '../../helpers/percentage'

defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <section class="analytics-panel">
    <div class="mb-5">
      <p class="text-sm font-bold uppercase text-sky-300">Ranking</p>
      <h2 class="mt-2 text-2xl font-black text-slate-50">Categorias mais caras</h2>
    </div>

    <div class="space-y-3">
      <article
        v-for="(category, index) in categories.slice(0, 7)"
        :key="category.category_id"
        class="group flex items-center gap-4 rounded-2xl bg-white/[0.04] p-4 transition hover:bg-white/[0.07]"
      >
        <span class="grid size-10 place-items-center rounded-xl bg-slate-800 text-sm font-black text-sky-200">
          {{ index + 1 }}
        </span>

        <div class="min-w-0 flex-1">
          <div class="flex items-center justify-between gap-3">
            <strong class="truncate text-slate-100">{{ category.category_name }}</strong>
            <strong class="text-slate-50">{{ formatCurrency(category.total) }}</strong>
          </div>
          <p class="mt-1 text-sm text-slate-400">
            {{ formatPercentage(category.percentage_of_expenses) }} dos gastos
            <span :class="category.difference > 0 ? 'text-rose-300' : 'text-emerald-300'">
              | {{ formatCurrency(category.difference) }} vs mes anterior
            </span>
          </p>
        </div>
      </article>
    </div>
  </section>
</template>

