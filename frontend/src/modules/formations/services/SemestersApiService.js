import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const SemestersApiService = {
  /**
   * Local cache for semesters
   */
  _semestersCache: null,

  /**
   * Fetches all semesters
   * @param {Object} filters - Optional filters (id_formation, id_school_year)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing semester data
   */
  async fetchSemesters(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      const params = {}
      const useFilters = !empty(filters.id_formation) || !empty(filters.id_school_year)

      if (!empty(filters.id_formation)) {
        params.id_formation = filters.id_formation
      }
      if (!empty(filters.id_school_year)) {
        params.id_school_year = filters.id_school_year
      }

      if (!useFilters && this._semestersCache !== null && !forceRefresh) {
        return enrichData ? await this._enrichData(this._semestersCache, enrichData) : this._semestersCache
      }

      const response = await api.get('/semesters', { params })

      if (!useFilters) {
        this._semestersCache = response.data
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching semesters', error)
      throw error
    }
  },

  /**
   * Fetches a single semester by ID
   * @param {number} id - The semester ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing semester data
   */
  async fetchSemester(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._semestersCache !== null) {
        const cachedSemester = this._semestersCache.find(semester => semester.id === id)
        if (cachedSemester) {
          return enrichData ? await this._enrichData(cachedSemester, enrichData) : cachedSemester
        }
      }

      const response = await api.get(`/semesters/${id}`)

      if (this._semestersCache !== null) {
        const index = this._semestersCache.findIndex(semester => semester.id === id)
        if (index !== -1) {
          this._semestersCache[index] = response.data
        } else {
          this._semestersCache.push(response.data)
        }
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching semester with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new semester
   * @param {Object} data - The semester data
   * @returns {Promise} Promise containing the created semester
   */
  async createSemester(data) {
    try {
      const response = await api.post('/semesters', data)

      if (this._semestersCache !== null) {
        this._semestersCache.push(response.data)
      }

      return response.data
    } catch (error) {
      logger.error('Error creating semester', error)
      throw error
    }
  },

  /**
   * Updates an existing semester
   * @param {number} id - The semester ID
   * @param {Object} data - The semester data to update
   * @returns {Promise} Promise containing updated data
   */
  async updateSemester(id, data) {
    try {
      const response = await api.put(`/semesters/${id}`, data)

      if (this._semestersCache !== null) {
        const index = this._semestersCache.findIndex(semester => semester.id === id)
        if (index !== -1) {
          this._semestersCache[index] = { ...this._semestersCache[index], ...data }
        }
      }

      return response.data
    } catch (error) {
      logger.error(`Error updating semester with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a semester
   * @param {number} id - The semester ID
   * @returns {Promise} Promise containing success message
   */
  async deleteSemester(id) {
    try {
      const response = await api.delete(`/semesters/${id}`)

      if (this._semestersCache !== null) {
        this._semestersCache = this._semestersCache.filter(semester => semester.id !== id)
      }

      return response.data
    } catch (error) {
      logger.error(`Error deleting semester with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Clears the semesters cache
   */
  clearCache() {
    this._semestersCache = null
    logger.info('Semesters cache cleared')
  },

  /**
   * Enriches semester data with related entities
   * @param {Object|Object[]} data - The semester data to enrich
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
        ['formation', 'school_year', 'resources', 'pedagogical_interruptions'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if (!empty(data.id_formation) && (entities.includes('formation') || entities.includes('all'))) {
        enriched.formation = await ApiService.formations.fetchFormation(data.id_formation)
      }

      if (!empty(data.id_school_year) && (entities.includes('school_year') || entities.includes('all'))) {
        enriched.school_year = await ApiService.schoolYears.fetchSchoolYear(data.id_school_year)
      }

      if (entities.includes('resources') || entities.includes('all')) {
        enriched.resources = await ApiService.resources.fetchResources({ id_semester: data.id })
      }

      enriched.test = true
      if (!empty(data.id_formation) && entities.includes('pedagogical_interruptions') || entities.includes('all')) {
        enriched.pedagogical_interruptions = await ApiService.pedagogicalInterruptions.fetchPedagogicalInterruptions({ formation_id: data.id_formation })
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching semester data', error)
      return data
    }
  }
}
