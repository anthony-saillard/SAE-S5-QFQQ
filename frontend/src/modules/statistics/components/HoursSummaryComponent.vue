<template>
  <section-component class="q-pa-sm">
    <div class="rounded-borders q-pa-sm">
      <div class="text-subtitle1 q-mb-xs font-weight-bold">
        Résumé des heures
      </div>

      <div class="stats-container">
        <!-- Rangée des statistiques principales -->
        <div class="stats-row">
          <div class="stat-item bg-amber text-white">
            <div class="stat-value">
              {{ formatHours(officialHours) }}h
            </div>
            <div class="stat-label">
              Officielles
            </div>
          </div>
          <div class="stat-item bg-teal text-white">
            <div class="stat-value">
              {{ formatHours(completedHours) }}h
            </div>
            <div class="stat-label">
              Réalisées
            </div>
          </div>
        </div>

        <!-- Barre de progression -->
        <div class="percentage-bar-container q-mt-sm">
          <div class="percentage-bar-label">
            Réalisation
          </div>
          <div class="percentage-bar-wrapper">
            <div
              class="percentage-bar"
              :class="getCompletionRateColor"
              :style="{ width: `${completionRate}%` }"
            >
              {{ completionRate }}%
            </div>
          </div>
        </div>
      </div>
    </div>
  </section-component>
</template>

<script setup>
  import { computed } from 'vue'
  import SectionComponent from 'src/modules/core/components/SectionComponent.vue'

  const props = defineProps({
    officialHours: {
      type: Number,
      required: true
    },
    completedHours: {
      type: Number,
      required: true
    }
  })

  const completionRate = computed(() => {
    if (props.officialHours === 0) {
      return 0
    }
    return Math.round((props.completedHours / props.officialHours) * 100)
  })

  const getCompletionRateColor = computed(() => {
    const colorClass =
      completionRate.value >= 90 ? 'bg-positive text-white' :
      completionRate.value >= 75 ? 'bg-light-green text-white' :
      completionRate.value >= 50 ? 'bg-amber text-black' :
      completionRate.value >= 25 ? 'bg-orange text-white' :
      'bg-negative text-white'

    return colorClass
  })

  function formatHours(hours) {
    return hours.toFixed(1).replace('.0', '')
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
  width: 70px;
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
  justify-content: center;
  color: white;
  font-size: 0.7rem;
  font-weight: 500;
  min-width: 25px;
}
</style>
