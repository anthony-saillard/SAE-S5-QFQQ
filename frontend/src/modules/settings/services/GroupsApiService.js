import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const GroupsApiService = {
  /**
   * Fetches all groups
   * @param {Object} filters - Optional filters (id_formation, id_school_year, id_course_type, id_parent_group)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} hierarchical - Whether to return data in a hierarchical tree structure
   * @returns {Promise} Promise containing groups data
   */
  async fetchGroups(filters = {}, enrichData = false, hierarchical = false) {
    try {
      const params = {}

      if (!empty(filters.id_formation)) {
        params.id_formation = filters.id_formation
      }
      if (!empty(filters.id_school_year)) {
        params.id_school_year = filters.id_school_year
      }
      if (!empty(filters.id_course_type)) {
        params.id_course_type = filters.id_course_type
      }
      if (!empty(filters.id_parent_group)) {
        params.id_parent_group = filters.id_parent_group
      }

      const response = await api.get('/groups', { params })

      const data = enrichData ? await this._enrichData(response.data, enrichData) : response.data

      return hierarchical ? this._buildHierarchy(data) : data
    } catch (error) {
      logger.error('Error fetching groups', error)
      throw error
    }
  },

  /**
   * Fetches a single group by ID
   * @param {number} id - The group ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @returns {Promise} Promise containing group data
   */
  async fetchGroup(id, enrichData = false) {
    try {
      const response = await api.get(`/groups/${id}`)

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching group with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Creates a new group
   * @param {Object} data - The group data
   * @returns {Promise} Promise containing the created group
   */
  async createGroup(data) {
    try {
      const response = await api.post('/groups', data)
      return response.data
    } catch (error) {
      logger.error('Error creating group', error)
      throw error
    }
  },

  /**
   * Updates an existing group
   * @param {number} id - The group ID
   * @param {Object} data - The group data to update
   * @returns {Promise} Promise containing success message
   */
  async updateGroup(id, data) {
    try {
      const response = await api.put(`/groups/${id}`, data)
      return response.data
    } catch (error) {
      logger.error(`Error updating group with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Deletes a group
   * @param {number} id - The group ID
   * @returns {Promise} Promise containing success message
   */
  async deleteGroup(id) {
    try {
      const response = await api.delete(`/groups/${id}`)
      return response.data
    } catch (error) {
      logger.error(`Error deleting group with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Builds a hierarchical tree structure of groups
   * @param {Object[]} groups - Flat array of group objects
   * @returns {Object[]} Hierarchical structure of groups
   */
  _buildHierarchy(groups) {
    if (!Array.isArray(groups) || groups.length === 0) {
      return []
    }

    const groupMap = {}
    const rootGroups = []

    groups.forEach(group => {
      groupMap[group.id] = {
        ...group,
        children: []
      }
    })

    groups.forEach(group => {
      if (group.id_parent_group) {
        if (groupMap[group.id_parent_group]) {
          groupMap[group.id_parent_group].children.push(groupMap[group.id])
        } else {
          rootGroups.push(groupMap[group.id])
        }
      } else {
        rootGroups.push(groupMap[group.id])
      }
    })

    return rootGroups
  },

  /**
   * Enriches group data with related entities
   * @param {Object|Object[]} data - The group data to enrich
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
        ['course_type', 'formation', 'parent_group', 'children_groups', 'assignments', 'course_teachers'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if ((entities.includes('course_type') || entities.includes('all')) && !empty(data.id_course_type)) {
        enriched.course_type = await ApiService.courseTypes.fetchCourseType(data.id_course_type)
      }

      if ((entities.includes('formation') || entities.includes('all')) && !empty(data.id_formation)) {
        enriched.formation = await ApiService.formations.fetchFormation(data.id_formation)
      }

      if ((entities.includes('parent_group') || entities.includes('all')) && !empty(data.id_parent_group)) {
        enriched.parent_group = await ApiService.groups.fetchGroup(data.id_parent_group)
      }

      if ((entities.includes('children_groups') || entities.includes('all'))) {
        enriched.children_groups = await ApiService.groups.fetchGroups({ id_parent_group: data.id })
      }

      if (entities.includes('assignments') || entities.includes('all')) {
        enriched.assignments = await ApiService.assignments.fetchAssignments({ id_group: data.id })
      }

      if (entities.includes('course_teachers') || entities.includes('all')) {
        enriched.course_teachers = await ApiService.courseTeachers.fetchCourseTeachers({ id_group: data.id })
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching group data', error)
      return data
    }
  }
}
