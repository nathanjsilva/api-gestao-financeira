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
      id="card-category-name"
      v-model="form.name"
      label="Nome"
      placeholder="Ex: Alimentação"
      :error="fieldError('name')"
    />

    <label
      v-if="showActiveToggle"
      class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/70 p-3 text-sm text-slate-200"
    >
      <input v-model="form.active" type="checkbox" class="size-4 accent-sky-400">
      Categoria ativa
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
