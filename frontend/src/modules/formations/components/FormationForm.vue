<template>
  <q-form ref="formRef" @submit="onSubmit">
    <q-input
      v-model="label"
      label="Nom de la formation"
      filled
      class="q-mb-md"
      :rules="[rules.required]"
    />
    <q-input
      v-model.number="orderNumber"
      label="Numéro d'ordre"
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
  import { createFormation, editFormation } from 'src/modules/settings/api.js'

  const props = defineProps({
    editMode: Boolean,
    initialData: Object
  })

  const emit = defineEmits(['submit'])
  const schoolYearStore = useSchoolYearStore()

  const formRef = ref(null)
  const label = ref('')
  const orderNumber = ref(1)

  onMounted(() => {
    if (props.editMode && props.initialData) {
      label.value = props.initialData.label
      orderNumber.value = props.initialData.order_number
    }
  })

  const onSubmit = async () => {
    if (!formRef.value.validate()) {
      return
    }

    try {
      const data = {
        label: label.value,
        order_number: orderNumber.value,
        id_school_year: schoolYearStore.currentYearId
      }

      const response = props.editMode
        ? await editFormation(props.initialData.id, data)
        : await createFormation(data)

      successNotify(`Formation ${props.editMode ? 'modifiée' : 'créée'} avec succès`)
      emit('submit', response.data)
      reset()
    } catch (error) {
      errorNotify(`La formation n'a pas pu être ${props.editMode ? 'modifiée' : 'créée'}`)
      logger.error(error)
    }
  }

  const reset = () => {
    label.value = ''
    orderNumber.value = 1
    formRef.value?.reset()
  }

  defineExpose({
    submit: onSubmit,
    reset
  })
</script>
