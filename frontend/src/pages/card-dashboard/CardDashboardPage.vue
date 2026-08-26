<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import BaseInput from '../../components/base/BaseInput.vue'
import CardBreakdownList from '../../components/card-dashboard/CardBreakdownList.vue'
import CardCategoryChart from '../../components/card-dashboard/CardCategoryChart.vue'
import CardEvolutionChart from '../../components/card-dashboard/CardEvolutionChart.vue'
import CardPaymentTypeChart from '../../components/card-dashboard/CardPaymentTypeChart.vue'
import CardsSubNav from '../../components/cards/CardsSubNav.vue'
import FinancialCard from '../../components/dashboard/FinancialCard.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import FinancialInsight from '../../components/shared/FinancialInsight.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { cardDashboardService } from '../../services/card-dashboard/cardDashboardService'

const competency = ref(getCurrentCompetency())
const months = ref(6)
const analytics = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi } = useFormErrors()

const overview = computed(() => analytics.value?.overview || {})
const insights = computed(() => analytics.value?.insights || [])
const evolution = computed(() => analytics.value?.evolution || [])
const categoryRanking = computed(() => analytics.value?.by_category?.ranking || [])
const concentration = computed(() => analytics.value?.by_category?.concentration || {})
const topGrowth = computed(() => analytics.value?.by_category?.top_growth || [])
const paymentTypeBreakdown = computed(() => analytics.value?.payment_type_breakdown || [])
const committedFuture = computed(() => analytics.value?.committed_future || 0)
const outstandingBalance = computed(() => analytics.value?.outstanding_balance || 0)

const byCardItems = computed(() => (analytics.value?.by_card || []).map((item) => ({
  label: `${item.card_name} (${item.responsible_person})`,
  total: item.total,
})))

const byPersonItems = computed(() => (analytics.value?.by_person || []).map((item) => ({
  label: item.responsible_person,
  total: item.total,
})))

async function loadAnalytics() {
  if (!competency.value) {
    return
  }

  clearErrors()

  await withLoading(async () => {
    try {
      analytics.value = await cardDashboardService.analytics({
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
        eyebrow="Cartões"
        title="Análise de gastos no cartão"
        description="Acompanhe totais, distribuição e parcelas futuras dos seus cartões."
      >
        <template #actions>
          <div class="grid gap-3 sm:grid-cols-[180px_150px]">
            <BaseInput id="card-dashboard-competency" v-model="competency" label="Competência" type="month" />
            <BaseInput id="card-dashboard-months" v-model="months" label="Período" type="number" />
          </div>
        </template>
      </PageHeader>
    </div>

    <CardsSubNav />

    <p v-if="generalError" class="mb-5 rounded-2xl bg-rose-500/10 p-4 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div v-if="isLoading && !analytics" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div v-for="item in 4" :key="item" class="skeleton-card" />
    </div>

    <template v-else>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <FinancialCard
          label="Total do mês"
          :value="formatCurrency(overview.total_month)"
          hint="Soma das parcelas com vencimento nesta competência"
          accent="rose"
        />
        <FinancialCard
          label="Total do ano"
          :value="formatCurrency(overview.total_year)"
          hint="Acumulado do ano da competência selecionada"
          accent="sky"
        />
        <FinancialCard
          label="Comprometido futuro"
          :value="formatCurrency(committedFuture)"
          hint="Parcelas com vencimento após este mês"
          accent="violet"
        />
        <FinancialCard
          label="Saldo devedor parcelado"
          :value="formatCurrency(outstandingBalance)"
          hint="Parte ainda não vencida das compras parceladas"
          accent="emerald"
        />
      </div>

      <div class="mt-5 grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <CardCategoryChart :categories="categoryRanking" />
        <CardPaymentTypeChart :items="paymentTypeBreakdown" />
      </div>

      <div class="mt-5 grid gap-4 xl:grid-cols-2">
        <CardBreakdownList title="Por cartão" subtitle="Concentração de gastos" :items="byCardItems" />
        <CardBreakdownList title="Por pessoa" subtitle="Gasto por responsável" :items="byPersonItems" />
      </div>

      <div class="mt-5">
        <CardEvolutionChart :items="evolution" />
      </div>

      <div class="mt-5 grid gap-4 xl:grid-cols-3">
        <FinancialInsight
          v-for="insight in insights"
          :key="insight.title"
          :title="insight.title"
          :description="insight.description"
          :tone="insight.type === 'warning' ? 'warning' : (insight.type === 'danger' ? 'danger' : 'info')"
        />

        <FinancialInsight
          v-if="concentration.top_1_percentage"
          title="Concentração de categorias"
          :description="`A categoria principal responde por ${concentration.top_1_percentage}% dos gastos e as 3 maiores somam ${concentration.top_3_percentage}%.`"
          tone="info"
        />

        <FinancialInsight
          v-if="topGrowth[0]"
          title="Categoria em maior crescimento"
          :description="`${topGrowth[0].category_name} cresceu ${topGrowth[0].growth_percentage}% em relação ao mês anterior.`"
          tone="warning"
        />
      </div>
    </template>
  </section>
</template>
