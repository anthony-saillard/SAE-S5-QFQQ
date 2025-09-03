<template>
  <div class="row q-mb-sm q-mx-lg items-center">
    <p class="col-grow fw-600 fs-230">
      Utilisateurs
    </p>
    <q-btn
      color="positive"
      icon-right="add"
      label="Ajouter un utilisateur"
      no-caps
      class="q-mr-sm"
      @click="showAddDialog"
    />
  </div>

  <UsersTableComponent
    ref="usersTable"
    @edit-user="showEditDialog"
  />

  <dialog-component
    v-model="dialogShow"
    :title="dialogTitle"
    :highlight-title-words="['utilisateur', 'l\'utilisateur']"
    :form-ref="formRef"
    :loading="loading"
    size="md"
  >
    <user-form
      ref="formRef"
      :edit-mode="isEditMode"
      :initial-data="selectedUser"
      @submit="onSubmit"
    />
  </dialog-component>
</template>

<script setup>
  import {ref, nextTick} from 'vue'
  import { ApiService } from 'src/services/apiService.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import UsersTableComponent from '../components/UsersTableComponent.vue'
  import UserForm from '../components/UserForm.vue'
  import DialogComponent from 'src/modules/core/components/DialogComponent.vue'
  import { logger } from 'src/utils/logger.js'

  const dialogShow = ref(false)
  const dialogTitle = ref('')

  const loading = ref(false)

  const isEditMode = ref(false)

  const selectedUser = ref(null)
  const formRef = ref(null)
  const usersTable = ref(null)

  const showAddDialog = () => {
    isEditMode.value = false
    selectedUser.value = null
    dialogTitle.value = 'Ajouter un utilisateur'
    dialogShow.value = true
  }

  const showEditDialog = (user) => {
    isEditMode.value = true
    selectedUser.value = { ...user }
    dialogTitle.value = 'Modifier l\'utilisateur'
    dialogShow.value = true
  }

  const onSubmit = async (formData) => {
    loading.value = true
    try {
      if (isEditMode.value) {
        await ApiService.users.updateUser(formData.id, formData)
        successNotify('Utilisateur modifié avec succès')
      } else {
        await ApiService.users.createUser(formData)
        successNotify('Utilisateur ajouté avec succès')
      }

      ApiService.users.clearCache()

      dialogShow.value = false

      await nextTick()

      if (usersTable.value) {
        await usersTable.value.fetchUsers()
      }
    } catch (error) {
      logger.error('Cannot create or edit user :', error)
      if (isEditMode.value) {
        errorNotify('Impossible de modifier cet utilisateur !')
      } else {
        errorNotify('Impossible de créer un utilisateur !')
      }
    } finally {
      loading.value = false
    }
  }
</script>
