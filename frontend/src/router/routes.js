import { ROUTE_NAMES } from '../constants/routeNames'
import { LAYOUTS } from '../constants/layouts'

const LoginPage = () => import('../pages/auth/LoginPage.vue')
const RegisterPage = () => import('../pages/auth/RegisterPage.vue')
const DashboardPage = () => import('../pages/dashboard/DashboardPage.vue')
const CategoriesPage = () => import('../pages/categories/CategoriesPage.vue')
const TransactionsPage = () => import('../pages/transactions/TransactionsPage.vue')
const MonthlyReservePage = () => import('../pages/monthly-reserve/MonthlyReservePage.vue')

export const routes = [
  {
    path: '/',
    redirect: { name: ROUTE_NAMES.DASHBOARD },
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
]
