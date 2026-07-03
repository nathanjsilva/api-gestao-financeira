# coding-standards.md — Padrões de Código Vue 3 / Tailwind

## Estrutura de Componente (obrigatória)

```vue
<script setup>
// 1. imports
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import transactionService from '@/services/transactions/transactionService'
import { useLoading } from '@/composables/useLoading'
import { useFormErrors } from '@/composables/useFormErrors'
import { formatCurrency } from '@/helpers/currency'

// 2. props e emits
const props = defineProps({
  transaction: { type: Object, required: true },
  editable:    { type: Boolean, default: false }
})
const emit = defineEmits(['updated', 'deleted'])

// 3. stores e composables
const authStore    = useAuthStore()
const { isLoading, withLoading } = useLoading()
const { generalError, fieldError, setErrorsFromApi, clearErrors } = useFormErrors()

// 4. estado local
const isEditing = ref(false)
const form = ref({ description: '', amount: '' })

// 5. computados
const formattedAmount = computed(() => formatCurrency(props.transaction.amount))

// 6. métodos
async function handleSubmit() {
  clearErrors()
  await withLoading(async () => {
    try {
      await transactionService.update(props.transaction.id, form.value)
      emit('updated')
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}

// 7. lifecycle
onMounted(() => {
  form.value.description = props.transaction.description
  form.value.amount      = props.transaction.amount
})
</script>

<template>
  <!-- conteúdo -->
</template>
```

---

## Regras de `<script setup>`

- **Sempre use `<script setup>`** — nunca `defineComponent()` ou `export default {}`
- **Sempre declare `defineProps` com tipo e required/default**
- **Sempre declare `defineEmits` com array de eventos**
- Ordem dos blocos: imports → props/emits → stores → estado → computados → métodos → lifecycle

---

## Componentes Base

Sempre use os componentes base em vez de HTML puro:

```vue
<!-- ❌ Não fazer -->
<input type="text" class="border rounded px-3 py-2" v-model="name" />
<button class="bg-blue-500 text-white px-4 py-2 rounded">Salvar</button>

<!-- ✅ Correto -->
<BaseInput v-model="form.name" label="Nome" :error="fieldError('name')" />
<BaseButton type="submit" :loading="isLoading">Salvar</BaseButton>
```

---

## Chamadas à API

```vue
<script setup>
import transactionService from '@/services/transactions/transactionService'
import { useLoading } from '@/composables/useLoading'
import { useFormErrors } from '@/composables/useFormErrors'

const { isLoading, withLoading } = useLoading()
const { setErrorsFromApi, clearErrors, generalError } = useFormErrors()

// ❌ Não fazer
async function salvar() {
  const res = await axios.post('/api/transactions', form.value) // nunca direto
}

// ✅ Correto
async function salvar() {
  clearErrors()
  await withLoading(async () => {
    try {
      await transactionService.create(form.value)
    } catch (error) {
      setErrorsFromApi(error)
    }
  })
}
</script>
```

---

## Roteamento

```vue
<script setup>
import { useRouter } from 'vue-router'
import { DASHBOARD, TRANSACTIONS } from '@/constants/routeNames'

const router = useRouter()

// ❌ Não fazer
router.push('/dashboard')        // string hardcoded
router.push({ name: 'login' })   // string hardcoded

// ✅ Correto
router.push({ name: DASHBOARD })
router.push({ name: TRANSACTIONS })
</script>

<!-- No template -->
<RouterLink :to="{ name: DASHBOARD }">Dashboard</RouterLink>
```

---

## Formatação de Valores

```vue
<script setup>
import { formatCurrency }   from '@/helpers/currency'
import { formatPercentage } from '@/helpers/percentage'
import { getCurrentCompetency } from '@/helpers/competency'

// ❌ Não fazer
const valor = `R$ ${amount.toFixed(2).replace('.', ',')}`

// ✅ Correto
const valor = formatCurrency(amount)    // 'R$ 1.234,56'
const pct   = formatPercentage(36.5)   // '36,5%'
const mes   = getCurrentCompetency()   // '2026-07'
</script>
```

---

## Tailwind CSS

- Use classes Tailwind diretamente — não crie CSS customizado sem necessidade
- Para condicionais: `:class="{ 'bg-green-500': isPaid, 'bg-yellow-500': !isPaid }"`
- Para variantes responsivas: `sm:`, `md:`, `lg:`
- **Não** use `style=""` inline para propriedades que Tailwind cobre

```vue
<!-- ✅ Correto -->
<span
  :class="{
    'bg-green-100 text-green-700': transaction.status === 'paid',
    'bg-yellow-100 text-yellow-700': transaction.status === 'pending'
  }"
  class="px-2 py-1 rounded-full text-xs font-medium"
>
  {{ transaction.status === 'paid' ? 'Pago' : 'Pendente' }}
</span>
```

---

## Estado de Loading e Erros (padrão)

```vue
<template>
  <form @submit.prevent="handleSubmit">
    <!-- Erro geral -->
    <p v-if="generalError" class="text-red-600 text-sm mb-4">{{ generalError }}</p>

    <BaseInput
      v-model="form.email"
      label="E-mail"
      type="email"
      :error="fieldError('email')"
    />

    <BaseButton type="submit" :loading="isLoading" :disabled="isLoading">
      Entrar
    </BaseButton>
  </form>
</template>
```

---

## Nomenclatura

| Elemento | Convenção | Exemplo |
|----------|-----------|---------|
| Componentes | PascalCase | `TransactionCard.vue` |
| Pages | PascalCase + sufixo Page | `LoginPage.vue` |
| Composables | camelCase + prefixo use | `useLoading.js` |
| Services | camelCase + sufixo Service | `transactionService.js` |
| Stores | camelCase + sufixo Store | `authStore.js` |
| Helpers | camelCase | `formatCurrency.js` |
| Variables/refs | camelCase | `isLoading`, `formData` |
| Props | camelCase | `modelValue`, `isEditable` |
| Emits | kebab-case | `'update:modelValue'`, `'item-deleted'` |

---

## Estrutura de Página

```vue
<script setup>
// imports, estado, métodos
</script>

<template>
  <div class="...">
    <!-- PageHeader -->
    <PageHeader title="Transações">
      <template #actions>
        <BaseButton @click="openForm">Nova Transação</BaseButton>
      </template>
    </PageHeader>

    <!-- Conteúdo principal -->
    <div class="mt-6">
      <!-- Loading state -->
      <div v-if="isLoading" class="...">Carregando...</div>

      <!-- Empty state -->
      <EmptyState v-else-if="items.length === 0" message="Nenhuma transação encontrada." />

      <!-- Lista -->
      <div v-else class="space-y-4">
        <TransactionCard
          v-for="item in items"
          :key="item.id"
          :transaction="item"
          @deleted="fetchTransactions"
        />
      </div>
    </div>
  </div>
</template>
```
