import { createRouter, createWebHistory } from 'vue-router'
import { authGuard } from '../middleware/authGuard'
import { guestGuard } from '../middleware/guestGuard'
import { routes } from './routes'

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach((to) => {
  const authResult = authGuard(to)

  if (authResult !== true) {
    return authResult
  }

  return guestGuard(to)
})
