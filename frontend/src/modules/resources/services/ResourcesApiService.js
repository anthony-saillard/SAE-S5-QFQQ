import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const ResourcesApiService = {
  /**
   * Local cache for resources
   */
  _resourcesCache: null,

  /**
   * Fetches all resources
   * @param {Object} filters - Optional filters (id_user, id_semester, id_formation, id_school_year)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing resource data
   */
  async fetchResources(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      const params = {}
      const useFilters = !empty(filters.id_user) ||
        !empty(filters.id_semester) ||
        !empty(filters.id_formation) ||
        !empty(filters.id_school_year)

      if (!empty(filters.id_user)) {
        params.id_user = filters.id_user
      }
      if (!empty(filters.id_semester)) {
        params.id_semester = filters.id_semester
      }
      if (!empty(filters.id_formation)) {
        params.id_formation = filters.id_formation
      }
      if (!empty(filters.id_school_year)) {
        params.id_school_year = filters.id_school_year
      }

      if (!useFilters && this._resourcesCache !== null && !forceRefresh) {
        return enrichData ? await this._enrichData(this._resourcesCache, enrichData) : this._resourcesCache
      }

      const response = await api.get('/resources', { params })

      if (!useFilters) {
        this._resourcesCache = response.data
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching resources', error)
      throw error
    }
  },

  /**
   * Fetches a single resource by ID
   * @param {number} id - The resource ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing resource data
   */
  async fetchResource(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._resourcesCache !== null) {
        const cachedResource = this._resourcesCache.find(resource => resource.id === id)
        if (cachedResource) {
          return enrichData ? await this._enrichData(cachedResource, enrichData) : cachedResource
        }
      }

      const response = await api.get(`/resources/${id}`)

      if (this._resourcesCache !== null) {
        const index = this._resourcesCache.findIndex(resource => resource.id === id)
        if (index !== -1) {
          this._resourcesCache[index] = response.data
        } else {
          this._resourcesCache.push(response.data)
        }
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching resource with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new resource
   * @param {Object} data - The resource data
   * @returns {Promise} Promise containing the created resource
   */
  async createResource(data) {
    try {
      const response = await api.post('/resources', data)

      if (this._resourcesCache !== null) {
        this._resourcesCache.push(response.data)
      }

      return response.data
    } catch (error) {
      logger.error('Error creating resource', error)
      throw error
    }
  },

  /**
   * Updates an existing resource
   * @param {number} id - The resource ID
   * @param {Object} data - The resource data to update
   * @returns {Promise} Promise containing success message
   */
  async updateResource(id, data) {
    try {
      const response = await api.put(`/resources/${id}`, data)

      if (this._resourcesCache !== null) {
        const index = this._resourcesCache.findIndex(resource => resource.id === id)
        if (index !== -1) {
          this._resourcesCache[index] = { ...this._resourcesCache[index], ...data }
        }
      }

      return response.data
    } catch (error) {
      logger.error(`Error updating resource with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a resource
   * @param {number} id - The resource ID
   * @returns {Promise} Promise containing success message
   */
  async deleteResource(id) {
    try {
      const response = await api.delete(`/resources/${id}`)

      if (this._resourcesCache !== null) {
        this._resourcesCache = this._resourcesCache.filter(resource => resource.id !== id)
      }

      return response.data
    } catch (error) {
      logger.error(`Error deleting resource with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Searches resources by name or identifier
   * @param {string} searchTerm - Term to search for
   * @param {Array} resources - Optional array of resources to search in (if not provided, will fetch all)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @returns {Promise} Promise containing matching resources
   */
  async searchResources(searchTerm, resources = null, enrichData = false) {
    try {
      if (empty(searchTerm)) {
        return []
      }

      const data = resources || await this.fetchResources()
      const lowerSearchTerm = searchTerm.toLowerCase()

      const filteredResources = data.filter(resource =>
        resource.name.toLowerCase().includes(lowerSearchTerm) ||
        resource.identifier.toLowerCase().includes(lowerSearchTerm) ||
        (resource.description && resource.description.toLowerCase().includes(lowerSearchTerm))
      )

      return enrichData ? await this._enrichData(filteredResources, enrichData) : filteredResources
    } catch (error) {
      logger.error(`Error searching resources with term "${searchTerm}"`, error)
      throw error
    }
  },

  /**
   * Clears the resources cache
   */
  clearCache() {
    this._resourcesCache = null
    logger.info('Resources cache cleared')
  },

  /**
   * Enriches resource data with related entities
   * @param {Object|Object[]} data - The resource data to enrich
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
        ['user', 'users', 'sub_resources', 'sub_resources_user', 'pedagogical_interruptions', 'semester', 'assignments', 'groups'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if (!empty(data.id_users) && (entities.includes('user') || entities.includes('all'))) {
        enriched.user = await ApiService.users.fetchUser(data.id_users)
      }

      if (!empty(data.sub_resources) && (entities.includes('sub_resources') || entities.includes('sub_resources_user') || entities.includes('all'))) {
        if (!empty(data.sub_resources) && Array.isArray(data.sub_resources)) {

          const enrichData = []
          if (entities.includes('sub_resources_user')) {
            enrichData.push('user')
          }
          const subResourcesPromises = data.sub_resources.map(id =>
            ApiService.subResources.fetchSubResource(id, !empty(enrichData) ? enrichData : false)
          )
          enriched.sub_resources = await Promise.all(subResourcesPromises, [])
        }
      }

      if (!empty(data.id_semesters) && (entities.includes('semester') || entities.includes('all'))) {
        const enrichData = []
        if (entities.includes('pedagogical_interruptions')) {
          enrichData.push('pedagogical_interruptions')
        }
        enriched.semester = await ApiService.semesters.fetchSemester(data.id_semesters, !empty(enrichData) ? enrichData : false)
      }

      if ((entities.includes('assignments') || entities.includes('all')) && !empty(data.id)) {
        enriched.assignments = await ApiService.assignments.fetchAssignments({
          id_resource: data.id
        })
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching resource data', error)
      return data
    }
  }
}
