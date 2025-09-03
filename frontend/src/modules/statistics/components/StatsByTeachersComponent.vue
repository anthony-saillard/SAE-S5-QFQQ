<template>
  <div>
    <div class="row q-col-gutter-sm align-items-center">
      <!-- Colonne principale: Tableau des ressources par enseignant -->
      <div class="col-grow">
        <section-component class="q-pa-sm">
          <div v-if="loading" class="text-center q-py-md">
            <q-spinner color="primary" size="3em" />
            <div class="q-mt-sm">
              Chargement des données...
            </div>
          </div>

          <div v-else-if="!teacherData || !teacherData.length" class="text-center q-py-md">
            <q-icon name="info" size="3em" color="grey-7" />
            <div class="q-mt-sm">
              Aucune donnée disponible pour cet enseignant
            </div>
          </div>

          <div v-else class="table-container">
            <div class="rounded-borders q-pa-sm">
              <div class="text-subtitle1 q-mb-sm font-weight-bold">
                Tableau des ressources attribuées
              </div>
              <q-table
                :rows="teacherData"
                :columns="columns"
                row-key="id"
                flat
                bordered
                hide-pagination
                :pagination="{ rowsPerPage: 0 }"
                class="resources-table"
              >
                <template #body-cell-resourceName="cellProps">
                  <q-td :props="cellProps">
                    <div class="resource-name-container">
                      <div class="parent-resource-name">
                        {{ cellProps.row.resourceName }}
                      </div>
                    </div>
                  </q-td>
                </template>

                <template #body-cell-hours="slotProps">
                  <q-td :props="slotProps" class="text-center">
                    <div class="hours-container">
                      <!-- Heures totales (en orange), à gauche -->
                      <div class="hours-allocated text-primary text-bold q-mr-md">
                        {{ formatHours(slotProps.row.allocatedHours) }}h
                      </div>

                      <!-- Types de cours (CM, DS, TD) à droite -->
                      <div class="course-types-container q-ml-sm">
                        <div class="course-types-grid">
                          <template v-for="courseType in slotProps.row.courseTypes" :key="courseType.id">
                            <div v-if="courseType.hours > 0" class="course-type-item">
                              <span class="course-type-name" :class="getCourseTypeColorClass(courseType.name)">
                                {{ courseType.name }}:
                              </span>
                              <span class="course-type-hours">
                                {{ formatHours(courseType.hours) }}h
                              </span>
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

      <!-- Colonne de droite: Statistiques -->
      <div v-if="!loading && teacherData.length > 0" class="col-md-3 q-ml-lg self-center">
        <stats-summary-component :teacher-hours="teacherData" />
      </div>
    </div>
  </div>
</template>

