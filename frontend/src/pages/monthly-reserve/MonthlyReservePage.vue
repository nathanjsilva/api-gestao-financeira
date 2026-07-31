<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import BaseMonthPicker from '../../components/base/BaseMonthPicker.vue'
import BasePagination from '../../components/base/BasePagination.vue'
import BaseTextarea from '../../components/base/BaseTextarea.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import ReserveAccountRow from '../../components/reserve/ReserveAccountRow.vue'
import ReserveChart from '../../components/reserve/ReserveChart.vue'
import FinancialInsight from '../../components/shared/FinancialInsight.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { usePagination } from '../../composables/usePagination'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { dashboardService } from '../../services/dashboard/dashboardService'
import { monthlyReserveService } from '../../services/monthly-reserves/monthlyReserveService'
import { reserveAccountService } from '../../services/reserve-accounts/reserveAccountService'

const reserves = ref([])
const editingId = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

const entries = ref([])
const editingEntryId = ref(null)
const { isLoading: isEntryLoading, withLoading: withEntryLoading } = useLoading()
const {
  generalError: entryGeneralError,
  clearErrors: clearEntryErrors,
  setErrorsFromApi: setEntryErrorsFromApi,
  fieldError: entryFieldError,
} = useFormErrors()

const entryForm = reactive({
  description: '',
  amount: '',
})

const form = reactive({
  competency: getCurrentCompetency(),
  observations: '',
})

const competencySummary = ref(null)

// Contas de reserva (Nathan, Esposa, Viagem etc.) da competência selecionada.
const reserveAccounts = ref([])
const accountDrafts = reactive({})
const newAccountName = ref('')
const { isLoading: isAccountsLoading, withLoading: withAccountsLoading } = useLoading()
const { generalError: accountGeneralError, clearErrors: clearAccountErrors, setErrorsFromApi: setAccountErrorsFromApi } = useFormErrors()

const { currentPage, totalPages, paginatedItems, pageNumbers, nextPage, prevPage, goToPage } = usePagination(reserves)

const latestReserve = computed(() => reserves.value[0] || null)
const previousReserve = computed(() => reserves.value[1] || null)
const reserveDifference = computed(() => Number(latestReserve.value?.current_reserve || 0) - Number(previousReserve.value?.current_reserve || 0))
const investmentDifference = computed(() => Number(latestReserve.value?.investimento || 0) - Number(previousReserve.value?.investimento || 0))
const reserveGrowthPercentage = computed(() => percentageChange(Number(latestReserve.value?.current_reserve || 0), Number(previousReserve.value?.current_reserve || 0)))
const submitLabel = computed(() => editingId.value ? 'Salvar alterações' : 'Cadastrar reserva')
const entriesTotal = computed(() => entries.value.reduce((sum, entry) => sum + Number(entry.amount || 0), 0))
const previewRemainingAmount = computed(() => Number(competencySummary.value?.remaining_amount || 0))
const reserveAccountsTotal = computed(() => reserveAccounts.value.reduce((sum, account) => sum + Number(accountDrafts[account.id]?.balance || 0), 0))
const currentReservePreview = computed(() => reserveAccountsTotal.value + previewRemainingAmount.value)
const totalSavedPreview = computed(() => currentReservePreview.value + entriesTotal.value)
const entrySubmitLabel = computed(() => editingEntryId.value ? 'Salvar lançamento' : 'Adicionar lançamento')

const reserveInsights = computed(() => {
  const insights = []

  if (!latestReserve.value) {
    return [{
      title: 'Comece sua evolução',
      description: 'Cadastre a primeira reserva mensal para acompanhar sua evolução patrimonial.',
      tone: 'info',
    }]
  }

  insights.push({
    title: reserveDifference.value >= 0 ? 'Reserva em crescimento' : 'Reserva em queda',
    description: reserveDifference.value >= 0
      ? `Sua reserva aumentou ${formatCurrency(reserveDifference.value)} em relação ao registro anterior.`
      : `Sua reserva caiu ${formatCurrency(Math.abs(reserveDifference.value))} em relação ao registro anterior.`,
    tone: reserveDifference.value >= 0 ? 'success' : 'danger',
  })

  insights.push({
    title: investmentDifference.value >= 0 ? 'Investimento reforçado' : 'Investimento menor',
    description: investmentDifference.value >= 0
      ? `O investimento cresceu ${formatCurrency(investmentDifference.value)} no comparativo.`
      : `O investimento foi ${formatCurrency(Math.abs(investmentDifference.value))} menor que o anterior.`,
    tone: investmentDifference.value >= 0 ? 'success' : 'warning',
  })

  if (Number(latestReserve.value.remaining_amount || 0) < 0) {
    insights.push({
      title: 'Saldo final negativo',
      description: 'O mês fechou com mais gastos do que entradas. Vale revisar gastos recorrentes.',
      tone: 'danger',
    })
  }

  return insights
})

