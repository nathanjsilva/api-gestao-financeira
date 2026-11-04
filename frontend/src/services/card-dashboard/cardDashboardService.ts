import { httpClient } from '../api/httpClient'

function getData(response) {
  return response.data.data
}

export const cardDashboardService = {
  async analytics(params) {
    return getData(await httpClient.get('/card-dashboard/analytics', { params }))
  },

  async monthlySummary(competency) {
    return getData(await httpClient.get('/card-dashboard/monthly-summary', {
      params: { competency },
    }))
  },
}