<script setup>
  import { ref, onMounted, watch } from 'vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import { getAssignments } from 'src/modules/statistics/api.js'
  import { errorNotify } from 'src/utils/notify.js'
  import StatsSummaryComponent from 'src/modules/statistics/components/StatsSummaryComponent.vue'
  import { ApiService } from 'src/services/apiService.js'

  const props = defineProps({
    formationId: {
      type: [Number, String],
      default: null
    },
    userId: {
      type: [Number, String],
      default: null
    },
    semesterId: {
      type: [Number, String],
      default: null
    }
  })

  const loading = ref(false)
  const assignments = ref([])
  const courseTypes = ref([])
  const teacherData = ref([])

  const columns = [
    {
      name: 'resourceName',
      label: 'Nom de la ressource',
      field: 'resourceName',
      align: 'left',
      classes: 'col-7'
    },
    {
      name: 'hours',
      label: 'Heures',
      field: 'allocatedHours',
      align: 'center',
      classes: 'col-5'
    }
  ]

  watch(() => props.userId, fetchData)
  watch(() => props.formationId, fetchData)
  watch(() => props.semesterId, fetchData)

  onMounted(async () => {
    await fetchCourseTypes()
    await fetchData()
  })

  async function fetchData() {
    loading.value = true
    teacherData.value = []

    try {
      const filters = {}

      if (props.userId) {
        filters.userId = props.userId
      }

      if (props.formationId) {
        filters.formationId = props.formationId
      }

      if (props.semesterId) {
        filters.semesterId = props.semesterId
      }

      const response = await getAssignments(filters)
      assignments.value = response.data

      processAssignments()
    } catch {
      errorNotify('Erreur lors du chargement des données des enseignants')
      assignments.value = []
    } finally {
      loading.value = false
    }
  }

  function processAssignments() {
    const resourceMap = new Map()

    assignments.value.forEach(assignment => {
      if (!assignment.resource_name) {
        return
      }

      const resourceKey = `${assignment.id_resources || 'unknown'}-${assignment.resource_name}`

      if (!resourceMap.has(resourceKey)) {
        resourceMap.set(resourceKey, {
          id: resourceKey,
          resourceId: assignment.id_resources,
          resourceName: assignment.resource_name,
          allocatedHours: 0,
          courseTypes: []
        })
      }

      const resource = resourceMap.get(resourceKey)
      const allocatedHours = assignment.allocated_hours || 0
      resource.allocatedHours += allocatedHours

      const courseTypeId = assignment.id_course_type
      const courseTypeName = assignment.course_type_name || 'Unknown'

      let courseType = resource.courseTypes.find(ct => ct.id === courseTypeId)
      if (!courseType) {
        courseType = {
          id: courseTypeId,
          name: courseTypeName,
          hours: 0
        }
        resource.courseTypes.push(courseType)
      }

      courseType.hours += allocatedHours
    })

    teacherData.value = Array.from(resourceMap.values())
  }

  function formatHours(hours) {
    return hours.toFixed(1).replace('.0', '')
  }

  function getCourseTypeColorClass(typeName) {
    const colors = [
      'text-teal',
      'text-blue',
      'text-deep-orange',
      'text-purple',
      'text-indigo',
      'text-green',
      'text-amber',
      'text-red'
    ]

    const courseType = courseTypes.value.find(ct => ct.name === typeName)
    if (!courseType) {
      return 'text-primary'
    }

    const colorIndex = (courseType.id - 1) % colors.length
    return colors[colorIndex]
  }

  async function fetchCourseTypes() {
    try {
      courseTypes.value = await ApiService.courseTypes.fetchCourseTypes()
    } catch {
      errorNotify('Erreur lors du chargement des types de cours')
      courseTypes.value = []
    }
  }
</script>

<style scoped lang="scss">
.table-container {
  position: relative;
  overflow: hidden;
}

.resources-table {
  max-height: 400px;
  overflow-y: auto;
}

.hours-container {
  display: flex;
  align-items: center;
  justify-content: center;
}

.hours-allocated {
  font-size: 1.1rem;
  min-width: 50px;
}

.course-types-container {
  border-left: 1px solid #eee;
  padding-left: 0.5rem;
  max-width: max-content;
}

.course-types-grid {
  display: flex;
  flex-direction: column;
  width: max-content;
  gap: 4px;
}

.course-type-item {
  display: flex;
  justify-content: flex-start;
  font-size: 0.85rem;
  width: max-content;
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
  color: #2196F3;
}

.text-deep-orange {
  color: #FF5722;
}

.text-purple {
  color: #9C27B0;
}

.text-indigo {
  color: #3F51B5;
}

.text-green {
  color: #4CAF50;
}

.text-amber {
  color: #FFC107;
}

.text-red {
  color: #F44336;
}

.resource-name-container {
  padding: 4px 0;
}

.parent-resource-name {
  font-size: 1em;
}

.text-bold {
  font-weight: 600;
}

.custom-header-center {
  text-align: center !important;
}

.custom-header-center .q-table__title {
  width: 100%;
  text-align: center !important;
  display: block;
}
</style>
