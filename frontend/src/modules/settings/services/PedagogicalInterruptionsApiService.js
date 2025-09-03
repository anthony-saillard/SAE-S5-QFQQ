import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const PedagogicalInterruptionsApiService = {
  /**
   * Local cache for pedagogical interruptions
   */
  _pedagogicalInterruptionsCache: null,

  /**
   * Fetches all pedagogical interruptions
   * @param {Object} filters - Optional filters (formation_id, id_school_year)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing pedagogical interruptions data
   */
  async fetchPedagogicalInterruptions(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      if (this._pedagogicalInterruptionsCache !== null && !forceRefresh) {
        return enrichData ?
          await this._enrichData(this._pedagogicalInterruptionsCache, enrichData) :
          this._pedagogicalInterruptionsCache
      }

      const params = {}

      if (!empty(filters.formation_id)) {
        params.id_formation = filters.formation_id
      }
      if (!empty(filters.id_school_year)) {
        params.id_school_year = filters.id_school_year
      }

      const response = await api.get('/pedagogical-interruptions', { params })

      this._pedagogicalInterruptionsCache = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching pedagogical interruptions', error)
      throw error
    }
  },

  /**
   * Fetches a single pedagogical interruption by ID
   * @param {number} id - The pedagogical interruption ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing pedagogical interruption data
   */
  async fetchPedagogicalInterruption(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._pedagogicalInterruptionsCache !== null) {
        const cachedItem = this._pedagogicalInterruptionsCache.find(item => item.id === id)
        if (cachedItem) {
          return enrichData ? await this._enrichData(cachedItem, enrichData) : cachedItem
        }
      }

      const response = await api.get(`/pedagogical-interruptions/${id}`)

      if (this._pedagogicalInterruptionsCache !== null) {
        const index = this._pedagogicalInterruptionsCache.findIndex(item => item.id === id)
        if (index !== -1) {
          this._pedagogicalInterruptionsCache[index] = response.data
        } else {
          this._pedagogicalInterruptionsCache.push(response.data)
        }
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching pedagogical interruption with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new pedagogical interruption
   * @param {Object} data - The pedagogical interruption data
   * @returns {Promise} Promise containing the created pedagogical interruption
   */
  async createPedagogicalInterruption(data) {
    try {
      const response = await api.post('/pedagogical-interruptions', data)

      if (this._pedagogicalInterruptionsCache !== null) {
        this._pedagogicalInterruptionsCache.push(response.data)
      }

      return response.data
    } catch (error) {
      logger.error('Error creating pedagogical interruption', error)
      throw error
    }
  },

  /**
   * Updates an existing pedagogical interruption
   * @param {number} id - The pedagogical interruption ID
   * @param {Object} data - The pedagogical interruption data to update
   * @returns {Promise} Promise containing updated data
   */
  async updatePedagogicalInterruption(id, data) {
    try {
      const response = await api.put(`/pedagogical-interruptions/${id}`, data)

      if (this._pedagogicalInterruptionsCache !== null) {
        const index = this._pedagogicalInterruptionsCache.findIndex(item => item.id === id)
        if (index !== -1) {
          this._pedagogicalInterruptionsCache[index] = { ...this._pedagogicalInterruptionsCache[index], ...data }
        }
      }

      return response.data
    } catch (error) {
      logger.error(`Error updating pedagogical interruption with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a pedagogical interruption
   * @param {number} id - The pedagogical interruption ID
   * @returns {Promise} Promise containing success message
   */
  async deletePedagogicalInterruption(id) {
    try {
      const response = await api.delete(`/pedagogical-interruptions/${id}`)

      if (this._pedagogicalInterruptionsCache !== null) {
        this._pedagogicalInterruptionsCache = this._pedagogicalInterruptionsCache.filter(item => item.id !== id)
      }

      return response.data
    } catch (error) {
      logger.error(`Error deleting pedagogical interruption with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Gets pedagogical interruptions for a specific formation
   * @param {number} formationId - The formation ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing pedagogical interruptions for the formation
   */
  async getInterruptionsByFormation(formationId, enrichData = false, forceRefresh = false) {
    try {
      return await this.fetchPedagogicalInterruptions({ formation_id: formationId }, enrichData, forceRefresh)

    } catch (error) {
      logger.error(`Error fetching interruptions for formation with ID ${formationId}`, error)
      throw error
    }
  },

  /**
   * Clears the pedagogical interruptions cache
   */
  clearCache() {
    this._pedagogicalInterruptionsCache = null
    logger.info('Pedagogical interruptions cache cleared')
  },

  /**
   * Enriches pedagogical interruption data with related entities
   * @param {Object|Object[]} data - The pedagogical interruption data to enrich
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
        ['formation', 'school_year'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if ((entities.includes('formation') || entities.includes('all')) && !empty(data.formation_id)) {
        enriched.formation = await ApiService.formations.fetchFormation(data.formation_id)
      }

      if ((entities.includes('school_year') || entities.includes('all')) && !empty(data.id_school_year)) {
        enriched.school_year = await ApiService.schoolYears.fetchSchoolYear(data.id_school_year)
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching pedagogical interruption data', error)
      return data
    }
  }
}
