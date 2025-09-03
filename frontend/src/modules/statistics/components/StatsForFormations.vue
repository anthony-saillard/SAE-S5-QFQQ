<template>
  <div class="stats-column">
    <div class="stats-container">
      <hours-summary-component
        :official-hours="totalOfficialHours"
        :completed-hours="totalCompletedHours"
      />

      <stats-summary-component :teacher-hours="convertedResourceHours" />
    </div>
  </div>
</template>

<script setup>
  import { computed } from 'vue'
  import StatsSummaryComponent from 'src/modules/statistics/components/StatsSummaryComponent.vue'
  import HoursSummaryComponent from 'src/modules/statistics/components/HoursSummaryComponent.vue'

  const props = defineProps({
    resourceHours: {
      type: Array,
      default: () => []
    }
  })

  const totalOfficialHours = computed(() => {
    return props.resourceHours.reduce((sum, resource) => sum + resource.totalHours, 0)
  })

  const totalCompletedHours = computed(() => {
    return props.resourceHours.reduce((sum, resource) => sum + resource.completedHours, 0)
  })

  const convertedResourceHours = computed(() => {
    return props.resourceHours.map(resource => {
      return {
        id: resource.id,
        resourceName: resource.parentResourceName,
        allocatedHours: resource.completedHours,
        totalHours: resource.totalHours,
        courseTypes: resource.courseTypes.map(type => ({
          id: type.id,
          name: type.name,
          hours: type.totalHours
        }))
      }
    })
  })
</script>

<style scoped lang="scss">
.stats-column {
  display: flex;
  flex-direction: column;
  justify-content: center;
  height: 100%;
}

.stats-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 12px;
}
</style>
