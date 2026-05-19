export function getApiErrorMessage(error, fallbackMessage = 'Nao foi possivel concluir a operacao.') {
  if (error.response?.data?.message) {
    return error.response.data.message
  }

  return fallbackMessage
}

export function getApiValidationErrors(error) {
  return error.response?.data?.errors || {}
}
