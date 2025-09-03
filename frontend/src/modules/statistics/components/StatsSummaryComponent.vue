<template>
  <section-component class="q-pa-sm">
    <div class="rounded-borders q-pa-sm">
      <div class="text-subtitle1 q-mb-xs font-weight-bold">
        Récapitulatif
      </div>

      <div class="stats-container">
        <!-- Statistiques principales en ligne -->
        <div class="stats-row">
          <div class="stat-item bg-primary text-white">
            <div class="stat-value">
              {{ formatHours(totalHours) }}h
            </div>
            <div class="stat-label">
              Total
            </div>
          </div>
          <template v-for="(type, index) in hoursByType" :key="index">
            <div v-if="index < 3" class="stat-item text-white" :class="getCourseTypeColorClass(type.name)">
              <div class="stat-value">
                {{ formatHours(type.hours) }}h
              </div>
              <div class="stat-label">
                {{ type.name }}
              </div>
            </div>
          </template>
        </div>

        <!-- Barres de pourcentage pour les types de cours -->
        <div class="percentages-container q-mt-sm">
          <template v-for="(courseType, index) in courseTypePercentages" :key="index">
            <div v-if="courseType.percentage > 0" class="percentage-bar-container q-mb-xs">
              <div class="percentage-bar-label">
                {{ courseType.name }}
              </div>
              <div class="percentage-bar-wrapper">
                <div
                  class="percentage-bar"
                  :class="getCourseTypeColorClass(courseType.name)"
                  :style="{ width: `${courseType.percentage}%` }"
                >
                  {{ courseType.percentage.toFixed(0) }}%
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </section-component>
</template>

<script setup>
  import { computed, ref, onMounted } from 'vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'
  import { ApiService } from 'src/services/apiService.js'
  import { errorNotify } from 'src/utils/notify.js'

  const props = defineProps({
    teacherHours: {
      type: Array,
      required: true
    }
  })

  const courseTypes = ref([])

  onMounted(async () => {
    await fetchCourseTypes()
  })

  async function fetchCourseTypes() {
    try {
      courseTypes.value = await ApiService.courseTypes.fetchCourseTypes()
    } catch {
      errorNotify('Erreur lors du chargement des types de cours')
      courseTypes.value = []
    }
  }

  const totalHours = computed(() => {
    return props.teacherHours.reduce((sum, resource) => sum + resource.allocatedHours, 0)
  })

  const hoursByType = computed(() => {
    if (!courseTypes.value.length) {
      return []
    }

    return courseTypes.value.map(courseType => ({
      id: courseType.id,
      name: courseType.name,
      hours: calculateHoursByType(courseType.name)
    })).filter(type => type.hours > 0)
  })

  const courseTypePercentages = computed(() => {
    const total = totalHours.value
    if (!total) {
      return []
    }

    return hoursByType.value.map(type => ({
      ...type,
      percentage: (type.hours / total) * 100
    }))
  })

  function calculateHoursByType(typeName) {
    return props.teacherHours.reduce((sum, resource) => {
      const courseType = resource.courseTypes.find(ct => ct.name === typeName)
      return sum + (courseType ? courseType.hours : 0)
    }, 0)
  }

  function formatHours(hours) {
    return hours.toFixed(1).replace('.0', '')
  }

  function getCourseTypeColorClass(typeName) {
    const colors = [
      'bg-teal',
      'bg-blue',
      'bg-deep-orange',
      'bg-purple',
      'bg-indigo',
      'bg-green',
      'bg-amber',
      'bg-red'
    ]

    const courseType = courseTypes.value.find(ct => ct.name === typeName)
    if (!courseType) {
      return 'bg-primary'
    }

    const colorIndex = (courseType.id - 1) % colors.length
    return colors[colorIndex]
  }
</script>

<style scoped lang="scss">
.stats-container {
  display: flex;
  flex-direction: column;
}

.stats-row {
  display: flex;
  gap: 6px;
  margin-bottom: 8px;
}

.stat-item {
  flex: 1;
  border-radius: 4px;
  padding: 4px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-weight: bold;
  font-size: 0.95rem;
}

.stat-label {
  font-size: 0.75rem;
}

.percentage-bar-container {
  display: flex;
  align-items: center;
  height: 20px;
}

.percentage-bar-label {
  width: 30px;
  font-size: 0.75rem;
  font-weight: 500;
}

.percentage-bar-wrapper {
  flex-grow: 1;
  background-color: #f0f0f0;
  border-radius: 3px;
  overflow: hidden;
  height: 16px;
}

.percentage-bar {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: 4px;
  color: white;
  font-size: 0.7rem;
  font-weight: 500;
  min-width: 25px;
}

.bg-teal { background-color: #009688; }
.bg-blue { background-color: #2196F3; }
.bg-deep-orange { background-color: #FF5722; }
.bg-purple { background-color: #9C27B0; }
</style>
