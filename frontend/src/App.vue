<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AuthenticatedLayout from './layouts/AuthenticatedLayout.vue'
import PublicLayout from './layouts/PublicLayout.vue'
import { LAYOUTS } from './constants/layouts'

const route = useRoute()

const layoutComponent = computed(() => {
  if (route.meta.layout === LAYOUTS.AUTHENTICATED) {
    return AuthenticatedLayout
  }

  return PublicLayout
})
</script>

<template>
  <div class="app-background min-h-screen">
    <component :is="layoutComponent" />
  </div>
</template>

<style>
.app-background {
  position: relative;
  min-height: 100vh;
  background-image: url('/login-background.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.app-background::before {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.72);
  pointer-events: none;
}

.app-background > * {
  position: relative;
  z-index: 1;
}
</style>
