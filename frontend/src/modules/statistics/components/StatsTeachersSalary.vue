<template>
  <div>
    <div class="row q-col-gutter-sm align-items-center q-mt-xl">
      <div class="col-grow">
        <section-component class="q-pa-sm">
          <div v-if="loading" class="text-center q-py-md">
            <q-spinner color="primary" size="3em" />
            <div class="q-mt-sm">
              Chargement des données...
            </div>
          </div>

          <div v-else-if="!resourceHours || !resourceHours.length" class="text-center q-py-md">
            <q-icon name="info" size="3em" color="grey-7" />
            <div class="q-mt-sm">
              Aucune donnée disponible pour cet utilisateur
            </div>
          </div>

          <div v-else>
            <div class="rounded-borders q-pa-sm">
              <div class="text-subtitle1 q-mb-sm font-weight-bold">
                Cumul des heures de l'année actuel par ressources
              </div>
              <q-table
                :rows="resourceHours"
                :columns="columns"
                row-key="id"
                flat
                bordered
                hide-pagination
                :pagination="{ rowsPerPage: 0 }"
                class="resources-table"
              >
                <!-- Template pour la colonne du nom de ressource -->
                <template #body-cell-resourceName="propResource">
                  <q-td :props="propResource">
                    <div class="resource-name-container">
                      <div v-if="propResource.row.formationName" class="formation-name text-weight-bold">
                        {{ propResource.row.formationName }}
                      </div>
                      <div class="parent-resource-name">
                        {{ propResource.row.resourceName }}
                      </div>
                    </div>
                  </q-td>
                </template>

                <!-- Template pour la colonne du calcul total -->
                <template #body-cell-totalHours="propTotal">
                  <q-td :props="propTotal" class="text-center">
                    <div class="total-hours text-primary text-bold">
                      {{ formatHours(propTotal.row.totalHours) }}h
                    </div>
                  </q-td>
                </template>
              </q-table>
            </div>
          </div>
        </section-component>
      </div>
    </div>
  </div>
</template>

