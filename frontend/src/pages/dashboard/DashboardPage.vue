<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import MetricCard from '../../components/data-display/MetricCard.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { dashboardService } from '../../services/dashboard/dashboardService'

const competency = ref(getCurrentCompetency())
const summary = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi } = useFormErrors()

const expensesByCategory = computed(() => summary.value?.expenses_by_category || [])
const biggestExpense = computed(() => Number(expensesByCategory.value[0]?.total || 0))

async function loadSummary() {
  if (!competency.value) {
    return
  }

  clearErrors()

  await withLoading(async () => {
    try {
      summary.value = await dashboardService.monthlySummary(competency.value)
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

function expenseBarWidth(total) {
  if (!biggestExpense.value) {
    return '0%'
  }

  return `${Math.max((Number(total) / biggestExpense.value) * 100, 6)}%`
}

watch(competency, loadSummary)
onMounted(loadSummary)
</script>

<template>
  <section class="mx-auto max-w-7xl px-6 py-10">
    <PageHeader
      eyebrow="Visao geral"
      title="Dashboard"
      description="Resumo da competencia mensal com entradas, gastos, reserva e categorias mais relevantes."
    >
      <template #actions>
        <BaseInput
          id="dashboard-competency"
          v-model="competency"
          label="Competencia"
          type="month"
        />
      </template>
    </PageHeader>

    <p v-if="generalError" class="mb-5 rounded-md bg-rose-500/10 p-3 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <MetricCard
        label="Entradas"
        :value="formatCurrency(summary?.total_income)"
        tone="positive"
      />
      <MetricCard
        label="Gastos"
        :value="formatCurrency(summary?.total_expense)"
        tone="negative"
      />
      <MetricCard
        label="Saldo restante"
        :value="formatCurrency(summary?.remaining_amount)"
        tone="info"
      />
      <MetricCard
        label="Total guardado"
        :value="formatCurrency(summary?.total_saved)"
      />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
      <BaseCard>
        <div class="mb-5 flex items-center justify-between">
          <div>
            <h2 class="text-xl font-black text-slate-50">Gastos por categoria</h2>
            <p class="text-sm text-slate-400">Distribuicao visual dos gastos no mes.</p>
          </div>
          <span v-if="isLoading" class="text-sm text-slate-400">Carregando...</span>
        </div>

        <EmptyState
          v-if="!expensesByCategory.length"
          title="Nenhum gasto encontrado"
          description="Cadastre transacoes de saida para alimentar este grafico."
        />

        <div v-else class="space-y-4">
          <div v-for="item in expensesByCategory" :key="item.category_id">
            <div class="mb-2 flex items-center justify-between gap-4 text-sm">
              <span class="font-semibold text-slate-200">{{ item.category_name }}</span>
              <span class="text-slate-400">{{ formatCurrency(item.total) }}</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-slate-800">
              <div
                class="h-full rounded-full bg-gradient-to-r from-rose-400 to-orange-300"
                :style="{ width: expenseBarWidth(item.total) }"
              />
            </div>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <h2 class="text-xl font-black text-slate-50">Leitura rapida</h2>
        <dl class="mt-5 space-y-4">
          <div class="rounded-md bg-white/[0.03] p-4">
            <dt class="text-sm text-slate-400">Categoria com maior gasto</dt>
            <dd class="mt-1 text-lg font-bold text-rose-200">
              {{ summary?.highest_expense_category || 'Sem dados' }}
            </dd>
          </div>
          <div class="rounded-md bg-white/[0.03] p-4">
            <dt class="text-sm text-slate-400">Categoria com menor gasto</dt>
            <dd class="mt-1 text-lg font-bold text-emerald-200">
              {{ summary?.lowest_expense_category || 'Sem dados' }}
            </dd>
          </div>
          <div class="rounded-md bg-white/[0.03] p-4">
            <dt class="text-sm text-slate-400">Reserva atual</dt>
            <dd class="mt-1 text-lg font-bold text-sky-200">
              {{ formatCurrency(summary?.current_reserve) }}
            </dd>
          </div>
        </dl>
      </BaseCard>
    </div>
  </section>
</template>
