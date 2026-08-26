<script setup>
import BaseButton from '../base/BaseButton.vue'
import BaseInput from '../base/BaseInput.vue'

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
  showActiveToggle: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['submit', 'cancel'])
</script>

<template>
  <form class="space-y-4" @submit.prevent="$emit('submit')">
    <BaseInput
      id="card-name"
      v-model="form.name"
      label="Nome do cartão"
      placeholder="Ex: Nubank Roxinho"
      :error="fieldError('name')"
    />

    <BaseInput
      id="card-responsible-person"
      v-model="form.responsible_person"
      label="Pessoa responsável"
      placeholder="Ex: Nathan"
      :error="fieldError('responsible_person')"
    />

    <label
      v-if="showActiveToggle"
      class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/70 p-3 text-sm text-slate-200"
    >
      <input v-model="form.active" type="checkbox" class="size-4 accent-sky-400">
      Cartão ativo
    </label>

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
