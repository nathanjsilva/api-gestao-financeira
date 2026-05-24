export function getApiErrorMessage(error, fallbackMessage = 'Não foi possível concluir a operação.') {
  if (error.response?.data?.message) {
    return error.response.data.message
  }

  return fallbackMessage
}

export function getApiValidationErrors(error) {
  return error.response?.data?.errors || {}
}
