import { api } from 'src/boot/axios.js'


/* SCHOOL YEARS */

export async function getAllSchoolYears() {
  return await api.get('/school-years')
}

export async function createSchoolYear(label) {
  return await api.post('/school-years', {
    label
  })
}

export async function updateSchoolYear(id, label) {
  return await api.put(`/school-years/${id}`, {
    label
  })
}


/* COURSES TYPES */

export async function getAllCourseTypes() {
  return await api.get('course-types')
}

/*
@param data => {
  name
  hourly_rate
  id_school_year
}
 */
export async function createCourseTypes(data) {
  return await api.post('/course-types', data)
}

/*
@param id
@param data => {
  name
  hourly_rate
  id_school_year
}
 */
export async function updateCourseTypes(id, data) {
  return await api.put(`/course-types/${id}`, data)
}

export async function deleteCourseTypes(id) {
  return await api.delete(`/course-types/${id}`)
}

/* FORMATIONS */

export async function getAllFormations() {
  return await api.get('/formations')
}

export async function createFormation(data) {
  return await api.post('/formations', data)
}

export async function editFormation(id, data) {
  return await api.put(`/formations/${id}`, data)
}

export async function deleteFormation(id) {
  return await api.delete(`/formations/${id}`)
}

/* SEMESTER */

export async function getAllSemestersByFormation(id) {
  return await api.get(`/semesters?id_formation=${id}`)
}

export async function createSemester(data) {
  return await api.post('/semesters', data)
}

export async function editSemester(id, data) {
  return await api.put(`/semesters/${id}`, data)
}

export async function deleteSemester(id) {
  return await api.delete(`/semesters/${id}`)
}

/* PEDAGOGICAL INTERRUPTIONS */

export async function getPedagogicalInterruptions() {
  return await api.get('/pedagogical-interruptions')
}

export async function deletePedagogicalInterruption(id) {
  return await api.delete(`/pedagogical-interruptions/${id}`)
}

/*
@param data => {
  name
  start_date
  end_date
  formation_id
}
 */
export async function updatePedagogicalInterruption(id, data) {
  return await api.put(`/pedagogical-interruptions/${id}`, data)
}

/*
@param data => {
  name
  start_date
  end_date
  formation_id
}
 */
export async function createPedagogicalInterruption(data) {
  return await api.post('/pedagogical-interruptions', data, {
    headers: {
      'Content-Type': 'application/json'
    }})
}

/* GROUPS */

export async function getGroups(filters = {}) {
  let url = '/groups'

  const params = new URLSearchParams()
  if (filters.id_formation) {
    params.append('id_formation', filters.id_formation)
  }
  if (filters.id_school_year) {
    params.append('id_school_year', filters.id_school_year)
  }

  if (params.toString()) {
    url += `?${params.toString()}`
  }

  return await api.get(url)
}

export async function getGroup(id) {
  return await api.get(`/groups/${id}`)
}

/*
@param data => {
  name
  description
  order_number
  id_parent_group
  id_course_types
  id_formation
}
*/
export async function createGroup(data) {
  return await api.post('/groups', data)
}

/*
@param id
@param data => {
  name
  description
  order_number
  id_parent_group
  id_course_types
  id_formation
}
*/
export async function updateGroup(id, data) {
  return await api.put(`/groups/${id}`, data)
}

export async function deleteGroup(id) {
  return await api.delete(`/groups/${id}`)
}
