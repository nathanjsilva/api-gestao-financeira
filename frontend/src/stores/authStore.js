import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

const AUTH_STORAGE_KEY = 'gestao_financeira_auth'

function getStoredAuth() {
  const rawValue = localStorage.getItem(AUTH_STORAGE_KEY)

  if (!rawValue) {
    return {
      user: null,
      token: null,
    }
  }

  try {
    return JSON.parse(rawValue)
  } catch {
    localStorage.removeItem(AUTH_STORAGE_KEY)

    return {
      user: null,
      token: null,
    }
  }
}

export const useAuthStore = defineStore('auth', () => {
  const storedAuth = getStoredAuth()
  const user = ref(storedAuth.user)
  const token = ref(storedAuth.token)

  const isAuthenticated = computed(() => Boolean(token.value))
  const userName = computed(() => user.value?.name || '')

  function setSession(payload) {
    user.value = payload.user
    token.value = payload.token

    localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify({
      user: user.value,
      token: token.value,
    }))
  }

  function clearSession() {
    user.value = null
    token.value = null

    localStorage.removeItem(AUTH_STORAGE_KEY)
  }

  return {
    user,
    token,
    isAuthenticated,
    userName,
    setSession,
    clearSession,
  }
})
