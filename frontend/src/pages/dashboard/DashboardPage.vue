<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import BaseInput from '../../components/base/BaseInput.vue'
import CashFlowChart from '../../components/dashboard/CashFlowChart.vue'
import CategoryRanking from '../../components/dashboard/CategoryRanking.vue'
import ExpenseChart from '../../components/dashboard/ExpenseChart.vue'
import FinancialCard from '../../components/dashboard/FinancialCard.vue'
import FinancialHeatmap from '../../components/dashboard/FinancialHeatmap.vue'
import FinancialInsights from '../../components/dashboard/FinancialInsights.vue'
import MonthlyComparison from '../../components/dashboard/MonthlyComparison.vue'
import ReserveEvolution from '../../components/dashboard/ReserveEvolution.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { formatPercentage } from '../../helpers/percentage'
import { dashboardService } from '../../services/dashboard/dashboardService'

const competency = ref(getCurrentCompetency())
const months = ref(6)
const analytics = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi } = useFormErrors()

const overview = computed(() => analytics.value?.overview || {})
const comparison = computed(() => analytics.value?.comparison || {})
const kpis = computed(() => analytics.value?.kpis || {})
const categories = computed(() => analytics.value?.categories?.ranking || [])
const insights = computed(() => analytics.value?.insights || [])
const reserveEvolution = computed(() => analytics.value?.reserve_evolution || [])
const cashFlow = computed(() => analytics.value?.cash_flow || [])
const heatmap = computed(() => analytics.value?.heatmap || [])

async function loadAnalytics() {
  if (!competency.value) {
    return
  }

  clearErrors()

  await withLoading(async () => {
    try {
      analytics.value = await dashboardService.analytics({
        competency: competency.value,
        months: months.value,
      })
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

watch([competency, months], loadAnalytics)
onMounted(loadAnalytics)
</script>

<template>
  <section class="dashboard-shell mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="dashboard-hero">
      <PageHeader
        eyebrow="Inteligencia financeira"
        title="Dashboard analitico"
        description="Entenda para onde seu dinheiro esta indo, compare meses e acompanhe a evolucao da sua reserva."
      >
        <template #actions>
          <div class="grid gap-3 sm:grid-cols-[180px_150px]">
            <BaseInput id="dashboard-competency" v-model="competency" label="Competencia" type="month" />
            <BaseInput id="dashboard-months" v-model="months" label="Periodo" type="number" />
          </div>
        </template>
      </PageHeader>
    </div>

    <p v-if="generalError" class="mb-5 rounded-2xl bg-rose-500/10 p-4 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div v-if="isLoading && !analytics" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div v-for="item in 4" :key="item" class="skeleton-card" />
    </div>

    <template v-else>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <FinancialCard
          label="Entradas do mes"
          :value="formatCurrency(overview.total_income)"
          hint="Renda e recebimentos confirmados"
          accent="emerald"
          trend="up"
        />
        <FinancialCard
          label="Gastos do mes"
          :value="formatCurrency(overview.total_expense)"
          :hint="`${formatPercentage(kpis.income_commitment_rate)} da renda comprometida`"
          accent="rose"
          :trend="comparison.expense_difference > 0 ? 'down' : 'up'"
        />
        <FinancialCard
          label="Saldo restante"
          :value="formatCurrency(overview.remaining_amount)"
          :hint="`Taxa de economia: ${formatPercentage(kpis.savings_rate)}`"
          accent="sky"
          :trend="overview.remaining_amount >= 0 ? 'up' : 'down'"
        />
        <FinancialCard
          label="Total guardado"
          :value="formatCurrency(overview.total_saved)"
          hint="Reserva atual + investimento"
          accent="violet"
          :trend="comparison.reserve_difference >= 0 ? 'up' : 'down'"
        />
      </div>

      <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <MonthlyComparison :comparison="comparison" />
        <FinancialInsights class="lg:col-span-2" :insights="insights" />
      </div>

      <div class="mt-5 grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <ExpenseChart :categories="categories" />
        <CategoryRanking :categories="categories" />
      </div>

      <div class="mt-5 grid gap-4 xl:grid-cols-[1fr_1fr]">
        <CashFlowChart :items="cashFlow" />
        <ReserveEvolution :items="reserveEvolution" />
      </div>

      <div class="mt-5 grid gap-4 xl:grid-cols-[0.8fr_1.2fr]">
        <FinancialHeatmap :items="heatmap" />

        <section class="analytics-panel">
          <div class="mb-5">
            <p class="text-sm font-bold uppercase text-sky-300">KPIs avancados</p>
            <h2 class="mt-2 text-2xl font-black text-slate-50">Comportamento financeiro</h2>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <div class="kpi-tile">
              <span>Media mensal de gastos</span>
              <strong>{{ formatCurrency(kpis.average_monthly_expense) }}</strong>
            </div>
            <div class="kpi-tile">
              <span>Media mensal de entradas</span>
              <strong>{{ formatCurrency(kpis.average_monthly_income) }}</strong>
            </div>
            <div class="kpi-tile">
              <span>Melhor mes</span>
              <strong>{{ kpis.best_month?.competency || '-' }}</strong>
            </div>
            <div class="kpi-tile">
              <span>Pior mes</span>
              <strong>{{ kpis.worst_month?.competency || '-' }}</strong>
            </div>
            <div class="kpi-tile">
              <span>Saldo medio</span>
              <strong>{{ formatCurrency(kpis.average_remaining) }}</strong>
            </div>
            <div class="kpi-tile">
              <span>Previsao fechamento</span>
              <strong>{{ formatCurrency(kpis.projected_closing) }}</strong>
            </div>
          </div>
        </section>
      </div>
    </template>
  </section>
</template>
