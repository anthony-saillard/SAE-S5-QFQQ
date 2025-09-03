import {adminPerm} from 'src/utils/utils.js'

export const routes = [
  {
    name: 'notFound',
    path: '/:catchAll(.*)*',
    redirect: '/error/404',
    meta: {
      layout: () => import('src/modules/core/layouts/CenterLayout.vue')
    }
  },
  {
    name: 'error',
    path: '/error/:errorType',
    component: () => import('src/modules/core/views/ErrorView.vue'),
    meta: {
      layout: () => import('src/modules/core/layouts/CenterLayout.vue'),
      title: 'Erreur'
    }
  },
  {
    name: 'home',
    path: '/',
    component: () => import('src/modules/core/views/IndexView.vue'),
    meta: {
      layout: () => import('src/modules/core/layouts/MainLayout.vue'),
      title: 'Accueil',
      requiresAuth: true
    }
  },
  {
    name: 'login',
    path: '/login',
    component: () => import('src/modules/login/views/LoginView.vue'),
    meta: {
      layout: () => import('src/modules/core/layouts/CenterLayout.vue'),
      title: 'Connexion'
    }
  },
  {
    name: 'users',
    path: '/users',
    component: () => import('src/modules/users/view/UsersView.vue'),
    meta: {
      layout: () => import('src/modules/core/layouts/MainLayout.vue'),
      title: 'User',
      permissions: [adminPerm],
      requiresAuth: true
    }
  },
  {
    name: 'settings',
    path: '/settings',
    component: () => import('src/modules/settings/views/SettingsView.vue'),
    meta: {
      layout: () => import('src/modules/core/layouts/MainLayout.vue'),
      title: 'Paramètres',
      permissions: [adminPerm],
      requiresAuth: true
    }
  },
  {
    name: 'stats',
    path: '/stats',
    component: () => import('src/modules/statistics/views/StatisticsView.vue'),
    meta: {
      layout: () => import('src/modules/core/layouts/MainLayout.vue'),
      title: 'Statistiques',
      requiresAuth: true
    }
  },
  {
    name: 'semester',
    path: '/semester/:id',
    component: () => import('src/modules/formations/views/SemesterListView.vue'),
    meta: {
      layout: () => import('src/modules/core/layouts/MainLayout.vue'),
      title: 'Semestre',
      requiresAuth: true
    }
  },
  {
    name: 'resource',
    path: '/resource/:id',
    component: () => import('src/modules/resources/views/ResourceView.vue'),
    meta: {
      layout: () => import('src/modules/core/layouts/MainLayout.vue'),
      title: 'Ressource',
      requiresAuth: true
    }
  }
]

export function getPath(routeName) {
  return routes.find((route) => route.name === routeName)?.path
}
