<script setup>
import { onBeforeUnmount, onMounted } from 'vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close'])

function handleKeydown(event) {
  if (event.key === 'Escape' && props.open) {
    emit('close')
  }
}

onMounted(() => document.addEventListener('keydown', handleKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/80 p-4 backdrop-blur-sm"
        @mousedown.self="$emit('close')"
      >
        <div class="w-full max-w-2xl rounded-3xl border border-white/10 bg-slate-950 p-6 shadow-2xl shadow-black/40">
          <div class="mb-5 flex items-center justify-between gap-4">
            <h2 class="text-2xl font-black text-slate-50">{{ title }}</h2>
            <button
              type="button"
              class="grid size-10 shrink-0 place-items-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10 hover:text-slate-50"
              aria-label="Fechar"
              @click="$emit('close')"
            >
              ✕
            </button>
          </div>

          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
