import { USE_MOCK } from './config'
import http from './http'

export async function login(email: string, password: string): Promise<string> {
  if (USE_MOCK) {
    if (email === 'demo@demo.com' && password === 'password') {
      return 'mock-token-abc123'
    }
    throw new Error('Credenciales incorrectas')
  }
  const { data } = await http.post('/auth/login', { email, password })
  return data.token
}

export async function logout(): Promise<void> {
  if (USE_MOCK) return
  await http.post('/auth/logout')
}
