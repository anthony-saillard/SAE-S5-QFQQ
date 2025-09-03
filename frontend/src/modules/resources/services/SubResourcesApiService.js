import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const SubResourcesApiService = {
  /**
   * Local cache for sub-resources
   */
  _subResourcesCache: {},

  /**
   * Fetches all sub-resources
   * @param {Object} filters - Optional filters (id_resource, id_user)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing sub-resource data
   */
  async fetchSubResources(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      const params = {}
      const useFilters = !empty(filters.id_resource) || !empty(filters.id_user)
      const cacheKey = useFilters ? JSON.stringify(filters) : 'all'

      if (!empty(filters.id_resource)) {
        params.id_resource = filters.id_resource
      }
      if (!empty(filters.id_user)) {
        params.id_user = filters.id_user
      }

      if (!forceRefresh && this._subResourcesCache[cacheKey]) {
        return enrichData ?
          await this._enrichData(this._subResourcesCache[cacheKey], enrichData) :
          this._subResourcesCache[cacheKey]
      }

      const response = await api.get('/sub-resources', { params })

      this._subResourcesCache[cacheKey] = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching sub-resources', error)
      throw error
    }
  },

  /**
   * Fetches a single sub-resource by ID
   * @param {number} id - The sub-resource ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing sub-resource data
   */
  async fetchSubResource(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh) {
        for (const cacheKey in this._subResourcesCache) {
          const cachedSubResources = this._subResourcesCache[cacheKey]
          const cachedSubResource = cachedSubResources.find(subResource => subResource.id === id)
          if (cachedSubResource) {
            return enrichData ? await this._enrichData(cachedSubResource, enrichData) : cachedSubResource
          }
        }
      }

      const response = await api.get(`/sub-resources/${id}`)

      for (const cacheKey in this._subResourcesCache) {
        const subResources = this._subResourcesCache[cacheKey]
        const index = subResources.findIndex(subResource => subResource.id === id)
        if (index !== -1) {
          this._subResourcesCache[cacheKey][index] = response.data
        }
      }

      if (this._subResourcesCache['all'] && !this._subResourcesCache['all'].some(sr => sr.id === id)) {
        this._subResourcesCache['all'].push(response.data)
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching sub-resource with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new sub-resource
   * @param {Object} data - The sub-resource data
   * @returns {Promise} Promise containing the created sub-resource
   */
  async createSubResource(data) {
    try {
      const response = await api.post('/sub-resources', data)

      if (this._subResourcesCache['all']) {
        this._subResourcesCache['all'].push(response.data)
      }

      for (const cacheKey in this._subResourcesCache) {
        if (cacheKey === 'all') {
          continue
        }

        const filters = JSON.parse(cacheKey)
        let shouldAdd = true

        if (filters.id_resource && data.id_resource !== filters.id_resource) {
          shouldAdd = false
        }
        if (filters.id_user && data.id_user !== filters.id_user) {
          shouldAdd = false
        }

        if (shouldAdd) {
          this._subResourcesCache[cacheKey].push(response.data)
        }
      }

      return response.data
    } catch (error) {
      logger.error('Error creating sub-resource', error)
      throw error
    }
  },

  /**
   * Updates an existing sub-resource
   * @param {number} id - The sub-resource ID
   * @param {Object} data - The sub-resource data to update
   * @returns {Promise} Promise containing success message
   */
  async updateSubResource(id, data) {
    try {
      const response = await api.put(`/sub-resources/${id}`, data)

      for (const cacheKey in this._subResourcesCache) {
        const subResources = this._subResourcesCache[cacheKey]
        const index = subResources.findIndex(subResource => subResource.id === id)

        if (index !== -1) {
          this._subResourcesCache[cacheKey][index] = {
            ...this._subResourcesCache[cacheKey][index],
            ...data
          }
        }
      }

      return response.data
    } catch (error) {
      logger.error(`Error updating sub-resource with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a sub-resource
   * @param {number} id - The sub-resource ID
   * @returns {Promise} Promise containing success message
   */
  async deleteSubResource(id) {
    try {
      const response = await api.delete(`/sub-resources/${id}`)

      for (const cacheKey in this._subResourcesCache) {
        this._subResourcesCache[cacheKey] = this._subResourcesCache[cacheKey]
          .filter(subResource => subResource.id !== id)
      }

      return response.data
    } catch (error) {
      logger.error(`Error deleting sub-resource with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Gets sub-resources for a specific resource
   * @param {number} resourceId - The resource ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing sub-resources for the resource
   */
  async getSubResourcesByResource(resourceId, enrichData = false, forceRefresh = false) {
    return this.fetchSubResources({ id_resource: resourceId }, enrichData, forceRefresh)
  },

  /**
   * Searches sub-resources by name
   * @param {string} searchTerm - Term to search for
   * @param {Array} subResources - Optional array of sub-resources to search in (if not provided, will fetch all)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @returns {Promise} Promise containing matching sub-resources
   */
  async searchSubResources(searchTerm, subResources = null, enrichData = false) {
    try {
      if (empty(searchTerm)) {
        return []
      }

      const data = subResources || (await this.fetchSubResources())
      const lowerSearchTerm = searchTerm.toLowerCase()

      const filteredSubResources = data.filter((subResource) =>
        subResource.name.toLowerCase().includes(lowerSearchTerm)
      )

      return enrichData ? await this._enrichData(filteredSubResources, enrichData) : filteredSubResources
    } catch (error) {
      logger.error(`Error searching sub-resources with term "${searchTerm}"`, error)
      throw error
    }
  },

  /**
   * Clears the sub-resources cache
   */
  clearCache() {
    this._subResourcesCache = {}
    logger.info('Sub-resources cache cleared')
  },

  /**
   * Enriches sub-resource data with related entities
   * @param {Object|Object[]} data - The sub-resource data to enrich
   * @param {boolean|Array} entitiesToEnrich - Whether to enrich all data or array of entities to enrich
   * @returns {Promise} Promise containing enriched data
   */
  async _enrichData(data, entitiesToEnrich = false) {
    if (empty(data)) {
      return data
    }

    if (Array.isArray(data)) {
      return Promise.all(data.map((item) => this._enrichData(item, entitiesToEnrich)))
    }

    try {
      const enriched = { ...data }
      const entities = entitiesToEnrich === true ?
        ['user', 'resource', 'assignments', 'course_teachers'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if ((entities.includes('user') || entities.includes('all')) && !empty(data.id_users)) {
        enriched.user = await ApiService.users.fetchUser(data.id_users)
      }

      if ((entities.includes('resource') || entities.includes('all')) && !empty(data.id_resource)) {
        enriched.resource = await ApiService.resources.fetchResource(data.id_resource)
      }

      if (entities.includes('assignments') || entities.includes('all')) {
        enriched.assignments = await ApiService.assignments.fetchAssignments({ id_sub_resource: data.id })
      }

      if (entities.includes('course_teachers') || entities.includes('all')) {
        enriched.course_teachers = await ApiService.courseTeachers.fetchCourseTeachers({ id_sub_resource: data.id })
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching sub-resource data', error)
      return data
    }
  },

  /**
   * Updates the status of a sub-resource
   * @param {number} id - The sub-resource ID
   * @param {string} status - The new status ('NOT_STARTED', 'IN_PROGRESS', 'COMPLETED')
   * @returns {Promise} Promise containing the updated sub-resource
   */
  async updateSubResourceStatus(id, status) {
    try {
      if (!['NOT_STARTED', 'IN_PROGRESS', 'COMPLETED'].includes(status)) {
        throw new Error('Invalid status value')
      }

      return await this.updateSubResource(id, { status })
    } catch (error) {
      logger.error(`Error updating sub-resource status for ID ${id}`, error)
      throw error
    }
  },

  /**
   * Checks and updates the status if needed when a new assignment is created
   * @param {number} subResourceId - The sub-resource ID
   * @returns {Promise<boolean>} Promise indicating if update was performed
   */
  async checkAndUpdateStatusForNewAssignment(subResourceId) {
    try {
      const subResource = await this.fetchSubResource(subResourceId, false)

      if (subResource && subResource.status === 'NOT_STARTED') {
        await this.updateSubResourceStatus(subResourceId, 'IN_PROGRESS')
        return true
      }

      return false
    } catch (error) {
      logger.error(`Error checking status for sub-resource ID ${subResourceId}`, error)
      return false
    }
  }
}
