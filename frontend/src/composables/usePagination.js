import { computed, ref, watch } from 'vue'

export function usePagination(items, perPage = 7) {
  const currentPage = ref(1)

  const totalPages = computed(() => Math.max(Math.ceil(items.value.length / perPage), 1))

  const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return items.value.slice(start, start + perPage)
  })

  const pageNumbers = computed(() => {
    const total = totalPages.value
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

    const current = currentPage.value
    const pages = new Set([1, total, current, current - 1, current + 1].filter((p) => p >= 1 && p <= total))
    const sorted = [...pages].sort((a, b) => a - b)
    const result = []

    for (let i = 0; i < sorted.length; i++) {
      if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('...')
      result.push(sorted[i])
    }

    return result
  })

  function nextPage() {
    if (currentPage.value < totalPages.value) currentPage.value++
  }

  function prevPage() {
    if (currentPage.value > 1) currentPage.value--
  }

  function goToPage(page) {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page
  }

  function resetPage() {
    currentPage.value = 1
  }

  watch(items, resetPage)

  return { currentPage, totalPages, paginatedItems, pageNumbers, nextPage, prevPage, goToPage, resetPage }
}
