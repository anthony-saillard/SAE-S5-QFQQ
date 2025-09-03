<template>
  <div>
    <q-select
      v-model="selectedYear"
      :options="yearOptions"
      option-label="label"
      option-value="id"
      :label="differentYear ? 'Visualisation: ' + selectedYear?.label : 'Année scolaire'"
      :color="differentYear ? 'accent' : 'primary'"
      filled dense
      style="min-width: 200px;"
      :loading="loading"
      @update:model-value="onSelect"
    >
      <template v-if="differentYear" #append>
        <q-btn
          round
          flat
          dense
          icon="sync"
          size="xs"
          color="accent"
          class="q-ml-xs"
          @click.stop="resetToDefaultYear"
        >
          <q-tooltip>Revenir à l'année par défaut</q-tooltip>
        </q-btn>
      </template>
    </q-select>
  </div>
</template>

<script setup>
  import { ref, computed, watch, onMounted } from 'vue'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import { logger } from 'src/utils/logger.js'
  import {ApiService} from 'src/services/apiService.js'

  const schoolYearStore = useSchoolYearStore()

  const yearOptions = ref([])
  const loading = ref(false)

  const selectedYear = ref(null)

  const differentYear = computed(() => schoolYearStore.isViewingDifferentYear)

  watch(() => schoolYearStore.lastUpdate, refreshSelectedYear)

  onMounted(async () => {
    await loadYears()
    refreshSelectedYear()
  })

  function refreshSelectedYear() {
    const viewedYearId = schoolYearStore.viewedYearId
    const currentYearId = schoolYearStore.currentYear?.id

    if (viewedYearId && yearOptions.value.length) {
      selectedYear.value = yearOptions.value.find(y => y.id === viewedYearId) || null
    } else if (currentYearId && yearOptions.value.length) {
      selectedYear.value = yearOptions.value.find(y => y.id === currentYearId) || null
    } else {
      selectedYear.value = null
    }
  }

  async function loadYears() {
    loading.value = true
    try {
      const response = await ApiService.schoolYears.fetchSchoolYears()
      yearOptions.value = response || []
      refreshSelectedYear()
    } catch (error) {
      yearOptions.value = []
      logger.error('Error loading school years', error)
    } finally {
      loading.value = false
    }
  }

  async function onSelect(newYear) {
    if (!newYear) {
      return
    }

    loading.value = true
    try {
      schoolYearStore.setViewedYear(newYear)
    } catch (error) {
      logger.error('Error when changing year displayed', error)
    } finally {
      loading.value = false
    }
  }

  function resetToDefaultYear(event) {
    if (event) {
      event.stopPropagation()
    }

    schoolYearStore.resetViewedYearToDefault()
    refreshSelectedYear()
  }

  defineExpose({
    refreshYears: loadYears
  })
</script>
