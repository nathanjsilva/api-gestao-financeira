<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../components/base/BaseButton.vue'
import { ROUTE_NAMES } from '../constants/routeNames'
import { authService } from '../services/auth/authService'
import { useAuthStore } from '../stores/authStore'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const isLoggingOut = ref(false)

const navigationItems = [
  {
    label: 'Dashboard',
    routeName: ROUTE_NAMES.DASHBOARD,
    icon: 'M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6V11h-6v9Zm0-16v5h6V4h-6Z',
  },
  {
    label: 'Categorias',
    routeName: ROUTE_NAMES.CATEGORIES,
    icon: 'M4 6h16M4 12h10M4 18h16',
  },
  {
    label: 'Transações',
    routeName: ROUTE_NAMES.TRANSACTIONS,
    icon: 'M7 17V7m0 0-3 3m3-3 3 3m7 4v10m0 0 3-3m-3 3-3-3M4 7h6m4 10h6',
  },
  {
    label: 'Reserva',
    routeName: ROUTE_NAMES.MONTHLY_RESERVE,
    icon: 'M12 3 3 8.5v11h18v-11L12 3Zm-3 8.5h6v7H9v-7Z',
  },
  {
    label: 'Cartões',
    routeName: ROUTE_NAMES.CARD_PURCHASES,
    icon: 'M2 7a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7Zm0 4h20M6 15h4',
  },
]

const CARD_SECTION_ROUTES = [
  ROUTE_NAMES.CARD_PURCHASES,
  ROUTE_NAMES.CARDS,
  ROUTE_NAMES.CARD_CATEGORIES,
  ROUTE_NAMES.CARD_DASHBOARD,
]

const displayName = computed(() => authStore.userName || 'Usuário')

function isActive(routeName) {
  if (routeName === ROUTE_NAMES.CARD_PURCHASES) {
    return CARD_SECTION_ROUTES.includes(route.name)
  }

  return route.name === routeName
}

async function handleLogout() {
  isLoggingOut.value = true

  try {
    await authService.logout()
  } catch {
    // If the token already expired, the local session still needs to be cleared.
  } finally {
    authStore.clearSession()
    isLoggingOut.value = false
    router.push({ name: ROUTE_NAMES.LOGIN })
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-950/80 text-slate-50">
    <aside class="fixed bottom-0 left-0 top-0 z-40 hidden w-[280px] overflow-y-auto border-r border-white/10 bg-slate-900/70 px-5 py-6 backdrop-blur lg:block">
      <RouterLink :to="{ name: ROUTE_NAMES.DASHBOARD }" class="block rounded-lg px-3 py-2">
        <span class="block text-sm font-bold uppercase text-sky-300">Gestão Financeira</span>
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

    <section class="min-w-0 pb-24 lg:pb-0 lg:pl-[280px]">
      <header class="sticky top-0 z-30 border-b border-white/10 bg-slate-950/75 px-4 py-4 backdrop-blur sm:px-6">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase text-slate-500">Área autenticada</p>
            <p class="truncate text-sm font-semibold text-slate-200">{{ displayName }}</p>
          </div>

          <div class="hidden lg:block">
            <BaseButton
              variant="secondary"
              :loading="isLoggingOut"
              @click="handleLogout"
            >
              Sair
            </BaseButton>
          </div>

          <button
            type="button"
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] text-slate-100 lg:hidden"
            aria-label="Sair"
            :disabled="isLoggingOut"
            @click="handleLogout"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <path d="M16 17l5-5-5-5" />
              <path d="M21 12H9" />
            </svg>
          </button>
        </div>
      </header>

      <RouterView />
    </section>

    <nav
      class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-slate-950/90 backdrop-blur lg:hidden"
      style="padding-bottom: env(safe-area-inset-bottom)"
      aria-label="Navegação principal"
    >
      <div class="mx-auto grid max-w-7xl grid-cols-5">
        <RouterLink
          v-for="item in navigationItems"
          :key="item.routeName"
          :to="{ name: item.routeName }"
          class="flex flex-col items-center gap-1 px-2 py-3 text-slate-400 transition"
          :class="isActive(item.routeName) ? 'text-sky-300' : 'hover:text-slate-200'"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6">
            <path :d="item.icon" />
          </svg>
          <span class="text-[11px] font-bold">{{ item.label }}</span>
        </RouterLink>
      </div>
    </nav>
  </main>
</template>
