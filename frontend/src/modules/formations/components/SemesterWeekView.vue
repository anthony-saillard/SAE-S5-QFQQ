<template>
  <div :key="'semester-week-' + semesterId" class="semester-week-container">
    <div class="row q-mb-sm q-mx-lg items-center">
      <p class="q-pl-lg col-grow fw-500 fs-200">
        Vue par semaine
      </p>
      <q-select
        v-model="selectedWeek"
        :options="weekOptions"
        label="Sélectionner une semaine"
        option-label="label"
        class="q-mx-md" filled
        style="min-width: 250px"
        @update:model-value="handleWeekChange"
      />
    </div>

    <div v-if="loading" class="flex flex-center q-pa-lg">
      <q-spinner size="48px" color="primary" />
      <span class="q-ml-md">Chargement des assignements...</span>
    </div>

    <div v-else-if="resources.length === 0 || !courseTypes.length" class="flex flex-center q-pa-lg">
      <q-icon name="info" size="24px" color="info" />
      <span class="q-ml-md">Aucune ressource ou type de cours disponible</span>
    </div>

    <div v-else-if="filteredResourcesWithAssignments.length === 0 && !empty(filteredResourcesWithAssignments)" class="flex flex-center q-pa-lg">
      <q-icon name="info" size="24px" color="info" />
      <span class="q-ml-md">Aucune ressource avec des heures pour cette semaine</span>
    </div>

    <div v-else class="table-container">
      <div class="controls-toolbar row q-mb-md q-ml-xl">
        <q-input
          v-model="searchTerm"
          placeholder="Rechercher..."
          dense
          debounce="300"
          class="q-mr-sm search-input"
        >
          <template #append>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>

      <div class="table-wrapper">
        <table class="assignment-week-table">
          <thead>
            <tr>
              <th class="resource-column sticky-header">
                Ressource
              </th>
              <th
                v-for="courseType in courseTypes"
                :key="courseType.id"
                class="course-type-column sticky-header"
              >
                {{ courseType.name }}
              </th>
              <th class="total-column sticky-header">
                Total
              </th>
            </tr>
          </thead>
          <tbody>
            <template v-for="resourceData in filteredResourcesWithAssignments" :key="resourceData.resource.id">
              <!-- Pour les ressources qui n'ont qu'une sous-ressource ou pas de sous-ressource -->
              <tr
                v-if="!hasMultipleSubResources(resourceData)"
                class="resource-row"
                @click="redirect('resource', { id: resourceData.resource.id })"
              >
                <td class="resource-cell">
                  <div class="text-weight-bold">
                    {{ resourceData.resource.name }}
                  </div>
                  <div class="text-caption">
                    {{ resourceData.resource.identifier }}
                  </div>
                </td>
                <td
                  v-for="courseType in courseTypes"
                  :key="`${resourceData.resource.id}-${courseType.id}`"
                  class="hours-cell"
                  :class="{'has-assignment': getAssignmentHours(resourceData, courseType.id) > 0}"
                >
                  <div v-if="getAssignmentHours(resourceData, courseType.id) > 0" class="assignment-content">
                    <div class="fs-100 text-weight-medium">
                      {{ getAssignmentHours(resourceData, courseType.id) }} h
                    </div>
                  </div>
                  <div v-else class="empty-cell">
                    -
                  </div>
                </td>
                <td class="total-cell">
                  {{ formatDuration(getResourceTotalHours(resourceData)) }}
                </td>
              </tr>

              <!-- Pour les ressources qui ont plusieurs sous-ressources -->
              <template v-else>
                <!-- Ligne principale de la ressource -->
                <tr
                  class="resource-parent-row"
                  @click="toggleSubResourcesVisibility(resourceData.resource.id)"
                >
                  <td class="resource-cell">
                    <div class="row items-center">
                      <q-icon
                        :name="isSubResourcesVisible(resourceData.resource.id) ? 'expand_more' : 'chevron_right'"
                        size="sm"
                        class="q-mr-xs"
                      />
                      <div>
                        <div class="text-weight-bold">
                          {{ resourceData.resource.name }}
                        </div>
                        <div class="text-caption">
                          {{ resourceData.resource.identifier }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td
                    v-for="courseType in courseTypes"
                    :key="`${resourceData.resource.id}-${courseType.id}`"
                    class="hours-cell"
                    :class="{'has-assignment': getAssignmentHours(resourceData, courseType.id) > 0}"
                  >
                    <div v-if="getAssignmentHours(resourceData, courseType.id) > 0" class="assignment-content">
                      <div class="fs-100 text-weight-medium">
                        {{ getAssignmentHours(resourceData, courseType.id) }} h
                      </div>
                    </div>
                    <div v-else class="empty-cell">
                      -
                    </div>
                  </td>
                  <td class="total-cell">
                    {{ formatDuration(getResourceTotalHours(resourceData)) }}
                  </td>
                </tr>

                <!-- Lignes des sous-ressources -->
                <template v-if="isSubResourcesVisible(resourceData.resource.id)">
                  <tr
                    v-for="subResource in getSubResources(resourceData)"
                    :key="`sub-${resourceData.resource.id}-${subResource.id}`"
                    class="sub-resource-row"
                    @click="redirect('resource', { id: resourceData.resource.id })"
                  >
                    <td class="sub-resource-cell">
                      <div class="text-weight-medium q-pl-md">
                        {{ subResource.name }}
                      </div>
                    </td>
                    <td
                      v-for="courseType in courseTypes"
                      :key="`sub-${resourceData.resource.id}-${subResource.id}-${courseType.id}`"
                      class="hours-cell"
                      :class="{'has-assignment': getSubResourceAssignmentHours(resourceData, subResource.id, courseType.id) > 0}"
                    >
                      <div v-if="getSubResourceAssignmentHours(resourceData, subResource.id, courseType.id) > 0" class="assignment-content">
                        <div class="fs-100">
                          {{ getSubResourceAssignmentHours(resourceData, subResource.id, courseType.id) }} h
                        </div>
                      </div>
                      <div v-else class="empty-cell">
                        -
                      </div>
                    </td>
                    <td class="total-cell">
                      {{ formatDuration(getSubResourceTotalHours(resourceData, subResource.id)) }}
                    </td>
                  </tr>
                </template>
              </template>
            </template>

            <tr class="total-row">
              <td class="resource-total-cell">
                TOTAL
              </td>
              <td
                v-for="courseType in courseTypes"
                :key="`total-${courseType.id}`"
                class="hours-total-cell"
              >
                {{ getTotalHoursByCourseType(courseType.id) }} h
              </td>
              <td class="grand-total-cell">
                {{ formatDuration(getGrandTotalHours()) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <resource-dialog
      v-if="userStore.isAdmin"
      ref="resourceDialogRef"
      :semester-id="String(semesterId)"
      @save-success="loadData"
    />
  </div>
</template>

<script setup>
  import { ref, onMounted, watch, computed } from 'vue'
  import { ApiService } from 'src/services/apiService.js'
  import { useRoute, useRouter } from 'vue-router'
  import { useRedirect } from 'src/router/useRedirect.js'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import { empty } from 'src/utils/utils.js'
  import ResourceDialog from 'src/modules/resources/components/dialog/ResourceDialog.vue'
  import { date } from 'quasar'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify } from 'src/utils/notify.js'

  const { redirect } = useRedirect()
  const route = useRoute()
  const router = useRouter()
  const userStore = useUserStore()
  const schoolYearStore = useSchoolYearStore()

  const semesterId = computed(() => route.params?.id)
  const resourceDialogRef = ref(null)
  const loading = ref(true)
  const searchTerm = ref('')

  const semester = ref(null)
  const resources = ref([])
  const courseTypes = ref([])
  const weeks = ref([])
  const selectedWeek = ref(null)
  const assignments = ref([])
  const resourcesWithAssignments = ref([])
  const subResourcesMap = ref({})

  const expandedSubResources = ref({})

  function isSubResourcesVisible(resourceId) {
    return expandedSubResources.value[resourceId] === true
  }

  function toggleSubResourcesVisibility(resourceId) {
    expandedSubResources.value[resourceId] = !expandedSubResources.value[resourceId]
  }

  function hasMultipleSubResources(resourceData) {
    if (!resourceData || !resourceData.assignments || !resourceData.resource) {
      return false
    }

    const subResourcesWithHours = new Set()

    resourceData.assignments.forEach(assignment => {
      if (assignment.id_sub_resource) {
        subResourcesWithHours.add(Number(assignment.id_sub_resource))
      }
    })

    return subResourcesWithHours.size > 1
  }

  function getSubResources(resourceData) {
    if (!resourceData || !resourceData.assignments || !resourceData.resource) {
      return []
    }

    const subResources = subResourcesMap.value[resourceData.resource.id] || []

    const subResourcesWithHours = new Set()
    resourceData.assignments.forEach(assignment => {
      if (assignment.id_sub_resource) {
        subResourcesWithHours.add(Number(assignment.id_sub_resource))
      }
    })

    return subResources.filter(subResource =>
      subResourcesWithHours.has(Number(subResource.id))
    )
  }

  function getSubResourceAssignmentHours(resourceData, subResourceId, courseTypeId) {
    if (!resourceData || !resourceData.assignments) {
      return 0
    }

    const filteredAssignments = resourceData.assignments.filter(a =>
      Number(a.id_sub_resource) === Number(subResourceId) &&
      Number(a.id_course_type) === Number(courseTypeId)
    )

    return filteredAssignments.reduce((total, a) => total + Number(a.allocated_hours || 0), 0)
  }

  function getSubResourceTotalHours(resourceData, subResourceId) {
    if (!resourceData || !resourceData.assignments) {
      return 0
    }

    const filteredAssignments = resourceData.assignments.filter(a =>
      Number(a.id_sub_resource) === Number(subResourceId)
    )

    return filteredAssignments.reduce((total, a) => total + Number(a.allocated_hours || 0), 0)
  }

  const filteredResourcesWithAssignments = computed(() => {
    if (!searchTerm.value) {
      return resourcesWithAssignments.value
    }

    const lowerSearchTerm = searchTerm.value.toLowerCase()

    return resourcesWithAssignments.value.filter(item => {
      const resourceMatch = item.resource.name.toLowerCase().includes(lowerSearchTerm) ||
        item.resource.identifier.toLowerCase().includes(lowerSearchTerm)

      const subResourceMatch = item.assignments.some(assignment =>
        assignment.sub_resource &&
        assignment.sub_resource.name &&
        assignment.sub_resource.name.toLowerCase().includes(lowerSearchTerm)
      )

      return resourceMatch || subResourceMatch
    })
  })

  const weekOptions = computed(() => {
    return weeks.value.map(week => {
      const startFormatted = formatDate(week.start_date)
      const endFormatted = formatDate(week.end_date)
      return {
        value: week,
        label: `Semaine ${week.index + 1} (${startFormatted} - ${endFormatted})`
      }
    })
  })

  function formatDate(dateObj) {
    return date.formatDate(dateObj, 'DD/MM/YYYY')
  }

  function formatDuration(hours) {
    if (!hours) {
      return '0h'
    }

    const hoursNum = parseFloat(hours.toFixed(1))

    if (hoursNum === Math.floor(hoursNum)) {
      return `${Math.floor(hoursNum)}h`
    } else {
      return `${hoursNum}h`
    }
  }

  function handleWeekChange() {
    expandedSubResources.value = {}
    loadWeekAssignments()
  }

  function getAssignmentHours(resourceData, courseTypeId) {
    if (!resourceData || !resourceData.assignments) {
      return 0
    }

    return resourceData.assignments
      .filter(a => Number(a.id_course_type) === Number(courseTypeId))
      .reduce((total, a) => total + Number(a.allocated_hours || 0), 0)
  }

  function getResourceTotalHours(resourceData) {
    if (!resourceData || !resourceData.assignments) {
      return 0
    }

    return resourceData.assignments.reduce((total, a) => total + Number(a.allocated_hours || 0), 0)
  }

  function getTotalHoursByCourseType(courseTypeId) {
    if (!resourcesWithAssignments.value) {
      return '0.0'
    }

    let total = 0
    resourcesWithAssignments.value.forEach(resourceData => {
      if (resourceData && resourceData.assignments) {
        total += getAssignmentHours(resourceData, courseTypeId)
      }
    })
    return total.toFixed(1)
  }

  function getGrandTotalHours() {
    if (!resourcesWithAssignments.value) {
      return 0
    }

    return resourcesWithAssignments.value.reduce((total, resourceData) =>
      total + (resourceData ? getResourceTotalHours(resourceData) : 0), 0
    )
  }

  function generateWeeks() {
    if (!semester.value?.start_date || !semester.value?.end_date) {
      weeks.value = []
      return
    }

    const result = []
    const startDate = new Date(semester.value.start_date)
    const endDate = new Date(semester.value.end_date)

    let currentWeekStart = new Date(startDate)

    const dayOfWeek = currentWeekStart.getDay()
    if (dayOfWeek !== 1) {
      const daysToSubtract = dayOfWeek === 0 ? 6 : dayOfWeek - 1
      currentWeekStart.setDate(currentWeekStart.getDate() - daysToSubtract)
    }

    let index = 0
    while (currentWeekStart <= endDate) {
      const weekEnd = new Date(currentWeekStart)
      weekEnd.setDate(weekEnd.getDate() + 6)

      result.push({
        index: index,
        start_date: new Date(currentWeekStart),
        end_date: new Date(weekEnd)
      })

      currentWeekStart.setDate(currentWeekStart.getDate() + 7)
      index++
    }

    weeks.value = result

    if (result.length > 0 && !selectedWeek.value) {
      selectedWeek.value = {
        value: result[0],
        label: `Semaine 1 (${formatDate(result[0].start_date)} - ${formatDate(result[0].end_date)})`
      }
    }
  }

  async function loadSemester() {
    try {
      loading.value = true
      semester.value = await ApiService.semesters.fetchSemester(semesterId.value, false)
      generateWeeks()
    } catch {
      logger.error('Erreur lors du chargement du semestre')
    } finally {
      loading.value = false
    }
  }

  async function loadResources() {
    try {
      loading.value = true
      resources.value = await ApiService.resources.fetchResources({ id_semester: semesterId.value }, true)

      await loadSubResources()
    } catch (error) {
      logger.error('Erreur lors du chargement des ressources', error)
      errorNotify('Impossible de charger les ressources')
    } finally {
      loading.value = false
    }
  }

  async function loadSubResources() {
    try {
      const promises = resources.value.map(async (resource) => {
        try {
          const subResources = await ApiService.subResources.getSubResourcesByResource(resource.id, false, false)
          return { resourceId: resource.id, subResources }
        } catch (error) {
          logger.error(`Erreur lors du chargement des sous-ressources pour la ressource ${resource.id}`, error)
          return { resourceId: resource.id, subResources: [] }
        }
      })

      const results = await Promise.all(promises)

      const map = {}
      results.forEach(result => {
        map[result.resourceId] = result.subResources
      })

      subResourcesMap.value = map
    } catch (error) {
      logger.error('Erreur lors du chargement des sous-ressources', error)
    }
  }

  async function loadCourseTypes() {
    try {
      loading.value = true
      courseTypes.value = await ApiService.courseTypes.fetchCourseTypes()
    } catch (error) {
      logger.error('Erreur lors du chargement des types de cours', error)
      errorNotify('Impossible de charger les types de cours')
    } finally {
      loading.value = false
    }
  }

  async function loadWeekAssignments() {
    if (!selectedWeek.value) {
      return
    }

    try {
      loading.value = true
      assignments.value = []
      resourcesWithAssignments.value = []

      const week = selectedWeek.value.value
      const startDate = date.formatDate(week.start_date, 'YYYY-MM-DD')
      const endDate = date.formatDate(week.end_date, 'YYYY-MM-DD')

      const allWeekAssignments = await ApiService.assignments.fetchAssignments({
        id_semester: semesterId.value,
        date_start: startDate,
        date_end: endDate
      }, ['sub_resource', 'resource'], true)

      const assignmentsByResource = {}

      allWeekAssignments.forEach(assignment => {
        if (assignment.resource) {
          const resourceId = assignment.resource.id

          if (!assignmentsByResource[resourceId]) {
            assignmentsByResource[resourceId] = {
              resource: assignment.resource,
              assignments: []
            }
          }

          assignmentsByResource[resourceId].assignments.push(assignment)
        }
      })

      resourcesWithAssignments.value = Object.values(assignmentsByResource)
        .sort((a, b) => a.resource.name.localeCompare(b.resource.name))

    } catch (error) {
      logger.error('Erreur lors du chargement des assignements', error)
      errorNotify('Impossible de charger les assignements')
    } finally {
      loading.value = false
    }
  }

  async function loadData() {
    loading.value = true
    try {
      await loadSemester()
      await loadCourseTypes()
      await loadResources()
      if (selectedWeek.value) {
        await loadWeekAssignments()
      }
    } catch (error) {
      logger.error('Erreur lors du chargement des données', error)
      errorNotify('Une erreur est survenue lors du chargement des données')
    } finally {
      loading.value = false
    }
  }

  watch(selectedWeek, async () => {
    await loadWeekAssignments()
  }, { immediate: true })

  watch(semesterId, (newId, oldId) => {
    if (newId && newId !== oldId) {
      loadData()
    }
  }, { immediate: false })

  watch(() => schoolYearStore.lastUpdate, async () => {
    await redirect('home')
  })

  const handleRouteChange = () => {
    const currentId = route.params?.id
    if (currentId) {
      loadData()
    }
  }

  onMounted(() => {
    if (empty(semesterId.value)) {
      redirect('error', { errorType: 404 })
      return
    }

    router.afterEach(handleRouteChange)
    loadData()
  })
</script>

<style scoped lang="scss">
  .semester-week-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 120px);
  }

  .controls-header {
    background-color: white;
    z-index: 10;
    padding-top: 8px;
    padding-bottom: 8px;
  }

  .controls-toolbar {
    z-index: 9;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
  }

  .sticky-top {
    position: sticky;
    top: 0;
  }

  .sticky-header {
    position: sticky;
    top: 0;
    z-index: 8;
  }

  .table-container {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
  }

  .table-wrapper {
    flex-grow: 1;
    overflow-y: auto;
  }

  .assignment-week-table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;

    th, td {
      padding: 8px;
      text-align: center;
      height: 60px;
    }

    th {
      background-color: var(--q-secondary);
      color: white;
      font-weight: bold;
      white-space: nowrap;
    }

    .resource-column {
      min-width: 180px;
      text-align: left !important;
    }

    .course-type-column {
      min-width: 100px;
    }

    .total-column {
      min-width: 100px;
      border-left: 2px solid #999;
    }

    .resource-cell {
      text-align: left !important;
    }

    .sub-resource-cell {
      text-align: left !important;
      background-color: #f9f9f9;
    }

    .hours-cell {
      vertical-align: middle;
      transition: background-color 0.2s;
      position: relative;

      &.has-assignment {
        background-color: #f5f5f5;
      }
    }

    .total-cell {
      background-color: #f5f5f5;
      font-weight: bold;
      border-left: 2px solid #999;
    }

    .resource-row {
      cursor: pointer;
      &:hover {
        background-color: rgba(25, 118, 210, 0.05);
      }
    }

    .resource-parent-row {
      cursor: pointer;
      background-color: #f0f0f0;
      &:hover {
        background-color: #e5e5e5;
      }
    }

    .sub-resource-row {
      cursor: pointer;
      &:hover {
        background-color: rgba(25, 118, 210, 0.05);
      }
    }

    .total-row {
      border-top: 2px solid #999;
      position: sticky;
      bottom: 0;
      background-color: white;
    }

    .resource-total-cell {
      font-weight: bold;
      text-align: left !important;
    }

    .hours-total-cell {
      background-color: #f5f5f5;
      font-weight: bold;
    }

    .grand-total-cell {
      background-color: #e0e0e0;
      font-weight: bold;
      border-left: 2px solid #999;
    }

    .assignment-content {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
    }

    .empty-cell {
      color: #999;
    }
  }

  .search-input {
    width: 300px;
  }
</style>
