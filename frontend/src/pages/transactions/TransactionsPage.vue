<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import BaseSelect from '../../components/base/BaseSelect.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import FinancialInsight from '../../components/shared/FinancialInsight.vue'
import TransactionCard from '../../components/transactions/TransactionCard.vue'
import TransactionStatusBadge from '../../components/transactions/TransactionStatusBadge.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { TRANSACTION_STATUS, TRANSACTION_TYPES } from '../../constants/transactions'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { categoryService } from '../../services/categories/categoryService'
import { transactionService } from '../../services/transactions/transactionService'

const categories = ref([])
const transactions = ref([])
const editingId = ref(null)
const search = ref('')
const filters = reactive({
  competency: getCurrentCompetency(),
  category_id: '',
  status: '',
  type: '',
  recurring: '',
})

const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

const form = reactive({
  category_id: '',
  description: '',
  amount: '',
  type: 'expense',
  status: 'paid',
  competency: getCurrentCompetency(),
  is_recurring: false,
})

const categoryOptions = computed(() => categories.value.map((category) => ({
  label: `${category.name} (${typeLabel(category.type)})`,
  value: category.id,
  type: category.type,
})))

const formCategoryOptions = computed(() => categoryOptions.value.filter((category) => category.type === form.type))
const totalIncome = computed(() => sumByType(filteredTransactions.value, 'income'))
const totalExpense = computed(() => sumByType(filteredTransactions.value, 'expense'))
const remainingAmount = computed(() => totalIncome.value - totalExpense.value)
const paidCount = computed(() => filteredTransactions.value.filter((transaction) => transaction.status === 'paid').length)
const pendingCount = computed(() => filteredTransactions.value.filter((transaction) => transaction.status === 'pending').length)
const recurringCount = computed(() => filteredTransactions.value.filter((transaction) => transaction.is_recurring).length)
const categoryTotals = computed(() => {
  const totals = new Map()

  filteredTransactions.value
    .filter((transaction) => transaction.type === 'expense')
    .forEach((transaction) => {
      const name = transaction.category?.name || 'Sem categoria'
      totals.set(name, (totals.get(name) || 0) + Number(transaction.amount || 0))
    })

  return [...totals.entries()]
    .map(([name, total]) => ({ name, total }))
    .sort((a, b) => b.total - a.total)
    .slice(0, 6)
})

const biggestCategoryTotal = computed(() => Math.max(...categoryTotals.value.map((item) => item.total), 1))
const transactionInsights = computed(() => {
  const insights = []

  insights.push({
    title: remainingAmount.value >= 0 ? 'Fluxo positivo' : 'Fluxo negativo',
    description: remainingAmount.value >= 0
      ? `Você ainda tem ${formatCurrency(remainingAmount.value)} disponível nesta competência.`
      : `Os gastos superaram as entradas em ${formatCurrency(Math.abs(remainingAmount.value))}.`,
    tone: remainingAmount.value >= 0 ? 'success' : 'danger',
  })

  insights.push({
    title: `${pendingCount.value} pendência(s)`,
    description: 'Lançamentos pendentes merecem atenção para manter a visão financeira atualizada.',
    tone: pendingCount.value > 0 ? 'warning' : 'success',
  })

  if (categoryTotals.value[0]) {
    insights.push({
      title: 'Categoria dominante',
      description: `${categoryTotals.value[0].name} concentra ${formatCurrency(categoryTotals.value[0].total)} em gastos.`,
      tone: 'info',
    })
  }

  return insights
})

const filteredTransactions = computed(() => {
  return transactions.value.filter((transaction) => {
    const matchesSearch = transaction.description.toLowerCase().includes(search.value.toLowerCase())
    const matchesCategory = !filters.category_id || String(transaction.category_id) === String(filters.category_id)
    const matchesStatus = !filters.status || transaction.status === filters.status
    const matchesType = !filters.type || transaction.type === filters.type
    const matchesRecurring = !filters.recurring || String(Boolean(transaction.is_recurring)) === filters.recurring

    return matchesSearch && matchesCategory && matchesStatus && matchesType && matchesRecurring
  })
})

