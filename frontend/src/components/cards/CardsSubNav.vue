<script setup>
import { useRoute } from 'vue-router'
import { ROUTE_NAMES } from '../../constants/routeNames'

const route = useRoute()

const items = [
  { label: 'Compras', routeName: ROUTE_NAMES.CARD_PURCHASES },
  { label: 'Cartões', routeName: ROUTE_NAMES.CARDS },
  { label: 'Categorias', routeName: ROUTE_NAMES.CARD_CATEGORIES },
  { label: 'Análises', routeName: ROUTE_NAMES.CARD_DASHBOARD },
]

function isActive(routeName) {
  return route.name === routeName
}
</script>

<template>
  <nav class="mb-6 flex gap-2 overflow-x-auto pb-1" aria-label="Navegação do módulo de cartões">
    <RouterLink
      v-for="item in items"
      :key="item.routeName"
      :to="{ name: item.routeName }"
      class="shrink-0 rounded-2xl px-4 py-2 text-sm font-bold transition"
      :class="isActive(item.routeName) ? 'bg-sky-400/15 text-sky-200' : 'bg-white/[0.04] text-slate-300 hover:bg-white/[0.08]'"
    >
      {{ item.label }}
    </RouterLink>
  </nav>
</template>
