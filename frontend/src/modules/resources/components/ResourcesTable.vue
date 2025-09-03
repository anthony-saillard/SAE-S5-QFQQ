<template>
  <q-table
    :rows="resources"
    :columns="columns"
    row-key="id"
    :loading="loading"
    :pagination="pagination"
    :filter="searchTerm"
    flat
    @update:pagination="pagination = $event"
    @row-click="(evt, row) => {redirect('resource', { id: row.id })}"
  >
    <template #top-right>
      <q-input
        v-model="searchTerm"
        placeholder="Rechercher..."
        dense
        debounce="300"
        class="q-mr-sm"
      >
        <template #append>
          <q-icon name="search" />
        </template>
      </q-input>
    </template>

    <template #body-cell-responsable="cell">
      <q-td :props="cell">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="primary" text-color="white" size="32px">
              {{ getInitials(cell.row.user) }}
            </q-avatar>
          </q-item-section>

          <q-item-section>
            <q-item-label>{{ cell.row.user.fullName }}</q-item-label>
            <q-item-label caption>
              {{ cell.row.user.email }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-td>
    </template>

    <template v-if="userStore.isAdmin" #body-cell-actions="cell">
      <q-td :props="cell">
        <q-btn
          flat round
          color="primary"
          icon="edit"
          @click.stop="emit('edit', cell.row.id)"
        />
        <q-btn
          flat round
          color="negative"
          icon="delete"
          @click.stop="confirmDelete(cell.row)"
        />
      </q-td>
    </template>

    <template #no-data>
      <empty-data action @action="load" />
    </template>
  </q-table>
</template>

<script setup>
  import { ref, onMounted } from 'vue'
  import EmptyData from 'src/modules/core/components/EmptyData.vue'
  import { ApiService } from 'src/services/apiService.js'
  import { logger } from 'src/utils/logger.js'
  import { confirmDialog } from 'src/utils/dialog.js'
  import { errorNotify, successNotify } from 'src/utils/notify.js'
  import { formatUserName, getInitials } from 'src/utils/utils.js'
  import { useRedirect } from 'src/router/useRedirect.js'
  import { useUserStore } from 'src/utils/stores/useUserStore.js'

  const { redirect } = useRedirect()
  const userStore = useUserStore()

  const props = defineProps({
    semesterId: {
      type: [String, Number],
      required: true
    }
  })

  const emit = defineEmits(['edit', 'resources-loaded'])

  const resources = ref([])
  const loading = ref(false)
  const searchTerm = ref('')
  const pagination = ref({
    rowsPerPage: 10
  })

  const columns = ref([
    {
      name: 'identifier',
      required: true,
      label: 'Identifiant',
      align: 'left',
      field: row => row.identifier,
      sortable: true
    },
    {
      name: 'name',
      required: true,
      label: 'Nom',
      align: 'left',
      field: row => row.name,
      sortable: true
    },
    {
      name: 'responsable',
      label: 'Responsable',
      align: 'left',
      sortable: true
    }
  ])

  onMounted(() => {
    if (userStore.isAdmin) {
      columns.value.push({
        name: 'actions',
        label: 'Actions',
        align: 'center'
      })
    }
    load()
  })

  async function load() {
    loading.value = true
    try {
      const filters = {
        id_semester: String(props.semesterId)
      }

      const resourcesData = await ApiService.resources.fetchResources(filters, true)

      resources.value = resourcesData.map(resource => {
        let fullName = 'Non défini'

        if (resource.user) {
          fullName = formatUserName(resource.user)
        }

        return {
          ...resource,
          user: {
            fullName,
            ...resource.user
          }
        }
      })

      emit('resources-loaded', resources.value)
    } catch (error) {
      logger.error('Error loading resources:', error)
      errorNotify( 'Impossible de charger les ressources')
    } finally {
      loading.value = false
    }
  }

  async function confirmDelete(resource) {
    if (!await confirmDialog('Êtes-vous sûr de vouloir supprimer cette ressource ?')) {
      return
    }

    try {
      await ApiService.resources.deleteResource(resource.id)
      successNotify('Ressource supprimée avec succès')
      await load()
    } catch (error) {
      logger.error('Error when deleting a resource:', error)
      errorNotify('Impossible de supprimer la ressource')
    }
  }

  defineExpose({
    load
  })
</script>
