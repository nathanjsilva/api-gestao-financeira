import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const reserveAccountService = {
  async list(params) {
    return getData(await httpClient.get('/reserve-accounts', { params }))
  },

  async create(payload) {
    return getData(await httpClient.post('/reserve-accounts', payload))
  },

  async update(id, payload) {
    return getData(await httpClient.put(`/reserve-accounts/${id}`, payload))
  },

  async listEntries(id) {
    return getData(await httpClient.get(`/reserve-accounts/${id}/entries`))
  },

  async setEntry(id, competency, payload) {
    return getData(await httpClient.put(`/reserve-accounts/${id}/entries/${competency}`, payload))
  },

  async removeEntry(id, competency) {
    await httpClient.delete(`/reserve-accounts/${id}/entries/${competency}`)
  },
}
