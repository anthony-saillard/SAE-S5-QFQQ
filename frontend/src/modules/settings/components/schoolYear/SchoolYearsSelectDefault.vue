<template>
  <q-select
    v-model="selectedYear"
    :options="yearOptions"
    option-label="label"
    option-value="id"
    label="Année scolaire par défaut"
    filled
    style="min-width: 250px"
    :loading="loading"
    @update:model-value="onSelect"
  />
</template>

<script setup>
  import { onMounted, ref, watch } from 'vue'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import { logger } from 'src/utils/logger.js'
  import { confirmDialog } from 'src/utils/dialog.js'
  import { ApiService } from 'src/services/apiService.js'

  const props = defineProps({
    modelValue: Object
  })

  const emit = defineEmits(['update:modelValue', 'update:options'])

  const schoolYearStore = useSchoolYearStore()

  const selectedYear = ref(props.modelValue || schoolYearStore.currentYear)
  const previousSelectedYear = ref(selectedYear.value)
  const yearOptions = ref([])
  const loading = ref(false)

  watch(
    () => schoolYearStore.currentYear,
    (newYear) => {
      if (newYear && (!selectedYear.value || newYear.id !== selectedYear.value.id)) {
        selectedYear.value = newYear
        previousSelectedYear.value = newYear
      }
    }
  )

  watch(
    () => props.modelValue,
    (newValue) => {
      if (newValue && (!selectedYear.value || newValue.id !== selectedYear.value.id)) {
        selectedYear.value = newValue
        previousSelectedYear.value = newValue
      }
    }
  )

  watch(
    () => schoolYearStore.lastUpdate,
    async () => {
      await refreshYears()
    }
  )

  onMounted(async () => {
    await loadYears()

    if (!selectedYear.value && yearOptions.value.length) {
      selectedYear.value = schoolYearStore.currentYear
      previousSelectedYear.value = selectedYear.value
    }
  })

  async function refreshYears() {
    await loadYears()
  }

  async function loadYears() {
    loading.value = true
    try {
      yearOptions.value = await ApiService.schoolYears.fetchSchoolYears()

      if (selectedYear.value) {
        const existingYear = yearOptions.value.find((y) => y.id === selectedYear.value.id)
        if (!existingYear) {
          selectedYear.value = schoolYearStore.currentYear
          previousSelectedYear.value = selectedYear.value
        }
      }

      emit('update:options', yearOptions.value)
    } catch (error) {
      logger.error('Error loading school years', error)
    } finally {
      loading.value = false
    }
  }

  async function onSelect(newValue) {
    if (!newValue) {
      return
    }

    if (!(await confirmDialog('Voulez-vous changer l\'année scolaire par défaut ?'))) {
      selectedYear.value = previousSelectedYear.value
      return
    }

    loading.value = true
    try {
      await schoolYearStore.setCurrentYear(newValue.id)
      previousSelectedYear.value = newValue
      emit('update:modelValue', newValue)
    } catch (error) {
      selectedYear.value = previousSelectedYear.value
      logger.error('Error when changing school year', error)
    } finally {
      loading.value = false
    }
  }

  defineExpose({
    refreshYears
  })
</script>
