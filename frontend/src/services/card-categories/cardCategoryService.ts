import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const cardCategoryService = {
  async list(params) {
    return getData(await httpClient.get('/card-categories', { params }))
  },

  async create(payload) {
    return getData(await httpClient.post('/card-categories', payload))
  },

  async update(id, payload) {
    return getData(await httpClient.put(`/card-categories/${id}`, payload))
  },

  async remove(id) {
    await httpClient.delete(`/card-categories/${id}`)
  },
}
