<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '../../components/base/BaseButton.vue'
import BaseInput from '../../components/base/BaseInput.vue'
import { useFormErrors } from '../../composables/useFormErrors'
import { useLoading } from '../../composables/useLoading'
import { ROUTE_NAMES } from '../../constants/routeNames'
import { authService } from '../../services/auth/authService'
import { useAuthStore } from '../../stores/authStore'

const router = useRouter()
const authStore = useAuthStore()
const { isLoading, withLoading } = useLoading()
const { generalError, clearErrors, setErrorsFromApi, fieldError } = useFormErrors()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

async function handleSubmit() {
  clearErrors()

  await withLoading(async () => {
    try {
      const session = await authService.register(form)
      authStore.setSession(session)
      router.push({ name: ROUTE_NAMES.DASHBOARD })
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}
</script>

<template>
  <section>
    <p class="mb-3 text-sm font-bold uppercase text-sky-300">Nova conta</p>
    <h1 class="mb-4 text-5xl font-black leading-none text-slate-50">Cadastro</h1>
    <p class="text-lg leading-8 text-slate-300">
      Crie seu acesso para manter os dados financeiros separados por usuário.
    </p>

    <form class="mt-8 space-y-5" @submit.prevent="handleSubmit">
      <BaseInput id="name" v-model="form.name" label="Nome" :error="fieldError('name')" />
      <BaseInput id="email" v-model="form.email" label="E-mail" type="email" :error="fieldError('email')" />
      <BaseInput id="password" v-model="form.password" label="Senha" type="password" :error="fieldError('password')" />
      <BaseInput
        id="password_confirmation"
        v-model="form.password_confirmation"
        label="Confirmar senha"
        type="password"
        :error="fieldError('password_confirmation')"
      />

      <p v-if="generalError" class="rounded-md bg-rose-500/10 p-3 text-sm text-rose-200">
        {{ generalError }}
      </p>

      <BaseButton type="submit" class="w-full" :loading="isLoading">
        Criar conta
      </BaseButton>
    </form>

    <p class="mt-6 text-sm text-slate-400">
      Já tem conta?
      <RouterLink :to="{ name: ROUTE_NAMES.LOGIN }" class="font-bold text-sky-300">
        Entrar
      </RouterLink>
    </p>
  </section>
</template>