function percentageChange(current, previous) {
  if (!previous) {
    return current > 0 ? 100 : 0
  }

  return ((current - previous) / previous) * 100
}

function formatPercentage(value) {
  return `${Number(value || 0).toLocaleString('pt-BR', {
    maximumFractionDigits: 1,
  })}%`
}

function resetForm() {
  editingId.value = null
  form.competency = getCurrentCompetency()
  form.observations = ''
  competencySummary.value = null
  clearErrors()
  resetEntryForm()
  entries.value = []
}

function buildAccountDrafts(accounts) {
  for (const key of Object.keys(accountDrafts)) {
    delete accountDrafts[key]
  }

  for (const account of accounts) {
    accountDrafts[account.id] = {
      balance: account.current_balance ?? 0,
      note: account.note ?? '',
    }
  }
}

async function loadReserveAccounts(competency) {
  if (!competency) {
    reserveAccounts.value = []
    return
  }

  clearAccountErrors()

  await withAccountsLoading(async () => {
    try {
      reserveAccounts.value = await reserveAccountService.list({ competency })
      buildAccountDrafts(reserveAccounts.value)
    } catch (error) {
      setAccountErrorsFromApi(error)
    }
  })
}

async function fetchCompetencySummary(competency) {
  if (!competency) {
    competencySummary.value = null
    return
  }

  try {
    competencySummary.value = await dashboardService.monthlySummary(competency)
  } catch {
    competencySummary.value = null
  }
}

watch(() => form.competency, (competency) => {
  loadReserveAccounts(competency)
  fetchCompetencySummary(competency)
}, { immediate: true })

