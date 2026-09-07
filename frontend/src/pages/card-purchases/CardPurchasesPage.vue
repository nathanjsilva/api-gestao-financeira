<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import BaseModal from '../../components/base/BaseModal.vue'
import BaseMonthPicker from '../../components/base/BaseMonthPicker.vue'
import BaseSelect from '../../components/base/BaseSelect.vue'
import CardsSubNav from '../../components/cards/CardsSubNav.vue'
import CardBreakdownList from '../../components/card-dashboard/CardBreakdownList.vue'
import CardCategoryChart from '../../components/card-dashboard/CardCategoryChart.vue'
import CardEvolutionChart from '../../components/card-dashboard/CardEvolutionChart.vue'
import CardPaymentTypeChart from '../../components/card-dashboard/CardPaymentTypeChart.vue'
import CardPurchaseCard from '../../components/card-purchases/CardPurchaseCard.vue'
import CardPurchaseForm from '../../components/card-purchases/CardPurchaseForm.vue'
import InstallmentStatusBadge from '../../components/card-purchases/InstallmentStatusBadge.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import FinancialCard from '../../components/dashboard/FinancialCard.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import FinancialInsight from '../../components/shared/FinancialInsight.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { CARD_PAYMENT_TYPES } from '../../constants/cardPurchases'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { cardService } from '../../services/cards/cardService'
import { cardCategoryService } from '../../services/card-categories/cardCategoryService'
import { cardDashboardService } from '../../services/card-dashboard/cardDashboardService'
import { cardPurchaseService } from '../../services/card-purchases/cardPurchaseService'

const cards = ref([])
const categories = ref([])
const purchases = ref([])
const editingId = ref(null)
const search = ref('')
const filters = reactive({
  competency: getCurrentCompetency(),
  card_id: '',
  card_category_id: '',
  payment_type: '',
})

const months = ref(6)
const analytics = ref(null)

const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

function blankForm() {
  return {
    card_id: '',
    card_category_id: '',
    description: '',
    total_amount: '',
    purchase_date: new Date().toISOString().slice(0, 10),
    reference_competency: getCurrentCompetency(),
    payment_type: 'cash',
    installments_total: '',
    starting_installment_number: '',
  }
}

const form = reactive(blankForm())
const editForm = reactive(blankForm())

const cardOptions = computed(() => cards.value.map((card) => ({
  label: `${card.name} (${card.responsible_person})`,
  value: card.id,
})))

const categoryOptions = computed(() => categories.value.map((category) => ({
  label: category.name,
  value: category.id,
})))

const isEditModalOpen = computed(() => editingId.value !== null)

function installmentForCompetency(purchase, competency) {
  return purchase.installments?.find((installment) => installment.competency === competency) || null
}

const filteredPurchases = computed(() => {
  return purchases.value.filter((purchase) => {
    const matchesSearch = purchase.description.toLowerCase().includes(search.value.toLowerCase())
    const matchesCard = !filters.card_id || String(purchase.card_id) === String(filters.card_id)
    const matchesCategory = !filters.card_category_id || String(purchase.card_category_id) === String(filters.card_category_id)
    const matchesPaymentType = !filters.payment_type || purchase.payment_type === filters.payment_type

    return matchesSearch && matchesCard && matchesCategory && matchesPaymentType
  })
})

const totalMonth = computed(() => filteredPurchases.value.reduce(
  (sum, purchase) => sum + Number(installmentForCompetency(purchase, filters.competency)?.amount || 0),
  0,
))

const cashTotal = computed(() => filteredPurchases.value
  .filter((purchase) => purchase.payment_type === 'cash')
  .reduce((sum, purchase) => sum + Number(installmentForCompetency(purchase, filters.competency)?.amount || 0), 0))

const installmentTotal = computed(() => totalMonth.value - cashTotal.value)

const settledCount = computed(() => filteredPurchases.value.filter((purchase) => purchase.is_settled).length)
const pendingCount = computed(() => filteredPurchases.value.length - settledCount.value)

