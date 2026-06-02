<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../components/base/BaseButton.vue'
import { ROUTE_NAMES } from '../constants/routeNames'
import { authService } from '../services/auth/authService'
import { useAuthStore } from '../stores/authStore'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const isLoggingOut = ref(false)
const isMenuOpen = ref(false)

const navigationItems = [
  { label: 'Dashboard', routeName: ROUTE_NAMES.DASHBOARD },
  { label: 'Categorias', routeName: ROUTE_NAMES.CATEGORIES },
  { label: 'Transações', routeName: ROUTE_NAMES.TRANSACTIONS },
  { label: 'Reserva mensal', routeName: ROUTE_NAMES.MONTHLY_RESERVE },
]

const displayName = computed(() => authStore.userName || 'Usuário')

watch(() => route.fullPath, () => {
  isMenuOpen.value = false
})

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

    <section class="min-w-0 lg:pl-[280px]">
      <header class="sticky top-0 z-30 border-b border-white/10 bg-slate-950/75 px-4 py-4 backdrop-blur sm:px-6">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase text-slate-500">Área autenticada</p>
            <p class="truncate text-sm font-semibold text-slate-200">{{ displayName }}</p>
          </div>

          <div class="hidden items-center gap-3 lg:flex">
            <BaseButton variant="secondary" :loading="isLoggingOut" @click="handleLogout">
              Sair
            </BaseButton>
          </div>

          <button
            type="button"
            class="inline-flex size-11 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] text-slate-100 lg:hidden"
            aria-label="Abrir menu"
            @click="isMenuOpen = true"
          >
            ☰
          </button>
        </div>
      </header>

      <RouterView />
    </section>

    <Teleport to="body">
      <button
        type="button"
        class="fixed bottom-5 right-5 z-[9998] inline-flex size-14 items-center justify-center rounded-2xl border border-sky-300/30 bg-sky-400 text-xl font-black text-slate-950 shadow-2xl shadow-sky-950/40 transition hover:-translate-y-1 lg:hidden"
        aria-label="Abrir menu"
        @click="isMenuOpen = true"
      >
        ☰
      </button>

      <div
        v-if="isMenuOpen"
        class="fixed inset-0 z-[9998] bg-slate-950/80 backdrop-blur-sm lg:hidden"
        @click="isMenuOpen = false"
      />

      <aside
        class="fixed bottom-0 left-0 top-0 z-[9999] w-[min(88vw,340px)] overflow-y-auto border-r border-white/10 bg-slate-950 p-5 text-slate-50 shadow-2xl shadow-black/40 transition-transform duration-300 ease-out lg:hidden"
        :style="{ transform: isMenuOpen ? 'translate3d(0, 0, 0)' : 'translate3d(-110%, 0, 0)' }"
      >
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-sm font-bold uppercase text-sky-300">Gestão Financeira</p>
            <strong class="text-2xl text-slate-50">Menu</strong>
          </div>
          <button
            type="button"
            class="grid size-10 place-items-center rounded-2xl bg-white/[0.06] text-slate-200"
            aria-label="Fechar menu"
            @click="isMenuOpen = false"
          >
            ×
          </button>
        </div>

        <nav class="mt-8 space-y-2">
          <RouterLink
            v-for="item in navigationItems"
            :key="item.routeName"
            :to="{ name: item.routeName }"
            class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-300 transition hover:bg-white/[0.06] hover:text-white"
            active-class="bg-sky-500/15 text-sky-200"
          >
            {{ item.label }}
          </RouterLink>
        </nav>

        <BaseButton class="mt-8 w-full" variant="secondary" :loading="isLoggingOut" @click="handleLogout">
          Sair
        </BaseButton>
      </aside>
    </Teleport>
  </main>
</template>
