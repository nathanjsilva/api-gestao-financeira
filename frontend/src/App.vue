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
  <component :is="layoutComponent" />
</template>
