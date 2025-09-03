<template>
  <div>
    <div class="row q-col-gutter-sm">
      <!-- Colonne 1: Tableau des ressources (à gauche) -->
      <div class="col-grow">
        <section-component class="q-pa-sm">
          <div v-if="loading" class="text-center q-py-md">
            <q-spinner color="primary" size="3em" />
            <div class="q-mt-sm">
              Chargement des données...
            </div>
          </div>

          <div
            v-else-if="
              !formationData ||
                !formationData.sub_resources ||
                formationData.sub_resources.length === 0
            "
            class="text-center q-py-md"
          >
            <q-icon name="info" size="3em" color="grey-7" />
            <div class="q-mt-sm">
              Aucune donnée disponible pour cette formation
            </div>
          </div>

          <div v-else>
            <div class="rounded-borders q-pa-sm">
              <div class="text-subtitle1 q-mb-sm font-weight-bold">
                Tableau des ressources
              </div>
              <q-table
                :rows="mergedResources"
                :columns="columns"
                row-key="id"
                flat
                bordered
                hide-pagination
                :pagination="{ rowsPerPage: 0 }"
                class="resources-table"
              >
                <template #body-cell-name="cellProps">
                  <q-td :props="cellProps">
                    <div class="resource-name-container">
                      <div class="row items-center">
                        <div class="parent-resource-name">
                          {{ cellProps.row.parentResourceName }}
                        </div>
                      </div>
                    </div>
                  </q-td>
                </template>

                <template #body-cell-hours="slotProps">
                  <q-td :props="slotProps" class="text-center">
                    <div class="row items-center justify-between q-gutter-x-md">
                      <div class="column items-center q-mr-md">
                        <div class="text-caption q-mb-xs font-weight-bold">
                          Total
                        </div>
                        <div class="progress-container">
                          <div class="progress-with-warning">
                            <q-circular-progress
                              :value="
                                calculatePercentage(
                                  slotProps.row.completedHours,
                                  slotProps.row.totalHours,
                                )
                              "
                              size="50px"
                              :color="
                                getProgressColor(
                                  slotProps.row.completedHours,
                                  slotProps.row.totalHours,
                                )
                              "
                              track-color="grey-3"
                              class="q-mb-xs"
                            >
                              <div class="text-subtitle2">
                                {{ slotProps.row.completedHours }}
                              </div>
                            </q-circular-progress>
                          </div>

                          <div
                            class="hours-indicator"
                            :class="{
                              'exceeded-hours': isExceeded(
                                slotProps.row.completedHours,
                                slotProps.row.totalHours,
                              ),
                            }"
                          >
                            <q-icon
                              v-if="
                                isExceeded(slotProps.row.completedHours, slotProps.row.totalHours)
                              "
                              name="warning"
                              color="negative"
                              size="xs"
                              class="q-mr-xs"
                            />
                            {{ slotProps.row.completedHours }}h/{{ slotProps.row.totalHours }}h
                            <q-tooltip
                              v-if="
                                isExceeded(slotProps.row.completedHours, slotProps.row.totalHours)
                              "
                            >
                              Dépassement
                            </q-tooltip>
                          </div>
                        </div>
                      </div>

                      <div class="course-types-container q-mr-md">
                        <div class="course-types-title text-caption q-mb-xs">
                          Heures par type
                        </div>
                        <div class="course-types-grid">
                          <template
                            v-for="courseType in slotProps.row.courseTypes"
                            :key="courseType.id"
                          >
                            <div v-if="courseType.totalHours > 0" class="course-type-item">
                              <span
                                class="course-type-name"
                                :class="getCourseTypeColorClass(courseType.name)"
                              >
                                {{ courseType.name }}:
                              </span>
                              <span class="course-type-hours"> {{ courseType.totalHours }}h </span>
                            </div>
                          </template>
                        </div>
                      </div>
                    </div>
                  </q-td>
                </template>
              </q-table>
            </div>
          </div>
        </section-component>
      </div>

      <!-- Colonne 2: Statistiques (à droite) -->
      <div v-if="showSummary && mergedResources.length > 0 && !loading" class="col-3 q-ml-lg">
        <stats-for-formations :resource-hours="mergedResources" />
      </div>
    </div>

    <dialog-component
      v-model="dialogShow"
      :title="dialogTitle"
      :form-ref="formRef"
      :loading="dialogLoading"
      size="md"
    />
  </div>
