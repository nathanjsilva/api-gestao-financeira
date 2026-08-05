<script setup>
import { computed, onMounted, ref } from 'vue'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseModal from '../../components/base/BaseModal.vue'
import BasePagination from '../../components/base/BasePagination.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import MonthlyReserveEditor from '../../components/reserve/MonthlyReserveEditor.vue'
import ReserveChart from '../../components/reserve/ReserveChart.vue'
import FinancialInsight from '../../components/shared/FinancialInsight.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { usePagination } from '../../composables/usePagination'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { monthlyReserveService } from '../../services/monthly-reserves/monthlyReserveService'

const reserves = ref([])
const editingId = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi } = useFormErrors()

const { currentPage, totalPages, paginatedItems, pageNumbers, nextPage, prevPage, goToPage } = usePagination(reserves)

const latestReserve = computed(() => reserves.value[0] || null)
const previousReserve = computed(() => reserves.value[1] || null)
const reserveDifference = computed(() => Number(latestReserve.value?.current_reserve || 0) - Number(previousReserve.value?.current_reserve || 0))
const investmentDifference = computed(() => Number(latestReserve.value?.investimento || 0) - Number(previousReserve.value?.investimento || 0))
const reserveGrowthPercentage = computed(() => percentageChange(Number(latestReserve.value?.current_reserve || 0), Number(previousReserve.value?.current_reserve || 0)))
const isEditModalOpen = computed(() => editingId.value !== null)
const editingReserve = computed(() => reserves.value.find((reserve) => reserve.id === editingId.value) || null)

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

function startEdit(reserve) {
  editingId.value = reserve.id
}

function closeEditModal() {
  editingId.value = null
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
        <h2 class="text-2xl font-black text-slate-50">Nova reserva</h2>
        <p class="mt-2 text-sm leading-6 text-slate-400">
          Declare o saldo de cada conta de reserva no mês. Contas sem alteração mantêm automaticamente o valor do mês anterior.
        </p>

        <div class="mt-6">
          <MonthlyReserveEditor
            :initial-competency="getCurrentCompetency()"
            allow-reset
            @saved="loadReserves"
          />
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

    <BaseModal :open="isEditModalOpen" title="Editar reserva" @close="closeEditModal">
      <MonthlyReserveEditor
        v-if="editingReserve"
        :key="editingId"
        :reserve-id="editingReserve.id"
        :initial-competency="editingReserve.competency"
        :initial-observations="editingReserve.observations || ''"
        show-cancel
        @saved="loadReserves"
        @cancel="closeEditModal"
      />
    </BaseModal>
  </section>
</template>
