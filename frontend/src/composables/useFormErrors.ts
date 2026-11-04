import { ref } from 'vue'
import { getApiErrorMessage, getApiValidationErrors } from '../helpers/apiError'

export function useFormErrors() {
  const formErrors = ref({})
  const generalError = ref('')

  function clearErrors() {
    formErrors.value = {}
    generalError.value = ''
  }

  function setErrorsFromApi(error) {
    formErrors.value = getApiValidationErrors(error)
    generalError.value = getApiErrorMessage(error)
  }

  function fieldError(field) {
    return formErrors.value[field]?.[0] || ''
  }

  return {
    formErrors,
    generalError,
    clearErrors,
    setErrorsFromApi,
    fieldError,
  }
}

