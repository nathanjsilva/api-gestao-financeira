import { httpClient } from '../api/httpClient'

function normalizeSession(payload) {
  const { token, ...user } = payload

  return {
    user,
    token,
  }
}

async function register(data) {
  const response = await httpClient.post('/auth/register', data)

  return normalizeSession(response.data.data)
}

async function login(data) {
  const response = await httpClient.post('/auth/login', data)

  return normalizeSession(response.data.data)
}

async function logout() {
  await httpClient.post('/auth/logout')
}

export const authService = {
  register,
  login,
  logout,
}
