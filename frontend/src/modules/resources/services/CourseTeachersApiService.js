import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const CourseTeachersApiService = {
  /**
   * Local cache for course teachers
   */
  _courseTeachersCache: {},

  /**
   * Fetches all course teachers
   * @param {Object} filters - Optional filters (id_group, id_sub_resource, id_user)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing course teacher data
   */
  async fetchCourseTeachers(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      const params = {}
      const useFilters = !empty(filters.id_group) || !empty(filters.id_sub_resource) || !empty(filters.id_user)
      const cacheKey = useFilters ? JSON.stringify(filters) : 'all'

      if (!empty(filters.id_group)) {
        params.id_group = filters.id_group
      }
      if (!empty(filters.id_sub_resource)) {
        params.id_sub_resource = filters.id_sub_resource
      }
      if (!empty(filters.id_user)) {
        params.id_user = filters.id_user
      }

      if (!forceRefresh && this._courseTeachersCache[cacheKey]) {
        return enrichData ?
          await this._enrichData(this._courseTeachersCache[cacheKey], enrichData) :
          this._courseTeachersCache[cacheKey]
      }

      const response = await api.get('/course-teachers', { params })

      this._courseTeachersCache[cacheKey] = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching course teachers', error)
      throw error
    }
  },

  /**
   * Fetches a single course teacher by ID
   * @param {number} id - The course teacher ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing course teacher data
   */
  async fetchCourseTeacher(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._courseTeachersCache['all']) {
        const cachedCourseTeacher = this._courseTeachersCache['all']
          .find(courseTeacher => courseTeacher.id === id)
        if (cachedCourseTeacher) {
          return enrichData ?
            await this._enrichData(cachedCourseTeacher, enrichData) :
            cachedCourseTeacher
        }
      }

      const response = await api.get(`/course-teachers/${id}`)

      Object.keys(this._courseTeachersCache).forEach(cacheKey => {
        const courseTeachers = this._courseTeachersCache[cacheKey]
        const index = courseTeachers.findIndex(courseTeacher => courseTeacher.id === id)
        if (index !== -1) {
          this._courseTeachersCache[cacheKey][index] = response.data
        }
      })

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching course teacher with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new course teacher
   * @param {Object} data - The course teacher data
   * @returns {Promise} Promise containing the created course teacher
   */
  async createCourseTeacher(data) {
    try {
      const response = await api.post('/course-teachers', data)

      this.clearCache()

      return response.data
    } catch (error) {
      logger.error('Error creating course teacher', error)
      throw error
    }
  },

  /**
   * Updates an existing course teacher
   * @param {number} id - The course teacher ID
   * @param {Object} data - The course teacher data to update
   * @returns {Promise} Promise containing success message
   */
  async updateCourseTeacher(id, data) {
    try {
      const response = await api.put(`/course-teachers/${id}`, data)

      Object.keys(this._courseTeachersCache).forEach(cacheKey => {
        const courseTeachers = this._courseTeachersCache[cacheKey]
        const index = courseTeachers.findIndex(courseTeacher => courseTeacher.id === id)
        if (index !== -1) {
          this._courseTeachersCache[cacheKey][index] = { ...courseTeachers[index], ...data }
        }
      })

      return response.data
    } catch (error) {
      logger.error(`Error updating course teacher with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a course teacher
   * @param {number} id - The course teacher ID
   * @returns {Promise} Promise containing success message
   */
  async deleteCourseTeacher(id) {
    try {
      const response = await api.delete(`/course-teachers/${id}`)

      Object.keys(this._courseTeachersCache).forEach(cacheKey => {
        this._courseTeachersCache[cacheKey] = this._courseTeachersCache[cacheKey]
          .filter(courseTeacher => courseTeacher.id !== id)
      })

      return response.data
    } catch (error) {
      logger.error(`Error deleting course teacher with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Clears the course teachers cache
   */
  clearCache() {
    this._courseTeachersCache = {}
    logger.info('Course teachers cache cleared')
  },

  /**
   * Enriches course teacher data with related entities
   * @param {Object|Object[]} data - The course teacher data to enrich
   * @param {boolean|Array} entitiesToEnrich - Whether to enrich all data or array of entities to enrich
   * @returns {Promise} Promise containing enriched data
   */
  async _enrichData(data, entitiesToEnrich = false) {
    if (empty(data)) {
      return data
    }

    if (Array.isArray(data)) {
      return Promise.all(data.map(item => this._enrichData(item, entitiesToEnrich)))
    }

    try {
      const enriched = { ...data }
      const entities = entitiesToEnrich === true ?
        ['user', 'group', 'subResource'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if ((entities.includes('user') || entities.includes('all')) && !empty(data.id_user)) {
        enriched.user = await ApiService.users.fetchUser(data.id_user)
      }

      if ((entities.includes('group') || entities.includes('all')) && !empty(data.id_group)) {
        enriched.group = await ApiService.groups.fetchGroup(data.id_group)
      }

      if ((entities.includes('subResource') || entities.includes('all')) && !empty(data.id_sub_resource)) {
        enriched.sub_resource = await ApiService.subResources.fetchSubResource(data.id_sub_resource)
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching course teacher data', error)
      return data
    }
  }
}
