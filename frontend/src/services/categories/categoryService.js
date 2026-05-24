import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const categoryService = {
  async list() {
    return getData(await httpClient.get('/categories'))
  },

  async create(payload) {
    return getData(await httpClient.post('/categories', payload))
  },

  async update(id, payload) {
    return getData(await httpClient.put(`/categories/${id}`, payload))
  },

  async remove(id) {
    await httpClient.delete(`/categories/${id}`)
  },
}

