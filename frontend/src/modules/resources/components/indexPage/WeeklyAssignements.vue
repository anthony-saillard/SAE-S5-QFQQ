<template>
  <div class="weekly-assignments">
    <q-card v-if="loading" class="q-pa-md full-height">
      <q-skeleton type="text" class="text-h6 q-mb-md" />
      <q-skeleton type="rect" height="100px" class="q-mb-md" />
    </q-card>

    <template v-else>
      <EmptyData
        v-if="error"
        :message="error"
        icon="error"
        text-color="negative"
        action
        action-label="Réessayer"
        action-color="negative"
        class="q-mb-md"
        @action="fetchWeeklyAssignments"
      />

      <q-card v-else class="full-height">
        <q-card-section>
          <div class="text-h6 q-mb-md">
            Cours de la semaine
          </div>

          <div v-if="!weeklyAssignments.length" class="text-center q-pa-md">
            <q-icon name="event_available" size="2rem" color="primary" />
            <p>Vous n'avez pas d'assignements cette semaine.</p>
          </div>

          <q-list v-else separator>
            <q-item
              v-for="assignment in weeklyAssignments"
              :key="assignment.id"
              v-ripple
              clickable
              :to="getAssignmentLink(assignment)"
            >
              <q-item-section side class="text-grey-7 text-weight-light">
                <div class="text-caption">
                  {{ formatDate(assignment.date) }}
                </div>
              </q-item-section>

              <q-item-section>
                <q-item-label>{{ getResourceName(assignment) }}</q-item-label>
                <q-item-label caption class="text-grey-8">
                  <span v-if="assignment.course_type_name" class="text-weight-medium">
                    {{ assignment.course_type_name }}
                  </span>
                  <span v-if="assignment.title"> - {{ assignment.title }}</span>
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-icon name="chevron_right" color="orange" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </template>
  </div>
</template>

<script setup>
  import { ref, onMounted } from 'vue'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'
  import { ApiService } from 'src/services/apiService.js'
  import dayjs from 'dayjs'
  import 'dayjs/locale/fr'
  import weekOfYear from 'dayjs/plugin/weekOfYear'
  import weekday from 'dayjs/plugin/weekday'
  import {logger} from 'src/utils/logger.js'
  import EmptyData from 'src/modules/core/components/EmptyData.vue'

  dayjs.locale('fr')
  dayjs.extend(weekOfYear)
  dayjs.extend(weekday)

  const userStore = useUserStore()
  const userId = userStore.user?.id

  const weeklyAssignments = ref([])
  const loading = ref(true)
  const error = ref(null)

  const formatDate = (dateString) => {
    try {
      return dayjs(dateString).format('ddd DD/MM')
    } catch (e) {
      logger.error('Error formatting date', e)
      return dateString
    }
  }

  const getResourceName = (assignment) => {
    if (assignment.sub_resource_name) {
      return assignment.sub_resource_name
    }

    if (assignment.sub_resource) {
      return assignment.sub_resource.name
    }

    if (assignment.resource) {
      return assignment.resource.identifier ?
        `${assignment.resource.identifier} - ${assignment.resource.name}` :
        assignment.resource.name
    }

    return 'Ressource'
  }

  const getAssignmentLink = (assignment) => {
    if (assignment.subResource_resource_id) {
      return `/resource/${assignment.subResource_resource_id}`
    }

    return null
  }

  const preloadSubResourcesInfo = async () => {
    if (!weeklyAssignments.value.length) {
      return
    }

    const subResourceIds = weeklyAssignments.value
      .filter(a => a.id_sub_resources)
      .map(a => a.id_sub_resources)

    if (!subResourceIds.length) {
      return
    }

    try {
      const uniqueIds = [...new Set(subResourceIds)]
      for (const id of uniqueIds) {
        const subResource = await ApiService.subResources.fetchSubResource(id)

        weeklyAssignments.value.forEach(assignment => {
          if (assignment.id_sub_resources === id) {
            assignment.subResource_resource_id = subResource.id_resources
          }
        })
      }
    } catch (err) {
      logger.error('Error fetching sub resources', err)
    }
  }

  const fetchWeeklyAssignments = async () => {
    loading.value = true
    error.value = null

    try {
      if (!userId) {
        return
      }

      const today = dayjs()
      const weekStart = today.startOf('week')
      const weekEnd = today.endOf('week').subtract(1, 'day')

      const startDate = weekStart.format('YYYY-MM-DD')
      const endDate = weekEnd.format('YYYY-MM-DD')

      const assignments = await ApiService.assignments.fetchAssignments({
        id_user: userId,
        start_date: startDate,
        end_date: endDate
      }, ['resource', 'sub_resource'], false)

      weeklyAssignments.value = assignments
        .filter(assignment => {
          const assignmentDate = dayjs(assignment.assignment_date || assignment.date)
          return assignmentDate.isSame(weekStart, 'year') &&
            assignmentDate.isAfter(weekStart.subtract(1, 'day')) &&
            assignmentDate.isBefore(weekEnd.add(1, 'day'))
        })
        .sort((a, b) => {
          const dateA = dayjs(a.assignment_date || a.date)
          const dateB = dayjs(b.assignment_date || b.date)
          return dateA.diff(dateB)
        })

      await preloadSubResourcesInfo()
    } catch (err) {
      logger.error('Error loading assignments:', err)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    fetchWeeklyAssignments()
  })
</script>
