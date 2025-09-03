<template>
  <div>
    <q-table
      :rows="users"
      :columns="columns"
      row-key="id"
      :pagination="pagination"
      flat
      color="primary"
      :loading="loading"
      @update:pagination="newPagination => pagination = newPagination"
    >
      <template #body-cell-role="props">
        <q-td :props="props">
          <q-chip
            :color="getRoleColor(props.row.role)"
            text-color="white"
          >
            {{ getRoleLabel(props.row.role) }}
          </q-chip>
        </q-td>
      </template>
      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn
            flat
            round
            color="blue-9"
            icon="edit"
            @click="$emit('edit-user', props.row)"
          >
            <q-tooltip>Modifier</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            color="negative"
            icon="delete"
            @click="deleteUser(props.row.id)"
          >
            <q-tooltip>Supprimer</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script setup>
  import { ref, onMounted } from 'vue'
  import { ApiService } from 'src/services/apiService.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { confirmDialog } from 'src/utils/dialog.js'
  import { logger } from 'src/utils/logger.js'

  defineEmits(['edit-user'])

  const users = ref([])
  const pagination = ref({
    page: 1,
    rowsPerPage: 10
  })

  const loading = ref(false)

  const columns = [
    { name: 'login', label: 'Login', field: 'login', sortable: true },
    { name: 'last_name', label: 'Nom', field: 'last_name', sortable: true },
    { name: 'first_name', label: 'Prénom', field: 'first_name', sortable: true },
    { name: 'email', label: 'Email', field: 'email', sortable: true },
    { name: 'role', label: 'Rôle', field: 'role', sortable: true },
    { name: 'actions', label: '', field: 'actions' }
  ]

  const getRoleLabel = (role) => {
    switch (role) {
      case 'ROLE_USER': return 'Enseignant'
      case 'ROLE_ADMIN': return 'Gestionnaire'
      default: return role
    }
  }

  const getRoleColor = (role) => {
    switch (role) {
      case 'ROLE_USER': return 'info'
      case 'ROLE_ADMIN': return 'positive'
      default: return 'grey'
    }
  }

  const fetchUsers = async (forceRefresh = true) => {
    loading.value = true
    try {
      const response = await ApiService.users.fetchUsers({}, false, forceRefresh)
      users.value = Array.isArray(response) ? response : []
    } catch (error) {
      logger.error(error)
      errorNotify('Impossible de récupérer la liste des utilisateurs !')
    } finally {
      loading.value = false
    }
  }

  const deleteUser = async (userId) => {
    const isConfirmed = await confirmDialog('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')

    if (isConfirmed) {
      loading.value = true
      try {
        await ApiService.users.deleteUser(userId)
        successNotify('Utilisateur supprimé avec succès')
        await fetchUsers(true)
      } catch (error) {
        logger.error(error)
        errorNotify('Impossible de supprimer l\'utilisateur !')
      } finally {
        loading.value = false
      }
    }
  }

  onMounted(async () => {
    await fetchUsers()
  })

  defineExpose({
    fetchUsers
  })
</script>
