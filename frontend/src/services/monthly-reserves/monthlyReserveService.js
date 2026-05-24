import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const monthlyReserveService = {
  async list() {
    return getData(await httpClient.get('/monthly-reserves'))
  },

  async create(payload) {
    return getData(await httpClient.post('/monthly-reserves', payload))
  },

  async update(id, payload) {
    return getData(await httpClient.put(`/monthly-reserves/${id}`, payload))
  },

  async remove(id) {
    await httpClient.delete(`/monthly-reserves/${id}`)
  },
}

