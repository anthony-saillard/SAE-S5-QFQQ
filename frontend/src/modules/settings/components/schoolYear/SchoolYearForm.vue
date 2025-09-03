<template>
  <q-form ref="formRef" @submit.prevent="onSubmit">
    <q-input
      v-model="label"
      label="Nom de l'année scolaire"
      filled
      :loading="loading"
      :rules="[rules.required]"
    />

    <div v-if="!props.editMode" class="q-mt-md">
      <q-toggle
        v-model="isDuplication"
        label="Duplication d'une année"
        @update:model-value="resetDuplicationOptions"
      />
    </div>

    <div v-if="isDuplication && !props.editMode" class="q-mt-md">
      <q-select
        v-model="selectedYearToDuplicate"
        :options="yearOptions"
        option-label="label"
        option-value="id"
        label="Duplication d'une année"
        filled
        :loading="loadingYears"
        :rules="[validateDuplicationYear]"
      />

      <p v-if="isDuplication && selectedYearToDuplicate && !anyOptionSelected" class="text-negative">
        Veuillez sélectionner au moins un élément à importer.
      </p>

      <div class="q-mt-md">
        <p>Choisissez des paramètres à importer</p>
        <div class="row q-col-gutter-md">
          <div class="col-6">
            <q-checkbox
              v-model="duplicationOptions.ressources"
              label="Ressources"
              @update:model-value="handleCheckboxChange('ressources')"
            />
          </div>
          <div class="col-6">
            <q-checkbox
              v-model="duplicationOptions.formations"
              label="Formations"
              :disable="disabledOptions.formations"
              @update:model-value="handleCheckboxChange('formations')"
            />
          </div>
          <div class="col-6">
            <q-checkbox
              v-model="duplicationOptions.periodPedagogical"
              label="Périodes particulières"
              @update:model-value="handleCheckboxChange('periodesParticulieres')"
            />
          </div>
          <div class="col-6">
            <q-checkbox
              v-model="duplicationOptions.semestres"
              label="Semestres"
              :disable="disabledOptions.semestres"
              @update:model-value="handleCheckboxChange('semestres')"
            />
          </div>
          <div class="col-6">
            <q-checkbox
              v-model="duplicationOptions.groups"
              label="Groupes"
              @update:model-value="handleCheckboxChange('groups')"
            />
          </div>
        </div>
      </div>
    </div>
  </q-form>
</template>

<script setup>
  import { defineEmits, onMounted, ref, reactive, computed } from 'vue'
  import { rules } from 'src/utils/rules.js'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { SchoolYearApiService } from 'src/modules/settings/services/SchoolYearApiService.js'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'

  const props = defineProps({
    editMode: Boolean,
    initialData: Object
  })

  const emit = defineEmits(['submit'])

  const schoolYearStore = useSchoolYearStore()
  const formRef = ref(null)
  const label = ref('')
  const isDuplication = ref(false)
  const selectedYearToDuplicate = ref(null)
  const yearOptions = ref([])
  const loading = ref(false)
  const loadingYears = ref(false)

  const duplicationOptions = ref({
    ressources: false,
    formations: false,
    periodPedagogical: false,
    semestres: false,
    groups: false
  })

  const disabledOptions = reactive({
    ressources: false,
    formations: false,
    periodPedagogical: false,
    semestres: false,
    groups: false
  })

  const anyOptionSelected = computed(() => {
    return Object.values(duplicationOptions.value).some(val => val === true)
  })

  const dependencies = {
    ressources: ['semestres', 'formations'],
    semestres: ['formations'],
    groups: ['formations'],
    periodPedagogical: ['formations']
  }

  const isDependentOnOtherCheckedOption = (option, excludeOption) => {
    for (const [key, deps] of Object.entries(dependencies)) {
      if (key !== excludeOption && duplicationOptions.value[key] && deps.includes(option)) {
        return true
      }
    }
    return false
  }

  const validateDuplicationYear = () => {
    if (!isDuplication.value) {
      return true
    }

    if (isDuplication.value && anyOptionSelected.value && !selectedYearToDuplicate.value) {
      return 'Vous devez sélectionner une année à dupliquer'
    }
    return true
  }

  const validate = () => {
    if (!isDuplication.value) {
      return !!label.value
    }

    if (!selectedYearToDuplicate.value) {
      return false
    }

    return anyOptionSelected.value && !!label.value
  }

  const handleCheckboxChange = (option) => {
    const isChecked = duplicationOptions.value[option]

    if (isChecked && dependencies[option]) {
      dependencies[option].forEach(dep => {
        duplicationOptions.value[dep] = true
        disabledOptions[dep] = true
      })
    } else if (!isChecked && dependencies[option]) {
      dependencies[option].forEach(dep => {
        if (!isDependentOnOtherCheckedOption(dep, option)) {
          duplicationOptions.value[dep] = false
          disabledOptions[dep] = false
        }
      })
    }
  }

  onMounted(async () => {
    if (props.editMode && props.initialData) {
      label.value = props.initialData.label
    }

    await loadYears()
  })

  const loadYears = async () => {
    loadingYears.value = true
    try {
      yearOptions.value = await SchoolYearApiService.fetchSchoolYears()
    } catch (error) {
      logger.error('Error loading school years', error)
    } finally {
      loadingYears.value = false
    }
  }

  const resetDuplicationOptions = () => {
    if (!isDuplication.value) {
      selectedYearToDuplicate.value = null
      duplicationOptions.value = {
        ressources: false,
        formations: false,
        periodPedagogical: false,
        semestres: false,
        groups: false
      }

      Object.keys(disabledOptions).forEach(key => {
        disabledOptions[key] = false
      })
    }
  }

  const onSubmit = async () => {
    if (!formRef.value.validate()) {
      return
    }

    loading.value = true
    try {
      let response

      if (props.editMode) {
        response = await SchoolYearApiService.updateSchoolYear(props.initialData.id, {
          label: label.value
        })
      } else if (isDuplication.value && selectedYearToDuplicate.value) {
        response = await SchoolYearApiService.duplicateSchoolYear(
          label.value,
          selectedYearToDuplicate.value.id,
          duplicationOptions.value
        )
      } else {
        response = await SchoolYearApiService.createSchoolYear({ label: label.value })
      }

      schoolYearStore.notifySchoolYearChange()

      SchoolYearApiService.clearCache()

      successNotify(`Année scolaire ${props.editMode ? 'modifiée' : 'créée'} avec succès`)
      emit('submit', response)
      reset()
    } catch (error) {
      logger.error(error)
      errorNotify(`L'année scolaire n'a pas pu être ${props.editMode ? 'modifiée' : 'créée'} !`)
    } finally {
      loading.value = false
    }
  }

  const reset = () => {
    label.value = props.editMode && props.initialData ? props.initialData.label : ''
    isDuplication.value = false
    selectedYearToDuplicate.value = null
    duplicationOptions.value = {
      ressources: false,
      formations: false,
      periodPedagogical: false,
      semestres: false,
      groups: false
    }

    Object.keys(disabledOptions).forEach(key => {
      disabledOptions[key] = false
    })

    formRef.value?.reset()
  }

  defineExpose({
    submit: onSubmit,
    reset,
    validate
  })
</script>
