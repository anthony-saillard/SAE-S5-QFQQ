import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const FormationsApiService = {
  /**
   * Local cache for formations
   */
  _formationsCache: null,

  /**
   * Fetches all formations
   * @param {Object} filters - Optional filters
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing formation data
   */
  async fetchFormations(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      if (this._formationsCache !== null && !forceRefresh) {
        return enrichData ? await this._enrichData(this._formationsCache, enrichData) : this._formationsCache
      }

      const params = {}

      if (!empty(filters.id_school_year)) {
        params.id_school_year = filters.id_school_year
      }

      const response = await api.get('/formations', { params })

      this._formationsCache = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching formations', error)
      throw error
    }
  },

  /**
   * Fetches a single formation by ID
   * @param {number} id - The formation ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing formation data
   */
  async fetchFormation(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._formationsCache !== null) {
        const cachedFormation = this._formationsCache.find(formation => formation.id === id)
        if (cachedFormation) {
          return enrichData ? await this._enrichData(cachedFormation, enrichData) : cachedFormation
        }
      }

      const response = await api.get(`/formations/${id}`)

      if (this._formationsCache !== null) {
        const index = this._formationsCache.findIndex(formation => formation.id === id)
        if (index !== -1) {
          this._formationsCache[index] = response.data
        } else {
          this._formationsCache.push(response.data)
        }
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching formation with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Fetches hours data for a formation
   * @param {number} id - The formation ID
   * @returns {Promise} Promise containing formation hours data
   */
  async fetchFormationHours(id) {
    try {
      const response = await api.get(`/formations/hours/${id}`)
      return response.data
    } catch (error) {
      logger.error(`Error fetching hours for formation with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new formation
   * @param {Object} data - The formation data
   * @returns {Promise} Promise containing the created formation
   */
  async createFormation(data) {
    try {
      const response = await api.post('/formations', data)

      if (this._formationsCache !== null) {
        this._formationsCache.push(response.data)
      }

      return response.data
    } catch (error) {
      logger.error('Error creating formation', error)
      throw error
    }
  },

  /**
   * Updates an existing formation
   * @param {number} id - The formation ID
   * @param {Object} data - The formation data to update
   * @returns {Promise} Promise containing success message
   */
  async updateFormation(id, data) {
    try {
      const response = await api.put(`/formations/${id}`, data)

      if (this._formationsCache !== null) {
        const index = this._formationsCache.findIndex(formation => formation.id === id)
        if (index !== -1) {
          this._formationsCache[index] = { ...this._formationsCache[index], ...data }
        }
      }

      return response.data
    } catch (error) {
      logger.error(`Error updating formation with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a formation
   * @param {number} id - The formation ID
   * @returns {Promise} Promise containing success message
   */
  async deleteFormation(id) {
    try {
      const response = await api.delete(`/formations/${id}`)

      if (this._formationsCache !== null) {
        this._formationsCache = this._formationsCache.filter(formation => formation.id !== id)
      }

      return response.data
    } catch (error) {
      logger.error(`Error deleting formation with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Clears the formations cache
   */
  clearCache() {
    this._formationsCache = null
    logger.info('Formations cache cleared')
  },

  /**
   * Enriches formation data with related entities
   * @param {Object|Object[]} data - The formation data to enrich
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
        ['school_year', 'pedagogical_interruptions', 'semesters', 'groups'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if (!empty(data.id_school_year) && (entities.includes('school_year') || entities.includes('all'))) {
        enriched.school_year = await ApiService.schoolYears.fetchSchoolYear(data.id_school_year)
      }

      if (!empty(data.pedagogical_interruptions) && Array.isArray(data.pedagogical_interruptions) &&
        (entities.includes('pedagogical_interruptions') || entities.includes('all'))) {
        const piPromises = data.pedagogical_interruptions.map(piId =>
          ApiService.pedagogicalInterruptions.fetchPedagogicalInterruption(piId)
        )
        enriched.pedagogical_interruptions = await Promise.all(piPromises)
      }

      if (!empty(data.semesters) && Array.isArray(data.semesters) &&
        (entities.includes('semesters') || entities.includes('all'))) {
        const semestersPromises = data.semesters.map(semesterId =>
          ApiService.semesters.fetchSemester(semesterId)
        )
        enriched.semesters = await Promise.all(semestersPromises)
      }

      if (!empty(data.id_groups) && Array.isArray(data.id_groups) &&
        (entities.includes('groups') || entities.includes('all'))) {
        const groupsPromises = data.id_groups.map(groupId =>
          ApiService.groups.fetchGroup(groupId)
        )
        enriched.groups = await Promise.all(groupsPromises)
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching formation data', error)
      return data
    }
  }
}