const categoryTotals = computed(() => {
  const totals = new Map()

  filteredPurchases.value.forEach((purchase) => {
    const name = purchase.category?.name || 'Sem categoria'
    const amount = Number(installmentForCompetency(purchase, filters.competency)?.amount || 0)
    totals.set(name, (totals.get(name) || 0) + amount)
  })

  return [...totals.entries()]
    .map(([name, total]) => ({ name, total }))
    .sort((a, b) => b.total - a.total)
    .slice(0, 6)
})

const biggestCategoryTotal = computed(() => Math.max(...categoryTotals.value.map((item) => item.total), 1))

const purchasesByCard = computed(() => {
  const groups = new Map()

  filteredPurchases.value.forEach((purchase) => {
    const cardId = purchase.card_id

    if (!groups.has(cardId)) {
      groups.set(cardId, {
        cardId,
        label: purchase.card ? `${purchase.card.name} (${purchase.card.responsible_person})` : 'Cartão removido',
        purchases: [],
        total: 0,
      })
    }

    const group = groups.get(cardId)
    group.purchases.push(purchase)
    group.total += Number(installmentForCompetency(purchase, filters.competency)?.amount || 0)
  })

  return [...groups.values()].sort((a, b) => b.total - a.total)
})

const purchaseInsights = computed(() => {
  const insights = []

  insights.push({
    title: 'Gasto do mês',
    description: `O total lançado em cartões nesta competência é ${formatCurrency(totalMonth.value)}.`,
    tone: 'info',
  })

  insights.push({
    title: `${pendingCount.value} compra(s) em andamento`,
    description: 'Compras parceladas ainda não quitadas continuam impactando os próximos meses.',
    tone: pendingCount.value > 0 ? 'warning' : 'success',
  })

  if (categoryTotals.value[0]) {
    insights.push({
      title: 'Categoria dominante',
      description: `${categoryTotals.value[0].name} concentra ${formatCurrency(categoryTotals.value[0].total)} neste mês.`,
      tone: 'info',
    })
  }

  return insights
})

const overview = computed(() => analytics.value?.overview || {})
const analyticsInsights = computed(() => analytics.value?.insights || [])
const evolution = computed(() => analytics.value?.evolution || [])
const categoryRanking = computed(() => analytics.value?.by_category?.ranking || [])
const concentration = computed(() => analytics.value?.by_category?.concentration || {})
const topGrowth = computed(() => analytics.value?.by_category?.top_growth || [])
const paymentTypeBreakdown = computed(() => analytics.value?.payment_type_breakdown || [])
const committedFuture = computed(() => analytics.value?.committed_future || 0)
const outstandingBalance = computed(() => analytics.value?.outstanding_balance || 0)

const byPersonItems = computed(() => (analytics.value?.by_person || []).map((item) => ({
  label: item.responsible_person,
  total: item.total,
})))

