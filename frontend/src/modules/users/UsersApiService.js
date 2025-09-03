import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'
import { empty, formatUserName } from 'src/utils/utils.js'
import { ApiService } from 'src/services/apiService.js'

export const UsersApiService = {
  /**
   * Local cache for users
   */
  _usersCache: [],

  /**
   * Fetches all users
   * @param {Object} filters - Optional filters (role, search)
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing user data
   */
  async fetchUsers(filters = {}, enrichData = false, forceRefresh = false) {
    try {
      if (!empty(this._usersCache) && !forceRefresh) {
        return enrichData ? await this._enrichData(this._usersCache, enrichData) : this._usersCache
      }

      const params = {}

      if (!empty(filters.role)) {
        params.role = filters.role
      }
      if (!empty(filters.search)) {
        params.search = filters.search
      }

      const response = await api.get('/users', { params })

      this._usersCache = response.data

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error('Error fetching users', error)
      throw error
    }
  },

  /**
   * Fetches a user by ID
   * @param {number} id - User ID
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing user data
   */
  async fetchUser(id, enrichData = false, forceRefresh = false) {
    try {
      if (!forceRefresh && !empty(this._usersCache)) {
        const cachedUser = this._usersCache.find(user => user.id === id)
        if (cachedUser) {
          return enrichData ? await this._enrichData(cachedUser, enrichData) : cachedUser
        }
      }

      const response = await api.get(`/users/${id}`)

      if (!empty(this._usersCache)) {
        const index = this._usersCache.findIndex(user => user.id === id)
        if (index !== -1) {
          this._usersCache[index] = response.data
        } else {
          this._usersCache.push(response.data)
        }
      }

      return enrichData ? await this._enrichData(response.data, enrichData) : response.data
    } catch (error) {
      logger.error(`Error fetching user with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Updates an existing user
   * @param {number} id - User ID to update
   * @param {Object} userData - New user data
   * @returns {Promise} Promise containing updated user data
   */
  async updateUser(id, userData) {
    try {
      const response = await api.put(`/users/${id}`, userData)

      if (!empty(this._usersCache)) {
        const index = this._usersCache.findIndex(user => user.id === id)
        if (index !== -1) {
          this._usersCache[index] = { ...this._usersCache[index], ...userData }
        }
      }

      return response.data
    } catch (error) {
      logger.error(`Error updating user with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Create new user
   * @param {Object} userData - New user data
   * @returns {Promise} Promise containing new user data
   */
  async createUser(userData) {
    try {
      const response = await api.post('/register', userData)

      if (!empty(this._usersCache)) {
        this._usersCache.push(response.data.user)
      }

      return response.data
    } catch (error) {
      logger.error('Error create new user', error)
      throw error
    }
  },

  /**
   * Deletes a user
   * @param {number} id - User ID to delete
   * @returns {Promise} Promise containing deletion confirmation
   */
  async deleteUser(id) {
    try {
      const response = await api.delete(`/users/${id}`)

      if (!empty(this._usersCache)) {
        this._usersCache = this._usersCache.filter(user => user.id !== id)
      }

      return response.data
    } catch (error) {
      logger.error(`Error deleting user with ID ${id}`, error)
      throw error
    }
  },

  /**
   * Clears the user cache
   */
  clearCache() {
    this._usersCache = null
    logger.info('User cache cleared')
  },

  /**
   * Enriches user data with related entities
   * @param {Object|Object[]} data - The user data to enrich
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
        ['resources', 'sub_resources', 'assignments', 'course_teachers'] :
        (Array.isArray(entitiesToEnrich) ? entitiesToEnrich : [])

      if (entities.includes('resources') || entities.includes('all')) {
        enriched.resources = await ApiService.resources.fetchResources({ id_user: data.id })
      }

      if (entities.includes('sub_resources') || entities.includes('all')) {
        enriched.sub_resources = await ApiService.subResources.fetchSubResources({ id_user: data.id })
      }

      if (entities.includes('assignments') || entities.includes('all')) {
        enriched.assignments = await ApiService.assignments.fetchAssignments({ id_user: data.id })
      }

      if (entities.includes('course_teachers') || entities.includes('all')) {
        enriched.course_teachers = await ApiService.courseTeachers.fetchCourseTeachers({ id_user: data.id })
      }

      return enriched
    } catch (error) {
      logger.error('Error enriching user data', error)
      return data
    }
  },

  /**
   * Searches users by name, email, or login
   * @param {string} searchTerm - Term to search for
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing matching users
   */
  async searchUsers(searchTerm, enrichData = false, forceRefresh = false) {
    try {
      if (empty(searchTerm)) {
        return []
      }

      if (api.defaults.baseURL.includes('/api/v2')) {
        return await this.fetchUsers({ search: searchTerm }, enrichData, forceRefresh)
      }

      const users = await this.fetchUsers({}, false, forceRefresh)
      const lowerSearchTerm = searchTerm.toLowerCase()

      const filteredUsers = users.filter(
        (user) =>
          user.last_name.toLowerCase().includes(lowerSearchTerm) ||
          user.first_name.toLowerCase().includes(lowerSearchTerm) ||
          user.email.toLowerCase().includes(lowerSearchTerm) ||
          user.login.toLowerCase().includes(lowerSearchTerm)
      )

      return enrichData ? await this._enrichData(filteredUsers, enrichData) : filteredUsers
    } catch (error) {
      logger.error(`Error searching users with term "${searchTerm}"`, error)
      throw error
    }
  },

  /**
   * Fetches users filtered by role
   * @param {string} role - Role to filter by
   * @param {boolean|Array} enrichData - Whether to enrich all data or array of entities to enrich
   * @param {boolean} forceRefresh - Whether to force a cache refresh
   * @returns {Promise} Promise containing filtered users
   */
  async getUsersByRole(role, enrichData = false, forceRefresh = false) {
    try {
      if (empty(role)) {
        return []
      }

      return await this.fetchUsers({ role }, enrichData, forceRefresh)

    } catch (error) {
      logger.error(`Error fetching users with role ${role}`, error)
      throw error
    }
  },

  /**
   * Gets a user's full name by ID
   * @param {number} id - User ID
   * @returns {Promise<string>} Promise containing user's full name
   */
  async getUserFullNameById(id) {
    try {
      if (empty(id)) {
        return ''
      }

      const user = await this.fetchUser(id)
      return formatUserName(user)
    } catch (error) {
      logger.error(`Error fetching full name for user with ID ${id}`, error)
      return ''
    }
  }
}
