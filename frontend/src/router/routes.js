import { ROUTE_NAMES } from '../constants/routeNames'
import { LAYOUTS } from '../constants/layouts'
import { useAuthStore } from '../stores/authStore'

const LoginPage = () => import('../pages/auth/LoginPage.vue')
const RegisterPage = () => import('../pages/auth/RegisterPage.vue')
const DashboardPage = () => import('../pages/dashboard/DashboardPage.vue')
const CategoriesPage = () => import('../pages/categories/CategoriesPage.vue')
const TransactionsPage = () => import('../pages/transactions/TransactionsPage.vue')
const MonthlyReservePage = () => import('../pages/monthly-reserve/MonthlyReservePage.vue')
const CardPurchasesPage = () => import('../pages/card-purchases/CardPurchasesPage.vue')
const CardsPage = () => import('../pages/cards/CardsPage.vue')
const CardCategoriesPage = () => import('../pages/card-categories/CardCategoriesPage.vue')
const CardDashboardPage = () => import('../pages/card-dashboard/CardDashboardPage.vue')

export const routes = [
  {
    path: '/',
    redirect: () => {
      const authStore = useAuthStore()

      if (authStore.isAuthenticated) {
        return { name: ROUTE_NAMES.DASHBOARD }
      }

      return { name: ROUTE_NAMES.LOGIN }
    },
  },
  {
    path: '/login',
    name: ROUTE_NAMES.LOGIN,
    component: LoginPage,
    meta: {
      layout: LAYOUTS.PUBLIC,
      public: true,
      guestOnly: true,
    },
  },
  {
    path: '/cadastro',
    name: ROUTE_NAMES.REGISTER,
    component: RegisterPage,
    meta: {
      layout: LAYOUTS.PUBLIC,
      public: true,
      guestOnly: true,
    },
  },
  {
    path: '/dashboard',
    name: ROUTE_NAMES.DASHBOARD,
    component: DashboardPage,
    meta: {
      layout: LAYOUTS.AUTHENTICATED,
      requiresAuth: true,
    },
  },
  {
    path: '/categorias',
    name: ROUTE_NAMES.CATEGORIES,
    component: CategoriesPage,
    meta: {
      layout: LAYOUTS.AUTHENTICATED,
      requiresAuth: true,
    },
  },
  {
    path: '/transacoes',
    name: ROUTE_NAMES.TRANSACTIONS,
    component: TransactionsPage,
    meta: {
      layout: LAYOUTS.AUTHENTICATED,
      requiresAuth: true,
    },
  },
  {
    path: '/reserva-mensal',
    name: ROUTE_NAMES.MONTHLY_RESERVE,
    component: MonthlyReservePage,
    meta: {
      layout: LAYOUTS.AUTHENTICATED,
      requiresAuth: true,
    },
  },
  {
    path: '/cartoes/compras',
    name: ROUTE_NAMES.CARD_PURCHASES,
    component: CardPurchasesPage,
    meta: {
      layout: LAYOUTS.AUTHENTICATED,
      requiresAuth: true,
    },
  },
  {
    path: '/cartoes/gerenciar',
    name: ROUTE_NAMES.CARDS,
    component: CardsPage,
    meta: {
      layout: LAYOUTS.AUTHENTICATED,
      requiresAuth: true,
    },
  },
  {
    path: '/cartoes/categorias',
    name: ROUTE_NAMES.CARD_CATEGORIES,
    component: CardCategoriesPage,
    meta: {
      layout: LAYOUTS.AUTHENTICATED,
      requiresAuth: true,
    },
  },
  {
    path: '/cartoes/analise',
    name: ROUTE_NAMES.CARD_DASHBOARD,
    component: CardDashboardPage,
    meta: {
      layout: LAYOUTS.AUTHENTICATED,
      requiresAuth: true,
    },
  },
]
