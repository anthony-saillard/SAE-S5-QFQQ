<template>
  <q-form ref="form" class="q-gutter-md" @submit="submit">
    <div class="fs-130 fw-700">
      Semaine {{ week.index + 1 }}
      <span class="fs-90 fw-400">
        - {{ formatDateRange(week) }}
      </span>
    </div>

    <q-input
      v-model.number="formData.allocated_hours"
      type="number"
      label="Heures"
      min="0.5" max="12" step="0.5"
      filled
      :rules="[
        val => (val !== null && val !== undefined && val > 0) || 'Veuillez saisir un nombre d\'heures valide'
      ]"
      required
    />

    <q-input
      v-model="formData.annotation"
      type="textarea"
      label="Commentaire"
      filled
    />

    <UserSelect
      v-model="formData.id_users"
      label="Enseignant"
    />
  </q-form>
</template>

<script setup>
  import { ref, watch, onMounted } from 'vue'
  import UserSelect from 'src/modules/users/components/UserSelect.vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify } from 'src/utils/notify.js'

  const props = defineProps({
    editMode: {
      type: Boolean,
      default: false
    },
    initialData: {
      type: Object,
      default: () => ({
        id: null,
        allocated_hours: 0.5,
        annotation: '',
        id_users: null,
        id_course_type: null,
        id_sub_resources: null,
        assignment_date: null
      })
    },
    week: {
      type: Object,
      required: true
    }
  })

  const emit = defineEmits(['submit'])

  const form = ref(null)
  const courseTypes = ref([])
  const loading = ref(false)

  const formData = ref({
    id: null,
    allocated_hours: 0.5,
    annotation: '',
    id_users: null,
    id_course_type: null,
    id_sub_resources: null,
    weekIndex: -1,
    assignment_date: null
  })

  watch(() => props.initialData, (newData) => {
    if (newData) {
      const allocatedHours = newData.allocated_hours || 0.5
      formData.value = {
        ...newData,
        allocated_hours: allocatedHours > 0 ? allocatedHours : 0.5
      }
    }
  }, { immediate: true })

  onMounted(async () => {
    try {
      loading.value = true
      await fetchCourseTypes()
    } catch (error) {
      logger.error('Error loading course types:', error)
      errorNotify('Erreur lors du chargement des types de cours')
    } finally {
      loading.value = false
    }
  })

  async function fetchCourseTypes() {
    try {
      courseTypes.value = await ApiService.courseTypes.fetchCourseTypes()
    } catch (error) {
      logger.error('Error fetching courses types:', error)
      errorNotify('Erreur lors du chargement des types de cours')
    }
  }

  function formatDateRange(week) {
    if (!week || !week.start_date || !week.end_date) {
      return ''
    }

    const formatDay = (date) => {
      if (!(date instanceof Date)) {
        date = new Date(date)
      }
      return date.getDate().toString().padStart(2, '0') + '/' +
        (date.getMonth() + 1).toString().padStart(2, '0')
    }

    return formatDay(week.start_date) + ' au ' + formatDay(week.end_date)
  }

  function reset() {
    formData.value = {
      id: null,
      allocated_hours: 0.5,
      annotation: '',
      id_users: null,
      id_course_type: null,
      id_sub_resources: null,
      weekIndex: -1,
      assignment_date: null
    }

    if (form.value) {
      form.value.resetValidation()
    }
  }

  async function validate() {
    if (!form.value) {
      return false
    }
    return await form.value.validate()
  }

  async function submit() {
    if (!formData.value.allocated_hours || formData.value.allocated_hours <= 0) {
      errorNotify('Veuillez spécifier un nombre d\'heures valide')
      return
    }

    const apiData = {
      id: formData.value.id,
      allocated_hours: formData.value.allocated_hours,
      annotation: formData.value.annotation,
      id_users: formData.value.id_users?.id || formData.value.id_users,
      id_sub_resources: formData.value.id_sub_resources,
      id_course_type: formData.value.id_course_type,
      assignment_date: props.week && props.week.start_date
        ? (props.week.start_date instanceof Date
          ? props.week.start_date.toISOString().split('T')[0]
          : new Date(props.week.start_date).toISOString().split('T')[0])
        : null
    }

    if (!apiData.assignment_date) {
      errorNotify('Erreur: date de semaine non spécifiée')
      return
    }

    emit('submit', apiData)
  }

  defineExpose({
    reset,
    validate,
    submit,
    form,
    getData: () => {
      return {
        id: formData.value.id,
        allocated_hours: formData.value.allocated_hours,
        annotation: formData.value.annotation,
        id_users: formData.value.id_users?.id || formData.value.id_users,
        id_sub_resources: formData.value.id_sub_resources,
        id_course_type: formData.value.id_course_type,
        assignment_date: props.week && props.week.start_date
          ? (props.week.start_date instanceof Date
            ? props.week.start_date.toISOString().split('T')[0]
            : new Date(props.week.start_date).toISOString().split('T')[0])
          : null
      }
    }
  })
</script>