async function loadReserves() {
  clearErrors()

  await withLoading(async () => {
    try {
      reserves.value = await monthlyReserveService.list()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function saveAccountDrafts(competency) {
  for (const account of reserveAccounts.value) {
    const draft = accountDrafts[account.id]

    if (!draft) {
      continue
    }

    const mudouSaldo = Number(draft.balance || 0) !== Number(account.current_balance || 0)
    const mudouNota = (draft.note || '') !== (account.note || '')

    if (!mudouSaldo && !mudouNota) {
      continue
    }

    await reserveAccountService.setEntry(account.id, competency, {
      balance: Number(draft.balance || 0),
      note: draft.note || null,
    })
  }
}

async function createReserveAccount() {
  if (!newAccountName.value.trim()) {
    return
  }

  clearAccountErrors()

  await withAccountsLoading(async () => {
    try {
      await reserveAccountService.create({ name: newAccountName.value.trim() })
      newAccountName.value = ''
      await loadReserveAccounts(form.competency)
    } catch (error) {
      setAccountErrorsFromApi(error)
    }
  })
}

async function handleSubmit() {
  clearErrors()

  await withLoading(async () => {
    try {
      await saveAccountDrafts(form.competency)

      if (editingId.value) {
        await monthlyReserveService.update(editingId.value, {
          competency: form.competency,
          observations: form.observations,
        })
        await loadReserves()
      } else {
        const created = await monthlyReserveService.create({
          competency: form.competency,
          investimento: 0,
          observations: form.observations,
        })
        await loadReserves()
        await startEdit(created)
      }

      await loadReserveAccounts(form.competency)
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function startEdit(reserve) {
  editingId.value = reserve.id
  form.competency = reserve.competency
  form.observations = reserve.observations || ''
  clearErrors()
  await loadEntries(reserve.id)
}

async function loadEntries(reserveId) {
  clearEntryErrors()

  try {
    entries.value = await monthlyReserveService.listEntries(reserveId)
  } catch (error) {
    setEntryErrorsFromApi(error)
  }
}

function resetEntryForm() {
  editingEntryId.value = null
  entryForm.description = ''
  entryForm.amount = ''
  clearEntryErrors()
}

function startEditEntry(entry) {
  editingEntryId.value = entry.id
  entryForm.description = entry.description
  entryForm.amount = entry.amount
  clearEntryErrors()
}

async function handleEntrySubmit() {
  clearEntryErrors()

  const payload = {
    description: entryForm.description,
    amount: Number(entryForm.amount || 0),
  }

  await withEntryLoading(async () => {
    try {
      if (editingEntryId.value) {
        await monthlyReserveService.updateEntry(editingId.value, editingEntryId.value, payload)
      } else {
        await monthlyReserveService.createEntry(editingId.value, payload)
      }

      resetEntryForm()
      await loadEntries(editingId.value)
      await loadReserves()
    } catch (error) {
      setEntryErrorsFromApi(error)
    }
  })
}

async function deleteEntry(entry) {
  if (!confirm(`Excluir o lançamento "${entry.description}"?`)) {
    return
  }

  await withEntryLoading(async () => {
    try {
      await monthlyReserveService.removeEntry(editingId.value, entry.id)
      await loadEntries(editingId.value)
      await loadReserves()
    } catch (error) {
      setEntryErrorsFromApi(error)
    }
  })
}

async function removeReserve(reserve) {
  if (!confirm(`Excluir a reserva da competência ${reserve.competency}?`)) {
    return
  }

  await withLoading(async () => {
    try {
      await monthlyReserveService.remove(reserve.id)
      await loadReserves()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

onMounted(loadReserves)
</script>

<template>
  <section class="dashboard-shell mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="dashboard-hero">
      <PageHeader
        eyebrow="Reserva financeira"
        title="Reserva mensal"
        description="Acompanhe sua evolução patrimonial, investimentos e saldo final mês a mês."
      />
    </div>

    <p v-if="generalError" class="mt-5 rounded-2xl bg-rose-500/10 p-4 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article class="financial-card financial-card--sky">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-semibold text-slate-400">Reserva atual</p>
            <strong class="mt-3 block text-3xl font-black text-slate-50">
              {{ formatCurrency(latestReserve?.current_reserve) }}
            </strong>
            <p class="mt-3 text-sm" :class="reserveDifference >= 0 ? 'text-emerald-300' : 'text-rose-300'">
              {{ reserveDifference >= 0 ? '↑' : '↓' }} {{ formatPercentage(reserveGrowthPercentage) }} vs. registro anterior
            </p>
          </div>
          <span class="financial-card__orb" />
        </div>
      </article>

      <article class="financial-card financial-card--emerald">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-semibold text-slate-400">Investimentos</p>
            <strong class="mt-3 block text-3xl font-black text-slate-50">
              {{ formatCurrency(latestReserve?.investimento) }}
            </strong>
            <p class="mt-3 text-sm" :class="investmentDifference >= 0 ? 'text-emerald-300' : 'text-amber-300'">
              {{ investmentDifference >= 0 ? '↑' : '↓' }} {{ formatCurrency(Math.abs(investmentDifference)) }} no comparativo
            </p>
          </div>
          <span class="financial-card__orb" />
        </div>
      </article>

      <article class="financial-card financial-card--violet">
        <p class="text-sm font-semibold text-slate-400">Total guardado</p>
        <strong class="mt-3 block text-3xl font-black text-slate-50">
          {{ formatCurrency(latestReserve?.total_saved) }}
        </strong>
        <p class="mt-3 text-sm text-slate-400">Reserva atual + investimentos.</p>
      </article>

      <article class="financial-card financial-card--rose">
        <p class="text-sm font-semibold text-slate-400">Saldo final</p>
        <strong
          class="mt-3 block text-3xl font-black"
          :class="Number(latestReserve?.remaining_amount || 0) >= 0 ? 'text-emerald-300' : 'text-rose-300'"
        >
          {{ formatCurrency(latestReserve?.remaining_amount) }}
        </strong>
        <p class="mt-3 text-sm text-slate-400">Entradas menos gastos do mês.</p>
      </article>
    </div>

    <div class="mt-5 grid min-w-0 gap-4 xl:grid-cols-[minmax(360px,0.9fr)_minmax(0,1.1fr)]">
      <BaseCard>
        <h2 class="text-2xl font-black text-slate-50">
          {{ editingId ? 'Editar reserva' : 'Nova reserva' }}
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-400">
          Declare o saldo de cada conta de reserva no mês. Contas sem alteração mantêm automaticamente o valor do mês anterior.
        </p>

        <form class="mt-6 space-y-5" @submit.prevent="handleSubmit">
          <BaseMonthPicker id="reserve-competency" v-model="form.competency" label="Competência" :error="fieldError('competency')" />

          <div>
            <p class="mb-3 text-sm font-bold uppercase text-sky-300">Contas de reserva</p>

            <p v-if="accountGeneralError" class="mb-3 text-sm text-rose-300">{{ accountGeneralError }}</p>

            <EmptyState
              v-if="!isAccountsLoading && !reserveAccounts.length"
              title="Nenhuma conta de reserva ainda"
              description="Crie uma conta para cada pessoa ou objetivo (ex: Nathan, Esposa, Viagem)."
            />

            <div v-else class="space-y-3">
              <ReserveAccountRow
                v-for="account in reserveAccounts"
                :key="account.id"
                :account="account"
                v-model="accountDrafts[account.id]"
              />
            </div>

            <div class="mt-3 flex gap-2">
              <BaseInput
                id="new-account-name"
                v-model="newAccountName"
                label="Nova conta"
                placeholder="Ex: Reserva de emergência"
              />
              <BaseButton type="button" variant="secondary" class="mt-7 shrink-0" :disabled="isAccountsLoading" @click="createReserveAccount">
                Adicionar
              </BaseButton>
            </div>
          </div>

          <BaseTextarea id="observations" v-model="form.observations" label="Observações" placeholder="Resumo opcional do mês" :error="fieldError('observations')" />

          <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-4">
            <p class="text-sm font-bold uppercase text-sky-300">Prévia automática</p>
            <div class="mt-4 grid gap-3">
              <div>
                <span class="text-sm text-slate-400">Contas de reserva</span>
                <strong class="block text-lg text-slate-50">{{ formatCurrency(reserveAccountsTotal) }}</strong>
              </div>
              <div>
                <span class="text-sm text-slate-400">Saldo das transações do mês (automático)</span>
                <strong class="block text-lg" :class="previewRemainingAmount >= 0 ? 'text-emerald-300' : 'text-rose-300'">
                  {{ formatCurrency(previewRemainingAmount) }}
                </strong>
              </div>
              <div>
                <span class="text-sm text-slate-400">Reserva atual (contas + saldo do mês)</span>
                <strong class="block text-lg text-emerald-300">{{ formatCurrency(currentReservePreview) }}</strong>
              </div>
              <div>
                <span class="text-sm text-slate-400">Total guardado (reserva atual + investimentos)</span>
                <strong class="block text-lg text-violet-300">{{ formatCurrency(totalSavedPreview) }}</strong>
              </div>
            </div>
          </div>

          <div class="grid gap-3">
            <BaseButton type="submit" class="w-full" :loading="isLoading">{{ submitLabel }}</BaseButton>
            <BaseButton v-if="editingId" class="w-full" variant="secondary" @click="resetForm">Cancelar</BaseButton>
          </div>
        </form>

        <div v-if="editingId" class="mt-6 rounded-3xl border border-white/10 bg-slate-950/60 p-4">
          <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-bold uppercase text-sky-300">Lançamentos de investimento</p>
            <span class="rounded-full bg-white/[0.06] px-3 py-1 text-xs text-slate-300">{{ formatCurrency(entriesTotal) }}</span>
          </div>

          <p v-if="entryGeneralError" class="mt-3 text-sm text-rose-300">{{ entryGeneralError }}</p>

          <EmptyState
            v-if="!entries.length"
            title="Nenhum lançamento neste mês"
            description="Cadastre os aportes de investimento do mês — eles ficam sempre separados da reserva."
          />

          <div v-else class="mt-4 space-y-2">
            <div
              v-for="entry in entries"
              :key="entry.id"
              class="flex items-center justify-between gap-3 rounded-2xl bg-white/[0.04] px-4 py-3"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-100">{{ entry.description }}</p>
                <p class="text-xs text-slate-400">{{ formatCurrency(entry.amount) }}</p>
              </div>
              <div class="flex shrink-0 gap-3">
                <button type="button" class="text-xs font-bold text-sky-300 hover:text-sky-200" :disabled="isEntryLoading" @click="startEditEntry(entry)">
                  Editar
                </button>
                <button type="button" class="text-xs font-bold text-rose-300 hover:text-rose-200" :disabled="isEntryLoading" @click="deleteEntry(entry)">
                  Excluir
                </button>
              </div>
            </div>
          </div>

          <form class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="handleEntrySubmit">
            <BaseInput id="entry-description" v-model="entryForm.description" label="Descrição" placeholder="Ex: Aporte em fundo DI" :error="entryFieldError('description')" />
            <BaseInput id="entry-amount" v-model="entryForm.amount" label="Valor" type="number" placeholder="Ex: 250.00" :error="entryFieldError('amount')" />
            <div class="flex gap-2 sm:col-span-2">
              <BaseButton type="submit" class="flex-1" :loading="isEntryLoading">{{ entrySubmitLabel }}</BaseButton>
              <BaseButton v-if="editingEntryId" type="button" variant="secondary" @click="resetEntryForm">Cancelar</BaseButton>
            </div>
          </form>
        </div>
      </BaseCard>

      <div class="space-y-4">
        <ReserveChart :items="reserves" />

        <section class="analytics-panel">
          <div class="mb-5">
            <p class="text-sm font-bold uppercase text-sky-300">Insights</p>
            <h2 class="mt-2 text-2xl font-black text-slate-50">Leitura rápida</h2>
          </div>
          <div class="grid gap-3 md:grid-cols-2">
            <FinancialInsight
              v-for="insight in reserveInsights"
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
          <h2 class="text-2xl font-black text-slate-50">Histórico de reservas</h2>
          <p class="mt-2 text-sm text-slate-400">Informações organizadas por competência.</p>
        </div>
        <span class="rounded-full bg-white/[0.06] px-3 py-1 text-sm text-slate-300">{{ reserves.length }} registros</span>
      </div>

      <EmptyState
        v-if="!reserves.length"
        title="Nenhuma reserva cadastrada"
        description="Cadastre a reserva mensal para completar a análise financeira."
      />

      <div v-else class="hidden xl:block">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Competência</th>
              <th>Reserva atual</th>
              <th>Total guardado</th>
              <th>Total do mês</th>
              <th>Total gasto</th>
              <th>Saldo final</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reserve in paginatedItems" :key="reserve.id">
              <td class="font-black text-slate-50">{{ reserve.competency }}</td>
              <td><span class="value-badge value-badge--info">{{ formatCurrency(reserve.current_reserve) }}</span></td>
              <td><span class="value-badge value-badge--success">{{ formatCurrency(reserve.total_saved) }}</span></td>
              <td>{{ formatCurrency(reserve.total_income) }}</td>
              <td>{{ formatCurrency(reserve.total_expense) }}</td>
              <td :class="Number(reserve.remaining_amount || 0) >= 0 ? 'text-emerald-300' : 'text-rose-300'">
                {{ formatCurrency(reserve.remaining_amount) }}
              </td>
              <td>
                <div class="flex justify-end gap-2">
                  <BaseButton variant="secondary" :disabled="isLoading" @click="startEdit(reserve)">Editar</BaseButton>
                  <BaseButton variant="danger" :disabled="isLoading" @click="removeReserve(reserve)">Excluir</BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="grid gap-3 lg:hidden">
        <article
          v-for="reserve in paginatedItems"
          :key="reserve.id"
          class="rounded-3xl border border-white/10 bg-white/[0.04] p-4"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase text-slate-500">Competência</p>
              <strong class="text-xl text-slate-50">{{ reserve.competency }}</strong>
            </div>
            <strong :class="Number(reserve.remaining_amount || 0) >= 0 ? 'text-emerald-300' : 'text-rose-300'">
              {{ formatCurrency(reserve.remaining_amount) }}
            </strong>
          </div>
          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <span class="value-badge value-badge--info">Reserva: {{ formatCurrency(reserve.current_reserve) }}</span>
            <span class="value-badge value-badge--success">Guardado: {{ formatCurrency(reserve.total_saved) }}</span>
            <span class="value-badge">Entradas: {{ formatCurrency(reserve.total_income) }}</span>
            <span class="value-badge value-badge--danger">Gastos: {{ formatCurrency(reserve.total_expense) }}</span>
          </div>
          <div class="mt-4 grid grid-cols-2 gap-2">
            <BaseButton variant="secondary" :disabled="isLoading" @click="startEdit(reserve)">Editar</BaseButton>
            <BaseButton variant="danger" :disabled="isLoading" @click="removeReserve(reserve)">Excluir</BaseButton>
          </div>
        </article>
      </div>

      <BasePagination
        :current-page="currentPage"
        :total-pages="totalPages"
        :total="reserves.length"
        :page-numbers="pageNumbers"
        @prev="prevPage"
        @next="nextPage"
        @go="goToPage"
      />
    </BaseCard>
  </section>
</template>
