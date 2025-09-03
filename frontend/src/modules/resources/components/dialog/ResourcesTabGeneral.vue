<template>
  <q-form ref="formRef" class="q-gutter-y-md q-pa-md" @submit.prevent="preventSubmitOnEnter">
    <q-input
      v-model="form.identifier"
      label="Identifiant"
      hint="Exemple : R1.01"
      :rules="[rules.required, rules.maxLength(20)]"
      lazy-rules filled
    />

    <q-input
      v-model="form.name"
      label="Nom"
      hint="Exemple : Initiation au développement"
      :rules="[rules.required, rules.maxLength(80)]"
      lazy-rules filled
    />

    <user-select
      v-model="form.user"
      label="Responsable" show-email
      :rules="[rules.required]"
    />

    <q-input
      v-model="form.total_hours"
      label="Heures à effectuées"
      hint="Heures du programme national"
      :rules="[rules.required, rules.numeric]"
      lazy-rules filled
    />
  </q-form>
</template>

<script setup>
  import { onMounted, ref, watch } from 'vue'
  import { rules } from 'src/utils/rules.js'
  import UserSelect from 'src/modules/users/components/UserSelect.vue'

  const props = defineProps({
    resource: {
      type: Object,
      required: true
    },
    semesterId: {
      type: String,
      required: true
    }
  })

  const formRef = ref(null)
  const form = ref({
    identifier: '',
    name: '',
    description: '',
    id_semesters: null,
    user: null,
    id_users: null,
    total_hours: 0
  })

  function preventSubmitOnEnter(e) {
    e.preventDefault()
    e.stopPropagation()
  }

  onMounted(initializeForm)

  watch(() => props.resource, initializeForm, { deep: true })

  function initializeForm() {
    form.value = {
      identifier: props.resource?.identifier || '',
      name: props.resource?.name || '',
      description: props.resource?.description || '',
      id_semesters: props.resource?.id_semesters || props.semesterId,
      user: props.resource?.user || null,
      total_hours: props.resource?.total_hours || 0
    }
  }

  async function validate() {
    return await formRef.value?.validate()
  }

  function getData() {
    return {
      identifier: form.value.identifier,
      name: form.value.name,
      description: form.value.description,
      id_semesters: form.value.id_semesters || props.semesterId,
      user: form.value.user,
      total_hours: form.value.total_hours || 0
    }
  }

  function reset() {
    initializeForm()
    formRef.value?.reset()
  }

  defineExpose({
    validate,
    getData,
    reset
  })
</script>