const submitLabel = computed(() => editingId.value ? 'Salvar alterações' : 'Cadastrar transação')
const impactPreview = computed(() => {
  const amount = Number(form.amount || 0)

  return form.type === 'income' ? amount : -amount
})

function sumByType(items, type) {
  return items
    .filter((transaction) => transaction.type === type)
    .reduce((sum, transaction) => sum + Number(transaction.amount || 0), 0)
}

function typeLabel(type) {
  return TRANSACTION_TYPES.find((item) => item.value === type)?.label || type
}

function statusLabel(status) {
  return TRANSACTION_STATUS.find((item) => item.value === status)?.label || status
}

function categoryBarWidth(total) {
  return `${Math.max((Number(total || 0) / biggestCategoryTotal.value) * 100, 5)}%`
}

function resetForm() {
  editingId.value = null
  form.category_id = ''
  form.description = ''
  form.amount = ''
  form.type = 'expense'
  form.status = 'paid'
  form.competency = filters.competency
  form.is_recurring = false
  clearErrors()
}

async function loadCategories() {
  categories.value = await categoryService.list()
}

async function loadTransactions() {
  if (!filters.competency) {
    return
  }

  clearErrors()

  await withLoading(async () => {
    try {
      transactions.value = await transactionService.list({ competency: filters.competency })
      form.competency = filters.competency
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function loadInitialData() {
  await withLoading(async () => {
    try {
      await loadCategories()
      transactions.value = await transactionService.list({ competency: filters.competency })
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function handleSubmit() {
  clearErrors()

  const payload = {
    ...form,
    amount: Number(form.amount),
  }

  await withLoading(async () => {
    try {
      if (editingId.value) {
        await transactionService.update(editingId.value, payload)
      } else {
        await transactionService.create(payload)
      }

      resetForm()
      await loadTransactions()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

function startEdit(transaction) {
  editingId.value = transaction.id
  form.category_id = transaction.category_id
  form.description = transaction.description
  form.amount = transaction.amount
  form.type = transaction.type
  form.status = transaction.status
  form.competency = transaction.competency
  form.is_recurring = Boolean(transaction.is_recurring)
  clearErrors()
}

async function removeTransaction(transaction) {
  if (!confirm(`Excluir a transação "${transaction.description}"?`)) {
    return
  }

  await withLoading(async () => {
    try {
      await transactionService.remove(transaction.id)
      await loadTransactions()
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
        eyebrow="Lançamentos"
        title="Transações"
        description="Controle entradas, saídas, filtros e comportamento financeiro por competência."
      >
        <template #actions>
          <div class="grid gap-3 sm:grid-cols-[180px_120px]">
            <BaseInput id="transactions-competency" v-model="filters.competency" label="Competência" type="month" />
            <BaseButton class="sm:mt-7" :loading="isLoading" @click="loadTransactions">Filtrar</BaseButton>
          </div>
        </template>
      </PageHeader>
    </div>

    <p v-if="generalError" class="mt-5 rounded-2xl bg-rose-500/10 p-4 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article class="financial-card financial-card--emerald">
        <p class="text-sm font-semibold text-slate-400">Entradas filtradas</p>
        <strong class="mt-3 block text-3xl font-black text-emerald-300">{{ formatCurrency(totalIncome) }}</strong>
        <p class="mt-3 text-sm text-slate-400">Tudo que entrou na competência.</p>
      </article>
      <article class="financial-card financial-card--rose">
        <p class="text-sm font-semibold text-slate-400">Saídas filtradas</p>
        <strong class="mt-3 block text-3xl font-black text-rose-300">{{ formatCurrency(totalExpense) }}</strong>
        <p class="mt-3 text-sm text-slate-400">Tudo que saiu na competência.</p>
      </article>
      <article class="financial-card financial-card--sky">
        <p class="text-sm font-semibold text-slate-400">Saldo filtrado</p>
        <strong class="mt-3 block text-3xl font-black" :class="remainingAmount >= 0 ? 'text-emerald-300' : 'text-rose-300'">
          {{ formatCurrency(remainingAmount) }}
        </strong>
        <p class="mt-3 text-sm text-slate-400">Entradas menos saídas.</p>
      </article>
      <article class="financial-card financial-card--violet">
        <p class="text-sm font-semibold text-slate-400">Status</p>
        <strong class="mt-3 block text-3xl font-black text-slate-50">{{ paidCount }}/{{ filteredTransactions.length }}</strong>
        <p class="mt-3 text-sm text-slate-400">{{ pendingCount }} pendente(s), {{ recurringCount }} recorrente(s).</p>
      </article>
    </div>

    <div class="mt-5 grid min-w-0 gap-4 xl:grid-cols-[minmax(360px,420px)_minmax(0,1fr)]">
      <BaseCard>
        <h2 class="text-2xl font-black text-slate-50">{{ editingId ? 'Editar transação' : 'Nova transação' }}</h2>
        <p class="mt-2 text-sm leading-6 text-slate-400">Cadastre lançamentos com categoria, status e impacto financeiro visível antes de salvar.</p>

        <form class="mt-6 space-y-5" @submit.prevent="handleSubmit">
          <div class="grid grid-cols-2 gap-3">
            <button
              v-for="type in TRANSACTION_TYPES"
              :key="type.value"
              type="button"
              class="rounded-2xl border p-4 text-left transition"
              :class="form.type === type.value ? 'border-sky-300 bg-sky-400/10 text-sky-100' : 'border-white/10 bg-white/[0.03] text-slate-300 hover:bg-white/[0.06]'"
              @click="form.type = type.value; form.category_id = ''"
            >
              <span class="block text-sm font-bold">{{ type.label }}</span>
              <span class="mt-1 block text-xs text-slate-400">{{ type.value === 'income' ? 'Aumenta o saldo' : 'Reduz o saldo' }}</span>
            </button>
          </div>

          <BaseSelect id="transaction-category" v-model="form.category_id" label="Categoria" :options="formCategoryOptions" :error="fieldError('category_id')" />
          <BaseInput id="transaction-description" v-model="form.description" label="Descrição" placeholder="Ex: Supermercado" :error="fieldError('description')" />
          <BaseInput id="transaction-amount" v-model="form.amount" label="Valor" type="number" placeholder="Ex: 250.90" :error="fieldError('amount')" />

          <div class="grid gap-4 sm:grid-cols-2">
            <BaseSelect id="transaction-status" v-model="form.status" label="Status" :options="TRANSACTION_STATUS" :error="fieldError('status')" />
            <BaseInput id="transaction-form-competency" v-model="form.competency" label="Competência" type="month" :error="fieldError('competency')" />
          </div>

          <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/70 p-4 text-sm text-slate-200">
            <input v-model="form.is_recurring" type="checkbox" class="size-4 accent-sky-400">
            Lançamento recorrente
          </label>

          <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-4">
            <p class="text-sm font-bold uppercase text-sky-300">Impacto financeiro</p>
            <strong class="mt-2 block text-2xl" :class="impactPreview >= 0 ? 'text-emerald-300' : 'text-rose-300'">
              {{ impactPreview >= 0 ? '+' : '-' }} {{ formatCurrency(Math.abs(impactPreview)) }}
            </strong>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <BaseButton type="submit" class="w-full" :loading="isLoading">{{ submitLabel }}</BaseButton>
            <BaseButton v-if="editingId" class="w-full" variant="secondary" @click="resetForm">Cancelar</BaseButton>
          </div>
        </form>
      </BaseCard>

      <div class="space-y-4">
        <section class="analytics-panel">
          <div class="mb-5">
            <p class="text-sm font-bold uppercase text-sky-300">Filtros inteligentes</p>
            <h2 class="mt-2 text-2xl font-black text-slate-50">Encontre lançamentos rapidamente</h2>
          </div>
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <BaseInput id="search" v-model="search" label="Buscar" placeholder="Descrição" />
            <BaseSelect id="filter-category" v-model="filters.category_id" label="Categoria" placeholder="Todas" :options="categoryOptions" />
            <BaseSelect id="filter-type" v-model="filters.type" label="Tipo" placeholder="Todos" :options="TRANSACTION_TYPES" />
            <BaseSelect id="filter-status" v-model="filters.status" label="Status" placeholder="Todos" :options="TRANSACTION_STATUS" />
            <BaseSelect
              id="filter-recurring"
              v-model="filters.recurring"
              label="Recorrência"
              placeholder="Todos"
              :options="[
                { label: 'Recorrentes', value: 'true' },
                { label: 'Não recorrentes', value: 'false' },
              ]"
            />
          </div>
        </section>

        <section class="analytics-panel">
          <div class="mb-5">
            <p class="text-sm font-bold uppercase text-sky-300">Análise visual</p>
            <h2 class="mt-2 text-2xl font-black text-slate-50">Gastos por categoria</h2>
          </div>

          <div class="space-y-3">
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
            <h2 class="mt-2 text-2xl font-black text-slate-50">Resumo financeiro</h2>
          </div>
          <div class="grid gap-3 md:grid-cols-3">
            <FinancialInsight
              v-for="insight in transactionInsights"
              :key="insight.title"
              :title="insight.title"
              :description="insight.description"
              :tone="insight.tone"
            />
          </div>
        </section>
      </div>
    </div>

    <BaseCard class="mt-5">
      <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-black text-slate-50">Lançamentos</h2>
          <p class="mt-2 text-sm text-slate-400">Tabela no desktop e cards financeiros no mobile.</p>
        </div>
        <span class="rounded-full bg-white/[0.06] px-3 py-1 text-sm text-slate-300">{{ filteredTransactions.length }} itens</span>
      </div>

      <EmptyState
        v-if="!filteredTransactions.length"
        title="Nenhuma transação encontrada"
        description="Cadastre uma transação ou ajuste os filtros."
      />

      <div v-else class="hidden xl:block">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Descrição</th>
              <th>Categoria</th>
              <th>Tipo</th>
              <th>Status</th>
              <th class="text-right">Valor</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transaction in filteredTransactions" :key="transaction.id">
              <td>
                <div class="flex items-center gap-3">
                  <span
                    class="grid size-10 place-items-center rounded-2xl"
                    :class="transaction.type === 'income' ? 'bg-emerald-400/10 text-emerald-200' : 'bg-rose-400/10 text-rose-200'"
                  >
                    {{ transaction.type === 'income' ? '↑' : '↓' }}
                  </span>
                  <strong class="text-slate-50">{{ transaction.description }}</strong>
                </div>
              </td>
              <td>{{ transaction.category?.name || 'Sem categoria' }}</td>
              <td>{{ typeLabel(transaction.type) }}</td>
              <td><TransactionStatusBadge :status="transaction.status" :recurring="Boolean(transaction.is_recurring)" /></td>
              <td
                class="text-right text-lg font-black"
                :class="transaction.type === 'income' ? 'text-emerald-300' : 'text-rose-300'"
              >
                {{ transaction.type === 'income' ? '+' : '-' }} {{ formatCurrency(transaction.amount) }}
              </td>
              <td>
                <div class="flex justify-end gap-2">
                  <BaseButton variant="secondary" title="Editar" @click="startEdit(transaction)">Editar</BaseButton>
                  <BaseButton variant="danger" title="Excluir" @click="removeTransaction(transaction)">Excluir</BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="grid gap-3 xl:hidden">
        <TransactionCard
          v-for="transaction in filteredTransactions"
          :key="transaction.id"
          :transaction="transaction"
          @edit="startEdit"
          @remove="removeTransaction"
        />
      </div>
    </BaseCard>
  </section>
</template>
