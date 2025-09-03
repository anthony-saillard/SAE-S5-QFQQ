import axios from 'axios'

export function login(email, password) {
  return axios.post('/login', { email, password })
}
