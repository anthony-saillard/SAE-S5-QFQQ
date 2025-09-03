import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const SchoolYearsApiService = {
  /**
   * Local cache for school years
   */
  _schoolYearsCache: null,

  /**
   * Local cache for current school year
   */
  _currentSchoolYearCache: null,

  /**
   * Fetches all school years
   * @param {Object} filters - Optional filters
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing school year data
   */
  async fetchSchoolYears(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      if (this._schoolYearsCache !== null && !forceRefresh) {
        return enrichData ?
          await this._enrichData(this._schoolYearsCache, enrichData) :
          this._schoolYearsCache
      }

      const params = {}

      if (!empty(filters.is_current)) {
        params.is_current = filters.is_current
      }

      const response = await api.get('/school-years', { params })

      this._schoolYearsCache = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching school years', error)
      throw error
    }
  },

  /**
   * Fetches the current school year
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing current school year data
   */
  async fetchCurrentSchoolYear(enrichData = false, forceRefresh = false) {
    try {
      if (this._currentSchoolYearCache !== null && !forceRefresh) {
        return enrichData ?
          await this._enrichData(this._currentSchoolYearCache, enrichData) :
          this._currentSchoolYearCache
      }

      const response = await api.get('/school-years/current')

      this._currentSchoolYearCache = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching current school year', error)
      throw error
    }
  },

  /**
   * Fetches a single school year by ID
   * @param {number} id - The school year ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing school year data
   */
  async fetchSchoolYear(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._schoolYearsCache !== null) {
        const cachedSchoolYear = this._schoolYearsCache.find(schoolYear => schoolYear.id === id)
        if (cachedSchoolYear) {
          return enrichData ? await this._enrichData(cachedSchoolYear, enrichData) : cachedSchoolYear
        }
      }

      if (!forceRefresh && this._currentSchoolYearCache !== null && this._currentSchoolYearCache.id === id) {
        return enrichData ?
          await this._enrichData(this._currentSchoolYearCache, enrichData) :
          this._currentSchoolYearCache
      }

      const response = await api.get(`/school-years/${id}`)

      if (this._schoolYearsCache !== null) {
        const index = this._schoolYearsCache.findIndex(schoolYear => schoolYear.id === id)
        if (index !== -1) {
          this._schoolYearsCache[index] = response.data
        } else {
          this._schoolYearsCache.push(response.data)
        }
      }

      if (this._currentSchoolYearCache !== null && this._currentSchoolYearCache.id === id) {
        this._currentSchoolYearCache = response.data
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
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

      if (this._schoolYearsCache !== null) {
        this._schoolYearsCache.push(response.data)
      }

      return response.data
    } catch (error) {
      logger.error('Error creating school year', error)
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

      if (this._schoolYearsCache !== null) {
        const index = this._schoolYearsCache.findIndex(schoolYear => schoolYear.id === id)
        if (index !== -1) {
          this._schoolYearsCache[index] = { ...this._schoolYearsCache[index], ...data }
        }
      }

      if (this._currentSchoolYearCache !== null && this._currentSchoolYearCache.id === id) {
        this._currentSchoolYearCache = { ...this._currentSchoolYearCache, ...data }
      }

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

      if (this._schoolYearsCache !== null) {
        this._schoolYearsCache = this._schoolYearsCache.filter(schoolYear => schoolYear.id !== id)
      }

      if (this._currentSchoolYearCache !== null && this._currentSchoolYearCache.id === id) {
        this._currentSchoolYearCache = null
      }

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

      this._currentSchoolYearCache = response.data

      if (this._schoolYearsCache !== null) {
        const previousCurrent = this._schoolYearsCache.find(schoolYear => schoolYear.is_current === true)
        if (previousCurrent) {
          previousCurrent.is_current = false
        }

        const index = this._schoolYearsCache.findIndex(schoolYear => schoolYear.id === id)
        if (index !== -1) {
          this._schoolYearsCache[index] = { ...this._schoolYearsCache[index], is_current: true }
        }
      }

      return response.data
    } catch (error) {
      logger.error(`Error setting school year with ID ${id} as current`, error)
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
  },

  /**
   * Enriches school year data with related entities
   * @param {Object|Object[]} data - The school year data to enrich
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
        ['formations', 'semesters', 'resources', 'groups'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if (entities.includes('formations') || entities.includes('all')) {
        enriched.formations = await ApiService.formations.fetchFormations({ id_school_year: data.id })
      }

      if (entities.includes('semesters') || entities.includes('all')) {
        enriched.semesters = await ApiService.semesters.fetchSemesters({ id_school_year: data.id })
      }

      if (entities.includes('resources') || entities.includes('all')) {
        enriched.resources = await ApiService.resources.fetchResources({ id_school_year: data.id })
      }

      if (entities.includes('groups') || entities.includes('all')) {
        enriched.groups = await ApiService.groups.fetchGroups({ id_school_year: data.id })
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching school year data', error)
      return data
    }
  }
}
