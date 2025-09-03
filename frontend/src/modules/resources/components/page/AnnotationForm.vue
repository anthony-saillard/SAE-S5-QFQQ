<template>
  <q-form ref="formRef" @submit.prevent="onSubmit">
    <q-input
      v-model="description"
      label="Annotation"
      type="textarea" maxlength="250"
      :rules="[rules.maxLength(250)]"
      lazy-rules filled autogrow counter
    />
  </q-form>
</template>

<script setup>
  import { ref, defineProps, defineEmits } from 'vue'
  import { rules } from 'src/utils/rules.js'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'

  const props = defineProps({
    resourceId: {
      type: [Number, String],
      required: true
    }
  })

  const emit = defineEmits(['submit'])
  const userStore = useUserStore()

  const formRef = ref(null)
  const description = ref('')

  const validate = () => {
    return !!description.value?.trim()
  }

  const onSubmit = () => {
    if (!formRef.value?.validate()) {
      return
    }

    const annotationData = {
      description: description.value?.trim(),
      id_resources: props.resourceId,
      id_user: userStore.user.id
    }

    emit('submit', annotationData)
  }

  const reset = () => {
    description.value = ''
    formRef.value?.reset()
  }

  defineExpose({
    submit: onSubmit,
    reset,
    validate
  })
</script>
