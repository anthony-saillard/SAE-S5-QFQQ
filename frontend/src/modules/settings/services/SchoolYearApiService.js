import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'

export const SchoolYearApiService = {
  /**
   * Fetches all school years
   * @returns {Promise} Promise containing school year data
   */
  async fetchSchoolYears() {
    try {
      const response = await api.get('/school-years')
      return response.data
    } catch (error) {
      logger.error('Error fetching school years', error)
      throw error
    }
  },

  /**
   * Fetches the current school year
   * @returns {Promise} Promise containing current school year data
   */
  async fetchCurrentSchoolYear() {
    try {
      const response = await api.get('/school-years/current')
      return response.data
    } catch (error) {
      logger.error('Error fetching current school year', error)
      throw error
    }
  },

  /**
   * Fetches a single school year by ID
   * @param {number} id - The school year ID
   * @returns {Promise} Promise containing school year data
   */
  async fetchSchoolYear(id) {
    try {
      const response = await api.get(`/school-years/${id}`)
      return response.data
    } catch (error) {
      logger.error(`Error fetching school year with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new school year
   * @param {Object} data - The school year data
   * @returns {Promise} Promise containing the created school year
   */
  async createSchoolYear(data) {
    try {
      const response = await api.post('/school-years', data)
      return response.data
    } catch (error) {
      logger.error('Error creating school year', error)
      throw error
    }
  },

  /**
   * Duplicates an existing school year
   * @param {string} label - The name for the new school year
   * @param {number} sourceYearId - The ID of the school year to duplicate from
   * @param {Object} duplicationOptions - Options specifying which elements to duplicate
   * @param {boolean} duplicationOptions.ressources - Whether to duplicate resources
   * @param {boolean} duplicationOptions.formations - Whether to duplicate formations
   * @param {boolean} duplicationOptions.periodesParticulieres - Whether to duplicate special periods
   * @param {boolean} duplicationOptions.semestres - Whether to duplicate semesters
   * @param {boolean} duplicationOptions.groups - Whether to duplicate groups
   * @returns {Promise} Promise containing the duplicated school year
   */
  async duplicateSchoolYear(label, sourceYearId, duplicationOptions) {
    try {
      const response = await api.post('/school-years/duplicate', {
        label,
        sourceYearId,
        duplicationOptions: {
          ressources: duplicationOptions?.ressources || false,
          formations: duplicationOptions?.formations || false,
          periodesParticulieres: duplicationOptions?.periodesParticulieres || false,
          semestres: duplicationOptions?.semestres || false,
          groups: duplicationOptions?.groups || false
        }
      })
      this.clearCache()
      return response.data
    } catch (error) {
      logger.error('Error duplicating school year', error)
      throw error
    }
  },

  /**
   * Updates an existing school year
   * @param {number} id - The school year ID
   * @param {Object} data - The school year data to update
   * @returns {Promise} Promise containing success message
   */
  async updateSchoolYear(id, data) {
    try {
      const response = await api.put(`/school-years/${id}`, data)
      return response.data
    } catch (error) {
      logger.error(`Error updating school year with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a school year
   * @param {number} id - The school year ID
   * @returns {Promise} Promise containing success message
   */
  async deleteSchoolYear(id) {
    try {
      const response = await api.delete(`/school-years/${id}`)
      return response.data
    } catch (error) {
      logger.error(`Error deleting school year with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Sets a school year as the current one
   * @param {number} id - The school year ID to set as current
   * @returns {Promise} Promise containing updated school year data
   */
  async setCurrentSchoolYear(id) {
    try {
      const response = await api.put(`/school-years/${id}/set-current`)
      return response.data
    } catch (error) {
      logger.error(`Error setting school year with ID ${id} as current`, error)
      throw error
    }
  },

  /**
   * Local cache for school years
   */
  _schoolYearsCache: null,

  /**
   * Fetches school years with caching
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing school year data
   */
  async getSchoolYearsWithCache(forceRefresh = false) {
    try {
      if (this._schoolYearsCache === null || forceRefresh) {
        const schoolYears = await this.fetchSchoolYears()
        this._schoolYearsCache = schoolYears
        return schoolYears
      }

      return this._schoolYearsCache
    } catch (error) {
      logger.error('Error fetching school years with cache', error)
      throw error
    }
  },

  /**
   * Local cache for current school year
   */
  _currentSchoolYearCache: null,

  /**
   * Fetches current school year with caching
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing current school year data
   */
  async getCurrentSchoolYearWithCache(forceRefresh = false) {
    try {
      if (this._currentSchoolYearCache === null || forceRefresh) {
        const currentSchoolYear = await this.fetchCurrentSchoolYear()
        this._currentSchoolYearCache = currentSchoolYear
        return currentSchoolYear
      }

      return this._currentSchoolYearCache
    } catch (error) {
      logger.error('Error fetching current school year with cache', error)
      throw error
    }
  },

  /**
   * Clears the school years cache
   */
  clearCache() {
    this._schoolYearsCache = null
    this._currentSchoolYearCache = null
    logger.info('School years cache cleared')
  }
}
