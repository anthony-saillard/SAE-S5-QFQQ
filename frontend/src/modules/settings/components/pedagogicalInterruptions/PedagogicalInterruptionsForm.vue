<template>
  <q-form ref="formRef" @submit.prevent="onSubmit">
    <q-input
      v-model="name"
      label="Type d'interruption"
      filled
      :loading="loading"
      :rules="[rules.required]"
    />
    <q-input
      v-model="startDate"
      label="Date de début"
      filled
      type="date"
      :loading="loading"
      :rules="[rules.required]"
    />
    <q-input
      v-model="endDate"
      label="Date de fin"
      filled
      type="date"
      :loading="loading"
      :rules="[rules.required, rules.dateOrder(startDate, endDate)]"
    />

    <q-checkbox
      v-model="applyToAllFormations"
      label="Appliquer à toutes les formations"
      class="q-mb-md"
    />

    <q-select
      v-if="!applyToAllFormations"
      v-model="formationId"
      :options="formationOptions"
      label="Formation"
      filled
      :loading="loading"
      :rules="[rules.required]"
      emit-value
      map-options
    />
  </q-form>
</template>

<script setup>
  import {ref, onMounted, computed} from 'vue'
  import { rules } from 'src/utils/rules.js'
  import { logger } from 'src/utils/logger.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import {ApiService} from 'src/services/apiService.js'

  const props = defineProps({
    editMode: Boolean,
    initialData: Object,
    formations: {
      type: Array,
      default: () => []
    }
  })

  const emit = defineEmits(['submit'])

  const formRef = ref(null)
  const name = ref('')
  const startDate = ref('')
  const endDate = ref('')
  const formationId = ref(null)
  const loading = ref(false)
  const applyToAllFormations = ref(false)

  const formationOptions = computed(() => {
    return props.formations.map(f => ({
      label: f.label,
      value: f.id
    }))
  })

  onMounted(() => {
    if (props.editMode && props.initialData) {
      name.value = props.initialData.name
      startDate.value = props.initialData.start_date
      endDate.value = props.initialData.end_date

      if (props.initialData.isGrouped) {
        applyToAllFormations.value = true
      } else {
        formationId.value = props.initialData.formation_id
        applyToAllFormations.value = props.initialData.formation_id === 'all'
      }
    }
  })

  const onSubmit = async () => {
    if (!formRef.value.validate()) {
      return
    }

    loading.value = true
    try {
      const baseData = {
        name: name.value,
        start_date: startDate.value,
        end_date: endDate.value
      }

      if (applyToAllFormations.value) {
        if (props.editMode) {
          if (props.initialData.isGrouped) {
            const promises = props.initialData.originalIds.map(id => {
              return ApiService.pedagogicalInterruptions.updatePedagogicalInterruption(id, baseData)
            })

            await Promise.all(promises)
            successNotify('Toutes les interruptions ont été modifiées avec succès')
            emit('submit')
          } else {
            const data = { ...baseData, formation_id: 'all' }
            const response = await ApiService.pedagogicalInterruptions.updatePedagogicalInterruption(props.initialData.id, data)
            successNotify('Interruption pédagogique modifiée avec succès')
            emit('submit', response.data)
          }
        } else {
          const promises = props.formations.map(formation => {
            const data = { ...baseData, formation_id: formation.id }
            return ApiService.pedagogicalInterruptions.createPedagogicalInterruption(data)
          })

          await Promise.all(promises)
          successNotify('Interruption pédagogique créée pour toutes les formations avec succès')
          emit('submit')
        }
      } else {
        const data = { ...baseData, formation_id: formationId.value }

        const response = props.editMode
          ? await ApiService.pedagogicalInterruptions.updatePedagogicalInterruption(props.initialData.id, data)
          : await ApiService.pedagogicalInterruptions.createPedagogicalInterruption(data)

        successNotify(`Interruption pédagogique ${props.editMode ? 'modifiée' : 'créée'} avec succès`)
        emit('submit', response.data)
      }

      reset()
    } catch (error) {
      logger.error(error)
      errorNotify(`L'interruption pédagogique n'a pas pu être ${props.editMode ? 'modifiée' : 'créée'} !`)
    } finally {
      loading.value = false
    }
  }

  const validate = () => {
    if (!name.value || !startDate.value || !endDate.value) {
      return false
    }

    if (new Date(endDate.value) < new Date(startDate.value)) {
      return false
    }

    return !(!applyToAllFormations.value && !formationId.value)
  }

  const reset = () => {
    name.value = props.editMode && props.initialData ? props.initialData.name : ''
    startDate.value = props.editMode && props.initialData ? props.initialData.start_date : ''
    endDate.value = props.editMode && props.initialData ? props.initialData.end_date : ''
    formationId.value = props.editMode && props.initialData ? props.initialData.formation_id : null
    applyToAllFormations.value = false
    formRef.value?.reset()
  }

  defineExpose({
    submit: onSubmit,
    reset,
    validate
  })
</script>
