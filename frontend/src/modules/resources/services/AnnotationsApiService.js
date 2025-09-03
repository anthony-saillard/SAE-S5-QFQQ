import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const AnnotationsApiService = {
  /**
   * Local cache for annotations
   */
  _annotationsCache: [],

  /**
   * Fetches all annotations
   * @param {Object} filters - Optional filters (id_resources, id_user)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing annotations data
   */
  async fetchAnnotations(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      const params = {}
      const useFilters = !empty(filters.id_resources) || !empty(filters.id_user)
      const cacheKey = useFilters ? JSON.stringify(filters) : 'all'

      if (!empty(filters.id_resources)) {
        params.id_resources = filters.id_resources
      }
      if (!empty(filters.id_user)) {
        params.id_user = filters.id_user
      }

      if (!forceRefresh && this._annotationsCache[cacheKey]) {
        return enrichData ?
          await this._enrichData(this._annotationsCache[cacheKey], enrichData) :
          this._annotationsCache[cacheKey]
      }

      const response = await api.get('/annotations', { params })

      this._annotationsCache[cacheKey] = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching annotations', error)
      throw error
    }
  },

  /**
   * Fetches a single annotation by ID
   * @param {number} id - The annotation ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing annotation data
   */
  async fetchAnnotation(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._annotationsCache['all']) {
        const cachedAnnotation = this._annotationsCache['all'].find(annotation => annotation.id === id)
        if (cachedAnnotation) {
          return enrichData ? await this._enrichData(cachedAnnotation, enrichData) : cachedAnnotation
        }
      }

      const response = await api.get(`/annotations/${id}`)

      Object.keys(this._annotationsCache).forEach(cacheKey => {
        const annotations = this._annotationsCache[cacheKey]
        const index = annotations.findIndex(annotation => annotation.id === id)
        if (index !== -1) {
          this._annotationsCache[cacheKey][index] = response.data
        }
      })

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching annotation with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new annotation
   * @param {Object} data - The annotation data
   * @returns {Promise} Promise containing the created annotation
   */
  async createAnnotation(data) {
    try {
      const response = await api.post('/annotations', data)

      this.clearCache()

      return response.data
    } catch (error) {
      logger.error('Error creating annotation', error)
      throw error
    }
  },

  /**
   * Updates an existing annotation
   * @param {number} id - The annotation ID
   * @param {Object} data - The annotation data to update
   * @returns {Promise} Promise containing success message
   */
  async updateAnnotation(id, data) {
    try {
      const response = await api.put(`/annotations/${id}`, data)

      Object.keys(this._annotationsCache).forEach(cacheKey => {
        const annotations = this._annotationsCache[cacheKey]
        const index = annotations.findIndex(annotation => annotation.id === id)
        if (index !== -1) {
          this._annotationsCache[cacheKey][index] = { ...annotations[index], ...data }
        }
      })

      return response.data
    } catch (error) {
      logger.error(`Error updating annotation with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes an annotation
   * @param {number} id - The annotation ID
   * @returns {Promise} Promise containing success message
   */
  async deleteAnnotation(id) {
    try {
      const response = await api.delete(`/annotations/${id}`)

      Object.keys(this._annotationsCache).forEach(cacheKey => {
        this._annotationsCache[cacheKey] = this._annotationsCache[cacheKey]
          .filter(annotation => annotation.id !== id)
      })

      return response.data
    } catch (error) {
      logger.error(`Error deleting annotation with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Clears the annotations cache
   */
  clearCache() {
    this._annotationsCache = {}
    logger.info('Annotations cache cleared')
  },

  /**
   * Enriches annotation data with related entities
   * @param {Object|Object[]} data - The annotation data to enrich
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
        ['user', 'resource'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if (!empty(data.id_user) && (entities.includes('user') || entities.includes('all'))) {
        enriched.user = await ApiService.users.fetchUser(data.id_user)
      }

      if (!empty(data.id_resources) && (entities.includes('resource') || entities.includes('all'))) {
        enriched.resource = await ApiService.resources.fetchResource(data.id_resources)
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching annotation data', error)
      return data
    }
  }
}
