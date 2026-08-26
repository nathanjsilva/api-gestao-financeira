<script setup>
import { computed } from 'vue'
import BaseButton from '../base/BaseButton.vue'
import BaseInput from '../base/BaseInput.vue'
import BaseMonthPicker from '../base/BaseMonthPicker.vue'
import BaseSelect from '../base/BaseSelect.vue'
import { CARD_PAYMENT_TYPES } from '../../constants/cardPurchases'
import { calculateInstallments } from '../../helpers/cardInstallments'
import { formatCurrency } from '../../helpers/currency'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  cardOptions: {
    type: Array,
    required: true,
  },
  categoryOptions: {
    type: Array,
    required: true,
  },
  fieldError: {
    type: Function,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  submitLabel: {
    type: String,
    required: true,
  },
  showCancel: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['submit', 'cancel'])

const isInstallment = computed(() => props.form.payment_type === 'installment')

const preview = computed(() => {
  if (!isInstallment.value) {
    return props.form.total_amount && props.form.reference_competency
      ? [{ installment_number: 1, competency: props.form.reference_competency, amount: Number(props.form.total_amount) }]
      : []
  }

  return calculateInstallments(
    props.form.total_amount,
    props.form.installments_total,
    props.form.starting_installment_number,
    props.form.reference_competency,
  )
})

const finalCompetency = computed(() => preview.value[preview.value.length - 1]?.competency)
</script>

<template>
  <form class="space-y-4" @submit.prevent="$emit('submit')">
    <BaseSelect id="card-purchase-card" v-model="form.card_id" label="Cartão" :options="cardOptions" :error="fieldError('card_id')" />
    <BaseSelect id="card-purchase-category" v-model="form.card_category_id" label="Categoria" :options="categoryOptions" :error="fieldError('card_category_id')" />
    <BaseInput id="card-purchase-description" v-model="form.description" label="Descrição" placeholder="Ex: Notebook" :error="fieldError('description')" />
    <BaseInput id="card-purchase-total-amount" v-model="form.total_amount" label="Valor total da compra" type="number" placeholder="Ex: 1200.00" :error="fieldError('total_amount')" />
    <BaseInput id="card-purchase-date" v-model="form.purchase_date" label="Data da compra" type="date" :error="fieldError('purchase_date')" />

    <div class="grid grid-cols-2 gap-2">
      <button
        v-for="type in CARD_PAYMENT_TYPES"
        :key="type.value"
        type="button"
        class="rounded-2xl border p-3 text-left transition"
        :class="form.payment_type === type.value ? 'border-sky-300 bg-sky-400/10 text-sky-100' : 'border-white/10 bg-white/[0.03] text-slate-300 hover:bg-white/[0.06]'"
        @click="form.payment_type = type.value"
      >
        <span class="block text-sm font-bold">{{ type.label }}</span>
      </button>
    </div>

    <BaseMonthPicker
      id="card-purchase-reference-competency"
      v-model="form.reference_competency"
      :label="isInstallment ? 'Mês da parcela atual' : 'Mês da compra'"
      :error="fieldError('reference_competency')"
    />

    <div v-if="isInstallment" class="grid gap-3 sm:grid-cols-2">
      <BaseInput
        id="card-purchase-installments-total"
        v-model="form.installments_total"
        label="Quantidade total de parcelas"
        type="number"
        placeholder="Ex: 12"
        :error="fieldError('installments_total')"
      />
      <BaseInput
        id="card-purchase-starting-installment"
        v-model="form.starting_installment_number"
        label="Parcela atual"
        type="number"
        placeholder="Ex: 1"
        :error="fieldError('starting_installment_number')"
      />
    </div>

    <div v-if="preview.length" class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
      <p class="text-xs font-bold uppercase text-sky-300">Prévia das parcelas</p>

      <div class="mt-3 max-h-48 space-y-2 overflow-y-auto pr-1">
        <div
          v-for="installment in preview"
          :key="installment.installment_number"
          class="flex items-center justify-between rounded-xl bg-white/[0.04] px-3 py-2 text-sm"
        >
          <span class="text-slate-300">{{ installment.installment_number }}/{{ form.installments_total || 1 }} · {{ installment.competency }}</span>
          <strong class="text-slate-50">{{ formatCurrency(installment.amount) }}</strong>
        </div>
      </div>

      <p class="mt-3 text-sm text-slate-400">
        <template v-if="isInstallment">
          Compra termina em <strong class="text-slate-200">{{ finalCompetency }}</strong>.
        </template>
        <template v-else>
          Lançamento único, cobrado integralmente em <strong class="text-slate-200">{{ form.reference_competency }}</strong>.
        </template>
      </p>
    </div>

    <div class="grid gap-3">
      <BaseButton type="submit" class="w-full" :loading="isLoading">{{ submitLabel }}</BaseButton>
      <BaseButton v-if="showCancel" type="button" class="w-full" variant="secondary" @click="$emit('cancel')">Cancelar</BaseButton>
    </div>
  </form>
</template>
