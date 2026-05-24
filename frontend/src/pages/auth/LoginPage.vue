<script setup>
import { reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { ROUTE_NAMES } from '../../constants/routeNames'
import { authService } from '../../services/auth/authService'
import { useAuthStore } from '../../stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

const form = reactive({
  email: '',
  password: '',
})

async function handleSubmit() {
  clearErrors()

  await withLoading(async () => {
    try {
      const session = await authService.login(form)
      authStore.setSession(session)

      router.push(route.query.redirect || { name: ROUTE_NAMES.DASHBOARD })
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}
</script>

<template>
  <section>
    <p class="mb-3 text-sm font-bold uppercase text-sky-300">Acesso</p>
    <h1 class="mb-4 text-5xl font-black leading-none text-slate-50">Login</h1>
    <p class="text-lg leading-8 text-slate-300">
      Entre com seu email e senha para acessar seus lancamentos financeiros.
    </p>

    <form class="mt-8 space-y-5" @submit.prevent="handleSubmit">
      <BaseInput
        id="email"
        v-model="form.email"
        label="Email"
        type="email"
        placeholder="voce@email.com"
        :error="fieldError('email')"
      />

      <BaseInput
        id="password"
        v-model="form.password"
        label="Senha"
        type="password"
        placeholder="Sua senha"
        :error="fieldError('password')"
      />

      <p v-if="generalError" class="rounded-md bg-rose-500/10 p-3 text-sm text-rose-200">
        {{ generalError }}
      </p>

      <BaseButton type="submit" class="w-full" :loading="isLoading">
        Entrar
      </BaseButton>
    </form>

    <p class="mt-6 text-sm text-slate-400">
      Ainda nao tem conta?
      <RouterLink :to="{ name: ROUTE_NAMES.REGISTER }" class="font-bold text-sky-300">
        Criar cadastro
      </RouterLink>
    </p>
  </section>
</template>

