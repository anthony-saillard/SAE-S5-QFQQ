import { defineStore } from 'pinia'
import { api } from 'boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { adminPerm } from 'src/utils/utils.js'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: localStorage.getItem('access_token') || sessionStorage.getItem('access_token') || null,
    tokenType: localStorage.getItem('token_type') || sessionStorage.getItem('token_type') || 'Bearer',
    refreshToken: localStorage.getItem('refresh_token') || sessionStorage.getItem('refresh_token') || null,
    user: null,
    rememberMe: localStorage.getItem('rememberMe') === 'true',
    loading: false
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    hasRole: (state) => (roles) => state.user && Array.isArray(roles) ? roles.includes(state.user.role) : state.user?.role === roles,
    isAdmin: (state) => state.hasRole(adminPerm),
    isLoading: (state) => state.loading,
    authorizationHeader: (state) => `${state.tokenType} ${state.token}`
  },

  actions: {
    setLoading(value) {
      this.loading = value
    },

    setToken(token, type) {
      this.token = token
      this.tokenType = type || 'Bearer'

      if (this.rememberMe) {
        localStorage.setItem('access_token', token)
        localStorage.setItem('token_type', this.tokenType)
      } else {
        sessionStorage.setItem('access_token', token)
        sessionStorage.setItem('token_type', this.tokenType)
      }
      api.defaults.headers.common['Authorization'] = `${this.tokenType} ${token}`
    },

    setAuthData(data, remember = false) {
      this.token = data.access_token
      this.tokenType = data.token_type || 'Bearer'
      this.refreshToken = data.refresh_token
      this.user = data.user
      this.rememberMe = remember

      if (remember) {
        localStorage.setItem('access_token', data.access_token)
        localStorage.setItem('token_type', this.tokenType)
        localStorage.setItem('refresh_token', data.refresh_token)
        localStorage.setItem('rememberMe', 'true')
      } else {
        sessionStorage.setItem('access_token', data.access_token)
        sessionStorage.setItem('token_type', this.tokenType)
        sessionStorage.setItem('refresh_token', data.refresh_token)

        localStorage.removeItem('access_token')
        localStorage.removeItem('token_type')
        localStorage.removeItem('refresh_token')
        localStorage.removeItem('rememberMe')
      }

      api.defaults.headers.common['Authorization'] = `${this.tokenType} ${data.access_token}`
    },

    clearAuth() {
      this.token = null
      this.tokenType = 'Bearer'
      this.refreshToken = null
      this.user = null
      this.rememberMe = false

      localStorage.removeItem('access_token')
      localStorage.removeItem('token_type')
      localStorage.removeItem('refresh_token')
      localStorage.removeItem('rememberMe')
      sessionStorage.removeItem('access_token')
      sessionStorage.removeItem('token_type')
      sessionStorage.removeItem('refresh_token')

      delete api.defaults.headers.common['Authorization']
    },

    async login(login, password, remember = false) {
      this.setLoading(true)
      try {
        const response = await api.post('/login', {
          login,
          password
        })

        this.setAuthData(response.data, remember)

        await this.checkLogin()

        return response.data.user.role
      } catch (error) {
        this.clearAuth()
        throw error
      } finally {
        this.setLoading(false)
      }
    },

    async checkLogin() {
      if (!this.token) {
        throw new Error('No token available')
      }

      this.setLoading(true)
      try {
        const response = await api.get('/me')
        this.user = response.data.user
        return response.data
      } catch (error) {
        logger.warn(error)
      } finally {
        this.setLoading(false)
      }
    }
  }
})
