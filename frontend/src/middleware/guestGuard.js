import { ROUTE_NAMES } from '../constants/routeNames'
import { useAuthStore } from '../stores/authStore'

export function guestGuard(to) {
  const authStore = useAuthStore()

  if (!to.meta.guestOnly || !authStore.isAuthenticated) {
    return true
  }

  return {
    name: ROUTE_NAMES.DASHBOARD,
  }
}
