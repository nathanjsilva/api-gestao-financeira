<script setup>
import BaseButton from '../base/BaseButton.vue'
import BaseInput from '../base/BaseInput.vue'
import BaseSelect from '../base/BaseSelect.vue'
import { TRANSACTION_TYPES } from '../../constants/transactions'

defineProps({
  form: {
    type: Object,
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
</script>

<template>
  <form class="space-y-4" @submit.prevent="$emit('submit')">
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

    <div class="grid gap-3">
      <BaseButton type="submit" class="w-full" :loading="isLoading">
        {{ submitLabel }}
      </BaseButton>
      <BaseButton v-if="showCancel" type="button" class="w-full" variant="secondary" @click="$emit('cancel')">
        Cancelar
      </BaseButton>
    </div>
  </form>
</template>
