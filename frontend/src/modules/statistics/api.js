/* STATISTICS */

import {api} from 'src/boot/axios.js'

export async function getFormationHours(formationId) {
  return await api.get(`/formations/hours/${formationId}`)
}

export async function getSubResourceById(subresourceId) {
  return await api.get(`/sub-resources/${subresourceId}`)
}

export async function updateSubResource(subResourceId, data) {
  return await api.put(`/sub-resources/${subResourceId}`, data)
}

export function getAssignments(filters = {}) {
  let url = '/assignments'

  const params = new URLSearchParams()
  if (filters.subResourceId) {
    params.append('id_sub_resource', filters.subResourceId)
  }
  if (filters.userId) {
    params.append('id_user', filters.userId)
  }
  if (filters.courseTypeId) {
    params.append('id_course_type', filters.courseTypeId)
  }
  if (filters.semesterId) {
    params.append('id_semester', filters.semesterId)
  }

  if (params.toString()) {
    url += `?${params.toString()}`
  }

  return api.get(url)
}
