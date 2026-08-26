<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseModal from '../../components/base/BaseModal.vue'
import BasePagination from '../../components/base/BasePagination.vue'
import CardForm from '../../components/cards/CardForm.vue'
import CardsSubNav from '../../components/cards/CardsSubNav.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { usePagination } from '../../composables/usePagination'
import { cardService } from '../../services/cards/cardService'

const cards = ref([])
const { currentPage, totalPages, paginatedItems, pageNumbers, nextPage, prevPage, goToPage } = usePagination(cards)
const editingId = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

const form = reactive({
  name: '',
  responsible_person: '',
})

const editForm = reactive({
  name: '',
  responsible_person: '',
  active: true,
})

const isEditModalOpen = computed(() => editingId.value !== null)

function resetForm() {
  form.name = ''
  form.responsible_person = ''
  clearErrors()
}

async function loadCards() {
  await withLoading(async () => {
    try {
      cards.value = await cardService.list()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function handleSubmit() {
  clearErrors()

  await withLoading(async () => {
    try {
      await cardService.create(form)
      resetForm()
      await loadCards()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

function startEdit(card) {
  editingId.value = card.id
  editForm.name = card.name
  editForm.responsible_person = card.responsible_person
  editForm.active = Boolean(card.active)
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
      await cardService.update(editingId.value, editForm)
      closeEditModal()
      await loadCards()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

async function removeCard(card) {
  if (!confirm(`Excluir o cartão "${card.name}"?`)) {
    return
  }

  await withLoading(async () => {
    try {
      await cardService.remove(card.id)
      await loadCards()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

onMounted(loadCards)
</script>

<template>
  <section class="dashboard-shell mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <PageHeader
      eyebrow="Cartões"
      title="Cartões cadastrados"
      description="Cadastre os cartões usados por você e por outras pessoas da casa."
    />

    <CardsSubNav />

    <p v-if="generalError" class="mb-5 rounded-md bg-rose-500/10 p-3 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(320px,380px)_minmax(0,1fr)]">
      <BaseCard>
        <h2 class="text-xl font-black text-slate-50">Novo cartão</h2>

        <div class="mt-5">
          <CardForm
            :form="form"
            :field-error="fieldError"
            :is-loading="isLoading"
            submit-label="Cadastrar cartão"
            @submit="handleSubmit"
          />
        </div>
      </BaseCard>

      <BaseCard>
        <div class="mb-5 flex items-center justify-between">
          <h2 class="text-xl font-black text-slate-50">Cartões cadastrados</h2>
          <span class="text-sm text-slate-400">{{ cards.length }} itens</span>
        </div>

        <EmptyState
          v-if="!cards.length"
          title="Nenhum cartão cadastrado"
          description="Cadastre um cartão para começar a lançar suas compras."
        />

        <div v-else class="hidden md:block">
          <table class="premium-table">
            <thead class="text-slate-400">
              <tr class="border-b border-white/10">
                <th class="py-3 font-semibold">Nome</th>
                <th class="py-3 font-semibold">Responsável</th>
                <th class="py-3 font-semibold">Status</th>
                <th class="py-3 text-right font-semibold">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="card in paginatedItems" :key="card.id" class="border-b border-white/5">
                <td class="max-w-55 truncate py-4 font-semibold text-slate-100">{{ card.name }}</td>
                <td class="py-4 text-slate-300">{{ card.responsible_person }}</td>
                <td class="py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="card.active ? 'bg-emerald-400/10 text-emerald-300' : 'bg-slate-500/10 text-slate-400'"
                  >
                    {{ card.active ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td class="py-4">
                  <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" :disabled="isLoading" @click="startEdit(card)">Editar</BaseButton>
                    <BaseButton variant="danger" :disabled="isLoading" @click="removeCard(card)">Excluir</BaseButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="cards.length" class="grid gap-3 md:hidden">
          <article
            v-for="card in paginatedItems"
            :key="card.id"
            class="rounded-3xl border border-white/10 bg-white/[0.04] p-4"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="text-xs font-bold uppercase text-slate-500">{{ card.responsible_person }}</p>
                <strong class="mt-1 block break-words text-lg text-slate-50">{{ card.name }}</strong>
              </div>
              <span
                class="rounded-full px-3 py-1 text-xs font-bold"
                :class="card.active ? 'bg-emerald-400/10 text-emerald-300' : 'bg-slate-500/10 text-slate-400'"
              >
                {{ card.active ? 'Ativo' : 'Inativo' }}
              </span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
              <BaseButton variant="secondary" :disabled="isLoading" @click="startEdit(card)">Editar</BaseButton>
              <BaseButton variant="danger" :disabled="isLoading" @click="removeCard(card)">Excluir</BaseButton>
            </div>
          </article>
        </div>

        <BasePagination
          :current-page="currentPage"
          :total-pages="totalPages"
          :total="cards.length"
          :page-numbers="pageNumbers"
          @prev="prevPage"
          @next="nextPage"
          @go="goToPage"
        />
      </BaseCard>
    </div>

    <BaseModal :open="isEditModalOpen" title="Editar cartão" @close="closeEditModal">
      <CardForm
        :form="editForm"
        :field-error="fieldError"
        :is-loading="isLoading"
        submit-label="Salvar alterações"
        show-cancel
        show-active-toggle
        @submit="handleEditSubmit"
        @cancel="closeEditModal"
      />
    </BaseModal>
  </section>
</template>
