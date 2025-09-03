import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from 'src/utils/stores/useUserStore.js'
import { routes, getPath } from './routes.js'
import { logger } from 'src/utils/logger.js'

const router = createRouter({
  history: createWebHistory(),
  routes
})

let authCheckPromise = null

router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore()

  if (to.path === '/login' && userStore.isAuthenticated) {
    return next(getPath('home'))
  }

  try {
    if (userStore.token && !authCheckPromise) {
      authCheckPromise = userStore.checkLogin()
      await authCheckPromise
    } else if (authCheckPromise) {
      await authCheckPromise
    }

    if (to.meta.requiresAuth && !userStore.isAuthenticated) {
      return next(getPath('login'))
    }

    if (to.meta.permissions && !userStore.hasRole(to.meta.permissions)) {
      return next(getPath('error') + '/403')
    }

    document.title = to.meta.title || 'Application'
    return next()
  } catch (error) {
    authCheckPromise = null
    if (to.meta.requiresAuth) {
      return next(getPath('login'))
    }
    logger.error(error)
    return next()
  }
})

export default router
