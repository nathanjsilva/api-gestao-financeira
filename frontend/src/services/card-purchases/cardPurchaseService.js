import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const cardPurchaseService = {
  async list(params = {}) {
    return getData(await httpClient.get('/card-purchases', { params }))
  },

  async create(payload) {
    return getData(await httpClient.post('/card-purchases', payload))
  },

  async update(id, payload) {
    return getData(await httpClient.put(`/card-purchases/${id}`, payload))
  },

  async remove(id) {
    await httpClient.delete(`/card-purchases/${id}`)
  },
}
