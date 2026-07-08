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

  async suggestPreviousReserve(competency) {
    const response = await httpClient.get('/monthly-reserves/reserva-anterior-sugerida', {
      params: { competency },
    })

    return response.data
  },

  async listEntries(reserveId) {
    return getData(await httpClient.get(`/monthly-reserves/${reserveId}/entries`))
  },

  async createEntry(reserveId, payload) {
    return getData(await httpClient.post(`/monthly-reserves/${reserveId}/entries`, payload))
  },

  async updateEntry(reserveId, entryId, payload) {
    return getData(await httpClient.put(`/monthly-reserves/${reserveId}/entries/${entryId}`, payload))
  },

  async removeEntry(reserveId, entryId) {
    await httpClient.delete(`/monthly-reserves/${reserveId}/entries/${entryId}`)
  },
}

