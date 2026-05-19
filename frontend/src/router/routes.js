import { ROUTE_NAMES } from '../constants/routeNames'

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
      public: true,
      guestOnly: true,
    },
  },
  {
    path: '/cadastro',
    name: ROUTE_NAMES.REGISTER,
    component: RegisterPage,
    meta: {
      public: true,
      guestOnly: true,
    },
  },
  {
    path: '/dashboard',
    name: ROUTE_NAMES.DASHBOARD,
    component: DashboardPage,
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/categorias',
    name: ROUTE_NAMES.CATEGORIES,
    component: CategoriesPage,
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/transacoes',
    name: ROUTE_NAMES.TRANSACTIONS,
    component: TransactionsPage,
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/reserva-mensal',
    name: ROUTE_NAMES.MONTHLY_RESERVE,
    component: MonthlyReservePage,
    meta: {
      requiresAuth: true,
    },
  },
]
