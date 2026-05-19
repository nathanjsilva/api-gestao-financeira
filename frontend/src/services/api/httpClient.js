import axios from 'axios'
import { API_BASE_URL } from '../../constants/api'
import { ROUTE_NAMES } from '../../constants/routeNames'
import { router } from '../../router'
import { useAuthStore } from '../../stores/authStore'

export const httpClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

httpClient.interceptors.request.use((config) => {
  const authStore = useAuthStore()

  if (!authStore.token) {
    return config
  }

  config.headers.Authorization = `Bearer ${authStore.token}`

  return config
})

httpClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status !== 401) {
      return Promise.reject(error)
    }

    const authStore = useAuthStore()

    authStore.clearSession()

    if (router.currentRoute.value.name !== ROUTE_NAMES.LOGIN) {
      router.push({ name: ROUTE_NAMES.LOGIN })
    }

    return Promise.reject(error)
  },
)