</template>

<script setup>
  import { computed, onMounted, ref, watch } from 'vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import StatsForFormations from 'src/modules/statistics/components/StatsForFormations.vue'
  import { getFormationHours } from 'src/modules/statistics/api.js'
  import { errorNotify } from 'src/utils/notify.js'
  import { ApiService } from 'src/services/apiService.js'

  const props = defineProps({
    formationId: {
      type: [Number, String],
      default: null
    },
    semesterId: {
      type: [Number, String],
      default: null
    },
    showSummary: {
      type: Boolean,
      default: true
    }
  })

  const formationData = ref(null)
  const assignments = ref([])
  const loading = ref(false)
  const resourceParentNames = ref({})

  const dialogShow = ref(false)
  const dialogTitle = ref('')
  const dialogLoading = ref(false)
  const formRef = ref(null)

  const columns = [
    {
      name: 'name',
      label: 'Nom de la ressource',
      field: 'parentResourceName',
      align: 'left'
    },
    {
      name: 'hours',
      label: 'Heures',
      field: 'completedHours',
      align: 'center'
    }
  ]

  const filteredResourceHours = computed(() => {
    if (!resourceHours.value) {
      return []
    }

    let filtered = resourceHours.value

    if (props.semesterId) {
      const targetSemesterId = String(props.semesterId)
      filtered = filtered.filter((resource) => {
        const resourceAssignments = assignments.value.filter(
          (a) => a.id_sub_resources === resource.id && String(a.semester_id) === targetSemesterId
        )
        return String(resource.semesterId) === targetSemesterId || resourceAssignments.length > 0
      })
    }

    return filtered
  })

  const resourceHours = computed(() => {
    if (!formationData.value || !formationData.value.sub_resources) {
      return []
    }

    return formationData.value.sub_resources.map((resource) => {
      const resourceAssignments = assignments.value.filter((a) => a.id_sub_resources === resource.id)

      const totalCompletedHours = resourceAssignments.reduce(
        (sum, a) => sum + (a.allocated_hours || 0),
        0
      )

      let parentResourceName = null
      let officialTotalHours = 0

      if (resourceAssignments.length > 0) {
        const firstAssignment = resourceAssignments.find((a) => a.resource_name)
        if (firstAssignment) {
          parentResourceName = firstAssignment.resource_name
          officialTotalHours = firstAssignment.total_hours || 0
        }
      }

      if (!parentResourceName && resourceParentNames.value[resource.id]) {
        parentResourceName = resourceParentNames.value[resource.id]
      }

      const courseTypes = resource.course_types_hours.map((courseType) => {
        return {
          id: courseType.course_type_id,
          name: courseType.course_type_name,
          totalHours: courseType.total_hours
        }
      })

      return {
        id: resource.id,
        name: resource.name,
        parentResourceName: parentResourceName || resource.name,
        totalHours: officialTotalHours,
        completedHours: totalCompletedHours,
        courseTypes: courseTypes,
        semesterId: resource.semester_id
      }
    })
  })

  const mergedResources = computed(() => {
    if (!filteredResourceHours.value || filteredResourceHours.value.length === 0) {
      return []
    }

    const resourceMap = new Map()

    filteredResourceHours.value.forEach((resource) => {
      if (!resource.parentResourceName) {
        return
      }

      const key = resource.parentResourceName

      if (!resourceMap.has(key)) {
        resourceMap.set(key, {
          id: key,
          parentResourceName: key,
          totalHours: 0,
          completedHours: 0,
          courseTypes: {},
          subResources: []
        })
      }

      const merged = resourceMap.get(key)

      if (resource.totalHours > 0) {
        if (merged.totalHours === 0) {
          merged.totalHours = resource.totalHours
        }
      }

      merged.completedHours += resource.completedHours
      merged.subResources.push(resource)

      resource.courseTypes.forEach((courseType) => {
        if (!merged.courseTypes[courseType.id]) {
          merged.courseTypes[courseType.id] = {
            id: courseType.id,
            name: courseType.name,
            totalHours: 0
          }
        }
        merged.courseTypes[courseType.id].totalHours += courseType.totalHours
      })
    })

    return Array.from(resourceMap.values()).map((resource) => {
      if (resource.totalHours === 0) {
        resource.totalHours = resource.completedHours
      }

      return {
        ...resource,
        courseTypes: Object.values(resource.courseTypes)
      }
    })
  })

  watch(() => props.formationId, fetchData)
  watch(() => props.semesterId, fetchData)

  onMounted(() => {
    if (props.formationId) {
      fetchData()
    }
  })

  async function fetchData() {
    if (!props.formationId) {
      return
    }

    loading.value = true

    try {
      const formationResponse = await getFormationHours(props.formationId)
      formationData.value = formationResponse.data

      const filters = {
        formationId: props.formationId,
        formation_id: props.formationId
      }

      if (props.semesterId) {
        filters.semesterId = props.semesterId
      }

      const assignmentsResponse = await ApiService.assignments.fetchAssignments(filters, false, true)
      assignments.value = assignmentsResponse

      resourceParentNames.value = {}
      assignmentsResponse.forEach((assignment) => {
        if (assignment.id_sub_resources && assignment.resource_name) {
          resourceParentNames.value[assignment.id_sub_resources] = assignment.resource_name
        }
      })
    } catch {
      errorNotify('Erreur lors du chargement des données')
      formationData.value = null
      assignments.value = []
      resourceParentNames.value = {}
    } finally {
      loading.value = false
    }
  }

  function calculatePercentage(completed, total) {
    if (!total) {
      return 0
    }
    return Math.min((completed / total) * 100, 100)
  }

  function isExceeded(completed, total) {
    return completed > total && total > 0
  }

  function getProgressColor(completed, total) {
    if (!total) {
      return 'grey'
    }

    if (completed > total) {
      return 'negative'
    }

    const percentage = (completed / total) * 100

    if (percentage === 100) {
      return 'positive'
    }
    if (percentage >= 90) {
      return 'light-green'
    }
    if (percentage >= 75) {
      return 'lime'
    }
    if (percentage >= 50) {
      return 'amber'
    }
    if (percentage >= 25) {
      return 'orange'
    }
    return 'red'
  }

  function getCourseTypeColorClass(typeName) {
    const colorMap = {
      CM: 'text-teal',
      TD: 'text-blue',
      TP: 'text-deep-orange'
    }

    return colorMap[typeName] || 'text-primary'
  }
</script>

<style scoped lang="scss">
.progress-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.hours-indicator {
  font-size: 0.8rem;
  margin-top: 0.1rem;
  color: #555;
  font-weight: 500;
  display: flex;
  align-items: center;
}

.exceeded-hours {
  color: #f44336;
  font-weight: 600;
}

.course-types-container {
  border-left: 1px solid #eee;
  padding-left: 0.5rem;
  min-width: 180px;
}

.course-types-title {
  font-weight: bold;
  text-align: left;
  color: #555;
}

.course-types-grid {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.course-type-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
}

.course-type-name {
  font-weight: 500;
  margin-right: 0.5rem;
}

.course-type-hours {
  min-width: 35px;
  text-align: right;
}

.text-teal {
  color: #009688;
}

.text-blue {
  color: #2196f3;
}

.text-deep-orange {
  color: #ff5722;
}

.resources-table {
  max-height: 500px;
  overflow: auto;
}

.resource-name-container {
  display: flex;
  flex-direction: column;
}

.parent-resource-name {
  font-size: 1em;
}

.progress-with-warning {
  display: flex;
  align-items: center;
  position: relative;
}
</style>
