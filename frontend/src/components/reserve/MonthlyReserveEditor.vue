<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import BaseButton from '../base/BaseButton.vue'
import BaseInput from '../base/BaseInput.vue'
import BaseMonthPicker from '../base/BaseMonthPicker.vue'
import BaseSelect from '../base/BaseSelect.vue'
import BaseTextarea from '../base/BaseTextarea.vue'
import EmptyState from '../data-display/EmptyState.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { monthlyReserveService } from '../../services/monthly-reserves/monthlyReserveService'
import { reserveAccountService } from '../../services/reserve-accounts/reserveAccountService'
import ReserveAccountRow from './ReserveAccountRow.vue'

type ReserveItem = {
  id: number
  name: string
  type: 'reserve' | 'investment'
  active: boolean
  current_balance: number | null
  previous_balance: number | null
  delta: number | null
  is_inherited: boolean
  note: string | null
  archive_status_needs_review: boolean
}

type ItemDraft = { name: string; balance: number | string; note: string }

const props = defineProps({
  reserveId: { type: [Number, String], default: null },
  initialCompetency: { type: String, required: true },
  initialObservations: { type: String, default: '' },
  showCancel: { type: Boolean, default: false },
  allowReset: { type: Boolean, default: false },
})

const emit = defineEmits(['saved', 'cancel'])
const currentId = ref(props.reserveId)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()
const { isLoading: isItemsLoading, withLoading: withItemsLoading } = useLoading()
const { generalError: itemsError, clearErrors: clearItemsError, setErrorsFromApi: setItemsError } = useFormErrors()

const form = reactive({ competency: props.initialCompetency, observations: props.initialObservations })
const items = ref<ReserveItem[]>([])
const drafts = reactive<Record<number, ItemDraft>>({})
const newItemName = ref('')
const newItemType = ref<'reserve' | 'investment'>('reserve')
const itemTypeOptions = [
  { value: 'reserve', label: 'Reserva' },
  { value: 'investment', label: 'Investimento' },
]

const activeItems = computed(() => items.value.filter((item) => item.active))
const archivedItems = computed(() => items.value.filter((item) => !item.active))
const reserveTotal = computed(() => activeItems.value
  .filter((item) => item.type === 'reserve')
  .reduce((sum, item) => sum + Number(drafts[item.id]?.balance || 0), 0))
const investmentTotal = computed(() => activeItems.value
  .filter((item) => item.type === 'investment')
  .reduce((sum, item) => sum + Number(drafts[item.id]?.balance || 0), 0))
const totalSaved = computed(() => reserveTotal.value + investmentTotal.value)
const submitLabel = computed(() => currentId.value ? 'Salvar alterações' : 'Registrar competência')

function buildDrafts(list: ReserveItem[]) {
  for (const key of Object.keys(drafts)) delete drafts[Number(key)]
  for (const item of list) {
    drafts[item.id] = {
      name: item.name,
      balance: item.current_balance ?? 0,
      note: item.note ?? '',
    }
  }
}

async function loadItems(competency: string) {
  if (!competency) {
    items.value = []
    return
  }
  clearItemsError()
  await withItemsLoading(async () => {
    try {
      items.value = await reserveAccountService.list({ competency, include_archived: 1 })
      buildDrafts(items.value)
    } catch (error) {
      setItemsError(error)
    }
  })
}

watch(() => form.competency, loadItems, { immediate: true })

async function saveItemBalances(competency: string) {
  const entries = []
  for (const item of activeItems.value) {
    const draft = drafts[item.id]
    if (!draft) continue
    if (draft.name.trim() !== item.name) {
      await reserveAccountService.update(item.id, { name: draft.name.trim() })
    }
    const balanceChanged = item.current_balance === null
      || Number(draft.balance || 0) !== Number(item.current_balance || 0)
    const noteChanged = (draft.note || '') !== (item.note || '')
    if (balanceChanged || noteChanged) {
      entries.push({ account_id: item.id, balance: Number(draft.balance || 0), note: draft.note || null })
    }
  }
  if (entries.length) await reserveAccountService.setEntries(competency, entries)
}

async function setItemActive(item: ReserveItem, active: boolean) {
  if (!active && !confirm(`Arquivar "${item.name}" a partir de ${form.competency}?`)) return
  clearItemsError()
  await withItemsLoading(async () => {
    try {
      await reserveAccountService.update(item.id, { active, competency: form.competency })
      await loadItems(form.competency)
    } catch (error) {
      setItemsError(error)
    }
  })
}

async function createItem() {
  if (!newItemName.value.trim()) return
  clearItemsError()
  await withItemsLoading(async () => {
    try {
      await reserveAccountService.create({ name: newItemName.value.trim(), type: newItemType.value })
      newItemName.value = ''
      newItemType.value = 'reserve'
      await loadItems(form.competency)
    } catch (error) {
      setItemsError(error)
    }
  })
}

