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
import { TRANSACTION_TYPES } from '../../constants/transactions'
import { categoryService } from '../../services/categories/categoryService'

const categories = ref([])
const editingId = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

const form = reactive({
  name: '',
  type: 'expense',
})

const submitLabel = computed(() => editingId.value ? 'Salvar alteracoes' : 'Cadastrar categoria')

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
  <section class="mx-auto max-w-7xl px-6 py-10">
    <PageHeader
      eyebrow="Organizacao"
      title="Categorias"
      description="Cadastre suas categorias de entrada e saida. Elas serao usadas nos filtros e graficos."
    />

    <p v-if="generalError" class="mb-5 rounded-md bg-rose-500/10 p-3 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
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
          description="Crie categorias para organizar suas entradas e saidas."
        />

        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="text-slate-400">
              <tr class="border-b border-white/10">
                <th class="py-3 font-semibold">Nome</th>
                <th class="py-3 font-semibold">Tipo</th>
                <th class="py-3 text-right font-semibold">Acoes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="category in categories" :key="category.id" class="border-b border-white/5">
                <td class="py-4 font-semibold text-slate-100">{{ category.name }}</td>
                <td class="py-4 text-slate-300">{{ typeLabel(category.type) }}</td>
                <td class="py-4">
                  <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="startEdit(category)">Editar</BaseButton>
                    <BaseButton variant="danger" @click="removeCategory(category)">Excluir</BaseButton>
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
