import { defineBoot } from '#q-app/wrappers'
import axios from 'axios'
import { useUserStore } from 'src/utils/stores/useUserStore.js'

const baseURL = process.env.DOMAIN_NAME
  ? `https://${process.env.DOMAIN_NAME}/api`
  : 'https://localhost/api'

const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json'
  },
  withCredentials: true
})

let ajaxBar
let pendingRequests = 0
let isRefreshing = false
let failedQueue = []

const resetAjaxBar = () => {
  pendingRequests = 0
  if (ajaxBar) {
    ajaxBar.stop()
  }
}

const processQueue = (error, token = null, tokenType = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error)
    } else {
      prom.resolve({ token, tokenType })
    }
  })
  failedQueue = []
}

api.interceptors.request.use(
  config => {
    pendingRequests++
    if (ajaxBar && pendingRequests === 1) {
      ajaxBar.start()
    }
    return config
  },
  error => {
    pendingRequests--
    if (pendingRequests <= 0) {
      resetAjaxBar()
    }
    return Promise.reject(error)
  }
)

api.interceptors.response.use(
  response => {
    pendingRequests--
    if (pendingRequests <= 0) {
      resetAjaxBar()
    }
    return response
  },
  async (error) => {
    pendingRequests--
    if (pendingRequests <= 0) {
      resetAjaxBar()
    }

    const userStore = useUserStore()
    const originalRequest = error.config

    if (error.response?.status !== 401 || originalRequest._retry || originalRequest.url === '/refresh-token') {
      return Promise.reject(error)
    }

    if (!userStore.refreshToken) {
      userStore.clearAuth()
      return Promise.reject(error)
    }

    originalRequest._retry = true

    if (isRefreshing) {
      return new Promise((resolve, reject) => {
        failedQueue.push({ resolve, reject })
      })
        .then(({ token, tokenType }) => {
          originalRequest.headers['Authorization'] = `${tokenType} ${token}`
          return api(originalRequest)
        })
        .catch(err => Promise.reject(err))
    }

    isRefreshing = true

    try {
      const response = await api.post('/refresh-token', {
        refresh_token: userStore.refreshToken
      })

      const newToken = response.data.access_token
      const tokenType = response.data.token_type || 'Bearer'
      userStore.setToken(newToken, tokenType)
      originalRequest.headers['Authorization'] = `${tokenType} ${newToken}`

      processQueue(null, newToken, tokenType)

      return api(originalRequest)
    } catch (refreshError) {
      processQueue(refreshError, null, null)
      userStore.clearAuth()
      return Promise.reject(refreshError)
    } finally {
      isRefreshing = false
    }
  }
)

export function debugAjaxBar() {
  resetAjaxBar()
}

export function setAjaxBar(bar) {
  ajaxBar = bar
}

export default defineBoot(({ app }) => {
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
  app.config.globalProperties.$debugAjaxBar = debugAjaxBar
})

export { api }
