<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import BasePagination from '../../components/base/BasePagination.vue'
import BaseSelect from '../../components/base/BaseSelect.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { usePagination } from '../../composables/usePagination'
import { TRANSACTION_TYPES } from '../../constants/transactions'
import { categoryService } from '../../services/categories/categoryService'

const categories = ref([])
const { currentPage, totalPages, paginatedItems, pageNumbers, nextPage, prevPage, goToPage } = usePagination(categories)
const editingId = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

const form = reactive({
  name: '',
  type: 'expense',
})

const submitLabel = computed(() => editingId.value ? 'Salvar alterações' : 'Cadastrar categoria')

function resetForm() {
  editingId.value = null
  form.name = ''
  form.type = 'expense'
  clearErrors()
}

async function loadCategories() {
  await withLoading(async () => {
    try {
      categories.value = await categoryService.list()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function handleSubmit() {
  clearErrors()

  await withLoading(async () => {
    try {
      if (editingId.value) {
        await categoryService.update(editingId.value, form)
      } else {
        await categoryService.create(form)
      }

      resetForm()
      await loadCategories()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

function startEdit(category) {
  editingId.value = category.id
  form.name = category.name
  form.type = category.type
  clearErrors()
}

async function removeCategory(category) {
  if (!confirm(`Excluir a categoria "${category.name}"?`)) {
    return
  }

  await withLoading(async () => {
    try {
      await categoryService.remove(category.id)
      await loadCategories()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

function typeLabel(type) {
  return TRANSACTION_TYPES.find((item) => item.value === type)?.label || type
}

onMounted(loadCategories)
</script>

<template>
  <section class="dashboard-shell mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <PageHeader
      eyebrow="Organização"
      title="Categorias"
      description="Cadastre suas categorias de entrada e saída. Elas serão usadas nos filtros e gráficos."
    />

    <p v-if="generalError" class="mb-5 rounded-md bg-rose-500/10 p-3 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(320px,380px)_minmax(0,1fr)]">
      <BaseCard>
        <h2 class="text-xl font-black text-slate-50">
          {{ editingId ? 'Editar categoria' : 'Nova categoria' }}
        </h2>

        <form class="mt-5 space-y-4" @submit.prevent="handleSubmit">
          <BaseInput
            id="category-name"
            v-model="form.name"
            label="Nome"
            placeholder="Ex: Mercado"
            :error="fieldError('name')"
          />

          <BaseSelect
            id="category-type"
            v-model="form.type"
            label="Tipo"
            :options="TRANSACTION_TYPES"
            :error="fieldError('type')"
          />

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
        <div class="mb-5 flex items-center justify-between">
          <h2 class="text-xl font-black text-slate-50">Categorias cadastradas</h2>
          <span class="text-sm text-slate-400">{{ categories.length }} itens</span>
        </div>

        <EmptyState
          v-if="!categories.length"
          title="Nenhuma categoria cadastrada"
          description="Crie categorias para organizar suas entradas e saídas."
        />

        <div v-else class="hidden md:block">
          <table class="premium-table">
            <thead class="text-slate-400">
              <tr class="border-b border-white/10">
                <th class="py-3 font-semibold">Nome</th>
                <th class="py-3 font-semibold">Tipo</th>
                <th class="py-3 text-right font-semibold">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="category in paginatedItems" :key="category.id" class="border-b border-white/5">
                <td class="max-w-55 truncate py-4 font-semibold text-slate-100">{{ category.name }}</td>
                <td class="py-4 text-slate-300">{{ typeLabel(category.type) }}</td>
                <td class="py-4">
                  <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" :disabled="isLoading" @click="startEdit(category)">Editar</BaseButton>
                    <BaseButton variant="danger" :disabled="isLoading" @click="removeCategory(category)">Excluir</BaseButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="categories.length" class="grid gap-3 md:hidden">
          <article
            v-for="category in paginatedItems"
            :key="category.id"
            class="rounded-3xl border border-white/10 bg-white/[0.04] p-4"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="text-xs font-bold uppercase text-slate-500">Categoria</p>
                <strong class="mt-1 block break-words text-lg text-slate-50">{{ category.name }}</strong>
              </div>
              <span class="value-badge">{{ typeLabel(category.type) }}</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
              <BaseButton variant="secondary" :disabled="isLoading" @click="startEdit(category)">Editar</BaseButton>
              <BaseButton variant="danger" :disabled="isLoading" @click="removeCategory(category)">Excluir</BaseButton>
            </div>
          </article>
        </div>

        <BasePagination
          :current-page="currentPage"
          :total-pages="totalPages"
          :total="categories.length"
          :page-numbers="pageNumbers"
          @prev="prevPage"
          @next="nextPage"
          @go="goToPage"
        />
      </BaseCard>
    </div>
  </section>
</template>
