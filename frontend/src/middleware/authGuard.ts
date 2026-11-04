import { ROUTE_NAMES } from '../constants/routeNames'
import { useAuthStore } from '../stores/authStore'

export function authGuard(to) {
  const authStore = useAuthStore()

  if (!to.meta.requiresAuth || authStore.isAuthenticated) {
    return true
  }

  return {
    name: ROUTE_NAMES.LOGIN,
    query: {
      redirect: to.fullPath,
    },
  }
}
