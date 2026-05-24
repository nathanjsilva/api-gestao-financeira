<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import BaseSelect from '../../components/base/BaseSelect.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
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

const filteredTransactions = computed(() => {
  return transactions.value.filter((transaction) => {
    const matchesSearch = transaction.description.toLowerCase().includes(search.value.toLowerCase())
    const matchesCategory = !filters.category_id || String(transaction.category_id) === String(filters.category_id)
    const matchesStatus = !filters.status || transaction.status === filters.status

    return matchesSearch && matchesCategory && matchesStatus
  })
})

const submitLabel = computed(() => editingId.value ? 'Salvar alteracoes' : 'Cadastrar transacao')

function typeLabel(type) {
  return TRANSACTION_TYPES.find((item) => item.value === type)?.label || type
}

function statusLabel(status) {
  return TRANSACTION_STATUS.find((item) => item.value === status)?.label || status
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
  if (!confirm(`Excluir a transacao "${transaction.description}"?`)) {
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
  <section class="mx-auto max-w-7xl px-6 py-10">
    <PageHeader
      eyebrow="Lancamentos"
      title="Transacoes"
      description="Controle entradas e saidas por competencia mensal, categoria, status e recorrencia."
    >
      <template #actions>
        <BaseInput
          id="transactions-competency"
          v-model="filters.competency"
          label="Competencia"
          type="month"
        />
        <BaseButton class="mt-7" :loading="isLoading" @click="loadTransactions">
          Filtrar
        </BaseButton>
      </template>
    </PageHeader>

    <p v-if="generalError" class="mb-5 rounded-md bg-rose-500/10 p-3 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
      <BaseCard>
        <h2 class="text-xl font-black text-slate-50">
          {{ editingId ? 'Editar transacao' : 'Nova transacao' }}
        </h2>

        <form class="mt-5 space-y-4" @submit.prevent="handleSubmit">
          <BaseSelect
            id="transaction-type"
            v-model="form.type"
            label="Tipo"
            :options="TRANSACTION_TYPES"
            :error="fieldError('type')"
          />

          <BaseSelect
            id="transaction-category"
            v-model="form.category_id"
            label="Categoria"
            :options="formCategoryOptions"
            :error="fieldError('category_id')"
          />

          <BaseInput
            id="transaction-description"
            v-model="form.description"
            label="Descricao"
            placeholder="Ex: Supermercado"
            :error="fieldError('description')"
          />

          <BaseInput
            id="transaction-amount"
            v-model="form.amount"
            label="Valor"
            type="number"
            placeholder="0.00"
            :error="fieldError('amount')"
          />

          <div class="grid gap-4 sm:grid-cols-2">
            <BaseSelect
              id="transaction-status"
              v-model="form.status"
              label="Status"
              :options="TRANSACTION_STATUS"
              :error="fieldError('status')"
            />

            <BaseInput
              id="transaction-form-competency"
              v-model="form.competency"
              label="Competencia"
              type="month"
              :error="fieldError('competency')"
            />
          </div>

          <label class="flex items-center gap-3 rounded-md border border-white/10 bg-slate-950/70 p-3 text-sm text-slate-200">
            <input v-model="form.is_recurring" type="checkbox" class="size-4 accent-sky-400">
            Lancamento recorrente
          </label>

          <div class="flex gap-3">
            <BaseButton type="submit" :loading="isLoading">
              {{ submitLabel }}
            </BaseButton>
            <BaseButton v-if="editingId" variant="secondary" @click="resetForm">
              Cancelar
            </BaseButton>
          </div>
        </form>
      </BaseCard>

      <BaseCard>
        <div class="mb-5 grid gap-4 lg:grid-cols-3">
          <BaseInput id="search" v-model="search" label="Buscar" placeholder="Descricao" />
          <BaseSelect
            id="filter-category"
            v-model="filters.category_id"
            label="Categoria"
            placeholder="Todas"
            :options="categoryOptions"
          />
          <BaseSelect
            id="filter-status"
            v-model="filters.status"
            label="Status"
            placeholder="Todos"
            :options="TRANSACTION_STATUS"
          />
        </div>

        <EmptyState
          v-if="!filteredTransactions.length"
          title="Nenhuma transacao encontrada"
          description="Cadastre uma transacao ou ajuste os filtros."
        />

        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="text-slate-400">
              <tr class="border-b border-white/10">
                <th class="py-3 font-semibold">Descricao</th>
                <th class="py-3 font-semibold">Categoria</th>
                <th class="py-3 font-semibold">Tipo</th>
                <th class="py-3 font-semibold">Status</th>
                <th class="py-3 text-right font-semibold">Valor</th>
                <th class="py-3 text-right font-semibold">Acoes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="transaction in filteredTransactions" :key="transaction.id" class="border-b border-white/5">
                <td class="py-4 font-semibold text-slate-100">{{ transaction.description }}</td>
                <td class="py-4 text-slate-300">{{ transaction.category?.name || 'Sem categoria' }}</td>
                <td class="py-4 text-slate-300">{{ typeLabel(transaction.type) }}</td>
                <td class="py-4 text-slate-300">{{ statusLabel(transaction.status) }}</td>
                <td
                  class="py-4 text-right font-bold"
                  :class="transaction.type === 'income' ? 'text-emerald-300' : 'text-rose-300'"
                >
                  {{ formatCurrency(transaction.amount) }}
                </td>
                <td class="py-4">
                  <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="startEdit(transaction)">Editar</BaseButton>
                    <BaseButton variant="danger" @click="removeTransaction(transaction)">Excluir</BaseButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </div>
  </section>
</template>
