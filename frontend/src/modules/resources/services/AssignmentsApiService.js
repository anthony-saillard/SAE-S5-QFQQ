import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const AssignmentsApiService = {
  /**
   * Local cache for assignments
   */
  _assignmentsCache: {},

  /**
   * Fetches all assignments
   * @param {Object} filters - Optional filters (id_sub_resource, id_user, id_group)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing assignment data
   */
  async fetchAssignments(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      const params = {}
      const useFilters = !empty(filters.id_sub_resource) || !empty(filters.id_user) || !empty(filters.id_group)
      const cacheKey = useFilters ? JSON.stringify(filters) : 'all'

      if (!empty(filters.id_sub_resource)) {
        params.id_sub_resource = filters.id_sub_resource
      }
      if (!empty(filters.id_user)) {
        params.id_user = filters.id_user
      }
      if (!empty(filters.id_group)) {
        params.id_group = filters.id_group
      }
      if (!empty(filters.id_semester)) {
        params.id_semester = filters.id_semester
      }
      if (!empty(filters.id_formation)) {
        params.id_formation = filters.id_formation
      }
      if (!empty(filters.date_start)) {
        params.date_start = filters.date_start
      }
      if (!empty(filters.date_end)) {
        params.date_end = filters.date_end
      }
      if (!forceRefresh && this._assignmentsCache[cacheKey]) {
        return enrichData ?
          await this._enrichData(this._assignmentsCache[cacheKey], enrichData) :
          this._assignmentsCache[cacheKey]
      }

      const response = await api.get('/assignments', { params })

      this._assignmentsCache[cacheKey] = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching assignments', error)
      throw error
    }
  },

  /**
   * Fetches a single assignment by ID
   * @param {number} id - The assignment ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing assignment data
   */
  async fetchAssignment(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && this._assignmentsCache['all']) {
        const cachedAssignment = this._assignmentsCache['all'].find(assignment => assignment.id === id)
        if (cachedAssignment) {
          return enrichData ? await this._enrichData(cachedAssignment, enrichData) : cachedAssignment
        }
      }

      const response = await api.get(`/assignments/${id}`)

      Object.keys(this._assignmentsCache).forEach(cacheKey => {
        const assignments = this._assignmentsCache[cacheKey]
        const index = assignments.findIndex(assignment => assignment.id === id)
        if (index !== -1) {
          this._assignmentsCache[cacheKey][index] = response.data
        }
      })

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching assignment with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new assignment
   * @param {Object} data - The assignment data
   * @returns {Promise} Promise containing the created assignment
   */
  async createAssignment(data) {
    try {
      const response = await api.post('/assignments', data)

      this.clearCache()

      return response.data
    } catch (error) {
      logger.error('Error creating assignment', error)
      throw error
    }
  },

  /**
   * Updates an existing assignment
   * @param {number} id - The assignment ID
   * @param {Object} data - The assignment data to update
   * @returns {Promise} Promise containing success message
   */
  async updateAssignment(id, data) {
    try {
      const response = await api.put(`/assignments/${id}`, data)

      Object.keys(this._assignmentsCache).forEach(cacheKey => {
        const assignments = this._assignmentsCache[cacheKey]
        const index = assignments.findIndex(assignment => assignment.id === id)
        if (index !== -1) {
          this._assignmentsCache[cacheKey][index] = { ...assignments[index], ...data }
        }
      })

      return response.data
    } catch (error) {
      logger.error(`Error updating assignment with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes an assignment
   * @param {number} id - The assignment ID
   * @returns {Promise} Promise containing success message
   */
  async deleteAssignment(id) {
    try {
      const response = await api.delete(`/assignments/${id}`)

      Object.keys(this._assignmentsCache).forEach(cacheKey => {
        this._assignmentsCache[cacheKey] = this._assignmentsCache[cacheKey]
          .filter(assignment => assignment.id !== id)
      })

      return response.data
    } catch (error) {
      logger.error(`Error deleting assignment with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Clears the assignments cache
   */
  clearCache() {
    this._assignmentsCache = {}
    logger.info('Assignments cache cleared')
  },

  /**
   * Enriches assignment data with related entities
   * @param {Object|Object[]} data - The assignment data to enrich
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
        ['user', 'sub_resource', 'group', 'resource'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if (!empty(data.id_users) && (entities.includes('user') || entities.includes('all'))) {
        enriched.user = await ApiService.users.fetchUser(data.id_users)
      }

      if (!empty(data.id_sub_resources) && (entities.includes('sub_resource') || entities.includes('all'))) {
        enriched.sub_resource = await ApiService.subResources.fetchSubResource(data.id_sub_resources)
      }

      if (!empty(data.formation_id) && (entities.includes('group') || entities.includes('all'))) {
        enriched.group = await ApiService.groups.fetchGroups(data.formation_id)
      }

      if ((!empty(data.id_group) || !empty(data.id_groups)) && (entities.includes('group') || entities.includes('all'))) {
        const groupId = data.id_group || data.id_groups
        enriched.group = await ApiService.groups.fetchGroup(groupId)
      }

      if (enriched.sub_resource && !empty(enriched.sub_resource.id_resources) &&
        (entities.includes('resource') || entities.includes('all'))) {
        try {
          enriched.resource = await ApiService.resources.fetchResource(enriched.sub_resource.id_resources)

          if (enriched.resource) {
            enriched.resource_name = `${enriched.resource.identifier} - ${enriched.resource.name}`
          }
        } catch (e) {
          logger.error('Erreur lors du chargement de la ressource:', e)
        }
      } else if ((!empty(data.id_resource) || !empty(data.id_resources)) && (entities.includes('resource') || entities.includes('all'))) {
        const resourceId = data.id_resource || data.id_resources
        enriched.resource = await ApiService.resources.fetchResource(resourceId)
      } else if (!empty(data.resource) && (entities.includes('resource') || entities.includes('all'))) {
        enriched.resource = await ApiService.resources.fetchResources()
      }

      if (enriched.sub_resource && enriched.sub_resource.resource) {
        const resource = enriched.sub_resource.resource
        enriched.resource_name = `${resource.identifier} - ${resource.name}`
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching assignment data', error)
      return data
    }
  }
}
