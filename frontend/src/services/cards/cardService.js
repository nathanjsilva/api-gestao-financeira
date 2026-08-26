import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const cardService = {
  async list() {
    return getData(await httpClient.get('/cards'))
  },

  async create(payload) {
    return getData(await httpClient.post('/cards', payload))
  },

  async update(id, payload) {
    return getData(await httpClient.put(`/cards/${id}`, payload))
  },

  async remove(id) {
    await httpClient.delete(`/cards/${id}`)
  },
}
