import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const transactionService = {
  async list(params = {}) {
    return getData(await httpClient.get('/transactions', { params }))
  },

  async create(payload) {
    return getData(await httpClient.post('/transactions', payload))
  },

  async update(id, payload) {
    return getData(await httpClient.put(`/transactions/${id}`, payload))
  },

  async remove(id) {
    await httpClient.delete(`/transactions/${id}`)
  },
}