watch(months, async () => {
  await withLoading(async () => {
    try {
      await fetchAnalytics()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
})

function categoryBarWidth(total) {
  return `${Math.max((Number(total || 0) / biggestCategoryTotal.value) * 100, 5)}%`
}

function buildPayload(source) {
  const payload = {
    card_id: source.card_id,
    card_category_id: source.card_category_id,
    description: source.description,
    total_amount: Number(source.total_amount),
    purchase_date: source.purchase_date,
    reference_competency: source.reference_competency,
    payment_type: source.payment_type,
  }

  if (source.payment_type === 'installment') {
    payload.installments_total = Number(source.installments_total)
    payload.starting_installment_number = Number(source.starting_installment_number)
  }

  return payload
}

function resetForm() {
  Object.assign(form, blankForm())
  form.reference_competency = filters.competency
  clearErrors()
}

async function loadCardsAndCategories() {
  cards.value = await cardService.list()
  categories.value = await cardCategoryService.list()
}

async function fetchAnalytics() {
  if (!filters.competency) {
    return
  }

  analytics.value = await cardDashboardService.analytics({
    competency: filters.competency,
    months: months.value,
  })
}

async function loadPurchases() {
  if (!filters.competency) {
    return
  }

  clearErrors()

  await withLoading(async () => {
    try {
      purchases.value = await cardPurchaseService.list({ competency: filters.competency })
      form.reference_competency = filters.competency
      await fetchAnalytics()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function loadInitialData() {
  await withLoading(async () => {
    try {
      await loadCardsAndCategories()
      purchases.value = await cardPurchaseService.list({ competency: filters.competency })
      await fetchAnalytics()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function handleSubmit() {
  clearErrors()

  await withLoading(async () => {
    try {
      await cardPurchaseService.create(buildPayload(form))
      resetForm()
      await loadPurchases()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

function startEdit(purchase) {
  editingId.value = purchase.id
  editForm.card_id = purchase.card_id
  editForm.card_category_id = purchase.card_category_id
  editForm.description = purchase.description
  editForm.total_amount = purchase.total_amount
  editForm.purchase_date = purchase.purchase_date
  editForm.reference_competency = purchase.reference_competency
  editForm.payment_type = purchase.payment_type
  editForm.installments_total = purchase.installments_total
  editForm.starting_installment_number = purchase.starting_installment_number
  clearErrors()
}

function closeEditModal() {
  editingId.value = null
  clearErrors()
}

async function handleEditSubmit() {
  clearErrors()

  await withLoading(async () => {
    try {
      await cardPurchaseService.update(editingId.value, buildPayload(editForm))
      closeEditModal()
      await loadPurchases()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function removePurchase(purchase) {
  if (!confirm(`Excluir a compra "${purchase.description}"?`)) {
    return
  }

  await withLoading(async () => {
    try {
      await cardPurchaseService.remove(purchase.id)
      await loadPurchases()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

onMounted(loadInitialData)
</script>

<template>
  <section class="dashboard-shell mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="dashboard-hero">
      <PageHeader
        eyebrow="Cartões"
        title="Compras no cartão"
        description="Lance compras à vista ou parceladas e acompanhe o impacto de cada uma mês a mês."
      >
        <template #actions>
          <div class="grid gap-3 @min-[40rem]:grid-cols-[1fr_120px]">
            <BaseMonthPicker id="card-purchases-competency" v-model="filters.competency" label="Competência" />
            <BaseButton class="@min-[40rem]:mt-7" :loading="isLoading" @click="loadPurchases">Filtrar</BaseButton>
          </div>
        </template>
      </PageHeader>
    </div>

    <CardsSubNav />

    <p v-if="generalError" class="mt-5 rounded-2xl bg-rose-500/10 p-4 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="mt-5 grid gap-4 @min-[48rem]:grid-cols-2 @min-[80rem]:grid-cols-4">
      <article class="financial-card financial-card--rose">
        <p class="text-sm font-semibold text-slate-400">Total do mês</p>
        <strong class="mt-3 block text-3xl font-black text-rose-300">{{ formatCurrency(totalMonth) }}</strong>
        <p class="mt-3 text-sm text-slate-400">Soma das parcelas com vencimento nesta competência.</p>
      </article>
      <article class="financial-card financial-card--sky">
        <p class="text-sm font-semibold text-slate-400">À vista</p>
        <strong class="mt-3 block text-3xl font-black text-sky-300">{{ formatCurrency(cashTotal) }}</strong>
        <p class="mt-3 text-sm text-slate-400">Compras pagas integralmente no mês.</p>
      </article>
      <article class="financial-card financial-card--violet">
        <p class="text-sm font-semibold text-slate-400">Parcelado</p>
        <strong class="mt-3 block text-3xl font-black text-violet-300">{{ formatCurrency(installmentTotal) }}</strong>
        <p class="mt-3 text-sm text-slate-400">Parcelas de compras parceladas neste mês.</p>
      </article>
      <article class="financial-card financial-card--emerald">
        <p class="text-sm font-semibold text-slate-400">Situação</p>
        <strong class="mt-3 block text-3xl font-black text-slate-50">{{ settledCount }}/{{ filteredPurchases.length }}</strong>
        <p class="mt-3 text-sm text-slate-400">{{ pendingCount }} compra(s) ainda em andamento.</p>
      </article>
    </div>

    <div class="mt-5 grid min-w-0 gap-4 @min-[80rem]:grid-cols-[minmax(380px,440px)_minmax(0,1fr)]">
      <BaseCard>
        <h2 class="text-2xl font-black text-slate-50">Nova compra</h2>
        <p class="mt-2 text-sm leading-6 text-slate-400">Informe o valor total e a quantidade de parcelas — o sistema calcula cada parcela automaticamente.</p>

        <div class="mt-6">
          <CardPurchaseForm
            :form="form"
            :card-options="cardOptions"
            :category-options="categoryOptions"
            :field-error="fieldError"
            :is-loading="isLoading"
            submit-label="Cadastrar compra"
            @submit="handleSubmit"
          />
        </div>
      </BaseCard>

      <div class="space-y-4">
        <section class="analytics-panel">
          <div class="mb-5">
            <p class="text-sm font-bold uppercase text-sky-300">Filtros</p>
            <h2 class="mt-2 text-2xl font-black text-slate-50">Encontre compras rapidamente</h2>
          </div>
          <div class="grid gap-4 @min-[48rem]:grid-cols-2 @min-[80rem]:grid-cols-4">
            <BaseInput id="card-purchase-search" v-model="search" label="Buscar" placeholder="Descrição" />
            <BaseSelect id="filter-card" v-model="filters.card_id" label="Cartão" placeholder="Todos" :options="cardOptions" />
            <BaseSelect id="filter-card-category" v-model="filters.card_category_id" label="Categoria" placeholder="Todas" :options="categoryOptions" />
            <BaseSelect id="filter-payment-type" v-model="filters.payment_type" label="Tipo de pagamento" placeholder="Todos" :options="CARD_PAYMENT_TYPES" />
          </div>
        </section>

        <section class="analytics-panel">
          <div class="mb-5">
            <p class="text-sm font-bold uppercase text-sky-300">Análise visual</p>
            <h2 class="mt-2 text-2xl font-black text-slate-50">Gastos por categoria</h2>
          </div>

          <EmptyState v-if="!categoryTotals.length" title="Sem gastos no período" description="Cadastre compras para ver a distribuição por categoria." />

          <div v-else class="space-y-3">
            <div v-for="category in categoryTotals" :key="category.name" class="rounded-2xl bg-white/[0.04] p-4">
              <div class="flex items-center justify-between gap-3">
                <strong class="break-words text-slate-100">{{ category.name }}</strong>
                <span class="shrink-0 text-sm text-slate-400">{{ formatCurrency(category.total) }}</span>
              </div>
              <div class="mt-3 h-2 rounded-full bg-slate-800">
                <div class="h-full rounded-full bg-gradient-to-r from-rose-300 to-orange-300" :style="{ width: categoryBarWidth(category.total) }" />
              </div>
            </div>
          </div>
        </section>

        <section class="analytics-panel">
          <div class="mb-5">
            <p class="text-sm font-bold uppercase text-sky-300">Insights</p>
            <h2 class="mt-2 text-2xl font-black text-slate-50">Resumo do mês</h2>
          </div>
          <div class="grid gap-3 @min-[48rem]:grid-cols-3">
            <FinancialInsight
              v-for="insight in purchaseInsights"
              :key="insight.title"
              :title="insight.title"
              :description="insight.description"
              :tone="insight.tone"
            />
          </div>
        </section>
      </div>
    </div>

    <div class="mt-5">
      <div class="mb-5 flex flex-col gap-3 @min-[40rem]:flex-row @min-[40rem]:items-center @min-[40rem]:justify-between">
        <div>
          <h2 class="text-2xl font-black text-slate-50">Compras lançadas</h2>
          <p class="mt-2 text-sm text-slate-400">Um card por cartão, para não misturar as compras.</p>
        </div>
        <span class="rounded-full bg-white/6 px-3 py-1 text-sm text-slate-300">{{ filteredPurchases.length }} itens</span>
      </div>

      <EmptyState
        v-if="!filteredPurchases.length"
        title="Nenhuma compra encontrada"
        description="Cadastre uma compra ou ajuste os filtros."
      />

      <div v-else class="space-y-4">
        <BaseCard v-for="group in purchasesByCard" :key="group.cardId">
          <div class="mb-5 flex flex-col gap-3 @min-[40rem]:flex-row @min-[40rem]:items-center @min-[40rem]:justify-between">
            <div>
              <h3 class="text-xl font-black text-slate-50">{{ group.label }}</h3>
              <p class="mt-1 text-sm text-slate-400">{{ group.purchases.length }} compra(s) nesta competência</p>
            </div>
            <span class="value-badge value-badge--info">{{ formatCurrency(group.total) }}</span>
          </div>

          <div class="hidden @min-[80rem]:block">
            <div class="max-h-80 overflow-y-auto pr-1">
              <table class="premium-table">
                <thead>
                  <tr>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Tipo</th>
                    <th class="text-right">Parcela do mês</th>
                    <th class="text-right">Valor total</th>
                    <th class="text-right">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="purchase in group.purchases" :key="purchase.id">
                    <td>
                      <strong class="block max-w-55 truncate text-slate-50">{{ purchase.description }}</strong>
                    </td>
                    <td><span class="block max-w-40 truncate">{{ purchase.category?.name || 'Sem categoria' }}</span></td>
                    <td><InstallmentStatusBadge :purchase="purchase" /></td>
                    <td class="text-right text-lg font-black text-rose-300">
                      {{ formatCurrency(installmentForCompetency(purchase, filters.competency)?.amount) }}
                    </td>
                    <td class="text-right text-slate-300">{{ formatCurrency(purchase.total_amount) }}</td>
                    <td>
                      <div class="flex justify-end gap-2">
                        <BaseButton variant="secondary" title="Editar" :disabled="isLoading" @click="startEdit(purchase)">Editar</BaseButton>
                        <BaseButton variant="danger" title="Excluir" :disabled="isLoading" @click="removePurchase(purchase)">Excluir</BaseButton>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="grid max-h-108 gap-3 overflow-y-auto pr-1 @min-[80rem]:hidden">
            <CardPurchaseCard
              v-for="purchase in group.purchases"
              :key="purchase.id"
              :purchase="purchase"
              @edit="startEdit"
              @remove="removePurchase"
            />
          </div>
        </BaseCard>
      </div>
    </div>

    <div class="mt-8">
      <div class="mb-5 flex flex-col gap-3 @min-[40rem]:flex-row @min-[40rem]:items-end @min-[40rem]:justify-between">
        <div>
          <p class="text-sm font-bold uppercase text-sky-300">Análises</p>
          <h2 class="mt-2 text-2xl font-black text-slate-50">Visão ampliada dos cartões</h2>
          <p class="mt-2 text-sm text-slate-400">Totais, distribuição e parcelas futuras considerando o período abaixo.</p>
        </div>
        <BaseInput id="card-analytics-months" v-model="months" label="Período (meses)" type="number" class="@min-[40rem]:w-40" />
      </div>

      <div class="grid gap-4 @min-[48rem]:grid-cols-2 @min-[80rem]:grid-cols-3">
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

      <div class="mt-5 grid gap-4 @min-[80rem]:grid-cols-[1.1fr_0.9fr]">
        <CardCategoryChart :categories="categoryRanking" />
        <CardPaymentTypeChart :items="paymentTypeBreakdown" />
      </div>

      <div class="mt-5">
        <CardBreakdownList title="Por pessoa" subtitle="Gasto por responsável" :items="byPersonItems" />
      </div>

      <div class="mt-5">
        <CardEvolutionChart :items="evolution" />
      </div>

      <div class="mt-5 grid gap-4 @min-[80rem]:grid-cols-3">
        <FinancialInsight
          v-for="insight in analyticsInsights"
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
    </div>

    <BaseModal :open="isEditModalOpen" title="Editar compra" @close="closeEditModal">
      <CardPurchaseForm
        :form="editForm"
        :card-options="cardOptions"
        :category-options="categoryOptions"
        :field-error="fieldError"
        :is-loading="isLoading"
        submit-label="Salvar alterações"
        show-cancel
        @submit="handleEditSubmit"
        @cancel="closeEditModal"
      />
    </BaseModal>
  </section>
</template>
