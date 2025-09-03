<template>
  <q-form ref="formRef" @submit="onSubmit">
    <q-input
      v-model="name"
      label="Nom du type de cours"
      filled
      class="q-mb-md"
      :rules="[rules.required]"
    />
    <q-input
      v-model.number="hourlyRate"
      label="Taux horaire"
      type="number"
      filled
      :rules="[rules.required, rules.positive]"
    />
  </q-form>
</template>

<script setup>
  import { ref, onMounted } from 'vue'
  import { rules } from 'src/utils/rules.js'
  import { useSchoolYearStore } from 'src/utils/stores/useSchoolYearStore.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { logger } from 'src/utils/logger.js'
  import {ApiService} from 'src/services/apiService.js'

  const props = defineProps({
    editMode: Boolean,
    initialData: Object
  })

  const emit = defineEmits(['submit'])
  const schoolYearStore = useSchoolYearStore()

  const formRef = ref(null)
  const name = ref('')
  const hourlyRate = ref(1.0)

  onMounted(() => {
    if (props.editMode && props.initialData) {
      name.value = props.initialData.name
      hourlyRate.value = props.initialData.hourly_rate
    }
  })

  const validate = () => {
    return !!name.value && hourlyRate.value > 0
  }

  const onSubmit = async () => {
    if (!formRef.value.validate()) {
      return
    }

    try {
      const data = {
        name: name.value,
        hourly_rate: hourlyRate.value,
        id_school_year: schoolYearStore.currentYearId
      }

      const response = props.editMode
        ? await ApiService.courseTypes.updateCourseType(props.initialData.id, data)
        : await ApiService.courseTypes.createCourseType(data)

      successNotify(`Type de cours ${props.editMode ? 'modifié' : 'créé'} avec succès`)
      emit('submit', response.data)
      reset()
    } catch (error) {
      errorNotify(`Le type de cours n'a pas pu être ${props.editMode ? 'modifié' : 'créé'}`)
      logger.error(error)
    }
  }

  const reset = () => {
    name.value = ''
    hourlyRate.value = 1.0
    formRef.value?.reset()
  }

  defineExpose({
    submit: onSubmit,
    reset,
    validate
  })
</script>
