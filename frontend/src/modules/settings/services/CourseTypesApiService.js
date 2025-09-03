import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const CourseTypesApiService = {
  /**
   * Local cache for course types
   */
  _courseTypesCache: null,

  /**
   * Fetches all course types
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing course type data
   */
  async fetchCourseTypes(enrichData = false, forceRefresh = false) {
    try {
      if (this._courseTypesCache !== null && !forceRefresh) {
        return enrichData ? await this._enrichData(this._courseTypesCache, enrichData) : this._courseTypesCache
      }

      const params = {}

      const response = await api.get('/course-types', { params })

      this._courseTypesCache = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching course types', error)
      throw error
    }
  },

  /**
   * Fetches a single course type by ID
   * @param {number} id - The course type ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing course type data
   */
  async fetchCourseType(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._courseTypesCache !== null) {
        const cachedCourseType = this._courseTypesCache.find(courseType => courseType.id === id)
        if (cachedCourseType) {
          return enrichData ? await this._enrichData(cachedCourseType, enrichData) : cachedCourseType
        }
      }

      const response = await api.get(`/course-types/${id}`)

      if (this._courseTypesCache !== null) {
        const index = this._courseTypesCache.findIndex(courseType => courseType.id === id)
        if (index !== -1) {
          this._courseTypesCache[index] = response.data
        } else {
          this._courseTypesCache.push(response.data)
        }
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching course type with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new course type
   * @param {Object} data - The course type data
   * @returns {Promise} Promise containing the created course type
   */
  async createCourseType(data) {
    try {
      const response = await api.post('/course-types', data)

      if (this._courseTypesCache !== null) {
        this._courseTypesCache.push(response.data)
      }

      return response.data
    } catch (error) {
      logger.error('Error creating course type', error)
      throw error
    }
  },

  /**
   * Updates an existing course type
   * @param {number} id - The course type ID
   * @param {Object} data - The course type data to update
   * @returns {Promise} Promise containing success message or updated data
   */
  async updateCourseType(id, data) {
    try {
      const response = await api.put(`/course-types/${id}`, data)

      if (this._courseTypesCache !== null) {
        const index = this._courseTypesCache.findIndex(courseType => courseType.id === id)
        if (index !== -1) {
          this._courseTypesCache[index] = { ...this._courseTypesCache[index], ...data }
        }
      }

      return response.data
    } catch (error) {
      logger.error(`Error updating course type with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a course type
   * @param {number} id - The course type ID
   * @returns {Promise} Promise containing success message
   */
  async deleteCourseType(id) {
    try {
      const response = await api.delete(`/course-types/${id}`)

      if (this._courseTypesCache !== null) {
        this._courseTypesCache = this._courseTypesCache.filter(courseType => courseType.id !== id)
      }

      return response.data
    } catch (error) {
      logger.error(`Error deleting course type with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Clears the course types cache
   */
  clearCache() {
    this._courseTypesCache = null
    logger.info('Course types cache cleared')
  },

  /**
   * Enriches course type data with related entities
   * @param {Object|Object[]} data - The course type data to enrich
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
        ['groups'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if (entities.includes('groups') || entities.includes('all')) {
        enriched.groups = await ApiService.groups.fetchGroups({ id_course_type: data.id })
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching course type data', error)
      return data
    }
  }
}
