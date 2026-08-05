<script setup>
import { computed, reactive, ref, watch } from 'vue'
import BaseButton from '../base/BaseButton.vue'
import BaseInput from '../base/BaseInput.vue'
import BaseMonthPicker from '../base/BaseMonthPicker.vue'
import BaseTextarea from '../base/BaseTextarea.vue'
import EmptyState from '../data-display/EmptyState.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { dashboardService } from '../../services/dashboard/dashboardService'
import { monthlyReserveService } from '../../services/monthly-reserves/monthlyReserveService'
import { reserveAccountService } from '../../services/reserve-accounts/reserveAccountService'
import ReserveAccountRow from './ReserveAccountRow.vue'

const props = defineProps({
  reserveId: {
    type: [Number, String],
    default: null,
  },
  initialCompetency: {
    type: String,
    required: true,
  },
  initialObservations: {
    type: String,
    default: '',
  },
  // Modal usage: shows a "Cancelar" button that asks the parent to close the modal.
  showCancel: {
    type: Boolean,
    default: false,
  },
  // Inline usage: after creating a reserve in place, shows a button to reset back to a blank draft.
  allowReset: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['saved', 'cancel'])

const currentId = ref(props.reserveId)
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
  competency: props.initialCompetency,
  observations: props.initialObservations,
})

const competencySummary = ref(null)

// Contas de reserva (Nathan, Esposa, Viagem etc.) da competência selecionada.
const reserveAccounts = ref([])
const accountDrafts = reactive({})
const newAccountName = ref('')
const { isLoading: isAccountsLoading, withLoading: withAccountsLoading } = useLoading()
const { generalError: accountGeneralError, clearErrors: clearAccountErrors, setErrorsFromApi: setAccountErrorsFromApi } = useFormErrors()

const submitLabel = computed(() => currentId.value ? 'Salvar alterações' : 'Cadastrar reserva')
const entriesTotal = computed(() => entries.value.reduce((sum, entry) => sum + Number(entry.amount || 0), 0))
const previewRemainingAmount = computed(() => Number(competencySummary.value?.remaining_amount || 0))
const reserveAccountsTotal = computed(() => reserveAccounts.value.reduce((sum, account) => sum + Number(accountDrafts[account.id]?.balance || 0), 0))
const currentReservePreview = computed(() => reserveAccountsTotal.value + previewRemainingAmount.value)
const totalSavedPreview = computed(() => currentReservePreview.value + entriesTotal.value)
const entrySubmitLabel = computed(() => editingEntryId.value ? 'Salvar lançamento' : 'Adicionar lançamento')

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

async function loadEntries(reserveId) {
  clearEntryErrors()

  try {
    entries.value = await monthlyReserveService.listEntries(reserveId)
  } catch (error) {
    setEntryErrorsFromApi(error)
  }
}

async function handleSubmit() {
  clearErrors()

  await withLoading(async () => {
    try {
      await saveAccountDrafts(form.competency)

      if (currentId.value) {
        const updated = await monthlyReserveService.update(currentId.value, {
          competency: form.competency,
          observations: form.observations,
        })
        emit('saved', updated)
      } else {
        const created = await monthlyReserveService.create({
          competency: form.competency,
          investimento: 0,
          observations: form.observations,
        })
        currentId.value = created.id
        await loadEntries(created.id)
        emit('saved', created)
      }

      await loadReserveAccounts(form.competency)
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
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
        await monthlyReserveService.updateEntry(currentId.value, editingEntryId.value, payload)
      } else {
        await monthlyReserveService.createEntry(currentId.value, payload)
      }

      resetEntryForm()
      await loadEntries(currentId.value)
      emit('saved')
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
      await monthlyReserveService.removeEntry(currentId.value, entry.id)
      await loadEntries(currentId.value)
      emit('saved')
    } catch (error) {
      setEntryErrorsFromApi(error)
    }
  })
}

function resetToBlankDraft() {
  currentId.value = null
  form.competency = getCurrentCompetency()
  form.observations = ''
  entries.value = []
  clearErrors()
  resetEntryForm()
}

if (currentId.value) {
  loadEntries(currentId.value)
}
</script>

<template>
  <div>
    <p v-if="generalError" class="mb-4 rounded-2xl bg-rose-500/10 p-4 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <form class="space-y-5" @submit.prevent="handleSubmit">
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
        <BaseButton v-if="showCancel" type="button" class="w-full" variant="secondary" @click="$emit('cancel')">Cancelar</BaseButton>
        <BaseButton v-if="allowReset && currentId" type="button" class="w-full" variant="secondary" @click="resetToBlankDraft">Cancelar edição e iniciar nova reserva</BaseButton>
      </div>
    </form>

    <div v-if="currentId" class="mt-6 rounded-3xl border border-white/10 bg-slate-950/60 p-4">
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
  </div>
</template>