<script setup>
  import { ref, onMounted, watch } from 'vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import { ApiService } from 'src/services/apiService.js'
  import { errorNotify } from 'src/utils/notify.js'
  import { logger } from 'src/utils/logger.js'

  const props = defineProps({
    userId: {
      type: [Number, String],
      required: false
    },
    formationId: {
      type: [Number, String],
      required: true
    }
  })

  const loading = ref(false)
  const assignments = ref([])
  const courseTypes = ref([])
  const semesters = ref([])
  const resources = ref([])
  const resourceHours = ref([])
  const formations = ref([])
  const semesterFormationMap = ref({})

  const columns = [
    {
      name: 'resourceName',
      label: 'Nom de la ressource',
      field: 'resourceName',
      align: 'left',
      classes: 'col-7'
    },
    {
      name: 'totalHours',
      label: 'Total (Taux × Groupes × Heures)',
      field: row => ({
        hourlyRate: row.hourlyRate,
        groupCount: row.groupCount,
        hours: row.allocatedHours
      }),
      align: 'center',
      classes: 'col-3'
    }
  ]

  watch(() => props.userId, fetchData)
  watch(() => props.formationId, fetchData)

  onMounted(async () => {
    await fetchCourseTypes()
    await fetchFormations()
    await fetchData()
  })

  async function fetchCourseTypes() {
    try {
      courseTypes.value = await ApiService.courseTypes.fetchCourseTypes()
    } catch {
      errorNotify('Erreur lors du chargement des types de cours')
      courseTypes.value = []
    }
  }

  async function fetchFormations() {
    try {
      formations.value = await ApiService.formations.fetchFormations()
      semesterFormationMap.value = {}
      formations.value.forEach(formation => {
        if (formation.semesters && Array.isArray(formation.semesters)) {
          formation.semesters.forEach(semesterId => {
            semesterFormationMap.value[semesterId] = formation.label
          })
        }
      })
    } catch {
      errorNotify('Erreur lors du chargement des formations')
      formations.value = []
    }
  }

  function getFormationLabel(semesterId) {
    if (!semesterId) {
      return ''
    }
    return semesterFormationMap.value[semesterId] || ''
  }

  async function fetchData() {
    if (!props.userId || !props.formationId) {
      return
    }

    loading.value = true
    resourceHours.value = []

    try {
      semesters.value = await ApiService.semesters.fetchSemesters({ id_formation: props.formationId })

      const resourcePromises = []
      for (const semester of semesters.value) {
        const semesterResources = ApiService.resources.fetchResources({ id_semester: semester.id })
        resourcePromises.push(semesterResources)
      }

      const resourcesResults = await Promise.all(resourcePromises)
      resources.value = resourcesResults.flat()

      const filters = {
        id_user: props.userId
      }

      assignments.value = await ApiService.assignments.fetchAssignments(filters, ['sub_resource', 'resource', 'group'], true)
      assignments.value.some(a => a.semester_id !== undefined)
      await processResourceHours()
    } catch (error) {
      logger.error('Error fetching data:', error)
      errorNotify('Erreur lors du chargement des données')
      assignments.value = []
      semesters.value = []
      resources.value = []
    } finally {
      loading.value = false
    }
  }

  async function processResourceHours() {
    const resourceMap = new Map()

    for (const assignment of assignments.value) {
      const resourceId = assignment.resource?.id || 'unknown'
      const resourceName = assignment.resource_name || 'Ressource inconnue'
      const allocatedHours = Number(assignment.allocated_hours) || 0
      const courseTypeId = assignment.id_course_type
      const courseTypeName = assignment.course_type_name
      const semesterId = assignment.semester_id

      const formationName = getFormationLabel(semesterId)

      const courseType = courseTypes.value.find(ct => ct.id === courseTypeId)
      const hourlyRate = courseType ? Number(courseType.hourly_rate) : 1

      if (!Array.isArray(assignment.group)) {
        continue
      }

      let groupsForThisCourseType = assignment.group.filter(g =>
        g.id_course_types && String(g.id_course_types) === String(courseTypeId)
      )

      let relevantGroups = []
      if (groupsForThisCourseType.length === 0) {
        const parentGroupsWithThisType = assignment.group
          .filter(g => g.id_course_types && String(g.id_course_types) === String(courseTypeId))
          .map(g => g.id)

        if (parentGroupsWithThisType.length > 0) {
          relevantGroups = assignment.group.filter(g =>
            g.id_parent_group && parentGroupsWithThisType.includes(g.id_parent_group)
          )
        }

        if (relevantGroups.length === 0) {
          const allGroups = assignment.group
          const groupHierarchy = new Map()

          allGroups.forEach(g => {
            if (g.id_parent_group) {
              if (!groupHierarchy.has(g.id_parent_group)) {
                groupHierarchy.set(g.id_parent_group, [])
              }
              groupHierarchy.get(g.id_parent_group).push(g.id)
            }
          })

          const findGroupsInHierarchy = (startGroupId, hierarchy, visitedIds = new Set()) => {
            if (visitedIds.has(startGroupId)) {
              return []
            }
            visitedIds.add(startGroupId)

            const result = [startGroupId]
            const children = hierarchy.get(startGroupId) || []

            for (const childId of children) {
              result.push(...findGroupsInHierarchy(childId, hierarchy, visitedIds))
            }

            return result
          }

          const groupsLinkedToOurType = []
          for (const group of allGroups) {
            if (group.id_course_types && String(group.id_course_types) === String(courseTypeId)) {
              const relatedGroupIds = findGroupsInHierarchy(group.id, groupHierarchy)
              for (const relatedId of relatedGroupIds) {
                const relatedGroup = allGroups.find(g => g.id === relatedId)
                if (relatedGroup) {
                  groupsLinkedToOurType.push(relatedGroup)
                }
              }
            }
          }

          if (groupsLinkedToOurType.length > 0) {
            relevantGroups = groupsLinkedToOurType
          }
        }
      } else {
        relevantGroups = groupsForThisCourseType
      }

      let groupCount = relevantGroups.length || 1

      if (groupCount === 0) {
        groupCount = 1
      }

      const mapKey = `${resourceId}_${courseTypeId}`

      if (!resourceMap.has(mapKey)) {
        resourceMap.set(mapKey, {
          id: resourceId,
          resourceName,
          formationName,
          semesterId,
          allocatedHours: 0,
          hourlyRate,
          groupCount,
          totalHours: 0,
          courseTypeId,
          courseTypeName,
          coursesDetail: []
        })
      }

      const resource = resourceMap.get(mapKey)

      if (groupCount > resource.groupCount) {
        resource.groupCount = groupCount
      }

      resource.allocatedHours += allocatedHours

      const courseDetail = resource.coursesDetail.find(detail =>
        detail.courseTypeId === courseTypeId && detail.hourlyRate === hourlyRate
      )

      if (!courseDetail) {
        resource.coursesDetail.push({
          name: courseTypeName,
          courseTypeId,
          hourlyRate,
          groupCount,
          hours: allocatedHours,
          totalHours: hourlyRate * groupCount * allocatedHours
        })
      } else {
        courseDetail.hours += allocatedHours
        courseDetail.totalHours = courseDetail.hourlyRate * courseDetail.groupCount * courseDetail.hours
      }

      resource.totalHours = resource.coursesDetail.reduce(
        (total, detail) => total + detail.totalHours, 0
      )
    }

    const combinedResourceMap = new Map()

    for (const resource of resourceMap.values()) {
      const resourceId = resource.id

      if (!combinedResourceMap.has(resourceId)) {
        combinedResourceMap.set(resourceId, {
          id: resourceId,
          resourceName: resource.resourceName,
          formationName: resource.formationName || '',
          semesterId: resource.semesterId,
          allocatedHours: 0,
          totalHours: 0,
          coursesDetail: []
        })
      }

      const combinedResource = combinedResourceMap.get(resourceId)
      combinedResource.allocatedHours += resource.allocatedHours
      combinedResource.totalHours += resource.totalHours

      for (const detail of resource.coursesDetail) {
        const existingDetail = combinedResource.coursesDetail.find(d =>
          d.courseTypeId === detail.courseTypeId && d.hourlyRate === detail.hourlyRate
        )

        if (!existingDetail) {
          combinedResource.coursesDetail.push(detail)
        } else {
          existingDetail.hours += detail.hours
          existingDetail.totalHours += detail.totalHours
          existingDetail.groupCount = Math.max(existingDetail.groupCount, detail.groupCount)
        }
      }
    }

    resourceHours.value = Array.from(combinedResourceMap.values())
      .sort((a, b) => a.resourceName.localeCompare(b.resourceName))
  }

  function formatHours(hours) {
    return Number(hours).toFixed(1).replace('.0', '')
  }
</script>

<style scoped lang="scss">
.resources-table {
  max-height: 500px;
  overflow: auto;
}

.resource-name-container {
  padding: 4px 0;
}

.formation-name {
  font-size: 1em;
  margin-bottom: 6px;
  color: #1976D2 !important;
}

.parent-resource-name {
  font-size: 1em;
}

.total-hours {
  font-size: 1.1rem;
}

.calculation-details {
  color: #666;
}

.course-type-detail {
  margin-top: 2px;
}

.font-weight-medium {
  font-weight: 500;
}
</style>
