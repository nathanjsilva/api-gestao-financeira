<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseCard from '../../components/base/BaseCard.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import BaseTextarea from '../../components/base/BaseTextarea.vue'
import EmptyState from '../../components/data-display/EmptyState.vue'
import MetricCard from '../../components/data-display/MetricCard.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { getCurrentCompetency } from '../../helpers/competency'
import { formatCurrency } from '../../helpers/currency'
import { monthlyReserveService } from '../../services/monthly-reserves/monthlyReserveService'

const reserves = ref([])
const editingId = ref(null)
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

const form = reactive({
  competency: getCurrentCompetency(),
  reserva_anterior: '',
  investimento: '',
  observations: '',
})

const latestReserve = computed(() => reserves.value[0] || null)
const submitLabel = computed(() => editingId.value ? 'Salvar alterações' : 'Cadastrar reserva')

function resetForm() {
  editingId.value = null
  form.competency = getCurrentCompetency()
  form.reserva_anterior = ''
  form.investimento = ''
  form.observations = ''
  clearErrors()
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

async function handleSubmit() {
  clearErrors()

  const payload = {
    ...form,
    reserva_anterior: Number(form.reserva_anterior || 0),
    investimento: Number(form.investimento || 0),
  }

  await withLoading(async () => {
    try {
      if (editingId.value) {
        await monthlyReserveService.update(editingId.value, payload)
      } else {
        await monthlyReserveService.create(payload)
      }

      resetForm()
      await loadReserves()
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

function startEdit(reserve) {
  editingId.value = reserve.id
  form.competency = reserve.competency
  form.reserva_anterior = reserve.reserva_anterior
  form.investimento = reserve.investimento
  form.observations = reserve.observations || ''
  clearErrors()
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
  <section class="mx-auto max-w-7xl px-6 py-10">
    <PageHeader
      eyebrow="Reserva financeira"
      title="Reserva mensal"
      description="Registre reserva anterior, investimento e observações por competência mensal."
    />

    <p v-if="generalError" class="mb-5 rounded-md bg-rose-500/10 p-3 text-sm text-rose-200">
      {{ generalError }}
    </p>

    <div class="mb-6 grid gap-4 md:grid-cols-2">
      <MetricCard
        label="Última reserva anterior"
        :value="formatCurrency(latestReserve?.reserva_anterior)"
        tone="info"
      />
      <MetricCard
        label="Ultimo investimento"
        :value="formatCurrency(latestReserve?.investimento)"
        tone="positive"
      />
    </div>

    <div class="grid gap-6 lg:grid-cols-[400px_1fr]">
      <BaseCard>
        <h2 class="text-xl font-black text-slate-50">
          {{ editingId ? 'Editar reserva' : 'Nova reserva' }}
        </h2>

        <form class="mt-5 space-y-4" @submit.prevent="handleSubmit">
          <BaseInput
            id="reserve-competency"
            v-model="form.competency"
            label="Competência"
            type="month"
            :error="fieldError('competency')"
          />

          <BaseInput
            id="previous-reserve"
            v-model="form.reserva_anterior"
            label="Reserva anterior"
            type="number"
            placeholder="0.00"
            :error="fieldError('reserva_anterior')"
          />

          <BaseInput
            id="investment"
            v-model="form.investimento"
            label="Investimento"
            type="number"
            placeholder="0.00"
            :error="fieldError('investimento')"
          />

          <BaseTextarea
            id="observations"
            v-model="form.observations"
            label="Observações"
            placeholder="Anotações opcionais sobre o mês"
            :error="fieldError('observations')"
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
          <h2 class="text-xl font-black text-slate-50">Reservas cadastradas</h2>
          <span class="text-sm text-slate-400">{{ reserves.length }} itens</span>
        </div>

        <EmptyState
          v-if="!reserves.length"
          title="Nenhuma reserva cadastrada"
          description="Cadastre a reserva mensal para completar o dashboard."
        />

        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="text-slate-400">
              <tr class="border-b border-white/10">
                <th class="py-3 font-semibold">Competência</th>
                <th class="py-3 text-right font-semibold">Reserva anterior</th>
                <th class="py-3 text-right font-semibold">Investimento</th>
                <th class="py-3 font-semibold">Observações</th>
                <th class="py-3 text-right font-semibold">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="reserve in reserves" :key="reserve.id" class="border-b border-white/5">
                <td class="py-4 font-semibold text-slate-100">{{ reserve.competency }}</td>
                <td class="py-4 text-right text-sky-200">{{ formatCurrency(reserve.reserva_anterior) }}</td>
                <td class="py-4 text-right text-emerald-200">{{ formatCurrency(reserve.investimento) }}</td>
                <td class="py-4 text-slate-300">{{ reserve.observations || '-' }}</td>
                <td class="py-4">
                  <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="startEdit(reserve)">Editar</BaseButton>
                    <BaseButton variant="danger" @click="removeReserve(reserve)">Excluir</BaseButton>
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