async function handleSubmit() {
  clearErrors()
  await withLoading(async () => {
    try {
      await saveItemBalances(form.competency)
      if (currentId.value) {
        emit('saved', await monthlyReserveService.update(currentId.value, {
          competency: form.competency,
          observations: form.observations,
        }))
      } else {
        const created = await monthlyReserveService.create({
          competency: form.competency,
          investimento: 0,
          observations: form.observations,
        })
        currentId.value = created.id
        emit('saved', created)
      }
      await loadItems(form.competency)
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

function resetToBlankDraft() {
  currentId.value = null
  form.competency = getCurrentCompetency()
  form.observations = ''
  clearErrors()
}
</script>

<template>
  <div>
    <p v-if="generalError" class="mb-4 rounded-2xl bg-rose-500/10 p-4 text-sm text-rose-200">{{ generalError }}</p>
    <form class="space-y-5" @submit.prevent="handleSubmit">
      <BaseMonthPicker id="reserve-competency" v-model="form.competency" label="Competência dos saldos" :error="fieldError('competency')" />

      <div>
        <p class="mb-3 text-sm font-bold uppercase text-sky-300">Itens patrimoniais</p>
        <p class="mb-3 text-sm text-slate-400">Informe o saldo total de cada item nesta competência. Sem nova declaração, o último saldo é herdado.</p>
        <p v-if="itemsError" class="mb-3 text-sm text-rose-300">{{ itemsError }}</p>
        <EmptyState v-if="!isItemsLoading && !activeItems.length" title="Nenhum item ativo" description="Cadastre uma Reserva ou um Investimento para acompanhar seu saldo." />
        <div v-else class="space-y-3">
          <ReserveAccountRow
            v-for="item in activeItems"
            :key="item.id"
            :account="item"
            v-model="drafts[item.id]"
            @archive="setItemActive(item, false)"
          />
        </div>

        <div v-if="archivedItems.length" class="mt-4 rounded-2xl border border-white/10 bg-slate-950/40 p-4">
          <p class="text-sm font-bold text-slate-300">Itens arquivados</p>
          <p v-if="archivedItems.some((item) => item.archive_status_needs_review)" class="mt-2 text-xs text-amber-300">
            Escolha a competência acima para confirmar quando os itens legados foram arquivados.
          </p>
          <div class="mt-3 space-y-2">
            <div v-for="item in archivedItems" :key="item.id" class="flex items-center justify-between gap-3">
              <span class="truncate text-sm text-slate-400">{{ item.name }} · {{ item.type === 'investment' ? 'Investimento' : 'Reserva' }}</span>
              <BaseButton
                type="button"
                variant="secondary"
                :disabled="isItemsLoading"
                @click="setItemActive(item, item.archive_status_needs_review ? false : true)"
              >
                {{ item.archive_status_needs_review ? 'Confirmar arquivamento nesta competência' : 'Reativar' }}
              </BaseButton>
            </div>
          </div>
        </div>

        <div class="mt-3 grid gap-2 @min-[40rem]:grid-cols-[minmax(0,1fr)_12rem_auto]">
          <BaseInput id="new-item-name" v-model="newItemName" label="Descrição do item" placeholder="Ex.: Reserva de emergência" />
          <BaseSelect id="new-item-type" v-model="newItemType" label="Classificação" :options="itemTypeOptions" />
          <BaseButton type="button" variant="secondary" class="mt-7" :disabled="isItemsLoading" @click="createItem">Adicionar</BaseButton>
        </div>
      </div>

      <BaseTextarea id="observations" v-model="form.observations" label="Observações" placeholder="Nota opcional da competência" :error="fieldError('observations')" />

      <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-4">
        <p class="text-sm font-bold uppercase text-sky-300">Patrimônio na competência</p>
        <div class="mt-4 grid gap-3 @min-[40rem]:grid-cols-3">
          <div><span class="text-sm text-slate-400">Reserva atual</span><strong class="block text-lg text-slate-50">{{ formatCurrency(reserveTotal) }}</strong></div>
          <div><span class="text-sm text-slate-400">Investimento atual</span><strong class="block text-lg text-slate-50">{{ formatCurrency(investmentTotal) }}</strong></div>
          <div><span class="text-sm text-slate-400">Total guardado</span><strong class="block text-lg text-violet-300">{{ formatCurrency(totalSaved) }}</strong></div>
        </div>
      </div>

      <div class="grid gap-3">
        <BaseButton type="submit" class="w-full" :loading="isLoading">{{ submitLabel }}</BaseButton>
        <BaseButton v-if="showCancel" type="button" class="w-full" variant="secondary" @click="$emit('cancel')">Cancelar</BaseButton>
        <BaseButton v-if="allowReset && currentId" type="button" class="w-full" variant="secondary" @click="resetToBlankDraft">Cancelar edição e iniciar nova competência</BaseButton>
      </div>
    </form>
  </div>
</template>
