<template>
  <div>
    <q-chip
      v-for="period in visiblePeriods"
      :key="period.id"
      :style="{ backgroundColor: getPeriodColor(period.index), color: 'white' }"
      dense
      class="q-mr-sm q-my-sm q-pa-sm"
    >
      {{ period.name || formatDateRange(period.start_date, period.end_date) }}
    </q-chip>
  </div>
</template>

<script setup>
  import { usePeriods } from 'src/modules/resources/composables/usePeriods.js'
  import { computed } from 'vue'

  const props = defineProps({
    periods: {
      type: Array,
      default: () => []
    }
  })

  const {
    getPeriodColor,
    formatDateRange,
    usedPeriodIndices
  } = usePeriods()

  const visiblePeriods = computed(() => {
    if (!props.periods || props.periods.length === 0) {
      return []
    }

    return props.periods
      .map((period, index) => ({ ...period, index }))
      .filter(period => usedPeriodIndices.value.has(period.index))
  })
</script>
