import { ref } from 'vue'

export function useLoading(initialValue = false) {
  const isLoading = ref(initialValue)

  async function withLoading(callback) {
    isLoading.value = true

    try {
      return await callback()
    } finally {
      isLoading.value = false
    }
  }

  return {
    isLoading,
    withLoading,
  }
}

