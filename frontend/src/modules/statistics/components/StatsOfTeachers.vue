<template>
  <section-component class="q-pa-md q-mt-xl">
    <div class="col-auto column justify-between">
      <div v-if="loading" class="text-center q-py-md">
        <q-spinner color="primary" size="3em" />
        <div class="q-mt-sm">
          Chargement des données...
        </div>
      </div>

      <div v-else-if="!teacherData || teacherData.length === 0" class="text-center q-py-md">
        <q-icon name="info" size="3em" color="grey-7" />
        <div class="q-mt-sm">
          Aucune donnée disponible pour les enseignants avec ces filtres
        </div>
      </div>

      <div v-else class="teachers-container">
        <!-- Filtre de recherche -->
        <div class="search-container q-mb-md">
          <q-input
            v-model="searchQuery"
            dense
            outlined
            placeholder="Rechercher un enseignant..."
            class="search-input"
            clearable
          >
            <template #prepend>
              <q-icon name="search" />
            </template>
            <template v-if="searchQuery" #append>
              <q-icon name="close" class="cursor-pointer" @click="searchQuery = ''" />
            </template>
          </q-input>
        </div>
        <q-list bordered separator class="rounded-borders">
          <template v-for="teacher in filteredTeacherData" :key="teacher.id">
            <q-expansion-item
              :label="teacher.name"
              :caption="`${teacher.totalHours} heures`"
              expand-separator
              header-class="teacher-header"
              icon="person"
            >
              <q-card>
                <q-card-section>
                  <div class="text-subtitle1 q-mb-sm">
                    Ressources affectées
                  </div>

                  <q-list separator>
                    <template v-for="resource in teacher.mergedResources" :key="resource.name">
                      <q-item class="resource-item">
                        <q-item-section>
                          <q-item-label class="resource-parent-name">
                            {{ resource.name }}
                          </q-item-label>
                        </q-item-section>

                        <q-item-section side>
                          <q-badge color="primary" class="resource-hours">
                            {{ resource.totalHours }} heures
                          </q-badge>
                        </q-item-section>
                      </q-item>
                    </template>

                    <!-- Ligne de total -->
                    <q-item class="total-row">
                      <q-item-section>
                        <q-item-label class="total-label">
                          Total
                        </q-item-label>
                      </q-item-section>

                      <q-item-section side>
                        <q-badge color="secondary" class="total-hours">
                          {{ teacher.totalHours }} heures
                        </q-badge>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-card-section>
              </q-card>
            </q-expansion-item>
          </template>
        </q-list>
      </div>
    </div>
  </section-component>
</template>

<script setup>
  import { ref, computed, onMounted, watch } from 'vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import { getAssignments } from 'src/modules/statistics/api.js'
  import { errorNotify } from 'src/utils/notify.js'

  const props = defineProps({
    formationId: {
      type: [Number, String],
      default: null
    },
    courseTypeId: {
      type: [Number, String],
      default: null
    },
    semesterId: {
      type: [Number, String],
      default: null
    }
  })

  const loading = ref(false)
  const teacherData = ref([])

  const searchQuery = ref('')

  const filteredTeacherData = computed(() => {
    if (!teacherData.value || teacherData.value.length === 0) {
      return []
    }

    let filtered = [...teacherData.value]

    if (searchQuery.value.trim() !== '') {
      const search = searchQuery.value.toLowerCase().trim()
      filtered = filtered.filter(teacher =>
        teacher.name.toLowerCase().includes(search)
      )
    }

    if (props.semesterId) {
      filtered = filtered.map(teacher => {
        const filteredResources = teacher.resources.filter(resource =>
          resource.semester_id === props.semesterId
        )

        if (filteredResources.length === 0) {
          return null
        }

        const resourceMap = new Map()

        filteredResources.forEach(resource => {
          const key = resource.parentName || 'Sans nom'

          if (!resourceMap.has(key)) {
            resourceMap.set(key, {
              name: key,
              totalHours: 0
            })
          }

          resourceMap.get(key).totalHours += resource.allocated_hours
        })

        const mergedResources = Array.from(resourceMap.values())
        const totalHours = mergedResources.reduce((sum, resource) => sum + resource.totalHours, 0)

        return {
          ...teacher,
          mergedResources,
          totalHours
        }
      }).filter(teacher => teacher !== null)
    } else {
      filtered = filtered.map(teacher => {
        const resourceMap = new Map()

        teacher.resources.forEach(resource => {
          const key = resource.parentName || 'Sans nom'

          if (!resourceMap.has(key)) {
            resourceMap.set(key, {
              name: key,
              totalHours: 0
            })
          }

          resourceMap.get(key).totalHours += resource.allocated_hours
        })

        const mergedResources = Array.from(resourceMap.values())
        const totalHours = mergedResources.reduce((sum, resource) => sum + resource.totalHours, 0)

        return {
          ...teacher,
          mergedResources,
          totalHours
        }
      })
    }

    return filtered.sort((a, b) => a.name.localeCompare(b.name))
  })

  watch(() => props.formationId, fetchData)
  watch(() => props.courseTypeId, fetchData)
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
      const filters = {}

      if (props.formationId) {
        filters.formationId = props.formationId
      }

      if (props.courseTypeId) {
        filters.courseTypeId = props.courseTypeId
      }

      if (props.semesterId) {
        filters.semesterId = props.semesterId
      }

      const assignmentsResponse = await getAssignments(filters)
      teacherData.value = processAssignmentsData(assignmentsResponse.data)

    } catch {
      errorNotify('Erreur lors du chargement des données des enseignants')
      teacherData.value = []
    } finally {
      loading.value = false
    }
  }

  function processAssignmentsData(assignments) {
    const teachersMap = new Map()

    const filteredAssignments = assignments.filter(assignment =>
      assignment.formation_id === props.formationId
    )

    filteredAssignments.forEach(assignment => {
      if (!assignment.id_users || !assignment.user_fullname) {
        return
      }

      const teacherId = assignment.id_users
      const teacherName = assignment.user_fullname

      if (!teachersMap.has(teacherId)) {
        teachersMap.set(teacherId, {
          id: teacherId,
          name: teacherName,
          resources: []
        })
      }

      const teacher = teachersMap.get(teacherId)

      const resourceId = assignment.id_sub_resources
      const parentName = assignment.resource_name || `Ressource ${resourceId}`
      const semesterId = assignment.semester_id
      const allocatedHours = assignment.allocated_hours || 0

      teacher.resources.push({
        id: resourceId,
        parentName: parentName,
        semester_id: semesterId,
        allocated_hours: allocatedHours
      })
    })

    return Array.from(teachersMap.values())
  }
</script>

<style scoped lang="scss">
.teachers-container {
  max-width: 100%;
}

.teacher-header {
  background-color: #f5f5f5;
}

.resource-item {
  transition: background-color 0.2s;

  &:hover {
    background-color: #f9f9f9;
  }
}

.resource-parent-name {
  font-weight: 500;
}

.resource-hours {
  padding: 5px 8px;
}

.total-row {
  background-color: #f0f0f0;
  border-top: 1px solid #ddd;
  margin-top: 8px;
}

.total-label {
  font-weight: 700;
  font-size: 1.05em;
}

.total-hours {
  padding: 5px 8px;
  font-weight: 700;
}

.search-container {
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

.search-input {
  width: 100%;
}
</style>
