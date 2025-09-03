<template>
  <q-form ref="form" @submit="submit">
    <div class="row q-col-gutter-md">
      <div class="col-12">
        <q-input
          v-model="formData.login"
          :rules="[rules.required]"
          label="Login"
          filled
          required
        />
      </div>

      <div class="col-6">
        <q-input
          v-model="formData.last_name"
          :rules="[rules.required]"
          label="Nom"
          filled
          required
        />
      </div>
      <div class="col-6">
        <q-input
          v-model="formData.first_name"
          :rules="[rules.required]"
          label="Prénom"
          filled
          required
        />
      </div>

      <div class="col-12">
        <q-input
          v-model="formData.email"
          label="Email"
          type="email"
          filled
          required
        />
      </div>

      <div class="col-6">
        <q-select
          v-model="formData.role"
          :rules="[rules.required]"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          map-options
          emit-value
          label="Rôle"
          filled
          required
        />
      </div>
      <div class="col-6">
        <q-input
          v-model="formData.phone"
          label="Téléphone"
          filled
        />
      </div>

      <div v-if="!editMode" class="col-12">
        <q-input
          v-model="formData.password"
          :rules="[rules.required]"
          label="Mot de passe"
          type="password"
          filled
          required
        />
      </div>
    </div>
  </q-form>
</template>

<script setup>
  import { ref, watch } from 'vue'
  import { rules } from 'src/utils/rules.js'

  const props = defineProps({
    editMode: Boolean,
    initialData: Object
  })

  const emit = defineEmits(['submit'])

  const form = ref(null)

  const roleOptions = [
    { label: 'Enseignant', value: 'ROLE_USER' },
    { label: 'Gestionnaire', value: 'ROLE_ADMIN' }
  ]

  const formData = ref({
    login: '',
    last_name: '',
    first_name: '',
    email: '',
    role: 'ROLE_USER',
    phone: '',
    password: ''
  })

  watch(() => props.initialData, (newVal) => {
    if (newVal) {
      formData.value = { ...newVal }
    }
  }, { immediate: true })

  const reset = () => {
    formData.value = {
      login: '',
      last_name: '',
      first_name: '',
      email: '',
      role: 'ROLE_USER',
      phone: '',
      password: ''
    }
  }

  const validate = () => {
    return form.value?.validate() || false
  }

  const submit = () => {
    emit('submit', formData.value)
  }

  defineExpose({
    reset,
    submit,
    validate
  })
</script>
