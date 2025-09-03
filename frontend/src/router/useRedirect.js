import { useRouter } from 'vue-router'
import { getPath } from './routes.js'

export function useRedirect() {
  const router = useRouter()

  const redirect = async (routeName, params = {}) => {
    let path = getPath(routeName)

    Object.entries(params).forEach(([key, value]) => {
      path = path.replace(`:${key}`, value)
    })

    await router.push(path)
  }

  return { redirect }
}
