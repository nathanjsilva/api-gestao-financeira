import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const dashboardService = {
  async monthlySummary(competency) {
    return getData(await httpClient.get('/dashboard/monthly-summary', {
      params: { competency },
    }))
  },

  async categoryComparison(params) {
    return getData(await httpClient.get('/dashboard/category-comparison', { params }))
  },

  async monthlyEvolution(params) {
    return getData(await httpClient.get('/dashboard/monthly-evolution', { params }))
  },

  async monthComparison(params) {
    return getData(await httpClient.get('/dashboard/month-comparison', { params }))
  },
}

