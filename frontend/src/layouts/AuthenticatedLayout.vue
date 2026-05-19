<script setup>
import { computed } from 'vue'
import { ROUTE_NAMES } from '../constants/routeNames'
import { useAuthStore } from '../stores/authStore'

const authStore = useAuthStore()

const navigationItems = [
  { label: 'Dashboard', routeName: ROUTE_NAMES.DASHBOARD },
  { label: 'Categorias', routeName: ROUTE_NAMES.CATEGORIES },
  { label: 'Transacoes', routeName: ROUTE_NAMES.TRANSACTIONS },
  { label: 'Reserva mensal', routeName: ROUTE_NAMES.MONTHLY_RESERVE },
]

const displayName = computed(() => authStore.userName || 'Usuario')
</script>

<template>
  <main class="min-h-screen bg-slate-950 text-slate-50">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
      <aside class="hidden border-r border-white/10 bg-slate-900/60 px-5 py-6 lg:block">
        <RouterLink :to="{ name: ROUTE_NAMES.DASHBOARD }" class="block rounded-lg px-3 py-2">
          <span class="block text-sm font-bold uppercase text-sky-300">Gestao Financeira</span>
          <span class="mt-2 block text-2xl font-black leading-none">Painel</span>
        </RouterLink>

        <nav class="mt-10 space-y-1">
          <RouterLink
            v-for="item in navigationItems"
            :key="item.routeName"
            :to="{ name: item.routeName }"
            class="block rounded-md px-3 py-2 text-sm font-semibold text-slate-300 transition hover:bg-white/5 hover:text-white"
            active-class="bg-sky-500/15 text-sky-200"
          >
            {{ item.label }}
          </RouterLink>
        </nav>
      </aside>

      <section class="min-w-0">
        <header class="sticky top-0 z-10 border-b border-white/10 bg-slate-950/85 px-6 py-4 backdrop-blur">
          <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase text-slate-500">Area autenticada</p>
              <p class="text-sm font-semibold text-slate-200">{{ displayName }}</p>
            </div>

            <nav class="flex gap-2 overflow-x-auto lg:hidden">
              <RouterLink
                v-for="item in navigationItems"
                :key="item.routeName"
                :to="{ name: item.routeName }"
                class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold text-slate-300"
                active-class="bg-sky-500/15 text-sky-200"
              >
                {{ item.label }}
              </RouterLink>
            </nav>
          </div>
        </header>

        <RouterView />
      </section>
    </div>
  </main>
</template>
