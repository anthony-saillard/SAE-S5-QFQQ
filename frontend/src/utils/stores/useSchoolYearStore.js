import { defineStore } from 'pinia'
import { api } from 'src/boot/axios.js'
import { logger } from 'src/utils/logger.js'

export const useSchoolYearStore = defineStore('schoolYear', {
  state: () => ({
    currentYear: null,
    viewedYearId: null,
    loading: false,
    lastUpdate: Date.now()
  }),

  getters: {
    isSchoolYearSelected: (state) => !!state.currentYear,
    isLoading: (state) => state.loading,

    effectiveYearId: (state) => state.viewedYearId || state.currentYear?.id,

    isViewingDifferentYear: (state) => {
      return !!state.viewedYearId && !!state.currentYear && state.viewedYearId !== state.currentYear.id
    }
  },

  actions: {
    setLoading(value) {
      this.loading = value
    },

    updateTimestamp() {
      this.lastUpdate = Date.now()
    },

    /**
     * Retrieves the default school year from the database
     */
    async fetchCurrentSchoolYear() {
      this.setLoading(true)
      try {
        const response = await api.get('/school-years/current')
        this.currentYear = response.data
        this.updateTimestamp()
        return response.data
      } catch (error) {
        logger.error('Failed to fetch current school year', error)
        this.clearCurrentYear()
        throw error
      } finally {
        this.setLoading(false)
      }
    },

    /**
     * Sets the default school year in BDD
     * Note: This method does NOT change the year displayed
     */
    async setCurrentYear(id) {
      this.setLoading(true)
      try {
        const response = await api.put(`/school-years/${id}/set-current`)
        this.currentYear = response.data

        this.updateTimestamp()
        return response.data
      } catch (error) {
        logger.error('Failed to set current school year', error)
        throw error
      } finally {
        this.setLoading(false)
      }
    },

    /**
     * Defines the year that the admin wishes to view
     * Note: Does NOT change the default year in DB
     */
    setViewedYear(year) {
      try {
        if (!year || !year.id) {
          this.viewedYearId = null

          if (api.defaults.headers.common && 'School-Year' in api.defaults.headers.common) {
            delete api.defaults.headers.common['School-Year']
          }
        } else {
          this.viewedYearId = year.id
          api.defaults.headers.common['School-Year'] = year.id

          if (this.currentYear && year.id === this.currentYear.id) {
            delete api.defaults.headers.common['School-Year']
          }
        }

        this.updateTimestamp()
      } catch (error) {
        logger.error('Error in setViewedYear:', error)
        this.viewedYearId = null

        if (api.defaults.headers.common && 'School-Year' in api.defaults.headers.common) {
          delete api.defaults.headers.common['School-Year']
        }

        this.updateTimestamp()
      }
    },

    /**
     * Forces the administrator to view the default year
     */
    resetViewedYearToDefault() {
      this.viewedYearId = null

      if (api.defaults.headers.common && 'School-Year' in api.defaults.headers.common) {
        delete api.defaults.headers.common['School-Year']
      }

      this.updateTimestamp()
    },

    /**
     * Completely deletes the state (useful for logout)
     */
    clearCurrentYear() {
      this.currentYear = null
      this.viewedYearId = null

      if (api.defaults.headers.common && 'School-Year' in api.defaults.headers.common) {
        delete api.defaults.headers.common['School-Year']
      }

      this.updateTimestamp()
    },

    /**
     * Notification that the school year has changed (creation/modification/deletion)
     */
    notifySchoolYearChange() {
      this.updateTimestamp()
    }
  }
})
